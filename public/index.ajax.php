<?php
/**
 * index / controller for AJAX (CUD) requests
 */

// constant to globally identify the request type (public, sse, ajax)
define('REQUEST_TYPE','ajax');

// ajax requests always expect json
header('Content-Type: application/json charset=utf-8');

try {

	// error handling
	$errorStatus = '';

	// system general security and configuration
	require('../config/config.inc.php');

	// no error can be displayed (only JSON)
	error_reporting(0);

	/**
	 * array to be converted to JSON and returned as response
	 */
	$aResponse = array(
		'status'	=> 'success', // success || error || warning
		'action'	=> '', // possible livetree button actions ($_POST['action'])
		'msg'		=> '', // main success message to the user
		'errorMsg'	=> '', // main error message
		'aNotify'	=> array() // js notifies = array('input_id' => 'message')
	);

	// mandatory parameter
	$action = $_POST['action'];

	$aResponse['action'] = $action;

	/* * * FORM VALIDATION * * */

	// invalid (required) fields
	$aInvalidField = array();

	switch($action) {

		case 'create':

			// instantiate and validate arguments
			$factoryName	= $_POST['factory_name_create'];
			$itemCount		= $_POST['item_count_create'];
			$lowerBound		= $_POST['lower_bound_create'];
			$upperBound		= $_POST['upper_bound_create'];

			if(! $factoryName)
				$aInvalidField['factory_name_create'] = 'Required';

			// value is not 0 nor '0' and is empty (''): error
			if($itemCount !== 0 && $itemCount !== '0' && empty($itemCount))
				$aInvalidField['item_count_create'] = 'Required';

			if($itemCount < 0)
				$aInvalidField['item_count_create'] = 'Has to be a positive number';

			// value is not 0 nor '0' and is empty (''): error
			if($lowerBound !== 0 && $lowerBound !== '0' && empty($lowerBound))
				$aInvalidField['lower_bound_create'] = 'Required';

			// value is not 0 nor '0' and is empty (''): error
			if($upperBound !== 0 && $upperBound !== '0' && empty($upperBound))
				$aInvalidField['upper_bound_create'] = 'Required';

			break;

		case 'update':

			// instantiate and validate arguments
			$factoryId = (int) $_POST['factory_id_update'];

			if(! $factoryId)
				throw new Exception('factory_id cannot be null on update');

			$factoryName = $_POST['factory_name_update'];

			// validate mandatory fields and consistency
			if(! $factoryName)
				$aInvalidField['factory_name_update'] = 'Required';

			break;

		case 'delete':

			// instantiate and validate arguments
			$factoryId = (int) $_POST['factory_id_delete'];

			if(! $factoryId)
				throw new Exception('factory_id cannot be null on delete');

			break;

		case 'generate':

			// instantiate and validate arguments
			$factoryId = (int) $_POST['factory_id_generate'];

			if(! $factoryId)
				throw new Exception('factory_id cannot be null on generate');

			$itemCount		= $_POST['item_count_generate'];
			$lowerBound		= $_POST['lower_bound_generate'];
			$upperBound		= $_POST['upper_bound_generate'];

			// value is not 0 nor '0' and is empty (''): error
			if($itemCount !== 0 && $itemCount !== '0' && empty($itemCount))
				$aInvalidField['item_count_generate'] = 'Required';

			if($itemCount < 0)
				$aInvalidField['item_count_generate'] = 'Has to be a positive number';

			// value is not 0 nor '0' and is empty (''): error
			if($lowerBound !== 0 && $lowerBound !== '0' && empty($lowerBound))
				$aInvalidField['lower_bound_generate'] = 'Required';

			// value is not 0 nor '0' and is empty (''): error
			if($upperBound !== 0 && $upperBound !== '0' && empty($upperBound))
				$aInvalidField['upper_bound_generate'] = 'Required';

			break;

		default:
			throw new Exception("invalid action: " . $action);

	}

	// functional error
	if(! empty($aInvalidField)) {

		// assign javascript notify commands
		$aResponse['aNotify'] = $aInvalidField;

		// raise friendly message to the user
		throw new \Exception\FunctionalError('Invalid Field(s)');

	}

	// validates -0 inputs
	if($itemCount === '-0' || $lowerBound === '-0' || $upperBound === '-0')
		throw new \Exception\FunctionalError('Please enter a valid number');

	/* * * CRUD actions * * */

	// always starts transaction; it will write the action and the (meta) log
	$dbh->beginTransaction();

	switch($action) {

		case 'create':

			// creates Factory object
			$factory = new Livetree\Factory(
				null,
				$factoryName,
				$lowerBound,
				$upperBound,
				$itemCount
			);

			$factory->save();

			if($itemCount > 0)
				$factory->generateNodes();

			$msg = "Factory {$factoryName} created";

			break;

		// in both cases, it's necessary to instantiate the object
		case 'update':
		case 'generate':

			$factory = \Livetree\Factory::retrieve($factoryId, null, true);

			if(! is_object($factory))
				throw new Exception("could not retrieve Factory with ID {$factoryId}");

			if($action == 'update') {

				if($factoryName == $factory->getName())
					throw new \Exception\FunctionalError("{$factoryName} is already the saved name");

				$factory->setName($factoryName);

			}
			// updates items and bounds
			elseif($action == 'generate') {

				$factory->setItemCount($itemCount);
				$factory->setLowerBound($lowerBound);
				$factory->setUpperBound($upperBound);

				$factory->resetNodes();

				$factory->generateNodes();

			}

			$factory->save();

			$msg = "Factory {$factory->getName()} {$action}d";

			break;

		case 'delete':

			$factory = \Livetree\Factory::retrieve($factoryId, null, true);

			if(! is_object($factory))
				throw new Exception("could not retrieve Factory with ID {$factoryId}");

			$factoryName = $factory->getName();

			// delete Factory's Nodes
			$factory->resetNodes();

			// and register from database
			$factory->delete();

			$msg = "Factory {$factoryName} deleted";

			// to avoid fk violation (event.factory_id -> factory.id)
			$factoryId = null;

			break;

	}

	// does the action requires the tree to be updated?
	$bUpdateTree = \Livetree\Tree::actionRequiresUpdate($action);

	// saves POST array serialized
	$metaLog = serialize($_POST);

	// register user's IP
	$ip = $_SERVER['REMOTE_ADDR'];

	// TODO: setting recently created factory_id is raising a FK exception; fix it
	// factory created: gets id for log
	// if($action == 'create')
	// 	$factoryId = \Database::getLastInsertId();

	// log action
	$objEvent = new \Livetree\Event(null,$action,null,session_id(),$metaLog,$bUpdateTree,$ip,@$factoryId);

	$objEvent->save();

	// commit transaction
	$dbh->commit();

	$aResponse['msg'] = $msg;

	$json = \Common\Lib::var2json($aResponse);

	echo $json;

	// sends message (using ratchet/pawl) to WebSocket server so it sends 'reload' messages to every WebSocket open in client connections
	if($bUpdateTree)
		\Livetree\WsLivetree::broadcastReload();

	exit;

}
// TODO: centralize excetion handling
catch(\Exception\ConfigException $e) {

	$errorStatus = 'error';
	$errorMessage = "Config: ". $e->getMessage();

}
catch(\Exception\DatabaseException $e) {

	$errorStatus = 'error';
	$errorMessage = "DB: ". $e->getMessage() . " -- last query: " . \Database::getLastQuery();

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

	// exception: mask it to the end user
	if($errorStatus == 'error' && ! DEBUG_MODE) {

		$message = 'Internal Error';

	}
	// functional error: shows it to the user
	else {

		$message = $e->getMessage();

	}

	$aResponse['status'] = $errorStatus;
	$aResponse['action'] = @$_POST['action'];
	$aResponse['errorMsg'] = $message;

	echo \Common\Lib::var2json($aResponse);

}
