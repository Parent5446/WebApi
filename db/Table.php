<?php

if(!defined("API")) { return false; }

/**
 * This program was created to make a set of classes to be used for easy
 * website creation. Usage: simply extend the DB_Object class to make models
 * and the OUT_Request class to make controllers. HTML is stored in templates
 * with special tags. See the OUT_Template class for more details.
 * Made by Tyler Romeo <tylerromeo@gmail.com>
 *
 * Copyright (C) 2009 Tyler Romeo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Interacts with a MySQL table to store and retrieve
 * information.
 *
 * Creates a number of abstract functions to allow easy database queries
 * as well as automatic escaping of given values.
 *
 * @package API
 * @author Tyler Romeo <tylerromeo@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License 3.0
 * @copyright Copyright (c) 2009, Tyler Romeo (Some Rights Reserved)
 */
class DB_Table
{
	/**
	 * The parent DB_Database object
	 * @private
	 */
	private $database;

	/**
	 * The name of the table in use
	 * @private
	 */
	private $tablename;

	/**
	 * Stores the database and table information.
	 *
	 * @param object &$database  Parent DB_Database object to use
	 * @param string  $tablename Name of the table to use
	 */
	public function __construct(&$database, $tablename) {
		$this->log =& $database->getLog();

		if(!$database instanceof DB_Database) {
			return new MAIN_Error(MAIN_Error::ERROR, 'DB_Table::__construct', 'Given Database object is not valid.', $this->log);
		}

		$this->database  =& $database;
		$this->tablename =  $tablename;
	}

	/**
	 * Get the log object for the database.
	 *
	 * @return object MAIN_Logger instance
	 */
	public function &getLog() {
		return $this->log;
	}

	/**
	 * Queries the database by using the parent object's function.
	 *
	 * @param string $sql Query to be submitted
	 *
	 * @return bool|resource Returns true or false for INSERT, DELETE,
	                         and other related queries. Returns a result
	                         resource for SELECT queries.
	 */
	public function query($sql) {
		$sql = "/* tablename: {$this->tablename} */ " . $sql;
		return $this->database->query($sql);
	}

	/**
	 * Obtains a result set from a result resource by using the parent
	 * object's function.
	 *
	 * @param resource $res  Result resource to get data from
	 * @param string   $type The function to use to get the data
	 *
	 * @param mixed By default returns an array, but may change
	                depending on the $type.
	 */
	public function result($res, $type = "array") {
		return $this->database->result($res, $type = "array");
	}

	/**
	 * Escapes strings and arrays for use in a database query.
	 *
	 * If a string is given, it is escaped using the parent object's
	 * function. If an array is given, all keys and values are
	 * escaped using the same method.
	 *
	 * @param mixed $var The variable to escape
	 *
	 * @param mixed The escaped variable
	 */
	public function escape($var) {
		if(is_array($var)) {
			// Escape all keys and values.
			$newvar = array();
			foreach($var as $key => $value) {
				$newvar[$this->database->escape($key)] = $this->database->escape($value);
			}
		} elseif(is_string($var)) {
			// Escape just the variable.
			$newvar = $this->database->escape($var);
		} elseif(is_int($var) || is_float($var)) {
			$newvar = $var;
		} else {
			// Bad variable type.
			$newvar = false;
		} return $newvar;
	}

	/**
	 * Selects information from a table.
	 *
	 * The equivalent of a SELECT query, which is created from
	 * the parameters and submitted to the database.
	 *
	 * @param string|array $fields      The fields to select as an array or string
	 * @param        array $where       Where to select information from
	 * @param string       $conditional The conditional to put in between the WHERE statements
	 * @param        array $options     Other options to add to the query
	 *
	 * @return resource Returns a result resource to get query information
	 */
	public function select($fields = '*', $where = '', $conditional = 'AND', $options = array()) {
		// Escape data and implode if an array.
		$fields = $this->escape($fields);
		if(is_array($fields)) {
			$fields = implode(',', $fields);
		}

		// WHERE statement and options.
		$where   = $this->whereStatement($where, $conditional);
		$options = $this->optionsStatement($options);

		// Do the query.
		$sql = "SELECT $fields FROM {$this->tablename} $where $options";
		return $this->query($sql);
	}

