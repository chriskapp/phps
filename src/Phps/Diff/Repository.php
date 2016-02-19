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

    protected function getClasses($offset)
    {
        $sql = 'SELECT class.id,
                       class.name,
                       class.extend
                  FROM comparabl_class class
                 LIMIT ' . $offset . ', 100';

        return $this->connection->fetchAll($sql);
    }

    protected function getClassCount()
    {
        $sql = 'SELECT COUNT(class.id)
                  FROM comparabl_class class';

        return $this->connection->fetchColumn($sql);
    }

    protected function getImplements(array $class)
    {
        $sql = '    SELECT implement.name
                      FROM comparabl_implement implement
                INNER JOIN comparabl_class class
                        ON class.id = implement.class_id
                     WHERE class.name = :name';

        $implements = $this->connection->fetchAll($sql, array(
            'name' => $class['name'],
        ));

        $names = array();
        foreach ($implements as $implement) {
            $names[] = $implement['name'];
        }

        return $names;
    }

    protected function getProperties(array $class)
    {
        $sql = '    SELECT property.id,
                           property.modifier,
                           property.name
                      FROM comparabl_property property
                INNER JOIN comparabl_class class
                        ON class.id = property.class_id
                     WHERE class.name = :name';

        return $this->connection->fetchAll($sql, array(
            'name' => $class['name'],
        ));
    }

    protected function getMethods(array $class)
    {
        $sql = '    SELECT method.id,
                           method.modifier,
                           method.name
                      FROM comparabl_method method
                INNER JOIN comparabl_class class
                        ON class.id = method.class_id
                     WHERE class.name = :name';

        return $this->connection->fetchAll($sql, array(
            'name' => $class['name'],
        ));
    }

    protected function getMethodParameters(array $method)
    {
        $sql = '    SELECT parameter.id,
                           parameter.position,
                           parameter.type_hint,
                           parameter.name,
                           parameter.by_ref,
                           parameter.default_value
                      FROM comparabl_parameter parameter
                INNER JOIN comparabl_method method
                        ON method.id = parameter.method_id
                     WHERE method.name = :name';

        return $this->connection->fetchAll($sql, array(
            'name' => $method['name'],
        ));
    }

    protected function findClass($className)
    {
        $sql = 'SELECT class.id,
                       class.name,
                       class.extend
                  FROM comparabl_class class
                 WHERE class.name = :name';

        return $this->connection->fetchAssoc($sql, array(
            'name' => $className,
        ));
    }
}
