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
use InvalidArgumentException;

/**
 * Repository
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class Repository
{
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getDescription($className, $query)
    {
        $class = $this->getClassByName($className);

        if (!empty($class)) {
            return array(
                'class'      => $class['name'],
                'extends'    => !empty($class['extend']) ? $class['extend'] : null,
                'implements' => $this->getImplements($class),
                'properties' => $this->getProperties($class, $query),
                'methods'    => $this->getMethods($class, $query),
            );
        } else {
            throw new \RuntimeException('Class name not found');
        }
    }

    public function getClasses($query)
    {
        $query = ltrim($query, '\\');

        $sql = 'SELECT name,
				       file
				  FROM phps_class
				 WHERE name LIKE :query';

        return $this->connection->fetchAll($sql, array(
            'query' => '%' . $query . '%'
        ));
    }

    public function getClassesByOffset($offset)
    {
        $sql = 'SELECT id,
                       name,
                       extend,
                       file
                  FROM phps_class
                 LIMIT ' . $offset . ', 100';

        return $this->connection->fetchAll($sql);
    }

    public function getClassByName($className)
    {
        $sql = 'SELECT id,
				       name,
				       extend,
				       file
				  FROM phps_class
				 WHERE name = :name';

        return $this->connection->fetchAssoc($sql, array(
            'name' => $className
        ));
    }

    public function getClassCount()
    {
        $sql = 'SELECT COUNT(id)
                  FROM phps_class';

        return (int) $this->connection->fetchColumn($sql);
    }

    public function getImplements(array $class)
    {
        $sql = 'SELECT name
                  FROM phps_implement
                 WHERE class_id = :class_id';

        $params = array(
            'class_id' => $class['id']
        );

        $result     = $this->connection->fetchAll($sql, $params);
        $implements = [];

        foreach ($result as $row) {
            $implements[] = $row['name'];
        }

        return $implements;
    }

    public function getProperties(array $class, $query = null)
    {
        $sql = 'SELECT modifier,
				       name,
				       type,
				       default_value
				  FROM phps_property
				 WHERE class_id = :class_id';

        $params = array(
            'class_id' => $class['id']
        );

        if (!empty($query)) {
            $sql.= ' AND name LIKE :name';
            $params['name'] = '%' . $query . '%';
        }

        return $this->connection->fetchAll($sql, $params);
    }

    public function getMethods(array $class, $query = null)
    {
        $sql = 'SELECT id,
				       modifier,
				       name,
				       return_type
				  FROM phps_method
				 WHERE class_id = :class_id';

        $params = array(
            'class_id' => $class['id']
        );

        if (!empty($query)) {
            $sql.= ' AND name LIKE :name';
            $params['name'] = '%' . $query . '%';
        }

        $methods = $this->connection->fetchAll($sql, $params);

        foreach ($methods as $key => $row) {
            $methods[$key]['parameters'] = $this->getParametersByMethod($row['id']);
        }

        return $methods;
    }

    public function getParametersByMethod($methodId)
    {
        $sql = 'SELECT position,
				       type_hint,
				       name,
				       by_ref,
				       default_value
				  FROM phps_parameter
				 WHERE method_id LIKE :method_id';

        return $this->connection->fetchAll($sql, array(
            'method_id' => $methodId
        ));
    }
}
