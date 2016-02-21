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

namespace Phps\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * InitCommand
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class InitCommand extends CommandAbstract
{
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initializes the database and searches recursively for all PHP classes')
            ->addArgument('path', InputArgument::OPTIONAL, 'Base path which should be scanned default is the current working dir')
            ->addOption('db', 'd', InputOption::VALUE_OPTIONAL, 'Path of the database file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path     = $input->getArgument('path') ?: '.';
        $realPath = realpath($path);

        if (!$realPath) {
            $output->writeln('Invalid folder ' . $path);

            return 1;
        }

        $container = $this->getContainer($output, $input->getOption('db'));

        $container['schema_manager']->createSchema($container['connection']);
        $container['schema_manager']->createSchema($container['memory_connection']);

        $container['cleaner']->deleteAll($container['connection']);

        $container['scanner']->scan($realPath);

        $container['copier']->copy($container['memory_connection'], $container['connection']);

        $output->writeln('Initialization finished');

        return 0;
    }
}
