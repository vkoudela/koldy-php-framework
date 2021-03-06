<?php namespace Koldy;

/**
 * Class for printing plain text as response to HTTP request.
 * 
 * @link http://koldy.net/docs/plain
 */
class Plain extends Response {

	/**
	 * This is data holder for this class
	 * 
	 * @var string
	 */
	private $text = null;

	/**
	 * Create the instance statically
	 * 
	 * @param string $text
	 * @return \Koldy\Plain
	 */
	public static function create($text) {
		if (!is_string($text)) {
			throw new \InvalidArgumentException('String expected, got ' . gettype($text));
		}

		/** @var \Koldy\Plain $self */
		$self = new static();

		return $self
			->setText($text)
			->header('Content-Type', 'text/plain');
	}

	/**
	 * Set the text in this object
	 * 
	 * @param string $text
	 * @return $this
	 */
	public function setText($text) {
		if (!is_string($text)) {
			throw new \InvalidArgumentException('String expected, got ' . gettype($text));
		}

		$this->text = $text;
		return $this;
	}

	/**
	 * Get the text stored in this object
	 * 
	 * @return string
	 */
	public function getText() {
		return $this->text;
	}

	/**
	 * Append current text with given text
	 * 
	 * @param string $text
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function append($text) {
		if (!is_string($text)) {
			throw new \InvalidArgumentException('String expected, got ' . gettype($text));
		}

		$this->text .= $text;
		return $this;
	}

	/**
	 * Prepend current text with given text
	 * 
	 * @param string $text
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function prepend($text) {
		if (!is_string($text)) {
			throw new \InvalidArgumentException('String expected, got ' . gettype($text));
		}

		$this->text = "{$text}{$this->text}";
		return $this;
	}

	/**
	 * @link http://koldy.net/docs/plain#usage
	 */
	public function flush() {
		ob_start();

			// print the text stored in this object
			print $this->text;

			$size = ob_get_length();
			$this->header('Content-Length', $size);
		
			$this->flushHeaders();

		ob_end_flush();
		flush();
	
		if (function_exists('fastcgi_finish_request')) {
			@fastcgi_finish_request();
		}
	
		if ($this->workAfterResponse instanceof \Closure) {
			call_user_func($this->workAfterResponse);
		}
	}

}
