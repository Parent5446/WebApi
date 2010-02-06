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
 * Holds session and request information, including the $_GET, $_SERVER,
 * $_SERVER, and $_SESSION variables.
 *
 * @package API
 * @author Tyler Romeo <tylerromeo@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License 3.0
 * @copyright Copyright (c) 2009, Tyler Romeo (Some Rights Reserved)
 */
class OUT_Session
{
	/**
	 * Session ID for the request.
	 * @private
	 */
	private $SessionID;

	/**
	 * Verification hash to prevent session hijacking.
	 * @private
	 */
	private $VerifyID;

	/**
	 * The $_POST superglobal.
	 * @private
	 */
	private $POST;

	/**
	 * The $_GET superglobal.
	 * @private
	 */
	private $GET;

	/**
	 * The $_SERVER superglobal.
	 * @private
	 */
	private $SERVER;

	/**
	 * Stores local copies of the POST, GET, and SERVER superglobals.
	 */
	public function __construct() {
		$this->POST   = $_POST;
		$this->GET    = $_GET;
		$this->SERVER = $_SERVER;
	}

	/**
	 * Starts a PHP session, stores the session id,
	 * and generates an unique hash using the IP address
	 * and the User Agent.
	 */
	public function startSession() {
		// Start session and store session id.
		session_start();
		$this->SessionID = session_id();

		// Create verification hash.
		$plaintext = $this->getIpAddress() . $this->getText('HTTP_USER_AGENT', 'SERVER');
		$hash      = hash('sha512', $plaintext);

		// Check hash if already existant.
		$oldhash = $this->getText('verifyid', 'SESSION', '');
		if(!empty($oldhash) && $oldhash != $hash) {
			// Invalid hash. Get new session.
			session_write_close();
			session_regenerate_id();
			return $this->startSession();
		}

		// Store verification id.
		$this->putSessionVal('verifyid', $hash);
		$this->VerifyID = $hash;
	}

	/**
	 * Ends a session by deleting the session cookie, destroying the session,
	 * and resetting the $_SESSION superglobal.
	 */
	public function endSession() {
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-42000, '/');
		} unset($this->SessionID, $this->VerifyID);
		@session_destroy();
		$_SESSION = array();
	}

	public function getIpAddress() {
		if ($this->getText('HTTP_CLIENT_IP', 'SERVER') != '')   {
			$ip = $this->getText('HTTP_CLIENT_IP', 'SERVER');
		} elseif($this->getText('HTTP_X_FORWARDED_FOR', 'SERVER') != '') {
			$ip = $this->getText('HTTP_X_FORWARDED_FOR', 'SERVER');
		} else {
			$ip = $this->getText('REMOTE_ADDR', 'SERVER');
		} return $ip;
	}

	/**
	 * Stores a session variable.
	 *
	 * @param string $name  Name of the variable
	 * @param string $value Value of the variable
	 */
	public function putSessionVal($name, $value) {
		$_SESSION[$name] = $value;
	}

	/**
	 * Gets a variable from either the $_GET, $_POST, $_SERVER, or $_SESSION superglobal.
	 * If $_GET is specified, the function will automatically search $_POST
	 * as well.
	 *
	 * @param string $name    Name of the variable to retrieve
	 * @param string $from    Superglobal to look from
	 * @param mixed  $default What to return if the variable does not exist
	 *
	 * @return mixed Variable if it exists, $default otherwise
	 */
	public function getVal($name, $from = 'GET', $default = false) {
		if($from == 'SERVER') {
			return isset($this->SERVER[$name]) ? $this->SERVER[$name] : $default;
		} elseif($from == 'SESSION') {
			return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
		} elseif($from == 'GET' && isset($this->GET[$name])) {
			return $this->GET[$name];
		} elseif(isset($this->POST[$name])) {
			return $this->POST[$name];
		} else {
			return $default;
		}
	}

	/**
	 * Gets a string from either the $_GET, $_POST, $_SERVER, or $_SESSION superglobal.
	 * If $_GET is specified, the function will automatically search $_POST
	 * as well. Variables will automatically be cast as strings.
	 *
	 * @param string $name    Name of the variable to retrieve
	 * @param mixed  $default What to return if the variable does not exist
	 * @param string $from    Superglobal to look from
	 *
	 * @return string Variable if it exists, $default otherwise
	 */
	public function getText($name, $from = 'GET', $default = '') {
		return (string) $this->getVal($name, $from, $default);
	}

	/**
	 * Gets a integer from either the $_GET, $_POST, $_SERVER, or $_SESSION superglobal.
	 * If $_GET is specified, the function will automatically search $_POST
	 * as well. Variables will automatically be cast as integers.
	 *
	 * @param string $name    Name of the variable to retrieve
	 * @param mixed  $default What to return if the variable does not exist
	 * @param string $from    Superglobal to look from
	 *
	 * @return int Variable if it exists, $default otherwise
	 */
	public function getInt($name, $from = 'GET', $default = 0) {
		return (int) $this->getVal($name, $from, $default);
	}

	/**
	 * Gets a float from either the $_GET, $_POST, $_SERVER, or $_SESSION superglobal.
	 * If $_GET is specified, the function will automatically search $_POST
	 * as well. Variables will automatically be cast as floats.
	 *
	 * @param string $name    Name of the variable to retrieve
	 * @param mixed  $default What to return if the variable does not exist
	 * @param string $from    Superglobal to look from
	 *
	 * @return float Variable if it exists, $default otherwise
	 */
	public function getFloat($name, $from = 'GET', $default = 0.0) {
		return (float) $this->getVal($name, $from, $default);
	}

	/**
	 * Gets a boolean from either the $_GET, $_POST, $_SERVER, or $_SESSION superglobal.
	 * If $_GET is specified, the function will automatically search $_POST
	 * as well. Variables will automatically be cast as true or false.
	 *
	 * @param string $name    Name of the variable to retrieve
	 * @param mixed  $default What to return if the variable does not exist
	 * @param string $from    Superglobal to look from
	 *
	 * @return bool Variable if it exists, $default otherwise
	 */
	public function getBool($name, $from = 'GET', $default = false) {
		return (bool) $this->getVal($name, $from, $default);
	}
}
