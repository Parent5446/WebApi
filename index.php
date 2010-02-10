<?php

// Entry point for script.
define("API", true);
define("ID", uniqid("webapi_", true));
define("ROOTDIR", dirname(__FILE__));

// Startup the autoloader
require ROOTDIR . '/app/main/Autoloader.php';
MAIN_Autoloader::getInstance(ROOTDIR);

try {
	// Get the controller and load the configuration.
	$config     = new MAIN_Config();
	$config->updateFromFile(ROOTDIR . '/config.php');

	// Load the custom classes and plugins
	MAIN_Autoloader::loadFile(ROOTDIR . '/custom.php');
	foreach(scandir(ROOTDIR . '/plugins') as $filename) {
		if(substr($filename, -5) == '.conf') {
			$config->insertPlugin(substr($filename, 0, -5),
			                      ROOTDIR . '/plugins/$filename');
			$plugin = ROOTDIR . '/plugins/' . substr($filename, 0, -5) . '.php';
			MAIN_Autoloader::loadFile($plugin);
		}
	}
		
	$controller = new MAIN_Controller();

	// Perform the request.
	$controller->boot($config);
	$controller->initiate();
	$controller->send();
	$controller->cleanup();
	exit(0);
} catch(MAIN_Error $error) {
	$controller->error($error);
	$controller->initiate();
	$controller->send();
	$controller->cleanup();
	exit(1);
}
