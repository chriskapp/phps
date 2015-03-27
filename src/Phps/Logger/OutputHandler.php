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

namespace Phps\Logger;

use Monolog\Handler\AbstractProcessingHandler;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * OutputHandler
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class OutputHandler extends AbstractProcessingHandler
{
	protected $output;

	public function __construct(OutputInterface $output, $level = Logger::DEBUG, $bubble = true)
	{
		parent::__construct($level, $bubble);

		$this->output = $output;
	}

	protected function write(array $record)
	{
		$this->output->write((string) $record['formatted']);
	}
}
