<?php

/**
 * Stores configuration option, usually taken from a file.
 *
 * @package API
 * @author Tyler Romeo <tylerromeo@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License 3.0
 * @copyright Copyright (c) 2009, Tyler Romeo (Some Rights Reserved)
 */
class MAIN_Config
{
	/**
	 * Configuration options
	 * @private
	 */
	private $options = array();

	/**
	 * Get a configuration option.
	 *
	 * @param string $option Name of the option
	 *
	 * @return mixed Option value, false if non-existent
	 */
	public function getOption($option) {
		return isset($this->options[$option]) ? $this->options[$option] : false;
	}

	/**
	 * Stores a configuration option.
	 *
	 * @param string $option Name of the option
	 * @param string $value  Value of the option
	 *
	 * @return bool True on success, false otherwise
	 */
	public function putOption($option, $value) {
		if(!is_string($option) || !is_string($value)) {
			return false;
		} $this->options[$option] = $value;
		return true;
	}

	/**
	 * Load configuration options from a file, where each option has its
	 * own new line, and the option name is separated from the value with
	 * an '='.
	 *
	 * @param string $filename Path to the configuration file
	 *
	 * @return bool True on success, false on failure
	 */
	public function updateFromFile($filename) {
		// Check the filename and return if already exists AND
		// is not a normal file.
		if(file_exists($filename) && !is_file($filename)) {
			return false;
		} $options = parse_ini_file($filename, true);

		// Check if the file failed to open,
		// then check if it is writable.
		if($options === false) {
			return false;
		} else {
			$this->options = $options;
			return true;
		}
	}

	/**
	 * Update a configuration file with the local configuration options.
	 *
	 * @param string $filename Path to the configuration file
	 *
	 * @return bool True on success, false on failure
	 */
	public function updateToFile($filename) {
		// Check the filename and return if already exists AND
		// is not a normal file.
		$filename = realpath($filename);
		if(file_exists($filename) && !is_file($filename)) {
			return false;
		} $file = fopen($filename, 'w');

		// Check if the file failed to open,
		// then check if it is writable.
		if($file === false) {
			return false;
		} elseif(!is_writable($filename)) {
			fclose($file);
			return false;
		}

		$lines = array();
		foreach($this->options as $key => $value) {
			if(is_array($value)) {
				$lines[] = "[$key]";
				foreach($value as $curkey => $curval) {
					$lines[] = "$curkey = $curval";
				}
			} else {
				$lines[] = "$key = $value";
			}
		}

		// Append the options to the file and close.
		$lines  = implode("\n", $lines);
		$retval = fwrite($file, $lines);
		fclose($file);

		return $retval;
	}
}

