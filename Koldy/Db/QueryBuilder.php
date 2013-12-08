<?php namespace Koldy\Db;

use Koldy\Db\Expr;

class QueryBuilder {

	protected $from = null;
	
	protected $joins = array();

	protected $fields = array();

	protected $where = array();

	protected $orderBy = array();
	
	protected $groupBy = array();

	protected $limit = null;
	
	protected $fieldTables = array();
	
	protected $fieldAliasMap = array();
	
	/**
	 * @var \Koldy\Db\Adapter
	 */
	protected $adapter = null;

	protected $bindings = array();

	public function __construct($model = null) {
		if ($model !== null) {
			$this
				->from($model::getTableName())
				->adapter($model::getAdapter());
		} else {
			$this->adapter(\Koldy\Db::getAdapter());
		}
	}

	/**
	 * @param \Koldy\Db\Adapter $adapter
	 * @return \Koldy\Db\ResultSet
	 */
	public function adapter($adapter) {
		$this->adapter = $adapter;
		return $this;
	}
	
	/**
	 * Get the database adapter
	 * @return \Koldy\Db\Adapter
	 */
	public function getAdapter() {
		return $this->adapter;
	}

	/**
	 * Set the table FROM which fields will be fetched
	 * @param string $table
	 * @param string $alias
	 * @param string $fields
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function from($table, $alias = null, $fields = null) {
		$this->from = array(
			'table' => $table,
			'alias' => $alias
		);
		
		$this->addTableFields($fields, $alias);
		return $this;
	}
	
	protected function addTableFields($fields, $alias) {
		if ($fields !== null) {
			if (is_string($fields)) {
				$this->field($fields, null, $alias);
			} else if (is_array($fields)) {
				foreach ($fields as $field) {
					$this->field($field, null, $alias);
				}
			}
		}
	}
	
	/**
	 * LEFT JOIN other table
	 * @param string $table
	 * @param string $on
	 * @param string $alias
	 * @param string $fields
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function leftJoin($table, $on, $alias = null, $fields = null) {
		$this->joins[] = array(
			'type' => 'LEFT JOIN',
			'table' => $table,
			'alias' => $alias,
			'on' => $on,
			'fields' => $fields
		);
		
		$this->addTableFields($fields, $alias);
		return $this;
	}
	
	/**
	 * INNER JOIN other table
	 * @param string $table
	 * @param string $on
	 * @param string $alias
	 * @param string $fields
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function innerJoin($table, $on, $alias = null, $fields = null) {
		$this->joins[] = array(
			'type' => 'INNER JOIN',
			'table' => $table,
			'alias' => $alias,
			'on' => $on,
			'fields' => $fields
		);
		
		$this->addTableFields($fields, $alias);
		return $this;
	}

	/**
	 * Add one field that will be fetched
	 * @param string $field
	 * @param string $as
	 * @param string $table
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function field($field, $as = null, $table = null) {
		$this->fields[] = array(
			'name' => $field,
			'as' => $as,
			'table' => $table
		);
		
		if ($table !== null) {
			$this->fieldTables[$field] = $table;
		}
		
		$this->fieldAliasMap[$as === null ? $field : $as] = ($table === null ? '' : ("{$table}.")) . $field;
		
		return $this;
	}

	/**
	 * Add fields to fetch by passing array of fields
	 * @param array $fields
	 * @param string $table
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function fields(array $fields, $table = null) {
		foreach ($fields as $field => $as) {
			$this->field($field, $as, $table);
		}
		return $this;
	}
	
	/**
	 * Reset all fields that will be fetched
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function resetFields() {
		$this->fields = array();
		return $this;
	}

	/**
	 * Add WHERE statement (will be automatically chained with AND)
	 * @param string $field
	 * @param mixed $value
	 * @param string $table
	 * @param string $operator
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function where($field, $value, $table = null, $operator = '=') {
		$this->where[] = array(
			'link' => 'AND',
			'field' => $field,
			'operator' => $operator,
			'value' => ($value === NULL) ? (new Expr('NULL')) : $value,
			'table' => $table
		);
		return $this;
	}

	/**
	 * Add OR WHERE statement
	 * @param string $field
	 * @param mixed $value
	 * @param string $table
	 * @param string $operator
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function orWhere($field, $value, $table = null, $operator = '=') {
		$this->where[] = array(
			'link' => 'OR',
			'field' => $field,
			'operator' => $operator,
			'value' => $value,
			'table' => $table
		);
		return $this;
	}
	
	/**
	 * Reset WHERE (remove all WHERE rules)
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function resetWhere() {
		$this->where = array();
		return $this;
	}
	
	/**
	 * Add field to GROUP BY
	 * @param string $field
	 * @param string $table
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function groupBy($field, $table = null) {
		$this->groupBy[] = array(
			'field' => $field,
			'table' => $table
		);
		return $this;
	}
	
	/**
	 * Reset GROUP BY (remove GROUP BY)
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function resetGroupBy() {
		$this->groupBy = array();
		return $this;
	}

	/**
	 * Add field to ORDER BY
	 * @param string $field
	 * @param string $direction
	 * @param string $table
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function orderBy($field, $direction = 'ASC', $table = null) {
		$this->orderBy[] = array(
			'field' => $field,
			'direction' => $direction,
			'table' => $table
		);
		return $this;
	}
	
	/**
	 * Reset ORDER BY (remove ORDER BY)
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function resetOrderBy() {
		$this->orderBy = array();
		return $this;
	}

	/**
	 * Set the LIMIT on query results
	 * @param int $start
	 * @param int $howMuch
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function limit($start, $howMuch) {
		$this->limit = new \stdClass;
		$this->limit->start = $start;
		$this->limit->howMuch = $howMuch;
		return $this;
	}
	
	/**
	 * Reset LIMIT (remove the LIMIT)
	 * @return \Koldy\Db\QueryBuilder
	 */
	public function resetLimit() {
		$this->limit = null;
		return $this;
	}
	
