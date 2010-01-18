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
 * information for a defined object, such as a user object. Classes
 * should inherit this class in order to become a Database object.
 *
 * @package API
 * @author Tyler Romeo <tylerromeo@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License 3.0
 * @copyright Copyright (c) 2009, Tyler Romeo (Some Rights Reserved)
 */
class DB_Object
{
	/**
	 * Determines whether to enable password protection on a global scope. Should
	 * be customized in the child class definition.
	 * @static
	 * @private
	 */
	protected static $enableprotect = true;

	/**
	 * Determines whether the object can take in other objects as its children. When
	 * taking in a child object, the profiles of the two are merged.
	 * @static
	 * @private
	 */
	protected static $parent = true;

	/**
	 * Stores information about the object that has been retrieved
	 * from the database.
	 * @private
	 */
	protected $info = array();

	/**
	 * Determines whether to enable password protection on a per-object basis.
	 * @private
	 */
	protected $protect       = true;

	/**
	 * Whether to enable client hashing, when a password is entered by a user,
	 * hash chaining is used on the client side to prevent man-in-the-middle attacks.
	 * Requires the proper client-side scripts to be in place.
	 * @private
	 */
	protected $clienthashing = false;

	/**
	 * A DB_Table object to be used for all database queries.
	 * @private
	 */
	protected $table;

