<?php namespace Koldy\Db;

use Koldy\Db;
use Koldy\Db\Expr;

abstract class Model {

	/**
	 * The connection string on which the queries will be executed. The
	 * connection string must be previously defined as connection in
	 * config.database.php.
	 * @var  string
	 */
	protected static $connection = null;

	/**
	 * If you don't define the table name, the framework will assume the table
	 * name by the called class name.
	 * @var  string
	 */
	protected static $table = null;

	/**
	 * While working with tables in database, framework will always assume that
	 * you have the field named "id" as unique identifier. If you have
	 * your primary key with different name, please define it in the child
	 * class.
	 * @var  string
	 */
	protected static $primaryKey = 'id';
	
	/**
	 * The array of fields that will naver be injected into query when calling
	 * the save() method. Be aware that this doesn't affect static update()
	 * method.
	 * @var array
	 */
	protected static $neverUpdate = array();
	
	/**
	 * The data holder in this object
	 * @var array
	 */
	protected $data = null;
	
	/**
	 * This is the array that holds informations loaded from database. When
	 * you call save() method, this data will be compared to the data set in
	 * object and update method will set only fields that are changed. If there
	 * is no change, update() method will return 0 without triggering query on
	 * database. 
	 * @var array
	 */
	protected $originalData = null;

	/**
	 * Construct the instance with or without starting data
	 * @param  array $data
	 */
	public function __construct(array $data = null) {
		if ($data !== null) {
			$setOriginalData = false;
			foreach ($data as $key => $value) {
				$this->$key = $value;
				if ($key === static::$primaryKey) {
					$setOriginalData = true;
				}
			}
			
			if ($setOriginalData) {
				$this->originalData = $data;
			}
		}
	}
	
	public function __get($property) {
		return (isset($this->data[$property]))
			? $this->data[$property]
			: null;
	}
	
	public function __set($property, $value) {
		$this->data[$property] = $value;
	}
	
	/**
	 * Set the array of values
	 * @param array $values
	 * @return \Koldy\Db\Model
	 */
	public function set(array $values) {
		foreach ($values as $key => $value) {
			$this->data[$key] = $value;
		}
		return $this;
	}
	
	/**
	 * Gets all data that this object currently has
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}
	
	/**
	 * Does this object has a field?
	 * @param string $field
	 * @return bool
	 */
	public function has($field) {
		return isset($this->data[$field]);
	}

	/**
	 * Get the adapter for this model
	 * @return  \Koldy\Db\Adapter
	 */
	public static function getAdapter() {
		return Db::getAdapter(static::$connection);
	}

	/**
	 * Get the table name for database for this model. If your model class is
	 * User\Login\History, then the database table name will be user_login_history
	 * @return  string
	 */
	public static function getTableName() {
		if (static::$table === null) {
			return str_replace('\\', '_', strtolower(get_called_class()));
		}
		return static::$table;
	}

	/**
	 * Insert the record in database with given array of data
	 * @param mixed $data pass array or valid instance of \Koldy\Db\Model
	 * @return  new static|false False if insert failed, otherwise, instance of this model
	 */
	public static function create($data) {
		if ($data instanceof Model) {
			$data = $data->getData();
		}
		$keys = $values = $bindings = array();
		foreach ($data as $key => $value) {
			$keys[] = $key;

			if ($value instanceof Expr) {
				$values[] = $value->getData();
			} else if ($value === null) {
				$values[] = 'NULL';
			} else {
				$values[] = '?';
				$bindings[] = $value;
			}
		}

		$sql = 'INSERT INTO ' . static::getTableName()
			. ' (' . implode(',', $keys) . ')'
			. ' VALUES (' . implode(',', $values) . ')';

		$ok = static::getAdapter()->query($sql, $bindings);
		if (!$ok) {
			return false;
		}

		$data[static::$primaryKey] = static::getLastInsertId();
		return new static($data);
	}

	/**
	 * If you statically created new record in database to the table with auto
	 * incrementing field, then you can use this static method to get the
	 * generated primary key
	 * @return  integer
	 * @example
	 * 
	 * 		if (User::create(array('first_name' => 'John', 'last_name' => 'Doe'))) {
	 *   		echo User::getLastInsertId();
	 *   	}
	 */
	public static function getLastInsertId() {
		return static::getAdapter()->getLastInsertId();
	}

