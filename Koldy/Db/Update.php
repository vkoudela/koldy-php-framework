<?php namespace Koldy\Db;

use Koldy\Exception;

/**
 * The UPDATE query builder.
 * @author vkoudela
 * @link http://koldy.net/docs/database/query-builder#update
 */
class Update extends Where {

	/**
	 * The table name on which UPDATE will be performed
	 * @var string
	 */
	protected $table = null;

	/**
	 * The key-value pairs of fields and values to be set
	 * @var array
	 */
	protected $what = array();

	/**
	 * @var array
	 */
	protected $orderBy = array();

	/**
	 * @param string $table
	 * @param array $values [optional] auto set values in this query
	 * @link http://koldy.net/docs/database/query-builder#update
	 */
	public function __construct($table, array $values = null) {
		$this->table = $table;
		
		if ($values !== null) {
			$this->setValues($values);
		}
	}
	
	/**
	 * Set field to be updated
	 * @param string $field
	 * @param mixed $value
	 * @return \Koldy\Db\Update
	 */
	public function set($field, $value) {
		$this->what[$field] = $value;
		return $this;
	}
	
	/**
	 * Set the values to be updated
	 * @param array $values
	 * @return \Koldy\Db\Update
	 */
	public function setValues(array $values) {
		$this->what = $values;
		return $this;
	}

	/**
	 * Add field to ORDER BY
	 *
	 * @param string $field
	 * @param string $direction
	 *
	 * @throws Exception
	 * @return \Koldy\Db\Select
	 */
	public function orderBy($field, $direction = null) {
		if ($direction === null) {
			$direction = 'ASC';
		} else {
			$direction = strtoupper($direction);
		}

		if ($direction !== 'ASC' && $direction !== 'DESC') {
			throw new Exception("Can not use invalid direction order ({$direction}) in ORDER BY statement");
		}

		$this->orderBy[] = array(
			'field' => $field,
			'direction' => $direction
		);
		return $this;
	}

	/**
	 * Reset ORDER BY (remove ORDER BY)
	 * @return \Koldy\Db\Select
	 */
	public function resetOrderBy() {
		$this->orderBy = array();
		return $this;
	}

	/**
	 * Increment numeric field's value in database
	 *
	 * @param string $field
	 * @param int $howMuch
	 *
	 * @return \Koldy\Db\Update
	 */
	public function increment($field, $howMuch = 1) {
		return $this->set($field, new Expr("{$field} + {$howMuch}"));
	}

	/**
	 * Decrement numeric field's value in database
	 *
	 * @param string $field
	 * @param int $howMuch
	 *
	 * @return \Koldy\Db\Update
	 */
	public function decrement($field, $howMuch = 1) {
		return $this->set($field, new Expr("{$field} - {$howMuch}"));
	}
	
	/**
	 * Get the query
	 */
	protected function getQuery() {
		$this->bindings = array();
		$sql = "UPDATE {$this->table}\nSET\n";
		
		if (sizeof($this->what) == 0) {
			throw new Exception('Can not build UPDATE query, SET is not defined');
		}
		
		foreach ($this->what as $field => $value) {
			$sql .= "\t{$field} = ";
			if ($value instanceof Expr) {
				$sql .= "{$value},\n";
			} else {
				$key = $field . (static::getKeyIndex());
				$sql .= ":{$key},\n";
				$this->bindings[$key] = $value;
			}
		}
		
		$sql = substr($sql, 0, -2);
		
		if ($this->hasWhere()) {
			$sql .= "\nWHERE{$this->getWhereSql()}";
		}

		if (sizeof($this->orderBy) > 0) {
			$sql .= "\nORDER BY";
			foreach ($this->orderBy as $r) {
				$sql .= "\n\t{$r['field']} {$r['direction']},";
			}
			$sql = substr($sql, 0, -1);
		}
		
		return $sql;
	}
	
}
