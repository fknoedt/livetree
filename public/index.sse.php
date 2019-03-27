<?php
/**
 * index for SSE requests
 * @see https://filipe.knoedt.net/livetree
 * @author fknoedt
 */

// constant to globally identify the request type (public, sse, ajax)
define('REQUEST_TYPE','sse');

// static header for SSE requests
header('Content-Type: text/event-stream charset=utf-8');
// prevents caching
header('Cache-Control: no-cache');
// indicates persistent connection
header('Connection: keep-alive');

try {

	/**
	 * function called recurrently by $objEventServer to check if the jstree needs to be reloaded due to other users changes
	 * @param $lastTs
	 * @param $objSSE
	 * @return bool
	 */
	function jstreeLookup($lastTs,$objSSE) {

		return \Livetree\Tree::lookupChanges($lastTs,$objSSE);

	}

	// let the script decide when to finish
	set_time_limit(-1);

	// system security and configuration
	require('../config/config.inc.php');

	// initiate event monitoring
	$objEventServer = new EventServer();

	$objEventServer->init('jstreeLookup');

}
catch(\Exception\ConfigException $e) {

	die("ConfigException: " . $e->getMessage());


}
catch(\Exception\DatabaseException $e) {

	die("DatabaseException: " . $e->getMessage());


}
catch(Exception $e) {

	die("Exception: " . $e->getMessage());

}
