<?php

namespace Livetree;

/**
 * ORM for table 'leaf'
 * @author fknoedt
 */
class Leaf extends \Orm{

	/**
	 * table name in database
	 * @var string
	 */
	static $tableName = 'leaf';

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
		'id' => 'int',
		'factory_id' => 'int',
		'value' => 'int',
		'creation_date' => 'timestamp'
	);

	/**
	 * @var INT
	 */
	var $id;

	/**
	 * @var ID
	 */
	var $factory_id;

	/**
	 * @var INT
	 */
	var $value;

	/**
	 * @var TIMESTAMP
	 */
	var $creation_date;

	/**
	 * Leaf constructor
	 * @param INT $factory_id
	 * @param INT $value
	 * @param TIMESTAMP $creation_date
	 */
	public function __construct($factory_id=null, $value=null, $creation_date=null) {

		if($factory_id)
			$this->setFactoryId($factory_id);

		if($value)
			$this->setValue($value);

		if($creation_date)
			$this->setCreationDate($creation_date);

	}


	/**
	 * @return INT
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param INT $id
	 */
	public function setId($id) {
		$this->setOrm('id',$id);
	}

	/**
	 * @return ID
	 */
	public function getFactoryId() {
		return $this->factory_id;
	}

	/**
	 * @param ID $factory_id
	 */
	public function setFactoryId($factory_id) {
		$this->setOrm('factory_id',$factory_id);
	}

	/**
	 * @return INT
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @param INT $value
	 */
	public function setValue($value) {

		if($value < \Livetree\Factory::MIN_LOWER_BOUND)
			throw new \Exception\FunctionalError("[{$this->getId()}] Lower bound ({$value}) cannot be lower than " . \Livetree\Factory::MIN_LOWER_BOUND);

		if($value > \Livetree\Factory::MAX_UPPER_BOUND)
			throw new \Exception\FunctionalError("[{$this->getId()}] Upper bound ({$value}) cannot be lower than " . \Livetree\Factory::MAX_UPPER_BOUND);

		$this->setOrm('value',$value);
	}

	/**
	 * @return TIMESTAMP
	 */
	public function getCreationDate() {
		return $this->creation_date;
	}

	/**
	 * @param TIMESTAMP $creation_date
	 */
	public function setCreationDate($creation_date) {
		$this->setOrm('creation_date',$creation_date);
	}

	/**
	 * method required for ORM to work
	 */
	function save() {

		// calls generic ORM save (insert or update)
		parent::save();

	}


}