<?php namespace Koldy;

/**
 * The JSON class
 * 
 * @link http://koldy.net/docs/json
 */
class Json extends Response {


	/**
	 * Holding array that will be converted into JSON string on page output
	 * 
	 * @var array
	 */
	private $data = array();


	/**
	 * Create the object with initial data
	 * 
	 * @param array $data [optional]
	 * @return \Koldy\Json
	 * @link http://koldy.net/docs/json#usage
	 */
	public static function create(array $data = array()) {
		$self = new static();
		$self->data = $data;
		return $self;
	}


	/**
	 * Get the response data
	 * 
	 * @return array
	 * @link http://koldy.net/docs/json#usage
	 */
	public function getData() {
		return $this->data;
	}


	/**
	 * JSON helper to quickly encode some data
	 * 
	 * @param mixed $data
	 * @return string in JSON format
	 * @link http://koldy.net/docs/json#encode-decode
	 */
	public static function encode($data) {
		return json_encode($data);
	}


	/**
	 * JSON helper to quickly decode JSON string into array or stdClass
	 * 
	 * @param string $stringData
	 * @param string $returnObject
	 * @return array|\stdClass
	 * @link http://koldy.net/docs/json#encode-decode
	 */
	public static function decode($stringData, $returnObject = false) {
		return json_decode($stringData, !$returnObject);
	}


	/**
	 * Set the key into JSON response
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return \Koldy\Json
	 * @link http://koldy.net/docs/json#usage
	 */
	public function set($key, $value) {
		$this->data[$key] = $value;
		return $this;
	}


	/**
	 * Is there key set in JSON data
	 * 
	 * @param string $key
	 * @return boolean
	 * @link http://koldy.net/docs/json#usage
	 */
	public function has($key) {
		return array_key_exists($key, $this->data);
	}


	/**
	 * Remove the JSON key from data
	 * 
	 * @param string $key
	 * @return \Koldy\Json
	 * @link http://koldy.net/docs/json#usage
	 */
	public function remove($key) {
		unset($this->data[$key]);
		return $this;
	}


	/**
	 * Get the key from JSON data
	 * 
	 * @param string $key
	 * @return mixed or null if key doesn't exist
	 */
	public function get($key) {
		return $this->has($key) ? $this->data[$key] : null;
	}


	/**
	 * Just setter ...
	 * 
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value) {
		$this->set($key, $value);
	}

	/**
	 * And just getter ...
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key) {
		return $this->get($key);
	}


	/**
	 * (non-PHPdoc)
	 * @see \Koldy\Response::flush()
	 * @link http://koldy.net/docs/json#usage
	 */
	public function flush() {
		// set the fixed headers
		$this->header('Content-type', 'application/json');

		ob_start();

			echo static::encode($this->getData());
			$size = ob_get_length();
			$this->header('Content-length', $size);

		$this->flushHeaders();
		ob_end_flush();
		flush();

		if (function_exists('fastcgi_finish_request')) {
			@fastcgi_finish_request();
		}

		if ($this->workAfterResponse !== null) {
			call_user_func($this->workAfterResponse);
		}
	}

}
