<?php
/**
 * ORM for table event
 * meta log for actions and errors
 */

namespace Livetree;


class Event extends \Orm{

	/**
	 * table name in database
	 * @var string
	 */
	static $tableName = 'event';

	/**
	 * primary key field name
	 * @var string
	 */
	static $pkFieldName = 'id';

	/**
	 * list of table fields and datatypes (for ORM purposes)
	 * @var array
	 */
	var $aField = array(
		'id'			=> 'int',
		'action'		=> 'string',
		'datetime'		=> 'timestamp',
		'session_id'	=> 'string',
		'metadata'		=> 'string',
		'update_tree'	=> 'bool',
		'ip'			=> 'string',
		'factory_id'	=> 'int'
	);

	/**
	 * @var
	 */
	var $id;

	/**
	 * @var
	 */
	var $action;

	/**
	 * @var
	 */
	var $datetime;

	/**
	 * @var
	 */
	var $sessionid;

	/**
	 * @var
	 */
	var $metadata;

	/**
	 * @var
	 */
	var $update_tree;

	/**
	 * @var
	 */
	var $ip;

	/**
	 * @var
	 */
	var $factory_id;

	/**
	 * Event constructor.
	 * @param null $id
	 * @param null $action
	 * @param null $datetime
	 * @param null $sessionid
	 * @param null $metadata
	 * @param null $update_tree
	 * @param null $ip
	 * @param null $factory_id
	 */
	public function __construct($id=null,  $action=null,  $datetime=null,  $sessionid=null,  $metadata=null, $update_tree=null, $ip=null, $factory_id=null) {

		// ID was received: object is already in the database
		if($id)
			$this->setId($id);
		// indicates that the object is new (has to be persisted in the database)
		else
			$this->bIsNew = true;

		if($action)
			$this->setAction($action);

		if($datetime)
			$this->setDatetime($datetime);

		if($sessionid)
			$this->setSessionid($sessionid);

		if($metadata)
			$this->setMetadata($metadata);

		if($update_tree)
			$this->setUpdateTree($update_tree);

		if($ip)
			$this->setIp($ip);

		if($factory_id)
			$this->setFactoryId($factory_id);

	}


	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param mixed $id
	 */
	public function setId($id) {
		$this->setOrm('id',$id);
	}

	/**
	 * @return mixed
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * @param mixed $action
	 */
	public function setAction($action) {
		$this->setOrm('action',$action);
	}

	/**
	 * @return mixed
	 */
	public function getDatetime() {
		return $this->datetime;
	}

	/**
	 * @param mixed $datetime
	 */
	public function setDatetime($datetime) {
		$this->setOrm('datetime',$datetime);
	}

	/**
	 * @return mixed
	 */
	public function getSessionid() {
		return $this->sessionid;
	}

	/**
	 * @param mixed $sessionid
	 */
	public function setSessionid($sessionid) {
		$this->setOrm('sessionid',$sessionid);
	}

	/**
	 * @return mixed
	 */
	public function getMetadata() {
		return $this->metadata;
	}

	/**
	 * @param mixed $metadata
	 */
	public function setMetadata($metadata) {
		$this->setOrm('metadata',$metadata);
	}

	/**
	 * @return mixed
	 */
	public function getUpdateTree() {
		return $this->update_tree;
	}

	/**
	 * @param mixed $update_tree
	 */
	public function setUpdateTree($update_tree) {
		$this->setOrm('update_tree',$update_tree);
	}

	/**
	 * @return mixed
	 */
	public function getIp() {
		return $this->ip;
	}

	/**
	 * @param mixed $ip
	 */
	public function setIp($ip) {
		$this->setOrm('ip',$ip);
	}

	/**
	 * @return mixed
	 */
	public function getFactoryId() {
		return $this->factory_id;
	}

	/**
	 * @param mixed $factory_id
	 */
	public function setFactoryId($factory_id) {
		$this->setOrm('factory_id',$factory_id);
	}

}