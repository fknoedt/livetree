<?php

/**
 * error handling and output
 * set through register_shutdown_function()
 */
class ErrorHandler
{
	/**
	 * debug mode
	 * @var bool
	 */
	static $appDebug = false;

	/**
	 * central technical error handler
	 * @param $error
	 */
	public static function php_fatal_error($error=null)
	{
		self::error_output($error);
	}

	/**
	 * output error accordingly to the request
	 * WARNING: this method ends execution if it gets an Exception or error_
	 * @param $e
	 */
	public static function error_output($e)
	{
		$error = error_get_last();

		// no fatal error has occured
		if ($error['type'] !== E_ERROR) {
			return;
		}

		// this function will be always called (if not by a fatal error, at the end of the script)
		if(is_null($e)) {

			$e = $error;

			if($e) {
				$msg = $e['message'];
				$code = $e['type'];

				if(self::$appDebug) {
					$file = $e['file'];
					$line = $e['line'];
					$trace = print_r($e, true);
				}
			}
			// no error happened
			else {
				return;
			}
		}
		else {
			$msg = $e->getMessage();
			$code = $e->getCode();
			if(self::$appDebug) {
				$file = $e->getFile();;
				$line = $e->getLine();
				$trace = $e->getTraceAsString();
			}
		}

		// responds depending on the request
		switch(REQUEST_TYPE) {

			// jQuery.ajax (expects JSON)
			case 'ajax':

				echo '{"status":"error", "message":"' . $msg . '"}';

				break;

			// api errors
			case 'api':

				// error array to be converted to json format
				$response = array(

					'error' => array(
						'code' => $code,
						'message' => $msg,
						'file' => $file,
						'line' => $line,
						'trace' => $trace,
					)

				);

				// always
				header('Content-Type: application/json charset=utf-8');

				// http status code
				header("HTTP/1.1 500 Internal Error", true, 500);

				// format as json object
				$json = json_encode($response);

				echo $json;
				break;

			case 'public':
			default:

				echo $msg;

				break;

		}

		// WARNING:
		exit;
	}
}