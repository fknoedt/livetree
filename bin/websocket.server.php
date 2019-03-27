<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Livetree\WsLivetree;

define('REQUEST_TYPE','websocket-server');

echo 'starting web socket server...';

try {

	// defines DOCUMENT_ROOT for CLI calls
	$_SERVER['DOCUMENT_ROOT'] =  dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'public';

	// system security and configuration
	require($_SERVER['DOCUMENT_ROOT'] . '/../config/config.inc.php');

	$server = IoServer::factory(
		new HttpServer(
			new WsServer(
				new WsLivetree()
			)
		),
		8080
	);

	$server->run();


}
catch(Exception $e) {

	// TODO: handle error
	die(get_class($e) . ": {$e->getMessage()}");

}

