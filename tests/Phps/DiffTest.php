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

        var_dump($result);
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
