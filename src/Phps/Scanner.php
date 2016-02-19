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

namespace Phps;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use PhpParser;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Scanner
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class Scanner
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    protected $connection;
    
    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;

    private $_namespace;
    private $_uses;
    private $_class;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger     = $logger;
        $this->parser     = new PhpParser\Parser(new PhpParser\Lexer());
    }

    public function scan($dir)
    {
        $this->createDefinition($dir);
    }

    protected function createDefinition($srcPath)
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcPath), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $path) {
            if ($path->isDir()) {
                // ignore dirs
            } elseif ($path->getExtension() == 'php') {
                $source = file_get_contents($path->getRealPath());

                $this->parseCode($source, $path->getRealPath());
            }
        }
    }

    protected function parseCode($source, $file)
    {
        $this->logger->info('Parse ' . $file);

        try {
            $stmts = $this->parser->parse($source);

            $this->buildDefinition($stmts, $file);
        } catch (PhpParser\Error $e) {
            $this->logger->error('Parse error in ' . $file);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    protected function buildDefinition(array $stmts, $file)
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Namespace_) {
                $this->_namespace = implode('\\', $stmt->name->parts);
                $this->_uses      = $this->getUses($stmt->stmts);

                $this->buildClasses($stmt->stmts, $file);
            }
        }

        // in case we have classes or functions without namespace
        $this->buildClasses($stmts, $file);
    }

    protected function getUses(array $stmts)
    {
        $uses = array();

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    $uses[$use->alias] = implode('\\', $use->name->parts);
                }
            }
        }

        return $uses;
    }

    protected function buildClasses(array $stmts, $file)
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                $this->_class = $stmt;

                if ($stmt->extends instanceof PhpParser\Node\Name) {
                    $extends = $this->resolveClassName($stmt->extends);
                } else {
                    $extends = null;
                }

                $implements = array();
                foreach ($stmt->implements as $implement) {
                    $implements[] = $this->resolveClassName($implement);
                }

                if ($this->_namespace !== null) {
                    $name = $this->_namespace . '\\' . $stmt->name;
                } else {
                    $name = $stmt->name;
                }

                // insert class
                $sql = ' SELECT id
						   FROM phps_class
						  WHERE file = :file
						    AND name = :name';

                $class = $this->connection->fetchAssoc($sql, array(
                    'file' => $file,
                    'name' => $name,
                ));

                if (!empty($class)) {
                    $classId = $class['id'];
                } else {
                    $classId = sha1($file . $name);

                    $this->connection->insert('phps_class', array(
                        'id'     => $classId,
                        'file'   => $file,
                        'name'   => $name,
                        'extend' => $extends,
                    ));
                }

                // insert implements
                $sql = 'DELETE FROM phps_implement
						      WHERE class_id = :class_id';

                $this->connection->executeUpdate($sql, array(
                    'class_id' => $classId,
                ));

                foreach ($implements as $implement) {
                    $this->connection->insert('phps_implement', array(
                        'class_id' => $classId,
                        'name'     => $implement,
                    ));
                }

                // insert properties
                $this->buildProperties($stmt->stmts, $classId);

                // insert methods
                $this->buildMethods($stmt->stmts, $classId);

                $this->_class = null;
            }
        }
    }

    protected function buildProperties(array $stmts, $classId)
    {
        $sql = 'DELETE FROM phps_property
				      WHERE class_id = :class_id';

        $this->connection->executeUpdate($sql, array(
            'class_id' => $classId,
        ));

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Property) {
                $type = $this->parseDocAnnotation($stmt, 'var');

                foreach ($stmt->props as $property) {
                    $this->connection->insert('phps_property', array(
                        'class_id'      => $classId,
                        'modifier'      => $stmt->type,
                        'name'          => $property->name,
                        'type'          => $type,
                        'default_value' => $this->resolveDefaultValue($stmt),
                    ));
                }
            }
        }
    }

    protected function buildMethods(array $stmts, $classId)
    {
        $sql = 'DELETE FROM phps_method
				      WHERE class_id = :class_id';

        $this->connection->executeUpdate($sql, array(
            'class_id' => $classId,
        ));

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $methodId   = sha1($classId . $stmt->name);
                $returnType = !empty($stmt->returnType) ? $stmt->returnType : $this->parseDocAnnotation($stmt, 'return');

                $this->connection->insert('phps_method', array(
                    'id'          => $methodId,
                    'class_id'    => $classId,
                    'modifier'    => $stmt->type,
                    'name'        => $stmt->name,
                    'return_type' => $returnType,
                ));

                $this->buildParameters($stmt->params, $methodId);
            }
        }
    }

    protected function buildParameters(array $stmts, $methodId)
    {
        $pos = 0;

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Param) {
                if ($stmt->type instanceof PhpParser\Node\Name) {
                    $type = $this->resolveClassName($stmt->type);
                } else {
                    $type = $stmt->type;
                }

                $this->connection->insert('phps_parameter', array(
                    'method_id'     => $methodId,
                    'position'      => $pos,
                    'type_hint'     => $type,
                    'name'          => $stmt->name,
                    'by_ref'        => $stmt->byRef ? 1 : 0,
                    'default_value' => $this->resolveDefaultValue($stmt),
                ));

                $pos++;
            }
        }
    }

    protected function resolveClassName(PhpParser\Node\Name $node)
    {
        if ($node instanceof PhpParser\Node\Name\FullyQualified) {
            return implode('\\', $node->parts);
        } else {
            $aliasName = $node->parts[0];

            if (isset($this->_uses[$aliasName])) {
                $parts = array_slice($node->parts, 1);

                if (!empty($parts)) {
                    return $this->_uses[$aliasName] . '\\' . implode('\\', $parts);
                } else {
                    return $this->_uses[$aliasName];
                }
            } else {
                return $this->_namespace . '\\' . implode('\\', $node->parts);
            }
        }
    }

    protected function resolveDefaultValue($stmt)
    {
        $defaultValue = null;
        if ($stmt->default instanceof PhpParser\Node\Expr\Array_) {
            $defaultValue = 'array()';
        } elseif ($stmt->default instanceof PhpParser\Node\Expr\ClassConstFetch) {
            $className    = $this->resolveClassName($stmt->default->class);
            $defaultValue = $className . '::' . $stmt->default->name;
        } elseif ($stmt->default instanceof PhpParser\Node\Scalar) {
            $defaultValue = $stmt->default->value;
        }

        return $defaultValue;
    }

    protected function parseDocAnnotation(PhpParser\NodeAbstract $stmt, $annotation)
    {
        $type    = null;
        $comment = $stmt->getDocComment();

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
                        // in this case type is an class name. We check whether
                        // we can resolve the name by looking at the uses
                        $parts     = explode('\\', $type);
                        $aliasName = $parts[0];

                        if (isset($this->_uses[$aliasName])) {
                            $parts = array_slice($parts, 1);

                            if (!empty($parts)) {
                                $result[] = $this->_uses[$aliasName] . '\\' . implode('\\', $parts);
                            } else {
                                $result[] = $this->_uses[$aliasName];
                            }
                        } elseif ($this->_class->name == $type) {
                            $result[] = $this->_namespace . '\\' . $type;
                        } else {
                            // @TODO we could also try to analyze array cases
                            // i.e. array<\DOMElement> or DOMElement[]

                            $result[] = $type;
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
