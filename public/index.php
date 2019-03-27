<?php
/**
 * index / controller for main (page) browser requests
 * @see https://github.com/fknoedt/livetree
 */

// constant to globally identify the request type (public, sse, ajax)
define('REQUEST_TYPE','www');

// default architecture: sse or websockets
define('DEFAULT_ARCH','sse');

try {

	// system security and configuration
	require('../config/config.inc.php');

	if(isset($_GET['arch']))
		$arch = $_GET['arch'];
	else
		$arch = DEFAULT_ARCH;

	// creates new template object
	$objTpl = new \Common\Template($_SERVER['DOCUMENT_ROOT'] . '/../view/tpl/');

	// SSE or WebSockets
	$objTpl->addVar('arch',$arch);
	// WebSocket protocol
	$objTpl->addVar('wsProtocol',ENVIRONMENT == 'prod' ? 'wss' : 'ws');
	// WebSocket host
	$objTpl->addVar('wsHost',$_SERVER['HTTP_HOST']);
	// WebSocket port
	$objTpl->addVar('wsPort',\Livetree\WsLivetree::PORT);

	// parses template and converts variables
	$objTpl->display('index.tpl.php');

	// TODO: call this function through AJAX only once
	// \Common\Lib::notifyVisit();


}
// TODO: personalize exception handlings
catch(\Exception\ConfigException $e) {

	$errorStatus = 'error';
	$errorMessage = "Config: ". $e->getMessage();

}
catch(\Exception\DatabaseException $e) {

	$errorStatus = 'error';
	$errorMessage = "DB: ". $e->getMessage();

	if(\Database::$singleton)
		$errorMessage .= " -- last query: " . \Database::getLastQuery();

}
catch(\Exception\FunctionalError $e) {

	$errorStatus = 'warning';
	$errorMessage = $e->getMessage();

}
catch(Exception $e) {

	$errorStatus = 'error';
	$errorMessage = $e->getMessage();

}

// some exception occurred
if(! empty($errorStatus)) {

	// if the error happened before the response array initiation
	if(! isset($aResponse))
		$aResponse = array(
			'status'	=> '',
			'action'	=> '',
			'errorMsg'	=> ''
		);

	// exception: shows label
	if($errorStatus == 'error')
		$message = 'Internal Error =X' . (defined('DEBUG_MODE') && DEBUG_MODE ? " [{$e->getMessage()}]" : '');
	// functional error: shows it to the user
	else
		$message = $e->getMessage();

	$aResponse['status'] = $errorStatus;
	$aResponse['action'] = @$_POST['action'];
	$aResponse['errorMsg'] = $message;

	// TODO: template for displaying error
	echo $message;

}
