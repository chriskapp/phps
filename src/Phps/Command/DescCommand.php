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
 * DescCommand
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class DescCommand extends CommandAbstract
{
    protected function configure()
    {
        $this
            ->setName('desc')
            ->setDescription('Describes an given class and lists all available properties and methods')
            ->addArgument('class', InputArgument::REQUIRED, 'The absolute class name')
            ->addArgument('query', InputArgument::OPTIONAL, 'Keyword which must be in the method name')
            ->addOption('json', 'j', InputOption::VALUE_NONE, 'Whether to return json')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer($output);
        $result    = $container['repository']->getDescription($input->getArgument('class'), $input->getArgument('query'));
        $formatter = $container['formatter_factory']->getFormatter($input->getOption('json') ? 'json' : null);
        $formatter->formatDescribeResult($result, $output);
    }
}
