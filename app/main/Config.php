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
	 * Helper function to insert plugins. Inserts the options of the given
	 * plugin configuration filename under the plugin_$name key in the
	 * global configuration.
	 *
	 * @param string $name     Base name for the plugin
	 * @param string $filename Full filename for the config file
	 *
	 * @return bool True on success, false on failure
	 */
	public function insertPlugin($name, $filename) {
		return $this->updateFromFile($filename, "plugin_$name");
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
	public function updateFromFile($filename, $name = '') {
		// Check the filename and return if already exists AND
		// is not a normal file.
		if(file_exists($filename) && !is_file($filename)) {
			return new MAIN_Error(MAIN_Error::ERROR, 'MAIN_Config::updateFromFile', "$filename does not exist.");
		} $options = parse_ini_file($filename, true);

		// Check if the file failed to open,
		// then check if it is writable.
		if($options === false) {
			return new MAIN_Error(MAIN_Error::ERROR, 'MAIN_Config::updateFromFile', "$filename failed to open.");
		} elseif($name == '') {
			$this->options = $options;
		} else {
			$this->options[$name] = $options;
		} return true;
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
			return new MAIN_Error(MAIN_Error::ERROR, 'MAIN_Config::updateToFile', "$filename does not exist.");
		} $file = fopen($filename, 'w');

		// Check if the file failed to open,
		// then check if it is writable.
		if($file === false) {
			return new MAIN_Error(MAIN_Error::ERROR, 'MAIN_Config::updateToFile', "$filename failed to open.");
		} elseif(!is_writable($filename)) {
			fclose($file);
			return new MAIN_Error(MAIN_Error::ERROR, 'MAIN_Config::updateToFile', "$filename is not writeable.");
		}

		$lines = array();
		foreach($this->options as $key => $value) {
			if(strpos($key, 'plugin_')) {
				continue;
			} elseif(is_array($value)) {
				$lines[] = "\n[$key]";
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

