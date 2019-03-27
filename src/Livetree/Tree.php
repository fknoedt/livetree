<?php
/**
 * methods for the Livetree
 */

namespace Livetree;

use Hhxsv5\SSE\Event;

class Tree {

	/**
	 * returns JSON for the tree in jstree format
	 * @throws \Exception
	 * @throws \Exception\DatabaseException
	 */
	function getJstreeJson() {

		// main array with factory -> nodes
		$aTree = array();

		// list of leaves on jstree format indexed by factory id
		$aLeaf = array();

		// Database Connection
		$dbh = \Database::getConnection();

		// retrieves every register from 'leaf' table as objects
		$aObjLeaf = $dbh->retrieveAll('\Livetree\Leaf', array(), true);

		foreach($aObjLeaf as $objLeaf) {

			$node = array(
				'text' => $objLeaf->getValue(),
				'icon' => 'jstree-file'
			);

			// groups every node from the same factory together
			$aLeaf
				[$objLeaf->getFactoryId()]
					[] = $node;

		}

		// rertrieves every register from 'factory' table as objects
		$aObjFactory = $dbh->retrieveAll('\Livetree\Factory', array(), true);

		foreach($aObjFactory as $objFactory) {

			if($objFactory->getItemCount() > 0)
				$span = " <span class='roundSpanItem' title='Nodes on this Factory'>{$objFactory->getItemCount()}</span> <span class='roundSpanBounds' title='Nodes Bounds (min : max)'>{$objFactory->getLowerBound()}:{$objFactory->getUpperBound()}</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			else
				$span = "";

			$a = array(
				"id" => $objFactory->getId(),
				"text" => $objFactory->getName() . $span,
				"item_count" => $objFactory->getItemCount(),
				"lower_bound" => $objFactory->getLowerBound(),
				"upper_bound" => $objFactory->getUpperBound(),
				"state" => false,
			);

			// there are nodes (leaves) for this factory: append
			if (isset($aLeaf[$objFactory->getId()])) {

				$a['children'] = $aLeaf[$objFactory->getId()];

			}

			$aTree[] = $a;

		}

		// adds root node to the tree
		$aTree = array(
			"id" => '0',
			"text" => 'Root',
			"state" => array('opened' => true),
			"children" => $aTree
		);

		$json = \Common\Lib::var2json($aTree);

		echo $json;

		exit;

	}

	/**
	 * checks if the client (identified by session id) has to have it's jstree reloaded due to other client's changes
	 * @param $lastDatetime -- last time the lookup ran
	 * @param \EventServer $objSSE
	 * @return bool
	 */
	public static function lookupChanges($lastDatetime, \EventServer $objSSE=null) {

		$dbh = \Database::$singleton;

		$sessioId = $objSSE->getSessionId();

		$sql = "select * from event where update_tree is true and sessionid != '{$sessioId}' and datetime > '$lastDatetime' limit 1;";

		$aRow = $dbh->fetchOne($sql);

		// changes were found: returns a HR message
		if(! empty($aRow)) {

			// message to the client
			$msg = "Client on IP {$aRow['ip']} (not this browser): {$aRow['action']}";

			$aResponse = array(
				'event' => 'reload',
				'data' => $msg
			);

			return $aResponse;

		}
		// debug
		else {

			/*$aResponse = array(
				'event' => 'debug',
				'data' => $sql
			);

			return $aResponse;*/

		}

		return false;

	}


	/**
	 * identify actions that requires the trees (in the clients) to be updated or not
	 * @param $action
	 * @return bool
	 */
	public static function actionRequiresUpdate($action) {

		// actions that requires the trees (in the clients) to be updated
		if(
			in_array(
				$action,
				array(
					'create',
					'update',
					'generate',
					'delete'
				)
			)
		) {

			return true;

		}
		else {

			return false;

		}

	}

}