	/**
	 * Inserts a row into the database.
	 *
	 * The equivalent of an INSERT query, which is created from
	 * the parameters and submitted to the database.
	 *
	 * @param array $row The columns and values to add
	 * @param array $options     Other options to add to the query
	 *
	 * @return bool Returns true on success, false on failure
	 */
	public function insert($row, $options = array()) {
		// Return if not an array.
		if(!is_array($row)) {
			return new MAIN_Error(MAIN_Error::WARNING, 'DB_Table::insert', 'Parameter is not the correct data type.', $this->log);
		}

		// Escape and separate list of columns and values.
		$row    = $this->escape($row);
		$cols   = implode(', ', array_keys($row));
		$values = implode('\', \'',            $row );

		// Options
		$options = $this->optionsStatement($options);

		// Do the query.
		$sql = "INSERT INTO {$this->tablename} ($cols) VALUES ('$values') $options";
		return $this->query($sql);
	}

	/**
	 * Updates information in a table.
	 *
	 * The equivalent of an UPDATE query, which is created from
	 * the parameters and submitted to the database.
	 *
	 * @param array  $row         The columns and values to be updated
	 * @param array  $where       Where to select information from
	 * @param string $conditional The conditional to put in between the WHERE statements
	 * @param array  $options     Other options to add to the query
	 *
	 * @return bool Returns true on success, false on failure
	 */
	public function update($row, $where = '', $conditional = 'AND', $options = array()) {
		// Return if not an array.
		if(!is_array($row)) {
			return new MAIN_Error(MAIN_Error::WARNING, 'DB_Table::insert', 'Parameter is not the correct data type.', $this->log);
		}

		// Escape them implode into usable statement.
		$row = $this->escape($row);
		foreach($row as $key => &$value) {
			$value = "$key='$value' ";
		} $values = implode(", ", $row);

		// WHERE statement and options.
		$where   = $this->whereStatement($where, $conditional);
		$options = $this->optionsStatement($options);

		// Do the query.
		$sql = "UPDATE {$this->tablename} SET $values $where $options";
		return $this->query($sql);
	}

	/**
	 * Deletes information from a table.
	 *
	 * The equivalent of a DELETE query, which is created from
	 * the parameters and submitted to the database.
	 *
	 * @param        array $where       Where to select information from
	 * @param string       $conditional The conditional to put in between the WHERE statements
	 * @param        array $options     Other options to add to the query
	 *
	 * @return bool Returns true on success, false on failure
	 */
	public function delete($where = array(), $conditional = 'AND', $options = array()) {
		// WHERE statement and options.
		$where   = $this->whereStatement($where, $conditional);
		$options = $this->optionsStatement($options);

		// Do the query.
		$sql = "DELETE FROM {$this->tablename} $where $options";
		return $this->query($sql);
	}

	/**
	 * Takes a user-given array of information and makes a SQL WHERE statement.
	 *
	 * @param array  $where       Where to select information from
	 * @param string $conditional The conditional to put in between the WHERE statements
	 *
	 * @return string Returns a valid WHERE statement
	 */
	private function whereStatement($where, $conditional) {
		// Check if it is an array.
		if(is_array($where)) {
			// Start off the statement and escape.
			$tmp = 'WHERE ';
			$where = $this->escape($where);

			// Combine and implode values.
			foreach($where as $key => &$value) {
				$value = "$key='$value' ";
			} $tmp .= implode(" $conditional ", $where);

			// Return final value
			return $tmp;
		} else {
			// Bad variable type.
			return false;
		}
	}

	/**
	 * Takes a user-given array of extra query options and
	 * sticks them together for use in a query.
	 *
	 * @param array $options The database options
	 *
	 * @return string Returns a valid list of options
	 */
	private function optionsStatement($options) {
		// Escape, combine, implode.
		$options = $this->escape($options);
		foreach($options as $key => &$value) {
			$value = "$key $value ";
		} return implode(' ', $options);
	}
}

