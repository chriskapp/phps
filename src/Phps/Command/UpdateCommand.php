<?php
/*
 * PHPS is a tool that generates an index of classes found in PHP source files
 * Copyright (C) 2015 Christoph Kappestein <k42b3.x@gmail.com>
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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * UpdateCommand
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class UpdateCommand extends CommandAbstract
{
	protected function configure()
	{
		$this
			->setName('update')
			->setDescription('Updates the index for the given path. In case you want to create the complete index use the init command')
			->addArgument('path', InputArgument::REQUIRED, 'Path or file which should be updated')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$path     = $input->getArgument('path') ?: '.';
		$realPath = realpath($path);

		if(!$realPath)
		{
			$output->writeln('Invalid folder ' . $path);

			return 1;
		}

		$container = $this->getContainer($output);

		$container['schema_manager']->createSchema($container['connection']);
		$container['schema_manager']->createSchema($container['memory_connection']);

		$container['cleaner']->deleteByPath($container['connection'], $realPath);

		$container['scanner']->scan($realPath);

		$container['copier']->copy($container['memory_connection'], $container['connection']);

		$output->writeln('Update successful');

		return 0;
	}
}

