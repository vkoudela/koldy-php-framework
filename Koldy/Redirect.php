<?php namespace Koldy;

class Redirect extends Response {

	private $responseCode = null;

	private $where = null;

	public static function permanent($where) {
		$self = new self();
		$self->responseCode = 301;
		$self->where = $where;
		return $self;
	}

	public static function temporary($where) {
		$self = new self();
		$self->responseCode = 302;
		$self->where = $where;
		return $self;
	}
	
	/**
	 * Alias to temporary() method
	 * @param string $where
	 * @return \Koldy\Redirect
	 */
	public static function to($where) {
		return static::temporary($where);
	}

	public static function href($controller, $action = null, array $params = null) {
		$self = new self();
		$self->responseCode = 302;
		$self->where = Application::route()->href($controller, $action, $params);
		return $self;
	}

	public static function link($path) {
		return self::temporary(Application::route()->link($path));
	}

	public function flush() {
		switch($this->responseCode) {
			case 301:
				header('HTTP/1.1 301 Moved Permanently');
				header("Location: {$this->where}");
				break;

			case 302:
				header("Location: {$this->where}");
				break;
		}

		header('Connection: close');
		header('Content-Length: 0');
		flush();

		if ($this->workAfterResponse !== null) {
			$fn = $this->workAfterResponse;
			$fn();
		}
	}
}