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

use Doctrine\DBAL\Connection;

/**
 * Cleaner
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class Cleaner
{
    const IN_BUFFER = 256;

    public function deleteAll(Connection $connection)
    {
        $connection->query('DELETE FROM phps_class');

        $connection->query('DELETE FROM phps_implement');

        $connection->query('DELETE FROM phps_property');

        $connection->query('DELETE FROM phps_method');

        $connection->query('DELETE FROM phps_parameter');
    }

    public function deleteByPath(Connection $connection, $basePath)
    {
        $result      = $connection->fetchAll('SELECT id FROM phps_class WHERE file LIKE ' . $connection->quote($basePath . '%'));
        $allClassIds = array();

        foreach ($result as $row) {
            $allClassIds[] = $row['id'];
        }

        $i = 0;
        while (count($classIds = array_slice($allClassIds, $i, self::IN_BUFFER)) > 0) {
            $connection->query('DELETE FROM phps_class WHERE id IN ("' . implode('","', $classIds) . '")');

            $connection->query('DELETE FROM phps_implement WHERE class_id IN ("' . implode('","', $classIds) . '")');

            $connection->query('DELETE FROM phps_property WHERE class_id IN ("' . implode('","', $classIds) . '")');

            $result       = $connection->fetchAll('SELECT id FROM phps_method WHERE class_id IN ("' . implode('","', $classIds) . '")');
            $allMethodIds = array();

            foreach ($result as $row) {
                $allMethodIds[] = $row['id'];
            }

            $connection->query('DELETE FROM phps_method WHERE class_id IN ("' . implode('","', $classIds) . '")');

            $j = 0;
            while (count($methodIds = array_slice($allMethodIds, $j, self::IN_BUFFER)) > 0) {
                $connection->query('DELETE FROM phps_parameter WHERE method_id IN ("' . implode('","', $methodIds) . '")');

                $j+= self::IN_BUFFER;
            }

            $i+= self::IN_BUFFER;
        }
    }
}
