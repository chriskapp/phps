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

namespace Phps\Diff;

use Phps\Diff;

/**
 * Result
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class Result
{
    protected $result = array();
    protected $leftCount;
    protected $rightCount;
    protected $calculator;

    public function __construct(RiskCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function add($className, $level, $description)
    {
        if (!isset($this->result[$className])) {
            $this->result[$className] = [];
        }

        $this->result[$className][] = [
            'level'       => $level,
            'description' => $description,
        ];
    }

    public function setLeftCount($count)
    {
        $this->leftCount = $count;
    }

    public function setRightCount($count)
    {
        $this->rightCount = $count;
    }

    public function getLeftCount()
    {
        return $this->leftCount;
    }

    public function getRightCount()
    {
        return $this->rightCount;
    }

    public function getBcCount()
    {
        $count = 0;
        foreach ($this->result as $className => $logs) {
            foreach ($logs as $log) {
                if ($log['level'] == Diff::LEVEL_BC) {
                    $count++;
                }
            }
        }
        return $count;
    }

    public function getChangedCount()
    {
        $count = 0;
        foreach ($this->result as $className => $logs) {
            foreach ($logs as $log) {
                $count++;
                break;
            }
        }
        return $count;
    }

    public function getUpgradeRisk()
    {
        return $this->calculator->calculate($this);
    }

    public function getCount()
    {
        return count($this->result);
    }

    public function toArray()
    {
        return [
            'bc_count'      => $this->getBcCount(),
            'changed_count' => $this->getChangedCount(),
            'left_count'    => $this->getLeftCount(),
            'right_count'   => $this->getRightCount(),
            'upgrade_risk'  => $this->getUpgradeRisk(),
            'result'        => $this->result,
        ];
    }
}
