<?php
/**
 * index / controller for jstree requests
 */

// constant to globally identify the request type (public, sse, ajax, json)
define('REQUEST_TYPE','jstree');

// ajax requests always expect json
header('Content-Type: application/json charset=utf-8');

if(isset($_GET['download']) && $_GET['download'] == 1)
	header('Content-Disposition: attachment; filename="jstree.json"');;

try {

	// system general security and configuration
	require('../config/config.inc.php');

	// no error can be displayed (only JSON)
	error_reporting(0);

	if(! isset($_GET['node'])) {

		// root node
		$node = 'livetree';

	}
	else {

		$node = $_GET['node'];

	}

	// node requested
	switch($node) {

		case 'livetree':

			$objTree = new \Livetree\Tree();

			$aTree = $objTree->getJstreeJson();

			// format as json object
			$json = \Common\Lib::var2json($aTree);

			echo $json;

			break;

		default:

			throw new Exception("node {$node} not implemented");

	}

}
catch(Exception $e) {

	// error array to be converted to json format
	$aResponse = array(

		'errors' => array(
			'error' => array(
				'code' => $e->getCode(),
				'message' => $e->getMessage(),
			)
		)

	);

	// format as json object
	$json = \Common\Lib::var2json($aResponse);

	echo $json;

	exit;

}


