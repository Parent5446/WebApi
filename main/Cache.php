<?php

/**
 * Handles local caching of HTML for requests that do not
 * change often.
 *
 * @package API
 * @author Tyler Romeo <tylerromeo@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License 3.0
 * @copyright Copyright (c) 2009, Tyler Romeo (Some Rights Reserved)
 */
class MAIN_Cache
{
	/**
	 * Stores a valid MAIN_Config class.
	 * @private
	 */
	private $config;

	/**
	 * Stores a valid OUT_Session class.
	 * @private
	 */
	private $session;

	/**
	 * Stores the controller and configuration objects.
	 *
	 * @param object &$config     MAIN_Config object
	 * @param object &$controller MAIN_Controller object
	 */
	public function __construct(&$config, &$session) {
		if(!$config instanceof MAIN_Config ||
		   !$controller instanceof OUT_Session) {
			return false;
		}

		$this->config     =& $config;
		$this->session    =& $session;
	}

	/**
	 * Determines whether a given action can be
	 * locally cached.
	 *
	 * @param string $action The action of the request
	 *
	 * @return bool True if it can, false otherwise
	 */
	public function canBeCached($action) {
		$cacheopts  = $this->config->getOption('cache');
		return isset($cacheopts["action.$action"]) && $cacheopts["action.$action"] &&
		          $this->controller->getText('REQUEST_METHOD', 'SERVER') != 'POST';
	}

	/**
	 * Determines whether the cache for this specific
	 * request has expired or not.
	 *
	 * @return True if it has or if it has never been cached
	 */
	public function cacheExpired() {
		$cacheopts = $this->config->getOption('cache');
		$filename  = $this->getCacheFilename();

		if( !file_exists($filename) || 
		    time() - $cacheopts['expires'] > filemtime($filename)) {
			return false;
		} return true;
	}

	/**
	 * Stores the current request in a cache file.
	 * Writes the given HTML to a local filename for
	 * fast access later.
	 *
	 * @param string $output HTML to be cached
	 *
	 * @return bool True on success, false on error
	 */
	public function storeCache($output) {
		$filename  = $this->getCacheFilename();
		$fp        = fopen($filename, 'w');
		if($fp === false) {
			return false;
		} fwrite($output);
		fclose($fp);
		return true;
	}

	/**
	 * Retrieves the cache for the current request, and
	 * returns it with a special footer.
	 *
	 * @return string|bool False on error, the cache otherwise
	 */
	public function getCache() {
		$filename  = $this->getCacheFilename();
		if(!file_exists($filename) || !is_readable($filename)) {
			return false;
		} return file_get_contents($filename) .
		          '\n<!-- Cached ' . date('jS F Y H:i', filemtime($filename)) . ' -->';
	}

	/**
	 * Determine the filename for the local cache
	 * of a specific request. Filename is simply
	 * the full URI including GET variables.
	 *
	 * @return string Valid filename
	 */
	private function getCacheFilename() {
		$paths     = $this->config->getOption('paths');
		$uri       = $this->session->getText('REQUEST_URI', 'SERVER');
		return $paths['cache'] . '/' . $uri;
	}
}
