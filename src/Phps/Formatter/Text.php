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

namespace Phps\Formatter;

use Phps\FormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * Text
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class Text implements FormatterInterface
{
	public function formatSearchResult(array $result, OutputInterface $output)
	{
		$data = array();
		foreach($result as $row)
		{
			$data[] = array($row['name']);
		}

		$table = new Table($output);
		$table
			->setStyle('compact')
			->setRows($data);

		$table->render();
	}

	public function formatDescribeResult(array $result, OutputInterface $output)
	{
		$data = array();
		foreach($result['properties'] as $row)
		{
			$visibility = $this->getVisibility($row['modifier']);

			$type = '';
			if(!empty($row['type']))
			{
				$type = ': ' . $row['type'];
			}

			$defaultValue = '';
			if(!empty($row['default_value']))
			{
				$defaultValue.= ' = ' . $row['default_value'];
			}

			$data[] = array($visibility . ' $' . $row['name'] . $type . $defaultValue);
		}

		foreach($result['methods'] as $row)
		{
			$visibility = $this->getVisibility($row['modifier']);
			$parameters = $this->getParameters($row['parameters']);

			$returnType = '';
			if(!empty($row['return_type']))
			{
				$returnType = ': ' . $row['return_type'];
			}

			$data[] = array($visibility . ' ' . $row['name'] . $parameters . $returnType);
		}

		$table = new Table($output);
		$table
			->setStyle('compact')
			->setRows($data);

		$table->render();
	}

	protected function getParameters(array $parameters)
	{
		$result = '(';

		foreach($parameters as $parameter)
		{
			if(!empty($parameter['type_hint']))
			{
				$result.= $parameter['type_hint'] . ' ';
			}

			$result.= $parameter['by_ref'] ? '&' : '';
			$result.= '$' . $parameter['name'];

			if(!empty($parameter['default_value']))
			{
				$result.= ' = ' . $parameter['default_value'];
			}

			$result.= ', ';
		}

		if(count($parameters) > 0)
		{
			$result = substr($result, 0, -2);
		}

		$result.= ')';

		return $result;
	}

	protected function getVisibility($modifier)
	{
		if($modifier & 1)
		{
			return '+';
		}
		else if($modifier & 2)
		{
			return '#';
		}
		else if($modifier & 4)
		{
			return '-';
		}
	}
}
