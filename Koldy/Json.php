<?php namespace Koldy;

class Json extends Response {

	private $data = array();

	public static function create(array $data) {
		$self = new self();
		$self->data = $data;
		return $self;
	}

	public function getResponse() {
		return $this->data;
	}

	public static function encode($data) {
		return json_encode($data);
	}

	public static function decode($stringData, $returnObject = false) {
		return json_decode($stringData, !$returnObject);
	}

	public function set($key, $value) {
		$this->data[$key] = $value;
		return $this;
	}

	public function has($key) {
		return isset($this->data[$key]);
	}

	public function get($key) {
		return $this->has($key) ? $this->data[$key] : null;
	}

	public function __set($key, $value) {
		$this->set($key, $value);
	}

	public function __get($key) {
		return $this->get($key);
	}

	public function flush() {
		header('Content-type: application/json');
		header('Connection: close');

		ob_start();

			echo self::encode($this->getResponse());
			$size = ob_get_length();
			header("Content-Length: {$size}");

		ob_end_flush();
		flush();

		if ($this->workAfterResponse !== null) {
			$fn = $this->workAfterResponse;
			$fn();
		}
	}
}