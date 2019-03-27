<?php

namespace Livetree;
use Exception\FunctionalError;

/**
 * ORM and methods for table 'factory'
 * @author fknoedt
 */

class Factory extends \Orm {

	/**
	 * table name in database
	 * @var string
	 */
	static $tableName = 'factory';

	/**
	 * primary key field name
	 * @var string
	 */
	static $pkFieldName = 'id';

	/**
	 * list of table fields and datatypes (for ORM purposes)
	 * it's not mandatory but if it's defined, datatype will be validated
	 * @var array
	 */
	var $aField = array(
		'id' => 'int',
		'name' => 'string',
		'lower_bound' => 'int',
		'upper_bound' => 'int',
		'item_count' => 'int'
	);

	/**
	 * regex for 'name' field validation
	 * @var string
	 */
	const NAME_REGEX = '/^[a-z0-9 \-]+$/i';

	/**
	 * name maxlength
	 */
	const MAX_LENGTH_FACTORY_NAME = 20;

	/**
	 * max items (nodes) per Factory
	 */
	const MAX_ITEM_COUNT = 15;

	/**
	 * minimum value for lower bound
	 */
	const MIN_LOWER_BOUND = -1000;

	/**
	 * maximum value for upper bound
	 */
	const MAX_UPPER_BOUND = 1000;

	/**
	 * @var INT
	 */
	var $id;

	/**
	 * @var VARCHAR(255)
	 */
	var $name;

	/**
	 * @var INT
	 */
	var $lower_bound;

	/**
	 * @var INT
	 */
	var $upper_bound;

	/**
	 * @var INT
	 */
	var $item_count;

	/**
	 * Factory constructor.
	 * @param INT $id
	 * @param VARCHAR $name
	 * @param INT $lower_bound
	 * @param INT $upper_bound
	 * @param INT $item_count
	 */
	public function __construct($id=null, $name=null, $lower_bound=null, $upper_bound=null, $item_count=null) {

		// has to use set methods for ORM to work

		// ID was received: object is already in the database
		if($id)
			$this->setId($id);
		// indicates that the object is new (has to be persisted in the database)
		else
			$this->bIsNew = true;

		if($name)
			$this->setName($name);

		if($lower_bound !== null)
			$this->setLowerBound($lower_bound);

		if($upper_bound !== null)
			$this->setUpperBound($upper_bound);

		if($item_count)
			$this->setItemcount($item_count);

	}

	/**
	 * checks if a factory id with that name already exists
	 * @param $name
	 * @param null $factoryId
	 * @return mixed
	 * @throws \Exception\DatabaseException
	 */
	public static function nameExists($name, $factoryId=null) {


		$sql = 'select count(*) from factory where `name` = :name';

		$aParam = array(
			'name' => $name
		);

		// factory_id received: make sure it won't be taken into account
		if($factoryId) {

			$sql .= ' and `id` != :id';
			$aParam['id'] = $factoryId;

		}

		$sql .= ';';

		return \Database::getConnection()->count($sql,$aParam);

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
	 * @return VARCHAR
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param VARCHAR $name
	 */
	public function setName($name) {

		// validates name's format and length
		if(! preg_match(self::NAME_REGEX, $name))
			throw new \Exception\FunctionalError("Invalid Name: {$name}");

		if(strlen($name) > self::MAX_LENGTH_FACTORY_NAME)
			throw new \Exception\FunctionalError("Name cannot exceed " . self::MAX_LENGTH_FACTORY_NAME . " characters (got " . strlen($name) . ")");

		$this->setOrm('name',$name);

	}

	/**
	 * @return INT
	 */
	public function getLowerBound() {
		return $this->lower_bound;
	}

	/**
	 * @param INT $lower_bound
	 */
	public function setLowerBound($lower_bound) {

		if($lower_bound < self::MIN_LOWER_BOUND)
			throw new \Exception\FunctionalError("Lower bound cannot be lower than " . self::MIN_LOWER_BOUND);

		$this->setOrm('lower_bound',$lower_bound);

	}

	/**
	 * @return INT
	 */
	public function getUpperBound() {
		return $this->upper_bound;
	}

	/**
	 * @param $upper_bound
	 * @throws FunctionalError
	 * @throws \Exception
	 */
	public function setUpperBound($upper_bound) {

		if($upper_bound > self::MAX_UPPER_BOUND)
			throw new \Exception\FunctionalError("Upper bound cannot be lower than " . self::MAX_UPPER_BOUND);

		$this->setOrm('upper_bound',$upper_bound);

	}

	/**
	 * @return INT
	 */
	public function getItemcount() {
		return $this->item_count;
	}

	/**
	 * @param $item_count
	 * @throws FunctionalError
	 * @throws \Exception
	 */
	public function setItemcount($item_count) {

		if($item_count > self::MAX_ITEM_COUNT)
			throw new \Exception\FunctionalError("Item count cannot be higher than " . self::MAX_ITEM_COUNT);

		$this->setOrm('item_count',$item_count);

	}

	/**
	 * validates and save (insert or update) the object in the database
	 */
	public function save() {

		// required fields
		if(! $this->getLowerBound() && $this->getLowerBound() !== 0)
			throw new \Exception\FunctionalError("Lower Bound is required");

		if(! $this->getUpperBound() && $this->getUpperBound() !== 0)
			throw new \Exception\FunctionalError("Upper Bound is required");

		if(! $this->getItemcount() && $this->getItemcount() !== 0)
			throw new \Exception\FunctionalError("Item Count is required");


		// validates inconsistency
		if($this->getLowerBound() > $this->getUpperBound())
			throw new \Exception\FunctionalError("Lower Bound ({$this->getLowerBound()}) cannot be higher than Upper Bound ({$this->getUpperBound()})");

		// name will be written: validates unique
		if(isset($this->aAlteredField['name'])) {

			if(self::nameExists($this->name, $this->getId()))
				throw new \Exception\FunctionalError("Name '{$this->name}' already exists");

		}

		// calls generic ORM save (insert or update)
		parent::save();

	}

	/**
	 * erase every Factory's node (leaf)
	 * @return mixed
	 * @throws \Exception\DatabaseException
	 */
	function resetNodes() {

		$dbh = \Database::getConnection();

		$sql = "delete from leaf where factory_id = {$this->getId()};";

		return $dbh->query($sql);

	}

	/**
	 * generate one node (leaf) for every item_count accordingly to lower and upper bounds
	 */
	function generateNodes() {

		for ($i = 0; $i < $this->getItemcount(); $i++) {

			$nodeValue = rand($this->getLowerBound(), $this->getUpperBound());

			$objNode = new Leaf($this->getId(), $nodeValue);

			$objNode->save();

		}

	}

}