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
 * Main class for handling web requests, and redirecting jobs
 * to the database and request classes.
 *
 * @package API
 * @author Tyler Romeo <tylerromeo@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License 3.0
 * @copyright Copyright (c) 2009, Tyler Romeo (Some Rights Reserved)
 */
class MAIN_Controller
{
	/**
	 * Stores the $_POST variable.
	 * @private
	 */
	private $POST;

	/**
	 * Stores the $_GET variable.
	 * @private
	 */
	private $GET;

	/**
	 * Stores the $_SERVER variable.
	 * @private
	 */
	private $SERVER;

	/**
	 * The given action for this request.
	 * @private
	 */
	private $action;

	/**
	 * The cached request if available.
	 * @private
	 */
	 private $cached = '';

	/**
	 * The MAIN_Config object for this request
	 * @private
	 */
	private $config;

	/**
	 * A subclass of OUT_Request.
	 * @private
	 */
	private $request;

	/**
	 * The DB_Database object for this request
	 * @private
	 */
	private $database;

	/**
	 * The MAIN_Logger object for this request
	 * @private
	 */
	private $logger;

	/**
	 * The MAIN_Cache object for this request
	 * @private
	 */
	private $cache;

	/**
	 * The OUT_Session object for this request
	 * @private
	 */
	private $session;

	/**
	 * The MAIN_Error object for this request should
	 * an error be encountered during the script.
	 * @private
	 */
	private $error;

	/**
	 * Stores local copies of the POST, GET, and SERVER superglobals.
	 */
	public function __construct() {
		$this->POST   = $_POST;
		$this->GET    = $_GET;
		$this->SERVER = $_SERVER;
	}

	/**
	 * Starts basic bootstrapping procedures before the request
	 * can be handled. Starts up the Logger and Database, and creates
	 * a Request object to handle the request.
	 *
	 * @return bool True on success, false on critical error
	 */
	public function boot(&$config) {
		$this->config =& $config;
		$this->logger = new MAIN_Logger();

		// Startup database
		$dbopts   = $this->config->getOption('database');
		$server   = $dbopts['server'  ];
		$username = $dbopts['username'];
		$password = $dbopts['password'];
		$database = $dbopts['database'];
		$this->database = new DB_Database($server, $username, $password, $database, $this->logger);

		// Startup cache and session.
		$this->session = new OUT_Session();
		$this->session->startSession();
		$this->cache   = new MAIN_Cache($this->config, $this->session);

		// Load models.
		$models = $config->getOption('models');
		foreach($models as $name => $value) {
			$options = $value[0];

			if(is_array($options)) {
				$protect = in_array("protect", $options);
				$parent  = in_array("parent",  $options);
			} else {
				$protect = $parent = false;
			} $this->newDatabaseObject($name, $protect, $parent);
		} return true;
	}

	/**
	 * Calls the initialize function for the request class.
	 *
	 * This function should start setting up the request, retrieving
	 * information from the database, getting templates from their
	 * respective files, etc.
	 *
	 * @return mixed Return value of OUT_Request::initiate
	 */
	public function initiate() {
		// Get action.
		$actions = $this->config->getOption('actions');
		$action  = $this->session->getText('action');
		if(empty($action) && isset($actions['default'])) {
			$action = $actions['default'];
		}

		// Try getting from the cache first.
		if($this->cache->canBeCached($action) && !$this->cache->cacheExpired()) {
			$this->cached = $this->cache->getCache();
			return true;
		} else {
			// Get classname for action and make new request.
			if(isset($actions[$action])) {
				$classname = $actions[$action];
			} elseif(isset($actions['error'])) {
				$classname = $actions['error'];
			} else {
				return false;
			} $this->request = new $classname($this->config, $this->database, $this->session);

			// Initiate request.
			return $this->request->initiate($this->session);
		}
	}

	/**
	 * Triggers the send function for the request. HTML will be loaded
	 * into the output buffer, but not sent until cleanup, in case of error.
	 */
	public function send() {
		if(!isset($this->request)) {
			return false;
		}

		$cacheable = $this->cache->canBeCached($this->action);
		$iscached  = empty($this->cached) || !$cacheable;
		$cache     = $this->cached;

		@ob_end_flush();
		ob_start();
		if(!$cacheable) {
			$this->request->send();
		} elseif(!$iscached) {
			$this->request->send();
			$this->cache->storeCache(ob_get_contents());
		} else {
			echo $this->cached;
		}
	}

	/**
	 * Shuts down the database, logger, and configuration. If no error occurs,
	 * sends the output buffer to the client.
	 *
	 * @return bool True on success, false on error
	 */
	public function cleanup() {
		$debug = $this->config->getOption('debug');
		if(($res = $this->database->close()) instanceof MAIN_Error) {
			if($debug) {
				@ob_end_flush();
			} else {
				@ob_end_clean();
			}
			$this->action = 'error';
			$this->error  = $res;
			return false;
		}

		$paths = $this->config->getOption('paths');
		$logpath = isset($paths['log']) ? $paths['log'] : ROOTDIR . '/log';
		if(($res = $this->logger->export($logpath)) instanceof MAIN_Error) {
			var_dump($res);
			if($debug) {
				@ob_end_flush();
			} else {
				@ob_end_clean();
			}
			$this->action = 'error';
			$this->error  = $res;
			return false;
		}

		if(($res = $this->config->updateToFile($this->configfile)) instanceof MAIN_Error) {
			if($debug) {
				@ob_end_flush();
			} else {
				@ob_end_clean();
			}
			$this->action = 'error';
			$this->error  = $res;
			return false;
		}

		@ob_end_flush();
		return true;
	}

	/**
	 * Get the error that occurred during the request.
	 *
	 * @return object|bool MAIN_Error object if there was an error, false otherwise
	 */
	public function getError() {
		return isset($this->error) ? $this->error : false;
	}

	/**
	 * Create a new database class definition with the given options.
	 *
	 * @param string $name    Name of the class to create
	 * @param bool   $protect Whether to allow password protection
	 * @param bool   $parent  Whether to allow child objects
	 */
	private function newDatabaseObject($name, $protect = false, $parent = false) {
		$protect = (int) $protect;
		$parent  = (int) $parent;
		$exec = "class $name extends DB_Object {\n" .
		        "\tprotected static \$parent = $parent;\n" .
		        "\tprotected static \$enableprotect = $protect;\n}";
		eval($exec);
	}
}
