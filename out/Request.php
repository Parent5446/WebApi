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
 * Takes configuration options from a controller for a web request, then
 * passes the options to a template, printing HTML to the client if applicable.
 *
 * @package API
 * @author Tyler Romeo <tylerromeo@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License 3.0
 * @copyright Copyright (c) 2009, Tyler Romeo (Some Rights Reserved)
 */
class OUT_Request
{
	/**
	 * A valid MAIN_Config object.
	 * @private
	 */
	private $config;

	/**
	 * A valid DB_Database object.
	 * @private
	 */
	private $db;

	/**
	 * Store the configuration and database objects.
	 *
	 * @param object &$config MAIN_Config object
	 */
	public function __construct(&$config, &$db) {
		if(!$config  instanceof MAIN_Config ||
		   !$db      instanceof DB_Database ||
		   !$session instanceof OUT_Session    ) {
			return false;
		}
		$this->config  =& $config;
		$this->db      =& $db;
	}

	/**
	 * Definition for a function to initialize the request, get database
	 * information, and load it into the template.
	 */
	public function initiate(&$session) { }

	/**
	 * Print the resulting HTML to the screen.
	 */
	public function send() {
		$html = $this->template->execute();
		echo $html;
	}

	/**
	 * Get an option that has been passed to the template.
	 *
	 * @param string $option Name of the option
	 *
	 * @return mixed Option value, false if non-existant or if template is missing
	 */
	public function getOption($option) {
		return $this->template instanceof OUT_Template ?
		            $this->template->getOption($option) :
		            false;
	}

	/**
	 * Stores an option in the template for the request.
	 *
	 * @param string $option Name of the option
	 * @param string $value  Value of the option
	 *
	 * @return bool True on success, false if template is missing
	 */
	public function putOption($option, $value) {
		return $this->template instanceof OUT_Template ?
		            $this->template->putOption($option, $value) :
		            false;
	}

	/**
	 * Gets the template for the request, using the name of the request
	 * subclass as the template name, and stored configuration options
	 * to get the directory (filename for template should be "Name.template").
	 *
	 * @return object Template for the request
	 */
	protected function getTemplate() {
		$paths        = $this->config->getOption('paths');
		$dirname      = $paths['templates'];
		$templatename = strtolower(get_class($this));
		$template     = new OUT_Template("$dirname/$templatename.template");

		if($template === false) {
			return false;
		} else {
			$this->template = $template;
		} return true;
	}

	/**
	 * Helper function to store uploaded files. Uses configuration object
	 * to find uploads directory.
	 *
	 * @param string $source Name given to the file input tag in the HTML
	 * @param string $name   Filename to give to the uploaded file
	 *
	 * @return bool True on success, false on failure
	 */
	private function storeUploadedFile($source, $name) {
		if(!is_uploaded_file($_FILES[$source]['tmp_name'])) {
			return false;
		}

		$paths       = $this->config->getOption('paths');
		$destination = $paths['uploads'] . '/' . addslashes($name);
		if (move_uploaded_file($_FILES[$source]['tmp_name'], $destination)) {
			return true;
		} return false;
	}
}

