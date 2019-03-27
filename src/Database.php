<?php

/**
 * wrapper for PDO for better error handling, debugging and easier operations
 * @TODO implement Dependency Injection for DatabaseQuery and DatabaseConnection
 */
class Database {

	/**
	 * singleton of this object
	 * @var
	 */
	public static $singleton;

	/**
	 * PDO connector
	 * @var
	 */
	var $dbh;

	/**
	 * database configuration info (set on config.inc.php)
	 */
	private $dbName;
	private $dbHost;
	private $dbUser;
	private $dbPass;
	private $dbPort;

	/**
	 * last executed query
	 * @var
	 */
	private $lastQuery;

	/**
	 * PDO fetch mode
	 * @var int
	 */
	private $pdoFetch = PDO::FETCH_ASSOC;

	/**
	 * default is PDO::FETCH_ASSOC
	 * @return int
	 */
	public function getPdoFetch()
	{
		return $this->pdoFetch;
	}

	/**
	 * @param int $pdoFetch
	 */
	public function setPdoFetch($pdoFetch)
	{
		$this->pdoFetch = $pdoFetch;
	}

	/**
	 * returns Database object for the given connection
	 * @return Database
	 * @throws \Exception\DatabaseException
	 */
	public static function getConnection()
	{
		// connection was not initialized; it has to be
		if(! isset(self::$singleton))
			throw new \Exception\DatabaseException(__METHOD__ . ": connection was not initialized (has to call constructor)");

		return self::$singleton;
	}

	/**
	 * Database constructor
	 * configures database connection parameters and connect to the database through PDO
	 * @param $dbHost
	 * @param $dbName
	 * @param $dbUser
	 * @param $dbPass
	 * @param $dbPort
	 * @throws \Exception\DatabaseException
	 */
	function __construct($dbHost, $dbName, $dbUser, $dbPass, $dbPort)
	{
		$this->dbHost = $dbHost;
		$this->dbName = $dbName;
		$this->dbUser = $dbUser;
		$this->dbPass = $dbPass;
		$this->dbPort = $dbPort;

		$dbHost = $this->dbHost;
		$dbName = $this->dbName;

		try {

			$this->dbh = new \PDO("mysql:host={$dbHost};dbname={$dbName}", $this->dbUser, $this->dbPass);

			// uses PDOException and show errors
			$this->dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			// saves this object for a singleton database connection (per connection)
			self::$singleton = $this;

		}
		catch(PDOException $e) {

			throw new \Exception\DatabaseException("PDO: " . $e->getMessage(),$e->getCode());

		}

	}

	/**
	 * starts PDO transaction
	 */
	function beginTransaction()
	{
		$this->dbh->beginTransaction();
	}

	/**
	 * commits PDO transaction
	 */
	function commit()
	{
		$this->dbh->commit();
	}

	/**
	 * rollbacks PDO transaction
	 */
	function rollBack()
	{
		$this->dbh->rollBack();
	}

	/**
	 * prepare (bind placeholders) and execute statement
	 * @param $sql
	 * @param $binds
	 * @return mixed
	 * @throws \Exception\DatabaseException
	 */
	public function query($sql, $binds=null)
	{
		$stmt = $this->getStatement($sql, $binds);
		$stmt->execute();
		return $stmt;
	}

	/**
	 * return query statement (to be used with runQuery())
	 * @param string $sql -- the query
	 * @param null $binds -- parameters for prepare/execute
	 * @return mixed
	 * @throws \Exception\DatabaseException
	 */
	function getStatement($sql, $binds=null) {

		// logs query
		$this->lastQuery = $sql;

		try {

			// bind parameter to query
			if(isset($binds) && !empty($binds)) {

				$stmt = $this->dbh->prepare($sql);

				foreach($binds as $field => $value) {

					// detects datatype

					// ip address in a string matches is_int
					if(is_int($value) && ! filter_var($value, FILTER_VALIDATE_IP))
						$dataType = \PDO::PARAM_INT;
					elseif(is_bool($value))
						$dataType = \PDO::PARAM_BOOL;
					else
						$dataType = \PDO::PARAM_STR;

					$stmt->bindValue(":{$field}", $value, $dataType);

				}

				return $stmt;

			}
			// regular query (still have to execute)
			else {

				return $this->dbh->query($sql);

			}

		}
			// catches PDOException, appends last query and return DatabaseException
		catch(PDOException $e) {

			throw new \Exception\DatabaseException($e->getMessage() . " -- code: {$e->getCode()} [last query: {$this->lastQuery}]");

		}

	}

	/**
	 * get count result for the given query
	 * @param $sql
	 * @return mixed
	 */
	function count($sql,$binds)
	{
		$stmt = $this->query($sql,$binds);
		$a = $stmt->fetch(PDO::FETCH_NUM);

		return $a[0];
	}

	/**
	 * fetch one row of the database with the given query
	 * @param $sql
	 * @param null $binds
	 * @return mixed
	 * @throws \Exception\DatabaseException
	 */
	function fetchOne($sql, $binds=null)
	{
		$stmt = $this->query($sql, $binds);
		return $stmt->fetch($this->pdoFetch);
	}

	/**
	 * fetch every row of the database with the given query
	 * @param $sql
	 * @param null $binds
	 * @return mixed
	 * @throws \Exception\DatabaseException
	 */
	function fetchAll($sql, $binds=null)
	{
		$stmt = $this->query($sql, $binds);
		return $stmt->fetchAll($this->pdoFetch);
	}

	/**
	 * retrieve rows (raw or objects) based on arguments
	 * @todo order by and other params
	 * @param $class
	 * @param array $binds
	 * @param bool $bHydrate -- return array of objects or rows
	 * @return array|mixed
	 * @throws Exception
	 */
	function retrieveAll($class, $binds=array(), $bHydrate=false)
	{
		// identify table name
		if(! property_exists($class, 'tableName'))
			throw new Exception(__METHOD__ . ": classe {$class} lacks static attribute tableName");

		$tableName = $class::$tableName;

		$sql = 'select * from ' . $tableName;

		if(! empty($binds)) {

			$aWhere = array();

			foreach($binds as $field => $value) {

				$aWhere[] = "{$field} = :{$field}";

			}

			$sqlWhere = implode(' and ', $aWhere);

			$sql .= $sqlWhere;

		}

		// return resultset as proper objects
		if($bHydrate) {

			$aObj = array();

			$aRow = $this->fetchAll($sql,$binds);

			foreach($aRow as $row) {

				// database row to object
				$obj = \Orm::hydrate($row, $class);

				$aObj[] = $obj;

			}

			return $aObj;

		}
		// retrieve result set as array
		else {

			return $this->fetchAll($sql,$binds);

		}
	}

	/**
	 * returns last id inserted in database
	 * @return string
	 * @throws \Exception\DatabaseException
	 */
	public static function getLastInsertId()
	{
		$dbh = self::getConnection();

		return $dbh->dbh->lastInsertId();
	}

	/**
	 * returns last id inserted in database
	 * @return string
	 * @throws \Exception\DatabaseException
	 */
	public static function getLastQuery()
	{
		$dbh = self::getConnection();

		return $dbh->lastQuery;
	}

	/**
	 * get CURRENT_TIMESTAMP from database
	 * @return mixed
	 */
	function getDatetime()
	{
		$sql = "SELECT CURRENT_TIMESTAMP AS DT;";

		$row = $this->fetchOne($sql);

		return $row['DT'];
	}

}