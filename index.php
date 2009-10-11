<?php

// Entry point for script.
define("API", true);

// Startup the autoloader
$rootdir = dirname(__FILE__);
require "$rootdir/main/Autoloader.php";
MAIN_Autoloader->getInstance();

// Get the controller and load the configuration.
$controller = new MAIN_Controller();
$controller->loadConfig(dirname(__FILE__) . '/config');

// Perform the request.
$controller->boot();
$controller->initiate();
$controller->send();

// Check for error.
if(!$controller->cleanup()) {
	// ERROR: Create error page and exit.
	$controller->boot();
	$controller->initiate();
	$controller->send();
	$controller->cleanup();
	exit(1);
} else {
	// NO ERROR: Exit.
	exit(0);
}