	/**
	 * Update the table with given array of data. Be aware that if you don't
	 * pass the second parameter, then the whole table will be updated (the
	 * query will be executed without the WHERE statement).
	 * @param  array $data
	 * @param  mixed $onWhat OPTIONAL if you pass single value, framework will
	 * assume that you passed primary key value. If you pass assoc array,
	 * then the framework will use those to create the WHERE statement.
	 * 
	 * @example
	 * 
	 * 		User::update(array('first_name' => 'new name'), 5) will execute:
	 *   	UPDATE user SET first_name = 'new name' WHERE id = 5
	 * 
	 * 		User::update(array('first_name' => 'new name'), array('disabled' => 0)) will execute:
	 *   	UPDATE user SET first_name = 'new name' WHERE disabled = 0
	 *  
	 * @return boolean|int False if query failes; number of affected rows if query passed
	 */
	public static function update(array $data, $onWhat = null) {
		$bindings = array();
		$tableName = static::getTableName();
		$sql = "UPDATE {$tableName} SET ";
		foreach ($data as $key => $value) {
			if (!in_array($key, static::$neverUpdate)) {
				if ($value instanceof Expr) {
					$sql .= "{$key} = {$value}, ";
				} else if ($value === null) {
					$sql .= "{$key} = NULL, ";
				} else {
					$sql .= "{$key} = ?, ";
					$bindings[] = $value;
				}
			}
		}

		$sql = substr($sql, 0, -2);

		if ($onWhat !== null) {
			if (!is_array($onWhat)) {
				$onWhat = array(static::$primaryKey => $onWhat);
			}

			$sql .= ' WHERE';
			foreach ($onWhat as $field => $value) {
				if ($value instanceof Expr) {
					$sql .= " {$field} = {$value} AND";
				} else if ($value === null) {
					$sql .= " {$field} IS NULL AND";
				} else {
					$sql .= " {$field} = ? AND";
					$bindings[] = $value;
				}
			}

			$sql = substr($sql, 0, -4);
		}

		return static::getAdapter()->query($sql, $bindings);
	}
	
	/**
	 * @return integer how many rows is affected
	 */
	public function save(array $data = null) {
		$pk = static::$primaryKey;
		
		$dataToSave = $this->data;
		foreach ($dataToSave as $key => $value) {
			if (isset($data[$key])) {
				$dataToSave[$key] = $data[$key];
			}
		}
		
		$data = $dataToSave;
		
		if (!isset($data[$pk]) || $data[$pk] === null) { 
			// primary doesn't exists, we can insert into
			return static::create($data);
		} else {
			// primary exists, lets make update
			$pkValue = $data[$pk];
			unset($data[$pk]);
			
			if ($this->originalData !== null) {
				$data2 = $data;
				foreach ($data2 as $key => $value) {
					if ($value === $this->originalData[$key]) {
						unset($data[$key]);
					}
				}
				unset($data2);
			}
			
			foreach (static::$neverUpdate as $field) {
				unset($data[$field]);
			}
			
			if (sizeof($data) > 0) {
				return (static::update($data, $pkValue) !== false);
			} else {
				return 0;
			}
		}
	}

	/**
	 * Increment one numeric field in table on the row identified by primary key.
	 * You can use this only if your primary key is just one field.
	 * @param string $field
	 * @param mixed $what the primary key value of the record
	 * @param int $howMuch [optional] default 1
	 * @return boolean|int False if query failes; number of affected rows if query passed
	 */
	public static function increment($field, $what, $howMuch = 1) {
		$bindings = array();
		$sql = 'UPDATE ' . static::getTableName() . ' SET ';
		if ($howMuch >= 0) {
			$sign = '+';
		} else {
			$sign = '-';
			$howMuch = abs($howMuch);
		}

		$sql .= "{$field} = {$field} {$sign} {$howMuch}";

		if (!is_array($what)) {
			$what = array(static::$primaryKey => $what);
		}

		$sql .= ' WHERE';
		foreach ($what as $field => $value) {
			if ($value instanceof Expr) {
				$sql .= " {$field} = {$value} AND";
			} else {
				$sql .= " {$field} = ? AND";
				$bindings[] = $value;
			}
		}

		$sql = substr($sql, 0, -4);

		return static::getAdapter()->query($sql, $bindings);
	}

