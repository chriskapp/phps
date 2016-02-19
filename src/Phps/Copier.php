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
use Psr\Log\LoggerInterface;

/**
 * Copier
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class Copier
{
    const ROW_BUFFER = 256;

    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Copies all data from the source to the destination and removes all data
     * on the source connection
     *
     * @param Doctrine\DBAL\Connection $srcConnection
     * @param Doctrine\DBAL\Connection $destConnection
     */
    public function copy(Connection $srcConnection, Connection $destConnection)
    {
        $this->copyClass($srcConnection, $destConnection);
        $this->copyImplement($srcConnection, $destConnection);
        $this->copyProperty($srcConnection, $destConnection);
        $this->copyMethod($srcConnection, $destConnection);
        $this->copyParameter($srcConnection, $destConnection);
    }

    protected function copyClass(Connection $srcConnection, Connection $destConnection)
    {
        $this->copyTable($srcConnection, $destConnection, 'phps_class', ['id', 'name', 'extend', 'file']);
    }

    protected function copyImplement(Connection $srcConnection, Connection $destConnection)
    {
        $this->copyTable($srcConnection, $destConnection, 'phps_implement', ['class_id', 'name']);
    }

    protected function copyProperty(Connection $srcConnection, Connection $destConnection)
    {
        $this->copyTable($srcConnection, $destConnection, 'phps_property', ['class_id', 'modifier', 'name', 'type', 'default_value']);
    }

    protected function copyMethod(Connection $srcConnection, Connection $destConnection)
    {
        $this->copyTable($srcConnection, $destConnection, 'phps_method', ['id', 'class_id', 'modifier', 'name', 'return_type']);
    }

    protected function copyParameter(Connection $srcConnection, Connection $destConnection)
    {
        $this->copyTable($srcConnection, $destConnection, 'phps_parameter', ['method_id', 'position', 'type_hint', 'name', 'by_ref', 'default_value']);
    }

    /**
     * Copies the table from the in-memory sqlite to the file sqlite database.
     * Tests have shown that it is more performant when we use multiple big sql
     * queries instead of prepared statments
     *
     * @param Doctrine\DBAL\Connection $srcConnection
     * @param Doctrine\DBAL\Connection $destConnection
     * @param string tableName
     * @param array $columns
     */
    protected function copyTable(Connection $srcConnection, Connection $destConnection, $tableName, array $columns)
    {
        $result = $srcConnection->fetchAll('SELECT ' . implode(', ', $columns) . ' FROM ' . $tableName);
        $sql    = '';
        
        foreach ($result as $i => $row) {
            if ($i % self::ROW_BUFFER == 0 && $i > 0) {
                $destConnection->query(substr($sql, 0, -1));
            }

            if ($i % self::ROW_BUFFER == 0) {
                $sql = 'INSERT INTO ' . $tableName . ' (' . implode(', ', $columns) . ') VALUES ';
            }

            $sql.= '(';
            foreach ($columns as $column) {
                $sql.= $destConnection->quote($row[$column]);
                $sql.= ',';
            }
            $sql = substr($sql, 0, -1);
            $sql.= '),';
        }

        $sql = substr($sql, 0, -1);
        if (!empty($sql)) {
            $destConnection->query($sql);
        }

        $srcConnection->query('DELETE FROM ' . $tableName);

        $this->logger->info('Found ' . count($result) . ' ' . substr($tableName, 5));
    }
}
