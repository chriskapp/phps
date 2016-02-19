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

use Symfony\Component\Console\Output\OutputInterface;

/**
 * FormatterInterface
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
interface FormatterInterface
{
    /**
     * Formats the search output
     *
     * @param array $result
     * @param Symfony\Component\Console\Output\OutputInterface $output
     */
    public function formatSearchResult(array $result, OutputInterface $output);

    /**
     * Formats the describe output
     *
     * @param array $result
     * @param Symfony\Component\Console\Output\OutputInterface $output
     */
    public function formatDescribeResult(array $result, OutputInterface $output);

    /**
     * Formats the diff output
     *
     * @param array $result
     * @param Symfony\Component\Console\Output\OutputInterface $output
     */
    public function formatDiffResult(array $result, OutputInterface $output);
}
