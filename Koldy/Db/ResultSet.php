<?php namespace Koldy\Db;

class ResultSet extends QueryBuilder {
	
	/**
	 * @var QueryBuilder
	 */
	protected $countQuery = null;

	public function page($number, $limitPerPage) {
		return $this->limit(($number -1) * $limitPerPage, $limitPerPage);
	}
	
	public function countQuery(QueryBuilder $query) {
		$this->countQuery = $query;
		return $this;
	}

	protected function getCountQuery() {
		if ($this->countQuery !== null) {
			return $this->countQuery;
		}
		
		$fields = $this->fields;
		$limit = $this->limit;
		
		$this->resetFields();
		$this->resetLimit();
		$this->resetOrderBy();
		
		$this->field('COUNT(*)', 'total');
		$query = $this->getQuery();
		
		$this->fields = $fields;
		$this->limit = $limit;
		return $query;
	}

	public function count() {
		$result = $this->adapter->query($this->getCountQuery(), $this->bindings);
		if (sizeof($result) == 1) {
			return $result[0]->total;
		}

		return 0;
	}
}