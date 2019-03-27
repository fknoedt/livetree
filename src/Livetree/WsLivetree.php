<?php
/**
 * Main WebSocket class: keeps every client updated
 * This class contains static methods for server sent websockets (with ratchet/pawl) and server supervisor
 */

namespace Livetree;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WsLivetree implements MessageComponentInterface
{

	protected $clients;

	/**
	 * websocket port
	 */
	const PORT = 8080;

	/**
	 * command to run on the production server
	 */
	const SERVER_COMMAND = '/public/websocket.server.php';

	/**
	 * directory where the command is included
	 */
	const SERVER_COMMAND_PATH = '~/livetree.filipe.knoedt.net';

	/**
	 * server process timeout (for shared hostings) in seconds
	 */
	const SERVER_COMMAND_TIMEOUT = 120;

	public function __construct()
	{
		$this->clients = new \SplObjectStorage;
	}

	/**
	 * saves $conn to list upon WebSocket connection open
	 * @param ConnectionInterface $conn
	 */
	public function onOpen(ConnectionInterface $conn)
	{
		// Store the new connection to send messages to later
		$this->clients->attach($conn);

		$log = "New connection! ({$conn->resourceId})\n";

		// dumps through the server running process
		echo $log;

		$this->onMessage($conn, $log);

	}

	/**
	 * sends message to every connection
	 * @param ConnectionInterface $from
	 * @param string $msg
	 */
	public function onMessage(ConnectionInterface $from, $msg)
	{
		$numRecv = count($this->clients) - 1;

		echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n", $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

		foreach ($this->clients as $client) {
			if ($from !== $client) {
				// The sender is not the receiver, send to each client connected
				$client->send($msg);
			}
		}
	}

	/**
	 * removes $conn from the list upon disconnection
	 * @param ConnectionInterface $conn
	 */
	public function onClose(ConnectionInterface $conn)
	{
		// The connection is closed, remove it, as we can no longer send it messages
		$this->clients->detach($conn);

		$log = "Connection {$conn->resourceId} has disconnected\n";

		// dumps through the server running process
		echo $log;

		$this->onMessage($conn, $log);
	}

	/**
	 * upon error: just closes the connection
	 * @param ConnectionInterface $conn
	 * @param \Exception $e
	 */
	public function onError(ConnectionInterface $conn, \Exception $e)
	{
		$log = "An error has occurred: {$e->getMessage()}\n";

		// dumps through the server running process
		echo $log;

		$this->onMessage($conn, $log);

		$conn->close();
	}

	/**
	 * sends message (using ratchet/pawl) to WebSocket server so it sends 'reload' messages to every WebSocket open in client connections
	 * this method is static while the connections are stored on the running (loop) server
	 */
	public static function broadcastReload($msg = '')
	{

		\Ratchet\Client\connect('ws://localhost:' . self::PORT)->then(function ($conn) {

			$reply = 'reload';

			if (!empty($msg))
				$reply .= ": {$msg}";

			$conn->send($reply);

			$conn->close();

		});

	}

	/**
	 * ensures that the websocket's server is running
	 */
	public static function serverSupervise()
	{

		// command line
		$command = self::SERVER_COMMAND;

		// gets every running process
		$ps = shell_exec("ps aux");

		// process is not running
		if (strpos($ps, $command) === false) {

			// starts process again
			self::startServerProcess();

		}

	}

	/**
	 * calls php websocket server script via shell
	 */
	public static function startServerProcess()
	{

		shell_exec('php ' . self::SERVER_COMMAND_PATH . '/public/websocket.server.php >websocket-server-access.log 2>websocket-server-error.log &');

	}

}