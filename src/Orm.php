<?php

/**
 * Class/DB mapping
 * TODO: relationships (get)
 */
class Orm
{

	/**
	 * indicates that the object is not in the database (has to persist)
	 * @var bool
	 */
	protected $bIsNew = true;


	/**
	 * list of altered table fields (to know when it's needed to insert/update)
	 * index: field name (as on table and $aField)
	 * value: value to be binded
	 * @var array
	 */
	protected $alteredFields = array();

	/**
	 * subclass for magic methods (while there's no DI Container)
	 * @var
	 */
	protected $class;

	/**
	 * list of related - one/many to one (belongsTo) or one to many (hasMany) - objects
	 * @var
	 */
	protected $relatedObjects = [];

	/**
	 * Orm constructor.
	 */
	public function __construct()
	{

	}

	/**
	 * set every attribute on $ormObject->attributes
	 * @param $ormObject
	 * @throws Exception
	 */
	public static function init(&$ormObject)
	{
		if (!isset($ormObject->attributes)) {
			throw new Exception("Class " . get_class($ormObject) . " has to have an `attributes` attribute");
		}

		foreach ($ormObject->attributes as $att => $dataType) {

			// set null attribute
			$ormObject->$att = null;

		}

		// subclass for later reference (while there's no DI container)
		$ormObject->class = get_class($ormObject);

		// instantiate hasMany and belongsTo if not defined
		if(! isset($ormObject->belongsTo)) {
			$ormObject->belongsTo = [];
		}
		if(! isset($ormObject->hasMany)) {
			$ormObject->hasMany = [];
		}
	}

	/**
	 * dynamic getters and setters
	 * although magic methods can be slightly slower, we're using it to implement a very simple yet dynamic ORM
	 * @param $methodName
	 * @param null $params
	 * @return mixed
	 * @throws Exception
	 */
	public function __call($methodName, $params = null)
	{
		// get or set method?
		$methodPrefix = substr($methodName, 0, 3);
		// key/table in snake case
		$attOrTable = \Common\Lib::camelToSnakeCase(substr($methodName, 3));

		// set can only receive one parameter
		// TODO: set related table with it's object
		if($methodPrefix == 'set' && count($params) == 1)
		{
			if(empty($attOrTable)) {
				throw new Exception(__METHOD__ . ": invalid method: {$methodName}");
			}
			$value = $params[0];
			$this->setOrm($attOrTable, $value);
		}
		elseif($methodPrefix == 'get')
		{
			// if there's a related table (belongsTo or hasMany attribute on the subclass) return one or a list of objects
			$list = $this->getRelation($attOrTable);

			if($list !== false) {
				return $list;
			}

			// attribute was never instantiated
			if(! array_key_exists($attOrTable, get_object_vars($this))) {
				throw new Exception(__METHOD__ . ": attribute {$attOrTable} not defined (check " . get_class($this) . "->attributes array)");
			}

			// return attribute
			return $this->$attOrTable;
		}
		else
		{
			throw new Exception("Method {$methodName} is not defined");
		}
	}

	/**
	 * retrieve the related belongs_to object and save it
	 * @param $tableName
	 * @return mixed
	 */
	protected function addBelongsToObject($tableName)
	{
		// relation info
		$relation = $this->belongsTo[$tableName];

		$class		= $relation['class'];
		$fk			= $relation['fk'];

		// value to be queried in the related table
		$fkValue	= $this->$fk;

		// retrieve and set single object
		$this->relatedObjects[$tableName] = $this->retrieveByClass($class, $fkValue)[0];

		return $this->relatedObjects[$tableName];
	}

	/**
	 * retrieve the related has_many objects and save them
	 * @param $tableName
	 * @return mixed
	 */
	protected function addHasManyObject($tableName)
	{
		// relation info
		$relation = $this->hasMany[$tableName];

		$class	= $relation['class'];
		$fk		= $relation['fk'];

		$this->relatedObjects[$tableName] = $this->retrieveByClass($class, $this->getPkValue(), $fk);

		return $this->relatedObjects[$tableName];

	}

