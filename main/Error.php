<?php

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
