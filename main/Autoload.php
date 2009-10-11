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

class MAIN_Autoload
{
	private static $instance;

	private $rootdir;
	private $loaded;
	private $classlist = array(
			'MAIN_Controller' => '/main/Controller.php',
			'MAIN_Cache'      => '/main/Cache.php',
			'MAIN_Config'     => '/main/Config.php',
			'MAIN_Error'      => '/main/Error.php',
			'MAIN_Logger'     => '/main/Logger.php',

			'DB_Database'     => '/db/Database.php',
			'DB_Object'       => '/db/Object.php',
			'DB_Table'        => '/db/Table.php',

			'OUT_Request'     => '/out/Request.php',
			'OUT_Session'     => '/out/Session.php',
			'OUT_Template'    => '/out/Template.php' );

	private function __construct($rootdir) {
		if(!is_dir($rootdir)) {
			return false;
		} $this->rootdir = $rootdir;
	}

	public function loadClass($classname) {
		if(!array_key_exists($classname, $this->classlist)) {
			return false;
		} elseif(array_key_exists($classname, $loaded)) {
			return true;
		} self::loadFile($this->classlist[$classname]);
		$this->loaded[$classname] = true;
		return true;
	}

	public static function loadFile($filename) {
		if(!file_exists($filename) ||
		   !is_file(    $filename) ||
		   !is_readable($filename) {
			return false;
		} require($filename);
		return true;
	}

	public static function getInstance($rootdir = '') {
		if(self::$instance instanceof self) {
			return self::$instance;
		} return self::$instance = new self($rootdir);
	}
}

function __autoload($classname) {
	MAIN_Autoload::getInstance()->loadClass($classname);
}
