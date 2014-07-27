<?php namespace Koldy;

/**
 * Perform redirect be flushing redirect headers to client. Usually, you'll use
 * this class as return value from method in your controller classes.
 * 
 * @example
 * 
 * 		class PageController {
 * 			public function userAction() {
 * 				return Redirect::href('user', 'list');
 * 			}
 * 		}
 * 
 * @link http://koldy.net/docs/redirect
 *
 */
class Redirect extends Response {


	/**
	 * The HTTP response code of redirect
	 * 
	 * @var int 301 or 302
	 */
	protected $responseCode = null;


	/**
	 * URL where user will be redirected. It is absolute, or relative URL,...
	 * Who cares.
	 * 
	 * @var string
	 */
	protected $where = null;


	/**
	 * Permanent redirect (301) to the given URL
	 * 
	 * @param string $where
	 * @return \Koldy\Redirect
	 * @link http://koldy.net/docs/redirect#methods
	 */
	public static function permanent($where) {
		$self = new static();
		$self->responseCode = 301;
		$self->where = $where;
		return $self;
	}


	/**
	 * Temporary redirect (302) to the given URL
	 * 
	 * @param string $where
	 * @return \Koldy\Redirect
	 * @link http://koldy.net/docs/redirect#methods
	 */
	public static function temporary($where) {
		$self = new static();
		$self->responseCode = 302;
		$self->where = $where;
		return $self;
	}


	/**
	 * Alias to temporary() method
	 * 
	 * @param string $where
	 * @return \Koldy\Redirect
	 * @example http://www.google.com
	 * @link http://koldy.net/docs/redirect#methods
	 */
	public static function to($where) {
		return static::temporary($where);
	}


	/**
	 * Redirect user to home page
	 * 
	 * @return \Koldy\Redirect
	 * @link http://koldy.net/docs/redirect#usage
	 */
	public static function home() {
		return static::href();
	}


	/**
	 * Redirect user to the URL generated with Url::href
	 * 
	 * @param string $controller [optional]
	 * @param string $action [optional]
	 * @param array $params [optional]
	 * @return \Koldy\Redirect
	 * @link http://koldy.net/docs/redirect#usage
	 * @link http://koldy.net/docs/url#href
	 */
	public static function href($controller = null, $action = null, array $params = null) {
		$self = new static();
		$self->responseCode = 302;
		$self->where = Application::route()->href($controller, $action, $params);
		return $self;
	}


	/**
	 * Redirect user the the given link under the same domain.
	 * 
	 * @param string $path
	 * @return \Koldy\Redirect
	 * @link http://koldy.net/docs/redirect#usage
	 * @link http://koldy.net/docs/url#link
	 */
	public static function link($path) {
		return self::temporary(Application::route()->link($path));
	}


	/**
	 * (non-PHPdoc)
	 * @see \Koldy\Response::header($name, $value)
	 */
	public function header($name, $value = null) {
		if ($name == 'Location') {
			throw new Exception('Using \'Location\' for header name is not permitted');
		}
	
		return parent::header($name, $value);
	}


	/**
	 * (non-PHPdoc)
	 * @see \Koldy\Response::flush()
	 */
	public function flush() {
		switch($this->responseCode) {
			case 301:
				$this->header('HTTP/1.1 301 Moved Permanently');
				parent::header('Location', $this->where);
				break;

			case 302:
				parent::header('Location', $this->where);
				break;
		}

		$this
			->header('Connection', 'close')
			->header('Content-Length', 0);

		$this->flushHeaders();
		flush();
		
		if (function_exists('fastcgi_finish_request')) {
			@fastcgi_finish_request();
		}

		if ($this->workAfterResponse !== null) {
			call_user_func($this->workAfterResponse);
		}
	}

}
