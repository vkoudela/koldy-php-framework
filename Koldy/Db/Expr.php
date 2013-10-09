<?php namespace Koldy\Db;

class Expr {

	private $data = null;

	public function __construct($data) {
		$this->data = $data;
	}

	public function getData() {
		return $this->data;
	}

	public function __toString() {
		$data = $this->getData();
		return ($data !== null ? $data : '');
	}

}