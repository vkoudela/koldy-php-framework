<?php namespace Koldy;

/**
 * If you want to return your own view object from controllers, then
 * extend this class.
 */
abstract class Response {


	/**
	 * The function that should be called when script finishes output
	 * 
	 * @var function
	 */
	protected $workAfterResponse = null;


	/**
	 * The array of headers that will be printed before outputing anyting
	 * 
	 * @var array
	 */
	private $headers = array();


	/**
	 * Flush the content to output buffer
	 */
	abstract public function flush();


	/**
	 * Set response header
	 * 
	 * @param string $name
	 * @param string $value [optional]
	 * @return \Koldy\Response
	 */
	public function header($name, $value = null) {
		if (!is_string($name)) {
			throw new \InvalidArgumentException('Invalid header name: ' . $name);
		}

		if ($value !== null && is_array($value)) {
			throw new \InvalidArgumentException("Invalid header value for name={$name}; expected string, got array");
		}

		$this->headers[] = array(
			'one-line' => ($value === null),
			'name' => $name,
			'value' => $value
		);

		return $this;
	}


	/**
	 * Is header already set
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function hasHeader($name) {
		foreach ($this->headers as $header) {
			if (!$header['one-line'] && $header['name'] == $name) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Remove the header by name
	 * 
	 * @param string $name
	 * @return \Koldy\Response
	 */
	public function removeHeader($name) {
		foreach ($this->headers as $index => $header) {
			if ($header['name'] == $name) {
				unset($this->headers[$index]);
				return $this;
			}
		}

		return $this;
	}


	/**
	 * Remove all headers
	 * 
	 * @return \Koldy\Response
	 */
	public function removeHeaders() {
		$this->headers = array();
		return $this;
	}


	/**
	 * Flush the headers
	 */
	protected function flushHeaders() {
		if (!headers_sent()) {
			foreach ($this->headers as $header) {
				header("{$header['name']}: {$header['value']}");
			}
		} else {
			Log::warning('Can\'t flushHeaders because headers are already sent');
		}
	}


	/**
	 * Set the function for after work
	 * 
	 * @param function $function
	 * @return \Koldy\Response
	 */
	public function after($function) {
		if (!is_object($function) || !($function instanceof \Closure)) {
			throw new Exception('You must pass the function to after method in ' . get_class($this) . ' class');
		}

		$this->workAfterResponse = $function;
		return $this;
	}

}