	/**
	 * Store the database and given information within the object. If an empty password
	 * is given then password protection is disabled. If the $curpass variable is empty then
	 * client hashing will be disabled.
	 *
	 * @param object &$db         A DB_Database object used for database queries
	 * @param int     $id         The ID of the object
	 * @param string  $name       The name of the object (e.g. username for users)
	 * @param string  $origpass   A SHA512 hash of the original password protecting the object
	 * @param string  $curpass    The current one-time key produced by hash chaining
	 * @param int     $curpassnum The number of hash iterations for the $curpass
	 * @param array   $profile    Any information specific to the object from the database
	 */
	protected function __construct(&$db, &$config, $column, $value, $profile = array(), $new = false) {
		// Check the parmeters.
		if(!is_string($column) ||
		   !is_string($value) ||
		   !$db instanceof DB_Database ||
		   !config instanceof MAIN_Config) {
			return new MAIN_Error(MAIN_Error::WARNING, get_class($this) .
			       '::__construct', 'Parameter is not the correct data type.', $this->log);
		}

		// Store local table, log, and options.
		$this->table         =& $db->getTable(strtolower(get_class($this)));
		$this->log           =& $db->getLog();
		$this->protect       =  self::$enableprotect;
		$this->clienthashing =  $config->getOption('clienthashing');

		// Initiate the object.
		if(!$new) {
			// Object is old, get info from database.
			$this->updateFromDatabase($column, $value);
		} else {
			// Object is new; put together info array and push to database.
			$this->info = array();
			$info[$column] = $value;
			$info = array_merge($info, $profile);
			if($this->clienthashing) {
				$info['hash'  ] = self::hashPassword($info['password'], 1000);
				$info['hasnum'] = 1000;
			} $this->updateToDatabase();
		} $this->log->log(MAIN_Logger::NOTICE, get_class() . '::__construct',
		                  "Creating new object with info: " . var_dump($info);
	}

	/**
	 * Get the name of the object.
	 *
	 * @return string The name of the object
	 */
	public function getName() {
		return $this->info['name'];
	}

	/**
	 * Get the ID of the object.
	 *
	 * @return int The ID of the object
	 */
	public function getId() {
		return $this->info['id'];
	}

	/**
	 * Get the profile of the object.
	 *
	 * @return array The profile of the object
	 */
	public function getProfile() {
		return $this->info['profile'];
	}

	/**
	 * Merges a child database object with the current object.
	 *
	 * Adds an array with the child object's ID, name, profile, and the object itself
	 * into the current object's profile. It is stored in:
	 * $<currentObject>->profile[<nameOfChildObject>][]
	 *
	 * @param object $child Child object to store
	 *
	 * @return bool True on success, false otherwise
	 */
	public function addChild($child) {
		if(!$this->parent || get_parent_class($child) != 'DB_Object') {
			return false;
		}

		// Put classname in lowercase and change to plural.
		$classname = strtolower(get_class($child)) . 's';
		if(isset($this->profile[$classname]) && is_array($this->profile[$classname])) {
			$this->profile[$classname][] = $child->getProfile();
		} else {
			$this->profile[$classname] = array('id'      => $child->getId(),
		                                           'name'    => $child->getName(),
		                                           'profile' => $child->getProfile(),
		                                           'object'  => &$child);
		} return true;
	}

	/**
	 * Merges multiple child database objects with the current object.
	 * Calls the addChild function for each child given.
	 *
	 * @param array $child Child objects to add
	 *
	 * @return bool True on success, false otherwise
	 */
	public function addChildren($children) {
		if(!is_array($children)) {
			return false;
		} $retval = true;
		foreach($children as $child) {
			$retval = $retval && $this->addChild($child);
		} return $retval;
	}

	/**
	 * Get the current iteration on the hash chain.
	 *
	 * @return array The current iteration on the hash chain
	 */
	public function getPassKey() {
		if(!$this->protect || !self::$enableprotect || !$this->clienthashing) {
			return 1;
		}
		return $this->info['passhash'][0] - 1;
	}

	/**
	 * Compare a given password to the stored password hash.
	 *
	 * @param string $new      The password to check
	 * @param bool   $original Whether the given password is the actual plaintext password
	 *                         or a one time key on the hash chain
	 *
	 * @return bool True if the password is correct, false otherwise
	 */
	public function checkPassword($new, $original = false) {
		if(!$this->protect || !self::$enableprotect) {
			return true;
		}

		if($original || !$this->clienthashing) {
			// Compare the password to the original hash
			// check if: h(original) == h(given)
			return $this->info['password'] == self::hashPassword($new, $salt);
		} else {
			// Compare the password to the a hash chain
			// check if: h^n(original) == h(h^(n-1)given) where n is the hash chain iteration
			if(self::comparePasswords($this->info['passhash'][1], $new)) {
				$this->changePasshash($new);
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Change the password for the object.
	 *
	 * Store a new password hash in the database as well as
	 * the password hashed 1000 times for client side hashing.
	 *
	 * @param string $new The new plaintext password
	 *
	 * @return bool True on success, false on failure.
	 */
	public function changePassword($new) {
		if(!$this->protect || !self::$enableprotect) {
			return true;
		}

		$password = self::hashPassword($new);
		$hash     = self::hashPassword($new, 1000);
		$row      = array( 'password' => $password,
		                   'hash'     => $hash,
		                   'hashnum'  => 1000 );
		$where    = array( 'name'     => $this->info['name'] );

		$this->info['password'] = $password;
		$this->info['passhash'] = array(1000, $hash);
		return $this->updateToDatabase();
	}

	/**
	 * Used for one time keys when hash chaining and client-side hashing is enabled.
	 * Decreases the hash chain count by one and uses the given hash as the new current hash.
	 *
	 * @param string $new The next hash in the hash chain (as supplied by the user)
	 *
	 * @return bool True on success, false on failure
	 */
	protected function changePasshash($new) {
		if(!$this->protect || !self::$enableprotect || !$this->clienthashing) {
			return true;
		}

		$newnum = $this->info['passhash'][0] - 1;
		$this->info['passhash'] = array($curnum, $new);
		return $this->table->update(array('hash'    => $new,
		                                  'hashnum' => $curnum),
		                            array('name'    => $info['name']));
	}

	/**
	 * Get properties about the object from the database.
	 *
	 * @param string $column Column to search for info from.
	 * @param string $value  Value in the column to match the object to.
	 */
	public function updateFromDatabase($column, $value) {
		if(!$db instanceof DB_Database) {
			return false;
		}

		$table = $db->getTable(strtolower(get_called_class()));
		$res   = $table->select('*', array($column => $value));

		$this->info = $table->result($res);
	}

	/**
	 * Update properties about the object to the database.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function updateToDatabase() {
		if(!$db instanceof DB_Database) {
			return false;
		}

		$table  = $db->getTable(strtolower(get_class()));
		return $table->insert($this->info);
	}

	/**
	 * Hash a given plaintext password a given number of times (1 hash is the default).
	 *
	 * @param string $password   The password to hash
	 * @param int    $iterations Number of times to hash the password
	 *
	 * @return string Hash of the password
	 */
	public static function hashPassword($password, $iterations = 1) {
		for($i = 0; $i < $iterations; $i++) {
			$hash = hash('sha512', $password);
		} return $hash;
	}
}

