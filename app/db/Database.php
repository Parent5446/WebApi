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
 * Interacts with a MySQL or Mssql database to store and retrieve
 * information.
 *
 * @package API
 * @author Tyler Romeo <tylerromeo@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License 3.0
 * @copyright Copyright (c) 2009, Tyler Romeo (Some Rights Reserved)
 */
class DB_Database
{
	/**
	 * Array of database functions to be used
	 * in object.
	 * @static
	 */
	static $globalfunctions = array( "mysql" => array(
	                                 	"connect" => "mysql_connect",
	                                 	"close"   => "mysql_close",
	                                 	"db"      => "mysql_select_db",
	                                 	"query"   => "mysql_query",
	                                 	"result"  => "mysql_result",
	                                 	"array"   => "mysql_fetch_array",
						"assoc"   => "mysql_fetch_assoc",
	                                 	"escape"  => "mysql_real_escape_string",
	                                 	"numrows" => "mysql_num_rows" ),
	                                 "mssql" => array(
	                                 	"connect" => "mssql_connect",
	                                 	"close"   => "mssql_close",
	                                 	"db"      => "mssql_select_db",
	                                 	"query"   => "mssql_query",
	                                 	"result"  => "mssql_result",
	                                 	"array"   => "mssql_fetch_array",
	                                 	"escape"  => "addslashes" )
	                         );

	/**
	 * Stores singleton instance of object.
	 * @static
	 */
	static $instance;

	/**
	 * Stores resource for database connection.
	 * @private
	 */
	private $conn = false;

	/**
	 * Stores connection data for database,
	 * i.e. server, username, and password.
	 * @private
	 */
	private $conndata;

	/**
	 * Stores the database name to connect to.
	 * @private
	 */
	private $dbname;

	/**
	 * Stores the functions to interact with
	 * the database. Usually loaded from one of the
	 * static variables.
	 * @private
	 */
	private $functions;

	/**
	 * Stores table objects.
	 * @private
	 */
	private $tables;

	/**
	 * Stores a MAIN_Logger object.
	 * @private
	 */
	private $log;

	/**
	 * Stores connection data and attempts to connect.
	 *
	 * The database type is given in the parameters. For MySQL and extensions
	 * with similar function calls can be built into the class. Otherwise, child
	 * classes will be automatically created if they exist.
	 *
	 * @param string  $server    Server address to connect to
	 * @param string  $username  Username for the server
	 * @param string  $password  Password for the server
	 * @param string  $database  Database name to connect to
	 * @param object &$log       MAIN_Logger object
	 * @param string  $type      Name of database type
	 *
	 * @return bool|object Returns false if connection fails, returns object otherwise
	 */
	public function __construct($server, $username, $password, $database, &$log, $type = 'mysql') {
		if(!$log instanceof MAIN_Logger) {
			return false;
		}

		if(isset(self::$globalfunctions[$type])) {
			$this->functions =  self::$globalfunctions[$type];
		} elseif(class_exists($classname = "DB_Database_" . ucfirst(strtolower($type)))) {
			return $classname($server, $username, $password, $database, $log);
		} else {
			throw new MAIN_Error(MAIN_Error::ERROR, 'DB_Database::__construct', 'Invalid database type.', $log);
		}

		$this->conndata  =  array($server, $username, $password);
		$this->dbname    =  $database;
		$this->log       =& $log;

		// Real connection attempt starts here.
		$this->conn      = false;
		$this->connect();
		if($this->conn instanceof MAIN_Error) {
			throw $this->conn;
		}
	}

	/**
	 * Connects to a database server, e.g. MySQL, and stores
	 * the connection.
	 *
	 * @return bool Returns true for success, false for failure
	 */
	public function connect() {
		/*
		 * Check for existing connection first.
		 * If so, close it then continue.
		 */
		if(is_resource($this->conn)) {
			$this->close();
		} $this->log->log(MAIN_Logger::INFO, 'DB_Database::connect',
		                 'Starting database connection with connection data: ' . strtr(var_export($this->conndata, true), "\n", ''));

		// Check for connect function and connect to server.
		if(!function_exists($this->functions["connect"])) {
			return new MAIN_Error(MAIN_Error::ERROR, 'DB_Database::connect', 'Connection function does not exist.', $this->log);
		}

		$retval = call_user_func_array($this->functions["connect"], $this->conndata);

		// See if connection is valid.
		if($retval === false) {
			return new MAIN_Error(MAIN_Error::ERROR, 'DB_Database::connect', 'Connection failed for unknown reason.', $this->log);
		} else {
			$this->conn = $retval;
		}

		// Check for database function and select database.
		if(!function_exists($this->functions["db"])) {
			return new MAIN_Error(MAIN_Error::ERROR, 'DB_Database::connect', 'Database selection function does not exist.', $this->log);
		}

		$retval = call_user_func($this->functions["db"], $this->dbname, $this->conn);

		// See if selection was successful.
		if(!$retval) {
			return new MAIN_Error(MAIN_Error::ERROR, 'DB_Database::connect', 'Database selection failed (it may not exist).', $this->log);
		}

		// Get list of tables and load into object.
		$res = $this->query("SHOW TABLES FROM {$this->dbname}");

		if($res === false) {
			return new MAIN_Error(MAIN_Error::WARNING, 'DB_Database::connect', 'Could not retrieve a list of tables.', $this->log);
		}

		$list = array();
		while($row = $this->result($res)) {
			$list[$row[0]] = false;
		} $this->tables = $list;

		return true;
	}