	/**
	 * Manually set field aliases if needed
	 * @param array $fields
	 * @return \Koldy\Db\QueryBuilder
	 */
	protected function setfieldTables(array $fields) {
		$this->fieldTables = $fields;
		return $this;
	}
	
	/**
	 * Get the SQL format of the field
	 * @param string $field
	 * @return string
	 */
	protected function getFieldSql($field) {
		if ($field['table'] !== null) {
			return "{$field['table']}.{$field['field']}";
		}
		
		$name = $field['field'];
		
		if (isset($this->fieldTables[$name])) {
			return "{$this->fieldTables[$name]}.{$name}";
		}
		
		return $name;
	}
	
	/**
	 * Get the field alias
	 * @param string $as
	 * @return string
	 */
	protected function getAliasField($as) {
		if (isset($this->fieldAliasMap[$as])) {
			return $this->fieldAliasMap[$as];
		}
		
		return $as;
	}

	/**
	 * Get the query string prepared for PDO
	 * @return string
	 */
	protected function getQuery() {
		if (sizeof($this->from) == 0) {
			\Log::error("Missing 'from' in sql query, class: " . get_class($this));
			\Application::throwError(500, 'Error building SQL query, missing \'from\'');
		}
		
		$this->bindings = array();
		$query = 'SELECT ';

		if (sizeof($this->fields) == 0) {
			$query .= '*';
		} else {
			foreach ($this->fields as $field) {
				if ($field['table'] !== null) {
					$query .= $field['table'] . '.';
				}
				$query .= $field['name'];
				if ($field['as'] !== null) {
			 		$query .= " as {$field['as']}, ";
				} else {
					$query .= ', ';
				}
			}

			$query = substr($query, 0, -2);
		}

		$query .= ' FROM ' . $this->from['table'];
		if ($this->from['alias'] !== null) {
			$query .= " as {$this->from['alias']}";
		}
		
		foreach ($this->joins as $join) {
			$query .= " {$join['type']} {$join['table']}";
			if ($join['alias'] !== null) {
				$query .= ' as ' . $join['alias'];
			}
			$query .= ' ON ' . $join['on'];
		}
		
		if (sizeof($this->where) > 0) {
			$query .= ' WHERE ';
			$wh = '';
			foreach ($this->where as $where) {
				if ($where['value'] instanceof \Koldy\Db\Expr) {
					$wh .= " {$where['link']} ({$this->getFieldSql($where)} {$where['operator']} {$where['value']})";
				} else {
					$wh .= " {$where['link']} ({$this->getFieldSql($where)} {$where['operator']} :{$where['field']})";
					$this->bindings[$where['field']] = $where['value'];
				}
			}
			$wh = substr($wh, strpos($wh, '('));
			$query .= $wh;
		}
		
		if (sizeof($this->groupBy) > 0) {
			$query .= ' GROUP BY';
			foreach ($this->groupBy as $field) {
				$query .= " {$this->getFieldSql($field)}, ";
			}
			$query = substr($query, 0, -2);
		}

		if (sizeof($this->orderBy) > 0) {
			$query .= ' ORDER BY';
			foreach ($this->orderBy as $order) {
				if ($order['table'] === null) {
					$fld = $this->getAliasField($order['field']);
				} else {
					$fld = $this->getFieldSql($order);
				}
				$query .= " {$fld} {$order['direction']},";
			}
			$query = substr($query, 0, -1);
		}

		if ($this->limit !== null) {
			$query .= " LIMIT {$this->limit->start}, {$this->limit->howMuch}";
		}

		return $query;
	}

	/**
	 * Fetch all records
	 * @param const $fetchMode [optional] default PDO::FETCH_ASSOC
	 * @return array
	 */
	public function fetch($fetchMode = \PDO::FETCH_ASSOC) {
		return $this->adapter->query($this->getQuery(), $this->bindings, $fetchMode);
	}
	
	/**
	 * Fetch all records as array of objects
	 * @return array
	 */
	public function fetchObj() {
		return $this->fetch(\PDO::FETCH_OBJ);
	}
	
	/**
	 * Fetch only first record as object or return false if there is no records
	 * @return \stdClass|false
	 */
	public function fetchFirstObj() {
		$results = $this->fetch(\PDO::FETCH_OBJ);
		return isset($results[0]) ? $results[0] : false;
	}
	
	/**
	 * Get bindings
	 * @return array
	 */
	public function getBindings() {
		$theQuery = $this->getQuery();
		return $this->bindings;
	}
	
	/**
	 * Return some debug informations about the query you built
	 * @return string
	 */
	public function debug() {
		$query = $this->__toString();
		$bindings = '';
		foreach ($this->bindings as $key => $value) {
			$bindings .= "{$key}={$value}&";
		}
		$bindings = substr($bindings, 0, -1);
		return "Query={$query}\nBindings={$bindings}";
	}
	
	public function __toString() {
		return $this->getQuery();
	}
}