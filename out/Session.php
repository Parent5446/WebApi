<?php

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
		if(!empty($oldhash = $this->getText('verifyid', 'SESSION')) && $oldhash != $hash) {
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
		if (!empty($this->getText('HTTP_CLIENT_IP', 'SERVER')))   {
			$ip = $this->getText('HTTP_CLIENT_IP', 'SERVER');
		} elseif(!empty($this->getText('HTTP_X_FORWARDED_FOR', 'SERVER'))) {
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
	public function getText($name, $default = '', $from = 'GET') {
		return (string) $this->getVal($name, $default, $from);
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
	public function getInt($name, $default = 0, $from = 'GET') {
		return (int) $this->getVal($name, $default, $from);
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
	public function getFloat($name, $default = 0.0, $from = 'GET') {
		return (float) $this->getVal($name, $default, $from);
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
	public function getBool($name, $default = false, $from = 'GET') {
		return (bool) $this->getVal($name, $default, $from);
	}
}
