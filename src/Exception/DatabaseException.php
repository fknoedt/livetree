<?php

namespace Exception;

/**
 * DB queries
 * @author filipe
 */
class DatabaseException extends \Exception{

	/**
	 * DatabaseException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Exception $previous
	 */
	function __construct($message, $code=null) {

		parent::__construct($message, $code);

	}


}