	/**
	 * Delete one or more records from the table defined in this model. If you
	 * pass array, then array must contain field names and values that will be
	 * used in WHERE statement. If you pass primitive value, method will treat
	 * that as passed value for primary key field.
	 * @param mixed $what
	 * @return integer How many records is deleted
	 * @example User::delete(1);
	 * @example User::delete(array('group_id' => 5, 'parent_id' => 10));
	 * @example User::delete(array('parent_id' => 10, array('time', '>', '2013-08-01 00:00:00')))
	 * @return boolean|int False if query failes; number of affected rows if query passed
	 */
	public static function delete($what) {
		if (!is_array($what)) {
			$what = array(static::$primaryKey => $what);
		}

		$bindings = array();
		$sql = 'DELETE FROM ' . static::getTableName() . ' WHERE';
		foreach ($what as $field => $value) {
			if ($value instanceof Expr) {
				$sql .= " {$field} = {$value} AND";
			} elseif (is_array($value)) {
				$sql .= " {$value[0]} {$value[1]} ? AND";
				$bindings[] = $value[2];
			} else {
				$sql .= " {$field} = ? AND";
				$bindings[] = $value;
			}
		}

		$sql = substr($sql, 0, -4);

		return static::getAdapter()->query($sql, $bindings);
	}
	
	/**
	 * The same as static::delete(), only this will work if object is populated with data
	 * @see static::delete()
	 *  @return boolean|int False if query failes; number of affected rows if query passed
	 */
	public function remove() {
		$pk = static::$primaryKey;
		if (!isset($this->data[$pk])) {
			\Koldy\Log::error("Trying to delete " . get_class($this) . " from database, but primary field is not set in object; pk={$pk}; data=" . \Koldy\Json::encode($this->data));
			\Koldy\Application::throwError(500, 'Can not delete object from database');
		}
		
		return static::delete($this->$pk);
	}

	/**
	 * Fetch one record from database. You can pass one or two parameters.
	 * If you pass only one parameter, framework will assume that you want to
	 * fetch the record from database according to primary key defined in
	 * model. Otherwise, you can fetch the record by any other field you have.
	 * If your criteria returnes more then one records, only first record will
	 * be taken.
	 * @param  mixed $field primaryKey value, single field or assoc array of arguments for query
	 * @param  mixed $value [optional]
	 * @param array $fields [optional]
	 * @return  new static|false
	 */
	public static function fetchOne($field, $value = null, array $fields = null) {
		$tableName = static::getTableName();
		$bindings = array();
		
		if ($fields === null) {
			$fields = '*';
		} else {
			$tmp = array();
			foreach ($fields as $field => $alias) {
				$tmp[] = "{$field} as {$alias}";
			}
			$fields = implode(', ', $tmp);
		}

		if (is_array($field) && $value === null) {
			$where = '';
			foreach ($field as $f => $v) {
				$where .= "{$f} = ? AND ";
				$bindings[] = $v;
			}
			
			$where = substr($where, 0, -5);
		} else if ($value === null) {
			$value = $field;
			$field = static::$primaryKey;
			$bindings = array($value);
			$where = "{$field} = ?";
		} else {
			$where = "{$field} = ?";
			$bindings = array($value);
		}
		
		$query = "SELECT {$fields} FROM {$tableName} WHERE {$where} LIMIT 0, 1";
		$results = static::getAdapter()->query($query, $bindings, \PDO::FETCH_ASSOC);

		if (sizeof($results) > 0) {
			return new static($results[0]);
		} else {
			return false;
		}
	}

	/**
	 * Fetch the array of records from database
	 * @param array $where [optional] the assoc array of WHERE condition
	 * @param array $fields [optional] array of fields to select; by default, all fields will be fetched
	 * @param const $fetchMode [optional] the fetch mode; PDO::FETCH_OBJ (default) or PDO::FETCH_ASSOC
	 * @return array of elements of type given in $fetchMode
	 */
	public static function fetch(array $where = array(), array $fields = null, $fetchMode = \PDO::FETCH_OBJ) {
		$tableName = static::getTableName();
		$bindings = array();

		if ($fields === null) {
			$query = "SELECT * FROM {$tableName} WHERE 1";
			foreach ($where as $key => $value) {
				if (!is_array($value)) {
					$query .= " AND {$key} = :{$key}";
					$bindings[$key] = $value;
				} else {
					$query .= " AND {$value[0]} {$value[1]} :{$value[0]}";
					$bindings[$value[0]] = $value[2];
				}
			}
		} else {
			$query = "SELECT\n";
			foreach ($fields as $key => $value) {
				if (is_numeric($key)) {
					$query .= "\t{$value},\n";
				} else {
					$query .= "\t{$key} as {$value},\n";
				}
			}

			$query = substr($query, 0, -2) . "\nFROM {$tableName}\nWHERE 1";

			foreach ($where as $key => $value) {
				if (!is_array($value)) {
					$query .= "\nAND {$key} = :{$key}";
					$bindings[$key] = $value;
				} else {
					$query .= "\nAND {$value[0]} {$value[1]} :{$value[0]}";
					$bindings[$value[0]] = $value[2];
				}
			}
		}

		$records = array();
		switch ($fetchMode) {
			case \PDO::FETCH_ASSOC:
				$records = static::getAdapter()->query($query, $bindings, \PDO::FETCH_ASSOC);
				break;

			case \PDO::FETCH_OBJ:
				foreach (static::getAdapter()->query($query, $bindings, \PDO::FETCH_ASSOC) as $record) {
					$records[] = new static($record);
				}
				break;
		}

		return $records;
	}
	
