<?php
/**
 * livetree by filipe knoedt
 * https://github.com/fknoedt/livetree
 *
 * No rights reserved. Anyone can use this project or parts of it's code.
 * This project is made available under the Creative Commons CC0 1.0 Universal.
 *
 * main configuration file included in every index: error handling, database, security and debug
 *
 */

ini_set("default_charset", 'utf-8');

// for error reporting
define('SYSADMIN_EMAIL', 'fkwebdev@gmail.com');

// Siteground's IP
define('PRODUCTION_SERVER_IP','146.66.69.32');

// views template dir
define('VIEWS_DIR', $_SERVER['DOCUMENT_ROOT'] . '/../view/tpl/');

// libs and models dir (autoload runs on it)
define('SRC_DIR', $_SERVER['DOCUMENT_ROOT'] . '/../src/');

// prod or dev environment
if($_SERVER['SERVER_ADDR'] == PRODUCTION_SERVER_IP) {
	$environment = 'prod';
	$debugMode = false;
	$errorReporting = 0;
}
else {
	$environment = 'dev';
	$debugMode = true;
	$errorReporting = E_ALL; // E_ERROR | E_RECOVERABLE_ERROR | E_PARSE | E_COMPILE_ERROR
}

define('ENVIRONMENT',$environment);

// verbosity on/off
define('DEBUG_MODE',$debugMode);

// don't dump errors when in production ; otherwise dump them all
error_reporting($errorReporting);

// SSL protocol
$sslHost = (bool) ! (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off");

define('SSL_HOST', $sslHost);

// force SSL when in production
if(ENVIRONMENT == 'prod' && ! SSL_HOST) {
	$redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ' . $redirect);
	exit();
}

// uses session to control who changed the tree
if(REQUEST_TYPE != 'websocket-server')
	session_start();

/* * * ERROR HANDLING AND AUTOLOAD * * */

// autoload: application will always load not-yet-loaded classes by requiring a file on /src/ [with namespace] with the given class name
spl_autoload_register(function ($className) {

	$file = SRC_DIR . str_replace("\\", '/', $className) . '.php';

	if(! is_file($file)) {
		throw new \Exception("Autoload: file not found");
	}
	require($file);

});

// defines central error handling method (which also catches fatal errors)
register_shutdown_function(array('ErrorHandler','php_fatal_error'));

// set debug mode for fatal errors
ErrorHandler::$appDebug = DEBUG_MODE;

/* * * CONFIGURATION FILE * * */

// parses config text file -- it was preferred to YAML approach due to simplicity
$configFile = $_SERVER['DOCUMENT_ROOT'] . '/../config/config.ini';

// config file missing
if(! is_file($configFile)) {
	throw new \Exception\ConfigException("config file ({$configFile}) not found on " . getcwd() . " (line " . __LINE__ . ")");
}

// retrieve local configuration
$aConfig = parse_ini_file($configFile);

if(! is_array($aConfig) || empty($aConfig)) {
	throw new \Exception\ConfigException("invalid config file contents");
}

/* * * SECURITY * * */

// issues addressed: https://php.earth/doc/security/intro

// every request has to define the request type (public, ajax, sse) on it's index
if(! defined('REQUEST_TYPE')) {
	throw new \Exception\ConfigException('bad request: use appropriate index');
}

// starts database connection
$dbh = new \Database(
	$aConfig['db_host'],
	$aConfig['db_name'],
	$aConfig['db_user'],
	$aConfig['db_pass'],
	$aConfig['db_port'])
;

// unsets the config.ini array for security purposes
unset($aConfig);

// composer autoload
require($_SERVER['DOCUMENT_ROOT'] . '/../src/composer/vendor/autoload.php');

// ensures that the websocket process is running
if(ENVIRONMENT == 'prod')
	\Livetree\WsLivetree::serverSupervise();

// returns to the index with all set
