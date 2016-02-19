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
use Pimple\Container;
use Phps\Logger\OutputHandler;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ContainerFactory
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class ContainerFactory
{
    public static function getContainer(OutputInterface $output)
    {
        $container = new Container();
        $container['output'] = $output;

        $container['logger'] = function ($c) {
            $logger = new Logger('phps');
            $logger->pushHandler(new OutputHandler($c['output'], $c['output']->isVerbose() ? Logger::DEBUG : Logger::WARNING));

            return $logger;
        };

        $container['memory_connection'] = function ($c) {
            $config = new Configuration();
            $params = array(
                'memory' => true,
                'driver' => 'pdo_sqlite',
            );

            return DriverManager::getConnection($params, $config);
        };

        $container['connection'] = function ($c) {
            $config = new Configuration();
            $params = array(
                'path'   => getcwd() . DIRECTORY_SEPARATOR . 'phps.db',
                'driver' => 'pdo_sqlite',
            );

            return DriverManager::getConnection($params, $config);
        };

        $container['scanner'] = function ($c) {
            return new Scanner($c['memory_connection'], $c['logger']);
        };

        $container['repository'] = function ($c) {
            return new Repository($c['connection']);
        };

        $container['schema_manager'] = function ($c) {
            return new SchemaManager();
        };

        $container['copier'] = function ($c) {
            return new Copier($c['logger']);
        };

        $container['formatter_factory'] = function ($c) {
            return new FormatterFactory();
        };

        $container['cleaner'] = function ($c) {
            return new Cleaner($c['connection']);
        };

        return $container;
    }
}
