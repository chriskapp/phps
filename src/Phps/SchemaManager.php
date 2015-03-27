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

namespace Phps;

use Doctrine\DBAL\Connection;

/**
 * SchemaManager
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 * @link    https://github.com/k42b3/phps
 */
class SchemaManager
{
	public function createSchema(Connection $connection)
	{
		$sql = 'CREATE TABLE IF NOT EXISTS phps_class (';
		$sql.= 'id VARCHAR(16) PRIMARY KEY,';
		$sql.= 'name VARCHAR(255) NOT NULL,';
		$sql.= 'extend VARCHAR(255),';
		$sql.= 'file VARCHAR(255) NOT NULL';
		$sql.= ')';

		$connection->query($sql);

		$sql = 'CREATE TABLE IF NOT EXISTS phps_implement (';
		$sql.= 'class_id VARCHAR(16) NOT NULL,';
		$sql.= 'name VARCHAR(255) NOT NULL';
		$sql.= ')';

		$connection->query($sql);

		$sql = 'CREATE TABLE IF NOT EXISTS phps_property (';
		$sql.= 'class_id VARCHAR(16) NOT NULL,';
		$sql.= 'modifier INTEGER NOT NULL,';
		$sql.= 'name VARCHAR(255) NOT NULL,';
		$sql.= 'type VARCHAR(255),';
		$sql.= 'default_value VARCHAR(255)';
		$sql.= ')';

		$connection->query($sql);

		$sql = 'CREATE TABLE IF NOT EXISTS phps_method (';
		$sql.= 'id VARCHAR(16) PRIMARY KEY,';
		$sql.= 'class_id VARCHAR(16) NOT NULL,';
		$sql.= 'modifier INTEGER NOT NULL,';
		$sql.= 'name VARCHAR(255) NOT NULL,';
		$sql.= 'return_type VARCHAR(255)';
		$sql.= ')';

		$connection->query($sql);

		$sql = 'CREATE TABLE IF NOT EXISTS phps_parameter (';
		$sql.= 'method_id VARCHAR(16) NOT NULL,';
		$sql.= 'position INTEGER NOT NULL,';
		$sql.= 'type_hint VARCHAR(255),';
		$sql.= 'name VARCHAR(255) NOT NULL,';
		$sql.= 'by_ref INTEGER NOT NULL,';
		$sql.= 'default_value VARCHAR(255)';
		$sql.= ')';

		$connection->query($sql);
	}
}
