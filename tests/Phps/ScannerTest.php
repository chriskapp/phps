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

class ScannerTest extends \PHPUnit_Framework_TestCase
{
    public function testScan()
    {
        // scan all available classes in the scan_test folder
        $connection = $this->getConnection();

        $sm = new SchemaManager();
        $sm->createSchema($connection);

        $scanner = new Scanner($connection, $this->getLogger());
        $scanner->scan(__DIR__ . '/scan_test');

        $repository = new Repository($connection);

        // check the classes
        $xml = simplexml_load_file(__DIR__ . '/scan_files.xml');

        foreach ($xml->file as $node) {
            $class  = $node->attributes()['class'];
            $expect = trim((string) $node);

            $result = $repository->getDescription($class, '');
            $stream = fopen('php://memory', 'r+');
            $output = new StreamOutput($stream);

            $formatter = FormatterFactory::getFormatter('json');
            $formatter->formatDescribeResult($result, $output);

            $actual = stream_get_contents($stream, -1, 0);

            $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
        }
    }

    protected function getConnection()
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

