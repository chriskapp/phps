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

/**
 * RiskCalculator
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class RiskCalculator
{
    public function calculate(Result $result)
    {
        $bcCount      = $result->getBcCount();
        $changedCount = $result->getChangedCount();
        $leftCount    = $result->getLeftCount();
        $rightCount   = $result->getRightCount();

        // upgrade risk
        if ($leftCount > 0) {
            if ($bcCount <= 0) {
                $riskBase = 0;
            } elseif ($bcCount <= 5) {
                $riskBase = 10;
            } elseif ($bcCount <= 10) {
                $riskBase = 15;
            } elseif ($bcCount <= 20) {
                $riskBase = 20;
            } elseif ($bcCount <= 40) {
                $riskBase = 30;
            } elseif ($bcCount <= 80) {
                $riskBase = 40;
            } else {
                $riskBase = 50;
            }

            // we increase the risk based on the changed classes
            $changePerClass = $riskBase * ($changedCount / $leftCount);

            // we increase the risk based on the BCs per class
            $bcPerClass = $riskBase * ($bcCount / $leftCount);

            // increase the risk
            $riskBase = $riskBase + ($changePerClass * 0.6) + ($bcPerClass * 0.4);

            $upgradeRisk = (int) $riskBase;

            // cap to 100
            if ($upgradeRisk > 100) {
                $upgradeRisk = 100;
            }
        } else {
            $upgradeRisk = 0;
        }

        return $upgradeRisk;
    }
}
