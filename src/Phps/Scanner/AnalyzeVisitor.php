<?php
/*
 * PHPS is a tool that generates an index of classes found in PHP source files
 *
 * Copyright (C) 2015-2016 Christoph Kappestein <k42b3.x@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Phps\Scanner;

use Doctrine\DBAL\Connection;
use Phps\Annotation;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * AnalyzeVisitor
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class AnalyzeVisitor extends NodeVisitorAbstract
{
    protected $connection;
    protected $resolver;

    protected $file;
    protected $classId;

    public function __construct(Connection $connection, ResolveVisitor $resolver)
    {
        $this->connection = $connection;
        $this->resolver   = $resolver;
    }

    public function setFile($file)
    {
        $this->file = $file;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            if ($node->extends instanceof Node\Name) {
                $extends = $node->extends;
            } else {
                $extends = null;
            }

            $name = (string) $node->namespacedName;

            // insert class
            $sql = ' SELECT id
                       FROM phps_class
                      WHERE file = :file
                        AND name = :name';

            $class = $this->connection->fetchAssoc($sql, array(
                'file' => $this->file,
                'name' => $name,
            ));

            if (!empty($class)) {
                $this->classId = $class['id'];
            } else {
                $this->classId = sha1($this->file . $name);

                $this->connection->insert('phps_class', array(
                    'id'     => $this->classId,
                    'file'   => $this->file,
                    'name'   => $name,
                    'extend' => $extends,
                ));
            }

            // insert implements
            foreach ($node->implements as $implement) {
                $this->connection->insert('phps_implement', array(
                    'class_id' => $this->classId,
                    'name'     => $implement,
                ));
            }
        } elseif ($node instanceof Node\Stmt\Property) {
            if (!empty($this->classId)) {
                $type = $this->parseDocAnnotation($node, 'var');

                foreach ($node->props as $property) {
                    $this->connection->insert('phps_property', array(
                        'class_id'      => $this->classId,
                        'modifier'      => $node->type,
                        'name'          => $property->name,
                        'type'          => $type,
                        'default_value' => $this->resolveDefaultValue($node),
                    ));
                }
            }
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            if (!empty($this->classId)) {
                $methodId   = sha1($this->classId . $node->name);
                $returnType = !empty($node->returnType) ? $node->returnType : $this->parseDocAnnotation($node, 'return');

                $this->connection->insert('phps_method', array(
                    'id'          => $methodId,
                    'class_id'    => $this->classId,
                    'modifier'    => $node->type,
                    'name'        => $node->name,
                    'return_type' => $returnType,
                ));


                if (!empty($node->params)) {
                    $pos = 0;
                    foreach ($node->params as $node) {
                        $this->connection->insert('phps_parameter', array(
                            'method_id'     => $methodId,
                            'position'      => $pos,
                            'type_hint'     => $node->type,
                            'name'          => $node->name,
                            'by_ref'        => $node->byRef ? 1 : 0,
                            'default_value' => $this->resolveDefaultValue($node),
                        ));

                        $pos++;
                    }
                }
            }
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            $this->classId = null;
        }
    }

    protected function resolveDefaultValue($node)
    {
        $defaultValue = null;
        if (isset($node->default)) {
            if ($node->default instanceof Node\Expr\Array_) {
                $defaultValue = 'array()';
            } elseif ($node->default instanceof Node\Expr\ClassConstFetch) {
                $className    = $node->default->class;
                $defaultValue = $className . '::' . $node->default->name;
            } elseif ($node->default instanceof Node\Scalar) {
                $defaultValue = $node->default->value;
            }
        }

        return $defaultValue;
    }

    protected function parseDocAnnotation(Node $node, $annotation)
    {
        $type    = null;
        $comment = $node->getDocComment();

        if (empty($comment)) {
            return null;
        }

        $doc = Annotation::parse($comment->getText());

        if ($doc->hasAnnotation($annotation)) {
            $type = trim($doc->getFirstAnnotation($annotation));

            if (strpos($type, ' ') !== false) {
                $type = strstr($type, ' ', true);
            }

            $types  = explode('|', $type);
            $result = array();

            foreach ($types as $type) {
                if (strpos($type, '\\') === 0) {
                    // we have an absolute namespace
                    $result[] = substr($type, 1);
                } else {
                    $basicType = $this->getBasicType($type);

                    if ($basicType !== null) {
                        $result[] = $basicType;
                    } else {
                        $parts = explode('\\', $type);
                        $names = array();
                        $valid = true;

                        foreach ($parts as $part) {
                            if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $part)) {
                                $names[] = $part;
                            } else {
                                $valid = false;
                                break;
                            }
                        }

                        if ($valid) {
                            $result[] = $this->resolver->resolveClassNameFromDoc(implode('\\', $names));
                        }
                    }
                }
            }

            return implode('|', $result);
        }

        return $type;
    }

    /**
     * Normalizes common types to an single vocabular so that other processors
     * dont have todo that
     *
     * @param string $type
     * @return string
     */
    protected function getBasicType($type)
    {
        switch (strtolower($type)) {
            case 'int':
            case 'integer':
                return 'int';
                break;

            case 'str':
            case 'string':
                return 'string';
                break;

            case 'bool':
            case 'boolean':
                return 'bool';
                break;

            case 'float':
            case 'double':
                return 'float';
                break;

            case 'null':
                return 'null';
                break;

            case 'array':
                return 'array';
                break;

            case 'mixed':
                return 'mixed';
                break;

            default:
                return null;
        }
    }
}
