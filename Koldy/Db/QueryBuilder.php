<?php namespace Koldy\Db;

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

	public function fields(array $fields, $table = null) {
		foreach ($fields as $field => $as) {
			$this->field($field, $as, $table);
		}
		return $this;
	}
	
	public function resetFields() {
		$this->fields = array();
		return $this;
	}

	public function where($field, $value, $table = null, $operator = '=') {
		$this->where[] = array(
			'link' => 'AND',
			'field' => $field,
			'operator' => $operator,
			'value' => $value,
			'table' => $table
		);
		return $this;
	}

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
	
	public function resetWhere() {
		$this->where = array();
		return $this;
	}
	
	public function groupBy($field, $table = null) {
		$this->groupBy[] = array(
			'field' => $field,
			'table' => $table
		);
		return $this;
	}
	
	public function resetGroupBy() {
		$this->groupBy = array();
		return $this;
	}

	public function orderBy($field, $direction = 'ASC', $table = null) {
		$this->orderBy[] = array(
			'field' => $field,
			'direction' => $direction,
			'table' => $table
		);
		return $this;
	}
	
	public function resetOrderBy() {
		$this->orderBy = array();
		return $this;
	}

	public function limit($start, $howMuch) {
		$this->limit = new \stdClass;
		$this->limit->start = $start;
		$this->limit->howMuch = $howMuch;
		return $this;
	}
	
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
	
	protected function getAliasField($as) {
		if (isset($this->fieldAliasMap[$as])) {
			return $this->fieldAliasMap[$as];
		}
		
		return $as;
	}

	protected function getQuery() {
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
// 				$tableAlias = ($order['table'] !== null) ? ($order['table'] . '.') : '';
// 				$query .= " {$tableAlias}{$order['field']} {$order['direction']},";
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

	public function fetch($fetchMode = \PDO::FETCH_ASSOC) {
		return $this->adapter->query($this->getQuery(), $this->bindings, $fetchMode);
	}
	
	public function fetchObj() {
		return $this->fetch(\PDO::FETCH_OBJ);
	}
	
	public function fetchFirstObj() {
		$results = $this->fetch(\PDO::FETCH_OBJ);
		return isset($results[0]) ? $results[0] : false;
	}
	
	public function __toString() {
		return $this->getQuery();
	}
}