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
 * Stores error information when an error occurs in a Database object. Automatically
 * logs the error using a MAIN_Logger object.
 *
 * @package API
 * @author Tyler Romeo <tylerromeo@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License 3.0
 * @copyright Copyright (c) 2009, Tyler Romeo (Some Rights Reserved)
 */
class MAIN_Error
{
	/**
	 * Signal for extremely dangerous errors, usually
	 * resulting in termination of the script.
	 */
	const ERROR    = 256;

	/**
	 * Signal for critical errors, creating unwanted results, though
	 * not necessarily requiring the script to be terminated.
	 */
	const WARNING  = 512;

	/**
	 * Signal for standard errors that may have an effect on the outcome
	 * of the script in certain situations.
	 */
	const NOTICE   = 1024;

	/**
	 * Level of the error.
	 * @private
	 */
	private $level;

	/**
	 * Callback for the error.
	 * @private
	 */
	private $callback;

	/**
	 * Error message.
	 * @private
	 */
	private $message;

	/**
	 * Stores error information and logs the error.
	 *
	 * @param const   $level    One of the classes local constants defining error severity
	 * @param string  $callback Name of the function creating the error
	 * @param string  $message  Message describing the error
	 * @param object &$log      Valid MAIN_Logger instance
	 */
	public function __construct($level, $callback, $message, &$log) {
		if(!$log instanceof MAIN_Logger) {
			return false;
		}

		$this->level    =  $level;
		$this->callback =  $callback;
		$this->message  =  $message;
		$this->log      =& $log;

		switch($level) {
			case self::ERROR:
			case self::WARNING:
			case self::NOTICE:
				break;
			default:
				return false;
		}

		$log->log($level, $callback, $message);
	}

	/**
	 * Get the level severity.
	 *
	 * @return int Code referring to the level severity
	 */
	public function getLevel() {
		return $this->code;
	}

	/**
	 * Get the callback for the error.
	 *
	 * @return string Callback for the error
	 */
	public function getCallback() {
		return $this->callback;
	}

	/**
	 * Get the error message.
	 *
	 * @return string Error message
	 */
	public function getMessage() {
		return $this->message;
	}
}