	/**
	 * if there's a relation for the given table, return it
	 * @param $tableName
	 * @return mixed (related object, list of related objects or false)
	 */
	protected function getRelation($tableName)
	{
		// is relation was called before, return it
		if(isset($this->relatedObjects[$tableName])) {
			return $this->relatedObjects[$tableName];
		}

		// table has one (or many) to one relation
		if(isset($this->belongsTo[$tableName])) {

			return $this->addBelongsToObject($tableName);

		}
		// table has one to many relation
		else if(isset($this->hasMany[$tableName])) {

			return $this->addHasManyObject($tableName);

		}

		return false;

	}

	/**
	 * should be called by subclass on it's set methods
	 * @param $field -- the same name as the attribute (table field) which is to be set
	 * @param $value
	 * @return bool
	 * @throws Exception
	 */
	public function setOrm($field, $value)
	{
		// values hadn't changed: return false
		if($value == $this->$field)
			return false;

		// makes sure the property exists in the class
		$class = get_class($this);

		$bAttributeExists = property_exists($this, $field);

		if(! $bAttributeExists) {
			throw new Exception(__METHOD__ . ": attribute {$field} doesn't exist for class {$class}");
		}

		// field is defined for the class/table: validates
		if(@isset($this->attributes[$field])) {

			$datatype = $this->attributes[$field];

			switch($datatype) {

				// integer on the db
				case 'int':

					// validate numeric
					if(! is_int($value) && ! filter_var($value, FILTER_VALIDATE_IP)) {

						// parses to int
						if(is_numeric($value))
							$value = (int) $value;
						else
							throw new Exception(__METHOD__ . ": invalid int for {$field}: {$value}");

					}

					break;

				// char, varchar and text on the database
				case 'string':

					if(! filter_var(trim($value), FILTER_VALIDATE_IP) && ! is_string($value))
						throw new Exception(__METHOD__ . ": invalid string for {$field}: " . $value);

					break;

				// datetime on the database
				case 'timestamp':

					if(! \Common\Lib::isValidDate($value))
						throw new Exception(__METHOD__ . ": invalid date for {$field}: {$value}");

					break;

			}

		}

		$this->$field = $value;

		$this->alteredFields[$field] = $value;

	}

	/**
	 * runs insert or update if necessary
	 * @return bool|void
	 * @throws Exception
	 * @throws \Exception\DatabaseException
	 */
	function save() {

		// gets table's name
		$table =	self::getTableName();
		$pkField =	self::getPkFieldName();

		// fields to insert or update
		$binds = $this->alteredFields;

		// new object but no altered fields: error
		if($this->bIsNew && empty($binds))
			throw new Exception(__METHOD__ . ": object ({$table}) is new but has no field set (it's mandatory to use setField methods, which should call parent::setOrm)");

		// no alteration
		if(empty($binds))
			return;;

		// prepare fields
		$attributes = array_keys($binds);

		// Database Connection
		$dbh = \Database::getConnection();

		// insert
		if($this->bIsNew) {

			$sql = "insert into {$table} (" . implode(", ", $attributes) . ") values (:" . implode(', :', $attributes) . ");";

		}
		// update
		else {

			$aUpdate = array();

			foreach($binds as $field => $value) {

				$aUpdate[] = "`$field` = :{$field}";

			}

			// query with binds
			$sql = "update {$table} set " . implode(', ', $aUpdate) . " where `{$pkField}` = :pk;";

			// where clause
			$pkValue = $this->getPkValue();

			$binds['pk'] = $pkValue;

		}

		$dbh->query($sql, $binds);

		// new object was saved: indicates the object doesn't have to be persisted in the database
		if($this->bIsNew) {

			$this->bIsNew = false;

			$this->$pkField = $dbh->dbh->lastInsertId();

		}

	}

	/**
	 * returns database's table name (see Subclass::$tableName)
	 * @return string
	 */
	public static function getTableName() {

		$tableName = '';

		// gets caller class (subclass) name
		$subclass = static::class;

		eval("\$tableName = {$subclass}::\$tableName;");

		return $tableName;

	}

