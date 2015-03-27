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

namespace Phps\Annotation;

/**
 * DocBlock
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class DocBlock
{
	protected $annotations = array();

	/**
	 * Adds an annotation
	 *
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	public function addAnnotation($key, $value)
	{
		if(!isset($this->annotations[$key]))
		{
			$this->annotations[$key] = array();
		}

		$this->annotations[$key][] = $value;
	}

	/**
	 * Returns all annotations associated with the $key
	 *
	 * @param string $key
	 * @return array
	 */
	public function getAnnotation($key)
	{
		if(isset($this->annotations[$key]))
		{
			return $this->annotations[$key];
		}
		else
		{
			return array();
		}
	}

	public function hasAnnotation($key)
	{
		return isset($this->annotations[$key]);
	}

	public function removeAnnotation($key)
	{
		unset($this->annotations[$key]);
	}

	public function getAnnotations()
	{
		return $this->annotations;
	}

	public function setAnnotations($key, array $values)
	{
		$this->annotations[$key] = $values;
	}

	/**
	 * Returns te first annotation for the $key
	 *
	 * @param string $key
	 * @return string|null
	 */
	public function getFirstAnnotation($key)
	{
		$annotation = $this->getAnnotation($key);

		return isset($annotation[0]) ? $annotation[0] : null;
	}
}
