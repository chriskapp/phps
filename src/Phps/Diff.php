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

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PhpParser;
use Phps\Diff\RiskCalculator;
use Phps\Diff\Result;

/**
 * Diff
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class Diff
{
    const LEVEL_INFO = 0x0;
    const LEVEL_BC   = 0x2;

    protected $left;
    protected $right;
    protected $calc;

    protected $result;

    private $classOffset = 100;

    public function __construct(Connection $leftConnection, Connection $rightConnection, RiskCalculator $calculator = null)
    {
        $this->left  = new Repository($leftConnection);
        $this->right = new Repository($rightConnection);
        $this->calc  = $calculator ?: new RiskCalculator();
    }

    public function diff()
    {
        $this->result = new Result($this->calc);
        $this->result->setLeftCount($this->left->getClassCount());
        $this->result->setRightCount($this->right->getClassCount());

        $this->generateDiff();

        return $this->result->toArray();
    }

    protected function generateDiff()
    {
        $offset = 0;

        while (true) {
            $leftClasses = $this->left->getClassesByOffset($offset);

            if (empty($leftClasses)) {
                break;
            }

            foreach ($leftClasses as $leftClass) {
                $rightClass = $this->right->getClassByName($leftClass['name']);

                if (empty($rightClass)) {
                    $this->addLog(self::LEVEL_BC, 'Removed', $leftClass);
                    continue;
                }

                // check extend
                if ($leftClass['extend'] != $rightClass['extend']) {
                    if (empty($rightClass['extend'])) {
                        $this->addLog(self::LEVEL_BC, 'Removed extends ' . $leftClass['extend'], $leftClass);
                    } elseif (empty($leftClass['extend'])) {
                        $this->addLog(self::LEVEL_INFO, 'Added extends ' . $rightClass['extend'], $leftClass);
                    } else {
                        $this->addLog(self::LEVEL_BC, 'Changed extends from ' . $leftClass['extend'] . ' to ' . $rightClass['extend'], $leftClass);
                    }
                }

                // check implements
                $this->compareImplements($leftClass, $rightClass);

                // check properties
                $this->compareProperties($leftClass, $rightClass);

                // check methods
                $this->compareMethods($leftClass, $rightClass);
            }

            $offset+= $this->classOffset;
        }
    }

    protected function compareImplements(array $leftClass, array $rightClass)
    {
        $leftImplements  = $this->left->getImplements($leftClass);
        $rightImplements = $this->right->getImplements($rightClass);

        foreach ($leftImplements as $leftImplement) {
            if (in_array($leftImplement, $rightImplements)) {
                // interface exists
            } else {
                $this->addLog(self::LEVEL_BC, 'Removed interface ' . $leftImplement, $leftClass);
            }
        }

        foreach ($rightImplements as $rightImplement) {
            if (in_array($rightImplement, $leftImplements)) {
                // interface exists
            } else {
                $this->addLog(self::LEVEL_INFO, 'Added interface ' . $rightImplement, $leftClass);
            }
        }
    }

    protected function compareProperties(array $leftClass, array $rightClass)
    {
        $leftProperties  = $this->left->getProperties($leftClass);
        $rightProperties = $this->right->getProperties($rightClass);

        foreach ($leftProperties as $leftProperty) {
            // find right property
            $rightProperty = null;
            foreach ($rightProperties as $property) {
                if ($leftProperty['name'] == $property['name']) {
                    $rightProperty = $property;
                    break;
                }
            }

            if ($rightProperty === null) {
                if ($this->isPrivate($leftProperty['modifier'])) {
                    $this->addLog(self::LEVEL_INFO, 'Removed private property ' . $leftProperty['name'], $leftClass);
                } else {
                    $visibility = $this->getVisibility($leftProperty['modifier']);
                    $this->addLog(self::LEVEL_BC, 'Removed ' . $visibility . ' property ' . $leftProperty['name'], $leftClass);
                }
                continue;
            }

            // check modifier
            $leftVisibility  = $this->getVisibility($leftProperty['modifier']);
            $rightVisibility = $this->getVisibility($rightProperty['modifier']);

            if ($leftVisibility != $rightVisibility) {
                // if the left visibility was private you can do whatever you
                // like since no code depends on the property
                if ($this->isPrivate($leftProperty['modifier'])) {
                    $this->addLog(self::LEVEL_INFO, 'Changed visibility of property ' . $leftProperty['name'] . ' from ' . $leftVisibility . ' to ' . $rightVisibility, $leftClass);
                }
                // if the right visibility gets public there is also no problem
                elseif ($this->isPublic($rightProperty['modifier'])) {
                    $this->addLog(self::LEVEL_INFO, 'Changed visibility of property ' . $leftProperty['name'] . ' from ' . $leftVisibility . ' to ' . $rightVisibility, $leftClass);
                } else {
                    $this->addLog(self::LEVEL_BC, 'Changed visibility of property ' . $leftProperty['name'] . ' from ' . $leftVisibility . ' to ' . $rightVisibility, $leftClass);
                }
            }

            $leftStatic  = $this->isStatic($leftProperty['modifier']);
            $rightStatic = $this->isStatic($rightProperty['modifier']);

            if ($leftStatic != $rightStatic) {
                $visibility = $this->getVisibility($leftProperty['modifier']);
                if ($leftStatic) {
                    if ($this->isPrivate($leftProperty['modifier'])) {
                        $this->addLog(self::LEVEL_INFO, 'Changed private property ' . $leftProperty['name'] . ' to non-static', $leftClass);
                    } else {
                        $this->addLog(self::LEVEL_BC, 'Changed ' . $visibility . ' property ' . $leftProperty['name'] . ' to non-static', $leftClass);
                    }
                } else {
                    $this->addLog(self::LEVEL_INFO, 'Changed ' . $visibility . ' property ' . $leftProperty['name'] . ' to static', $leftClass);
                }
            }
        }
    }

    protected function compareMethods(array $leftClass, array $rightClass)
    {
        $leftMethods  = $this->left->getMethods($leftClass);
        $rightMethods = $this->right->getMethods($rightClass);

        foreach ($leftMethods as $leftMethod) {
            // find right method
            $rightMethod = null;
            foreach ($rightMethods as $method) {
                if ($leftMethod['name'] == $method['name']) {
                    $rightMethod = $method;
                    break;
                }
            }

            if ($rightMethod === null) {
                if ($this->isPrivate($leftMethod['modifier'])) {
                    $this->addLog(self::LEVEL_INFO, 'Removed private method ' . $leftMethod['name'], $leftClass);
                } else {
                    $visibility = $this->getVisibility($leftMethod['modifier']);
                    $this->addLog(self::LEVEL_BC, 'Removed ' . $visibility . ' method ' . $leftMethod['name'], $leftClass);
                }
                continue;
            }

            // check modifier
            $leftVisibility  = $this->getVisibility($leftMethod['modifier']);
            $rightVisibility = $this->getVisibility($rightMethod['modifier']);

            if ($leftVisibility != $rightVisibility) {
                if ($this->isPrivate($leftMethod['modifier'])) {
                    // if the left visibility was private you can do whatever 
                    // you like since no code depends on the property else it is 
                    // a BC
                    $this->addLog(self::LEVEL_INFO, 'Changed visibility of method ' . $leftMethod['name'] . ' from ' . $leftVisibility . ' to ' . $rightVisibility, $leftClass);
                } elseif ($this->isPublic($rightMethod['modifier'])) {
                    // if the right visibility gets public there is also no 
                    // problem
                    $this->addLog(self::LEVEL_INFO, 'Changed visibility of method ' . $leftMethod['name'] . ' from ' . $leftVisibility . ' to ' . $rightVisibility, $leftClass);
                } else {
                    $this->addLog(self::LEVEL_BC, 'Changed visibility of method ' . $leftMethod['name'] . ' from ' . $leftVisibility . ' to ' . $rightVisibility, $leftClass);
                }
            }

            $leftStatic  = $this->isStatic($leftMethod['modifier']);
            $rightStatic = $this->isStatic($rightMethod['modifier']);

            if ($leftStatic != $rightStatic) {
                $visibility = $this->getVisibility($leftMethod['modifier']);
                if ($leftStatic) {
                    if ($this->isPrivate($leftMethod['modifier'])) {
                        $this->addLog(self::LEVEL_INFO, 'Changed private method ' . $leftMethod['name'] . ' to non-static', $leftClass);
                    } else {
                        $this->addLog(self::LEVEL_BC, 'Changed ' . $visibility . ' method ' . $leftMethod['name'] . ' to non-static', $leftClass);
                    }
                } else {
                    $this->addLog(self::LEVEL_INFO, 'Changed ' . $visibility . ' method ' . $leftMethod['name'] . ' to static', $leftClass);
                }
            }

            $leftAbstract  = $this->isAbstract($leftMethod['modifier']);
            $rightAbstract = $this->isAbstract($rightMethod['modifier']);

            if ($leftAbstract != $rightAbstract) {
                $visibility = $this->getVisibility($leftMethod['modifier']);
                if ($leftAbstract) {
                    $this->addLog(self::LEVEL_INFO, 'Changed ' . $visibility . ' method ' . $leftMethod['name'] . ' to non-abstract', $leftClass);
                } else {
                    if ($this->isPrivate($leftMethod['modifier'])) {
                        $this->addLog(self::LEVEL_INFO, 'Changed private method ' . $leftMethod['name'] . ' to abstract', $leftClass);
                    } else {
                        $this->addLog(self::LEVEL_BC, 'Changed ' . $visibility . ' method ' . $leftMethod['name'] . ' to abstract', $leftClass);
                    }
                }
            }

            $leftFinal  = $this->isFinal($leftMethod['modifier']);
            $rightFinal = $this->isFinal($rightMethod['modifier']);

            if ($leftFinal != $rightFinal) {
                $visibility = $this->getVisibility($leftMethod['modifier']);
                if ($leftFinal) {
                    $this->addLog(self::LEVEL_INFO, 'Changed ' . $visibility . ' method ' . $leftMethod['name'] . ' to non-final', $leftClass);
                } else {
                    if ($this->isPrivate($leftMethod['modifier'])) {
                        $this->addLog(self::LEVEL_INFO, 'Changed private method ' . $leftMethod['name'] . ' to final', $leftClass);
                    } else {
                        $this->addLog(self::LEVEL_BC, 'Changed ' . $visibility . ' method ' . $leftMethod['name'] . ' to final', $leftClass);
                    }
                }
            }

            // compare parameters
            $this->compareParameters($leftMethod, $rightMethod, $leftClass);
        }
    }

    protected function compareParameters(array $leftMethod, array $rightMethod, array $leftClass)
    {
        $leftParameters  = $leftMethod['parameters'];
        $rightParameters = $rightMethod['parameters'];

        $level = $this->isPrivate($leftMethod['modifier']) ? self::LEVEL_INFO : self::LEVEL_BC;

        foreach ($leftParameters as $leftParameter) {
            // find right property
            $rightParameter = null;
            foreach ($rightParameters as $parameter) {
                if ($leftParameter['position'] == $parameter['position']) {
                    $rightParameter = $parameter;
                    break;
                }
            }

            if ($rightParameter === null) {
                $this->addLog($level, 'Removed parameter ' . $leftParameter['position'] . ' of method ' . $leftMethod['name'], $leftClass);
                continue;
            }

            // check type hint
            if ($leftParameter['type_hint'] != $rightParameter['type_hint']) {
                if (empty($rightParameter['type_hint'])) {
                    $this->addLog(self::LEVEL_INFO, 'Removed type hint ' . $leftParameter['type_hint'] . ' of parameter ' . $leftParameter['position'] . ' from method ' . $leftMethod['name'], $leftClass);
                } elseif (empty($leftParameter['type_hint'])) {
                    $this->addLog($level, 'Added type hint ' . $rightParameter['type_hint'] . ' to parameter ' . $leftParameter['position'] . ' from method ' . $leftMethod['name'], $leftClass);
                } else {
                    $this->addLog($level, 'Changed type hint from ' . $leftParameter['type_hint'] . ' to ' . $rightParameter['type_hint'] . ' of parameter ' . $leftParameter['position'] . ' from method ' . $leftMethod['name'], $leftClass);
                }
            }

            // check ref
            if ($leftParameter['by_ref'] != $rightParameter['by_ref']) {
                if ($leftParameter['by_ref']) {
                    $this->addLog($level, 'Removed call by reference of parameter ' . $leftParameter['position'] . ' from method ' . $leftMethod['name'], $leftClass);
                } else {
                    $this->addLog($level, 'Added call by reference of parameter ' . $leftParameter['position'] . ' from method ' . $leftMethod['name'], $leftClass);
                }
            }

            // check default value
            if ($leftParameter['default_value'] != $rightParameter['default_value']) {
                if (!empty($rightParameter['default_value'])) {
                    $this->addLog(self::LEVEL_INFO, 'Added default value of parameter ' . $leftParameter['position'] . ' from method ' . $leftMethod['name'], $leftClass);
                } else {
                    $this->addLog($level, 'Removed default value of parameter ' . $leftParameter['position'] . ' from method ' . $leftMethod['name'], $leftClass);
                }
            }
        }

        foreach ($rightParameters as $rightParameter) {
            // find left property
            $leftParameter = null;
            foreach ($leftParameters as $parameter) {
                if ($rightParameter['position'] == $parameter['position']) {
                    $leftParameter = $parameter;
                    break;
                }
            }

            // parameter was added
            if ($leftParameter === null) {
                $this->addLog($level, 'Added parameter ' . $rightParameter['position'] . ' of method ' . $leftMethod['name'], $leftClass);
                continue;
            }
        }
    }

    protected function getVisibility($modifier)
    {
        if ($this->isPublic($modifier)) {
            return 'public';
        } elseif ($this->isProtected($modifier)) {
            return 'protected';
        } elseif ($this->isPrivate($modifier)) {
            return 'private';
        }
    }

    protected function isPublic($modifier)
    {
        return $modifier & PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC;
    }

    protected function isProtected($modifier)
    {
        return $modifier & PhpParser\Node\Stmt\Class_::MODIFIER_PROTECTED;
    }

    protected function isPrivate($modifier)
    {
        return $modifier & PhpParser\Node\Stmt\Class_::MODIFIER_PRIVATE;
    }

    protected function isStatic($modifier)
    {
        return $modifier & PhpParser\Node\Stmt\Class_::MODIFIER_STATIC;
    }

    protected function isAbstract($modifier)
    {
        return $modifier & PhpParser\Node\Stmt\Class_::MODIFIER_ABSTRACT;
    }

    protected function isFinal($modifier)
    {
        return $modifier & PhpParser\Node\Stmt\Class_::MODIFIER_FINAL;
    }

    protected function addLog($level, $description, array $class)
    {
        $this->result->add($class['name'], $level, $description);
    }

    public static function fromFiles($leftFile, $rightFile)
    {
        return new self(self::getConnection($leftFile), self::getConnection($rightFile));
    }

    protected static function getConnection($file)
    {
        $config = new Configuration();
        $params = array(
            'path'   => $file,
            'driver' => 'pdo_sqlite',
        );

        return DriverManager::getConnection($params, $config);
    }
}