	/**
	 * Fetch the array of records from database
	 * @param array $where [optional] the assoc array of WHERE condition
	 * @param array $fields [optional] array of fields to select; by default, all fields will be fetched
	 * @return array of elements of array
	 */
	public static function fetchAssoc(array $where = array(), array $fields = null) {
		return static::fetch($where, $fields, \PDO::FETCH_ASSOC);
	}
	
	/**
	 * Fetch the array of records from database
	 * @param array $where [optional] the assoc array of WHERE condition
	 * @param array $fields [optional] array of fields to select; by default, all fields will be fetched
	 * @return array of elements of object
	 */
	public static function fetchObj(array $where = array(), array $fields = null) {
		return static::fetch($where, $fields, \PDO::FETCH_OBJ);
	}

	/**
	 * Check if some value exists in database or not. This is useful if you
	 * want, for an example, check if user's e-mail already is in database
	 * before you try to insert your data.
	 * @param  string $field
	 * @param  mixed $value
	 * @param  mixed $exceptionValue OPTIONAL
	 * @param  string $exceptionField OPTIONAL
	 * 
	 * @example
	 * 
	 * 		User::isUnique('email', 'email@domain.com'); will execute:
	 *   	SELECT COUNT(*) FROM user WHERE email = 'email@domain.com'
	 * 
	 * 		User::isUnique('email', 'email@domain.com', 'other@mail.com');
	 *   	SELECT COUNT(*) FROM user WHERE email = 'email@domain.com' AND email != 'other@mail.com'
	 * 
	 * 		User::isUnique('email', 'email@domain.com', 5, 'id');
	 *   	SELECT COUNT(*) FROM user WHERE email = 'email@domain.com' AND id != 5
	 */
	public static function isUnique($field, $value, $exceptionValue = null, $exceptionField = null) {
		$tableName = static::getTableName();
		$query = "SELECT COUNT(*) as total FROM {$tableName} ";
		$query .= "WHERE {$field} = :value";
		$bindings = array('value' => $value);

		if ($exceptionValue !== null) {
			if ($exceptionField === null) {
				$exceptionField = $field;
			}
			$query .= " AND {$exceptionField} != :exceptionValue";
			$bindings['exceptionValue'] = $exceptionValue;
		}

		$results = static::getAdapter()->query($query, $bindings);
		if (isset($results[0])) {
			return ($results[0]->total == 0);
		}
		return null;
	}
	
	/**
	 * Count the records in table according to the parameters
	 * @param array $what
	 * @return int or null if failes
	 */
	public static function count(array $what = array()) {
		$tableName = static::getTableName();
		$query = "SELECT COUNT(*) as total FROM {$tableName}";
		$bindings = array();
		if (sizeof($what) > 0) {
			$query .= ' WHERE';
			foreach ($what as $field => $value) {
				$query .= " {$field} = :{$field} AND";
				$bindings[$field] = $value;
			}
			$query = substr($query, 0, -4);
		}
		
		\Koldy\Log::debug($query);
		\Koldy\Log::debug(print_r($bindings, true));
		
		$results = static::getAdapter()->query($query, $bindings);
		if (isset($results[0])) {
			return $results[0]->total;
		}
		return null;
	}

	/**
	 * Get the ResultSet object of this model
	 * @return \Koldy\Db\ResultSet
	 */
	public static function resultSet() {
		return new \Koldy\Db\ResultSet(get_called_class());
	}

	public function __toString() {
		return \Json::encode($this);
	}
}