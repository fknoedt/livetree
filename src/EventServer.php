<?php
/**
 * $descricaoAqui
 * @author filipe
 * @date 16/10/2017
 * @rotina $rotinaAqui
 */

class EventServer {

	/**
	 * closes connection after X seconds
	 */
	const TIMEOUT = 60;

	/**
	 * lookup for messages every
	 */
	const DEFAULT_LOOP_DELAY = 3;

	/**
	 * saves session_id because it will be terminated (to avoid hanging)
	 * @var
	 */
	var $sessionId;

	/**
	 * time the EventServer has started
	 * @var timestamp
	 */
	var $initTime;

	/**
	 * sleep between iterations
	 * @var
	 */
	var $loopDelay;

	/**
	 * event ID (updated upon each response message)
	 * @var
	 */
	private $id;

	/**
	 * client side EventListener possibilities (has to match javascript)
	 * @var array
	 */
	static $aKnownEvent = array('error','message','update_tree','server time', 'reload');

	/**
	 * @return mixed
	 */
	public function getId()	{

		return $this->id;

	}

	/**
	 * updates event id with current timestamp
	 */
	public function updateId() {

		$this->id = time();

	}

	/**
	 * @return timestamp
	 */
	public function getInitTime() {
		return $this->initTime;
	}

	/**
	 * @param timestamp $initTime
	 */
	public function setInitTime($initTime) {
		$this->initTime = $initTime;
	}

	/**
	 * @return mixed
	 */
	public function getLoopDelay() {
		return $this->loopDelay;
	}

	/**
	 * @param mixed $loopDelay
	 */
	public function setLoopDelay($loopDelay) {
		$this->loopDelay = $loopDelay;
	}

	/**
	 * @return mixed
	 */
	public function getSessionId() {
		return $this->sessionId;
	}

	/**
	 * @param mixed $sessionId
	 */
	public function setSessionId($sessionId) {
		$this->sessionId = $sessionId;
	}

	/**
	 * EventServer constructor
	 */
	function __construct() {

		// connection will be closed after self::TIMEOUT seconds
		$this->setInitTime(time());

		$this->setLoopDelay(self::DEFAULT_LOOP_DELAY);

		$this->setSessionId(session_id());

	}

	/**
	 * formats and send SSE data to the client
	 * limited to one JSON response [and one event type] per response
	 * @param string $msg -- Line of text that should be transmitted.
	 * @param string $event -- event type (requires specific source.addEventListener on client side; see self::$aKnownEvents)
	 * @throws Exception
	 */
	function sendMessage($msg, $event=null) {

		if($event) {

			// defines event for the listener
			echo "event: {$event}" . PHP_EOL;

		}

		// always sends the current timestamp (each response has a different ID)
		$this->updateId();
		echo "id: " . $this->getId() . PHP_EOL;

		// main json data

		// if it's an array, converts into string
		// array format hase to
		if(is_array($msg)) {

			$msg = json_encode($msg);

		}

		echo "data: $msg" . PHP_EOL;
		echo PHP_EOL;
		ob_flush();
		flush();

	}

	/**
	 * start monitoring for updates with $lookup function or class::method
	 * @param string $lookup
	 * @throws Exception
	 */
	function init($lookup) {

		$dbh = \Database::getConnection();

		// used in each lookup iteration
		// $dtLastLookup = date('Y-m-d H:i:s');

		$dtLastLookup = $dbh->getDatetime();

		// cannot use session (or it will block every other request)
		session_destroy();

		// infinite loop to keep the connection with the browser's request open
		while(1) {

			// reached timeout: closes connection (browser opens a new one)
			if(time() > $this->getInitTime() + self::TIMEOUT)
				exit;

			// callback function for lookup if a new message has to be sent to the client
			$resp = $lookup($dtLastLookup,$this);

			// updates last lookup time
			// TODO: demand app and server times to be synched so it's not necessary to query the database
			// $dtLastLookup = date('Y-m-d H:i:s');

			$dtLastLookup = $dbh->getDatetime();

			if(! empty($resp)) {

				if(is_array($resp)) {

					$message = $resp['data'];
					$event = $resp['event'];

				}
				else {

					$message = $resp;
					$event = null;

				}

				// dumps message
				$this->sendMessage($message,$event);

			}
			// sends ping feedback
			else {

				$this->sendMessage('nothing happened','ping');

			}

			// SSE: connection is not closed; it hangs in the loop until termination
			sleep($this->getLoopDelay());

		}

	}

}
