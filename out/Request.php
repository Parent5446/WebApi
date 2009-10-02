<?php

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
	public function __construct(&$config, &$db, &$session) {
		if(!$config  instanceof MAIN_Config ||
		   !$db      instanceof DB_Database ||
		   !$session instanceof OUT_Session    ) {
			return false;
		}
		$this->config  =& $config;
		$this->db      =& $db;
		$this->session =& $session;
	}

	/**
	 * Definition for a function to initialize the request, get database
	 * information, and load it into the template.
	 */
	public function initiate(&$controller) { }

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

