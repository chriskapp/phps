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
use Doctrine\DBAL\DriverManager;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Symfony\Component\Console\Output\StreamOutput;

class DiffTest extends \PHPUnit_Framework_TestCase
{
    public function testDiff()
    {
        $left   = $this->getConnection('left');
        $right  = $this->getConnection('right');
        $diff   = new Diff($left, $right);
        $result = $diff->diff();

        $actual = json_encode($result, JSON_PRETTY_PRINT);
        $expect = <<<'JSON'
{
    "bc_count": 22,
    "changed_count": 23,
    "left_count": 23,
    "right_count": 22,
    "upgrade_risk": 59,
    "result": {
        "Foo\\AddedExtends": [
            {
                "level": 0,
                "description": "Added extends Foo\\Bar"
            }
        ],
        "Foo\\AddedInterface": [
            {
                "level": 0,
                "description": "Added interface Foo\\Bar"
            }
        ],
        "Foo\\AddedParameter": [
            {
                "level": 2,
                "description": "Added parameter 0 of method foo"
            }
        ],
        "Foo\\AddedTypeHint": [
            {
                "level": 2,
                "description": "Added type hint array to parameter 0 from method foo"
            }
        ],
        "Foo\\ChangedAbstractMethod": [
            {
                "level": 0,
                "description": "Changed public method test1 to non-abstract"
            },
            {
                "level": 0,
                "description": "Changed protected method test2 to non-abstract"
            },
            {
                "level": 2,
                "description": "Changed public method test3 to abstract"
            }
        ],
        "Foo\\ChangedCallByReference": [
            {
                "level": 2,
                "description": "Added call by reference of parameter 0 from method foo"
            },
            {
                "level": 2,
                "description": "Removed call by reference of parameter 1 from method foo"
            }
        ],
        "Foo\\ChangedDefaultValue": [
            {
                "level": 0,
                "description": "Added default value of parameter 0 from method foo"
            },
            {
                "level": 2,
                "description": "Removed default value of parameter 1 from method foo"
            }
        ],
        "Foo\\ChangedExtends": [
            {
                "level": 2,
                "description": "Changed extends from Foo\\Foo to Foo\\Bar"
            }
        ],
        "Foo\\ChangedFinalMethod": [
            {
                "level": 0,
                "description": "Changed public method test1 to non-final"
            },
            {
                "level": 0,
                "description": "Changed protected method test2 to non-final"
            },
            {
                "level": 2,
                "description": "Changed public method test3 to final"
            }
        ],
        "Foo\\ChangedStaticMethod": [
            {
                "level": 2,
                "description": "Changed public method test1 to non-static"
            },
            {
                "level": 0,
                "description": "Changed private method test2 to non-static"
            },
            {
                "level": 0,
                "description": "Changed protected method test3 to static"
            }
        ],
        "Foo\\ChangedStaticProperty": [
            {
                "level": 2,
                "description": "Changed public property test1 to non-static"
            },
            {
                "level": 0,
                "description": "Changed private property test2 to non-static"
            },
            {
                "level": 0,
                "description": "Changed protected property test3 to static"
            }
        ],
        "Foo\\ChangedTypeHint": [
            {
                "level": 2,
                "description": "Changed type hint from array to stdClass of parameter 0 from method foo"
            }
        ],
        "Foo\\ChangedVisibilityMethod": [
            {
                "level": 2,
                "description": "Changed visibility of method test1 from public to private"
            },
            {
                "level": 2,
                "description": "Changed visibility of method test2 from protected to private"
            },
            {
                "level": 0,
                "description": "Changed visibility of method test3 from private to public"
            }
        ],
        "Foo\\ChangedVisibilityProperty": [
            {
                "level": 2,
                "description": "Changed visibility of property test1 from public to private"
            },
            {
                "level": 2,
                "description": "Changed visibility of property test2 from protected to private"
            },
            {
                "level": 0,
                "description": "Changed visibility of property test3 from private to public"
            }
        ],
        "Foo\\RemovedClass": [
            {
                "level": 2,
                "description": "Removed"
            }
        ],
        "Foo\\RemovedExtends": [
            {
                "level": 2,
                "description": "Removed extends Foo\\Foo"
            }
        ],
        "Foo\\RemovedInterface": [
            {
                "level": 2,
                "description": "Removed interface Foo\\Foo"
            }
        ],
        "Foo\\RemovedMethod": [
            {
                "level": 2,
                "description": "Removed public method foo"
            }
        ],
        "Foo\\RemovedParameter": [
            {
                "level": 2,
                "description": "Removed parameter 0 of method foo"
            }
        ],
        "Foo\\RemovedPrivateMethod": [
            {
                "level": 0,
                "description": "Removed private method foo"
            }
        ],
        "Foo\\RemovedPrivateProperty": [
            {
                "level": 0,
                "description": "Removed private property foo"
            }
        ],
        "Foo\\RemovedProperty": [
            {
                "level": 2,
                "description": "Removed public property foo"
            },
            {
                "level": 2,
                "description": "Removed protected property bar"
            }
        ],
        "Foo\\RemovedTypeHint": [
            {
                "level": 0,
                "description": "Removed type hint array of parameter 0 from method foo"
            }
        ]
    }
}
JSON;

        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    protected function getConnection($path)
    {
        $connection = $this->newConnection();

        $sm = new SchemaManager();
        $sm->createSchema($connection);

        $scanner = new Scanner($connection, $this->getLogger());
        $scanner->scan(__DIR__ . '/diff_test/' . $path);

        return $connection;
    }

    protected function newConnection()
    {
        $params = array(
            'memory' => true,
            'driver' => 'pdo_sqlite',
        );

        return DriverManager::getConnection($params, new Configuration());
    }

    protected function getLogger()
    {
        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());

        return $logger;
    }
}