	/**
	 * returns table's primary key field name (see Subclass::$tableName)
	 * @return string
	 */
	public static function getPkFieldName() {

		$pkFieldName = '';

		// gets caller class (subclass) name
		$subclass = static::class;

		eval("\$pkFieldName = {$subclass}::\$pkFieldName;");

		return $pkFieldName;

	}

	/**
	 * gets value from PK property
	 * @return mixed
	 */
	function getPkValue()
	{
		$value = null;

		$pkFieldName = self::getPkFieldName();

		eval("\$value = \$this->{$pkFieldName};");

		return $value;
	}


	/**
	 * returns array or object for the given PK
	 * @param $value
	 * @param $column
	 * @param $bObject -- return object (default) or array
	 * @return string
	 * @throws \Exception\DatabaseException
	 */
	public static function retrieve($value, $column=null, $bObject=true)
	{
		// gets subclass (which called this method)
		$class = static::class;

		$tableName = self::getTableName();

		$column = $column ?? self::getPkFieldName();

		$sql = "select * from {$tableName} where {$column} = :{$column};";

		// Database Connection
		$dbh = \Database::getConnection();

		$rows = $dbh->fetchOne(
			$sql,
			array(
				$column => $value
			)
		);

		// no result set
		if(empty($rows))
			return $rows;

		// return result set as object
		if($bObject) {

			return self::hydrate($rows, $class);

		}
		// returns result set in array format
		else {

			return $rows;

		}
	}

	/**
	 * returns objects or array for the rows that match the filter
	 * @param $value
	 * @param $column
	 * @param $bObject -- return object (default) or array
	 * @return string
	 * @throws \Exception\DatabaseException
	 */
	public static function retrieveAll($value, $column=null, $bObject=true) {

		// gets subclass (which called this method)
		$class = static::class;

		$tableName = self::getTableName();

		$column = $column ?? self::getPkFieldName();

		$sql = "select * from {$tableName} where {$column} = :{$column};";

		// Database Connection
		$dbh = \Database::getConnection();

		$rows = $dbh->fetchAll(
			$sql,
			array(
				$column => $value
			)
		);

		// no result set
		if(empty($rows))
			return $rows;

		$list = [];

		foreach($rows as $row) {

			if($bObject) {

				$list[] = self::hydrate($row, $class);

			}
			// returns result set in array format
			else {

				$list[] = $row;

			}
		}

		return $list;

	}

	/**
	 * call retrieveAll() for the given class and parameters
	 * @param $class
	 * @param $value
	 * @param null $column
	 * @param bool $bObj
	 * @return mixed
	 */
	public static function retrieveByClass($class, $value, $column=null, $bObj=true)
	{
		return $class::retrieveAll($value, $column, $bObj);
	}

	/**
	 * returns object for the given class based on $rows (Database's fetchOne) array
	 * @param $rows
	 * @param $class
	 * @return mixed
	 * @throws Exception
	 */
	public static function hydrate($rows, $class) {

		$obj = new $class();

		foreach($rows as $field => $value) {

			$setMethod = 'set' . Common\Lib::snakeToCamelCase($field);

			// TODO: test if the $obj->attributes (magic) methods exist
			// if(! method_exists($obj,$setMethod))
			// 	throw new Exception(__METHOD__ . ": method {$setMethod} doesn't exist for class {$class}");

			// sets DB's value on object's property
			$obj->$setMethod($value);

		}

		// indicates that the object is already in the database
		$obj->bIsNew = false;

		return $obj;

	}

	/**
	 * delete current Factory and it's nodes
	 * @return mixed
	 * @throws \Exception\DatabaseException
	 */
	function delete() {

		$pkFieldName = self::getPkFieldName();
		$tableName = self::getTableName();

		$pkValue = $this->getPkValue();

		$sql = "delete from {$tableName} where {$pkFieldName} = :pk";

		$binds = array(
			'pk' => $pkValue
		);

		$dbh = \Database::getConnection();

		return $dbh->query($sql,$binds);

	}

}