	/**
	 * Checks if a database connection is open, and if so
	 * shuts the connection.
	 *
	 * @return bool Returns true for success, false for failure
	 */
	public function close() {
		// If there is no connection, return.
		$this->log->log(MAIN_Logger::INFO, 'DB_Database::close', 'Closing connection.');
		if(!is_resource($this->conn)) {
			return new MAIN_Error(MAIN_Error::NOTICE, 'DB_Database::connect', 'Closing connection that never opened.', $this->log);
			return true;
		}

		// Check for close function and close connection.
		if(!function_exists($this->functions["close"])) {
			return new MAIN_Error(MAIN_Error::ERROR, 'DB_Database::close', 'Function to close connection does not exist.', $this->log);
		}

		$retval = call_user_func($this->functions["close"], $this->conn);

		return $retval;
	}

	/**
	 * Submits a query to the database server, then returns
	 * the result of the query function.
	 *
	 * @param string $sql A SQL query to submit
	 *
	 * @return bool|resource Returns true or false for INSERT, DELETE,
	                         and other related queries. Returns a result
	                         resource for SELECT queries.
	 */
	public function query($sql) {
		$curtime = gmdate('c');
		$sql = "/* Time: $curtime */ /* DB: {$this->dbname} */ " . $sql;

		$this->log->log(MAIN_Logger::INFO, 'DB_Database::query', "Making query: $sql");

		// Check for a connection first.
		if(!is_resource($this->conn)) {
			return new MAIN_Error(MAIN_Error::WARNING, 'DB_Database::query', 'Database not connected yet.', $this->log);
		}

		// Check for query function and submit query.
		if(!function_exists($this->functions["query"])) {
			return new MAIN_Error(MAIN_Error::ERROR, 'DB_Database::query', 'Query function does not exist.', $this->log);
		}

		$retval = call_user_func($this->functions["query"], $sql, $this->conn);

		return $retval;
	}

	/**
	 * Obtains a result set from a result resource.
	 *
	 * @param resource $res  Result resource to get data from
	 * @param string   $type The function to use to get the data
	 *
	 * @param array Array of all the rows retrieved
	 */
	public function result($res, $type = "array") {
		// Check for a connection first.
		if(!is_resource($this->conn)) {
			return new MAIN_Error(MAIN_Error::WARNING, 'DB_Database::result', 'Database not connected yet.', $this->log);
		}

		/*
		 * Check for function and submit query.
		 * The function is taken from the array of database
		 * functions, using $type as the key.
		 */
		if(!function_exists($this->functions[$type])) {
			return new MAIN_Error(MAIN_Error::ERROR, 'DB_Database::result', 'Result retrieval function does not exist.', $this->log);
		}

		return call_user_func($this->functions[$type], $res);
	}

	/**
	 * Escapes a string for use in a database query.
	 *
	 * @param string $string The string to escape
	 *
	 * @return bool|string Returns the escaped string, or false on failure.
	 */
	public function escape($string) {
		// Check for a connection first.
		if(!is_resource($this->conn)) {
			return new MAIN_Error(MAIN_Error::WARNING, 'DB_Database::escape', 'Database not connected yet.', $this->log);
		}

		// Check for escape function and escape string.
		if(!function_exists($this->functions["escape"])) {
			return new MAIN_Error(MAIN_Error::ERROR, 'DB_Database::escape', 'Escape function does not exist.', $this->log);
		}

		return call_user_func($this->functions["escape"], $string);
	}

	/**
	 * Get a table object for query usage.
	 *
	 * Retrive an object for a specific table, rather than using the
	 * general query function.
	 *
	 * @param string $tablename Name of the table
	 *
	 * @return object|bool Returns Table object, or false if table does not exist
	 */
	public function getTable($tablename) {
		$this->log->log(MAIN_Logger::INFO, 'DB_Database::getTable', "Getting table $tablename.");
		if($this->tables[$tablename] instanceof DB_Table) {
			return $this->tables[$tablename];
		} elseif($this->tables[$tablename] === false) {
			$this->tables[$tablename] = new DB_Table($this, $tablename);
			return $this->tables[$tablename];
		} else {
			return new MAIN_Error(MAIN_Error::WARNING, 'DB_Database::getTable',
			                      "$tablename does not exist.");
		}
	}

	/**
	 * Get the log object for the database.
	 *
	 * @return object MAIN_Logger instance
	 */
	public function &getLog() {
		return $this->log;
	}
}
