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
		
		$query = clone $this;
		$query->resetFields();
		$query->resetLimit();
		$query->resetOrderBy();
		$query->field('COUNT(*)', 'total');
		
		return $query;
	}

	public function count() {
		$result = $this->getCountQuery()->fetchObj();
		
		if (sizeof($result) == 1) {
			return $result[0]->total;
		}

		return 0;
	}
}