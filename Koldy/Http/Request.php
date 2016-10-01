<?php namespace Koldy\Http;

use Koldy\Exception;
use Koldy\Directory;
use Koldy\Json;
use Koldy\Log;

/**
 * Make HTTP request to any given URL.
 * This class requires PHP CURL extension!
 * @TODO završiti ovo
 *
 */
class Request {

	const GET = 'GET';
	const POST = 'POST';
	const PUT = 'PUT';
	const DELETE = 'DELETE';

	/**
	 * @var string
	 */
	protected $url = null;

	/**
	 * @var array
	 */
	protected $params = array();

	/**
	 * @var string
	 */
	protected $method = 'GET';

	/**
	 * The CURL options
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var array
	 */
	private $appliedOptions = array();

	/**
	 * Request headers
	 * 
	 * @var array
	 */
	protected $headers = array();

	/**
	 * @var bool
	 */
	private $prepared = false;

	/**
	 * Update the request's target URL
	 *
	 * @param string $url
	 *
	 * @return $this
	 */
	public function url($url) {
		$this->url = $url;
		return $this;
	}

	/**
	 * Get the URL on which the request will be fired
	 *
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * Set the request type
	 *
	 * @param int $type constant
	 *
	 * @return $this
	 * @example $request->method(\Koldy\Http\Request::POST);
	 * @deprecated use method() instead
	 */
	public function type($type) {
		return $this->method($type);
	}

	/**
	 * Get the request's type (method)
	 *
	 * @return string GET or POST (for now)
	 * @deprecated use getMethod() instead
	 */
	public function getType() {
		return $this->getMethod();
	}

	/**
	 * @param $method
	 *
	 * @return $this
	 */
	public function method($method) {
		$this->method = $method;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Set the request parameter
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return $this
	 */
	public function param($key, $value) {
		$this->params[$key] = $value;
		return $this;
	}

	/**
	 * Set the parameters that will be sent. Any previously set parameters will be overriden.
	 *
	 * @param array $params
	 *
	 * @return $this
	 */
	public function params(array $params) {
		$this->params = $params;
		return $this;
	}

	/**
	 * Get parameters that were set
	 * @return array
	 */
	public function getParams() {
		return $this->params;
	}

	/**
	 * Check if URL parameter is set
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function hasParam($key) {
		return array_key_exists($key, $this->params);
	}

	/**
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public function getParam($key) {
		return $this->hasParam($key) ? $this->params[$key] : null;
	}

	/**
	 * Set the request header
	 *
	 * @header string $key
	 * @header mixed $value
	 *
	 * @return $this
	 */
	public function header($key, $value) {
		$this->headers[$key] = $value;
		return $this;
	}

	/**
	 * Set the headers that will be sent. Any previously set headers will be overriden.
	 *
	 * @header array $headers
	 *
	 * @return $this
	 */
	public function headers(array $headers) {
		$this->headers = $headers;
		return $this;
	}

	/**
	 * Get headereters that were set
	 * @return array
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * @param string $header
	 *
	 * @return mixed|null
	 */
	public function getHeader($header) {
		return $this->hasHeader($header) ? $this->headers[$header] : null;
	}

	/**
	 * Check if URL headereter is set
	 *
	 * @header string $key
	 *
	 * @return boolean
	 */
	public function hasHeader($key) {
		return array_key_exists($key, $this->headers);
	}

	/**
	 * Set the array of options. Array must be valid array with CURL constants as keys
	 *
	 * @param array $curlOptions
	 *
	 * @return $this
	 * @link http://php.net/manual/en/function.curl-setopt.php
	 */
	public function options(array $curlOptions) {
		$this->options = $curlOptions;
		return $this;
	}

	/**
	 * Set the CURL option
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return $this
	 * @link http://php.net/manual/en/function.curl-setopt.php
	 */
	public function option($key, $value) {
		$this->options[$key] = $value;
		return $this;
	}

	/**
	 * Check if CURL option is set (exists in options array)
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function hasOption($key) {
		return isset($this->options[$key]);
	}

	/**
	 * Get all CURL options
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * @param int $option
	 *
	 * @return mixed|null
	 */
	public function getOption($option) {
		return $this->hasOption($option) ? $this->options[$option] : null;
	}

	/**
	 * @param int $option
	 */
	public function removeOption($option) {
		if ($this->hasOption($option)) {
			unset($this->options[$option]);
		}
	}

	/**
	 * @return array
	 */
	private function prepareHeaders() {
		if (count($this->headers) > 0) {
			$headers = array();

			foreach ($this->headers as $key => $value) {
				$headers[] = "{$key}: {$value}";
			}

			return $headers;
		}

		return array();
	}

	/**
	 * Prepare standard HTTP request. Override this method if needed
	 */
	protected function prepareStandard() {
		$this->option(CURLOPT_CUSTOMREQUEST, $this->getMethod());
		$this->option(CURLOPT_URL, $this->getUrl());
		$this->option(CURLOPT_RETURNTRANSFER, true);
		$this->option(CURLOPT_HEADER, true);

		$this->option(CURLOPT_HEADER, true);
		if (count($this->headers) > 0) {
			$this->option(CURLOPT_HTTPHEADER, $this->prepareHeaders());
		}
	}

	/**
	 * Prepare GET request
	 */
	protected function prepareGet() {
		$this->prepareStandard();

		// append parameters to GET URL if any
		if (count($this->params)) {
			$url = $this->getUrl();

			if (strpos('?', $url) !== false && substr($url, -1) != '?') {
				// just add with "&"
				$url .= http_build_query($this->params);
			} else {
				$url .= '?' . http_build_query($this->params);
			}

			$this->option(CURLOPT_URL, $url);
		}
	}

	/**
	 * Prepare POST request
	 */
	protected function preparePost() {
		$this->prepareStandard();
		$this->option(CURLOPT_POSTFIELDS, count($this->params) > 0 ? http_build_query($this->params) : '');

		if ($this->hasHeader('Content-Type') && $this->getHeader('Content-Type') == 'application/json') {
			$this->option(CURLOPT_POSTFIELDS, Json::encode($this->params));
		}
	}

	/**
	 * Prepare PUT request
	 */
	protected function preparePut() {
		$this->prepareStandard();
		$this->option(CURLOPT_POSTFIELDS, count($this->params) > 0 ? http_build_query($this->params) : '');
	}

	/**
	 * Prepare DELETE request
	 */
	protected function prepareDelete() {
		$this->prepareStandard();
		$this->option(CURLOPT_POSTFIELDS, count($this->params) > 0 ? http_build_query($this->params) : '');
	}

	/**
	 * Prepare all CURL options
	 */
	public function prepare() {
		if (!$this->prepared) {
			switch ($this->getMethod()) {
				case self::GET:
					$this->prepareGet();
					break;

				case self::POST:
					$this->preparePost();
					break;

				case self::PUT:
					$this->preparePut();
					break;

				case self::DELETE:
					$this->prepareDelete();
					break;
			}

			$this->prepared = true;
		}
	}

	/**
	 * Execute request
	 * @throws Exception
	 * @return \Koldy\Http\Response
	 */
	public function exec() {
		if (!function_exists('curl_init')) {
			throw new Exception('CURL is not installed');
		}

		$this->prepare();

		$ch = curl_init();
		$this->appliedOptions = $this->options;
		curl_setopt_array($ch, $this->options);
		$body = curl_exec($ch);

		if (curl_errno($ch)) {
			throw new Exception(curl_error($ch));
		//} else {
			//$info = curl_getinfo($ch);
		}

		return new Response($ch, $body);
	}

	/**
	 * Make quick GET request
	 *
	 * @param string $url
	 * @param array $params [optional]
	 * @param array $headers [optional]
	 *
	 * @return $this
	 * @example echo \Koldy\Http\Request::get('http://www.google.com') will output body HTML of google.com
	 */
	public static function get($url, array $params = null, $headers = null) {
		$self = new static();
		$self->url($url)->method(self::GET);
		
		if ($params != null) {
			$self->params($params);
		}
		
		if ($headers != null) {
			$self->headers($headers);
		}

		return $self;
	}

	/**
	 * Make quick POST request
	 *
	 * @param string $url
	 * @param array $params [optional]
	 * @param array $headers [optional]
	 *
	 * @return $this
	 * @example echo \Koldy\Http\Request::post('http://www.google.com') will output body HTML of google.com
	 */
	public static function post($url, array $params = null, $headers = null) {
		$self = new static();
		$self->url($url)->method(self::POST);

		if ($params != null) {
			$self->params($params);
		}
		
		if ($headers != null) {
			$self->headers($headers);
		}

		return $self;
	}

	/**
	 * Make quick PUT request
	 *
	 * @param string $url
	 * @param array $params [optional]
	 * @param array $headers [optional]
	 *
	 * @return $this
	 * @example echo \Koldy\Http\Request::put('http://www.google.com') will output body HTML of google.com
	 */
	public static function put($url, array $params = null, $headers = null) {
		$self = new static();
		$self->url($url)->method(self::PUT);
		
		if ($params != null) {
			$self->params($params);
		}
		
		if ($headers != null) {
			$self->headers($headers);
		}

		return $self;
	}

	/**
	 * Make quick DELETE request
	 *
	 * @param string $url
	 * @param array $params [optional]
	 * @param array $headers [optional]
	 *
	 * @return $this
	 * @example echo \Koldy\Http\Request::delete('http://www.google.com') will output body HTML of google.com
	 */
	public static function delete($url, array $params = null, $headers = null) {
		$self = new static();
		$self->url($url)->method(self::DELETE);
		
		if ($params != null) {
			$self->params($params);
		}
		
		if ($headers != null) {
			$self->headers($headers);
		}

		return $self;
	}

	/**
	 * Get (download) file from remote URL and save it on local path
	 *
	 * @param string $remoteUrl
	 * @param string $localPath
	 *
	 * @return \Koldy\Http\Response
	 * @example Request::getFile('http://remote.com/path/to.gif', '/var/www/local/my.gif');
	 */
	public static function getFile($remoteUrl, $localPath) {
		Directory::mkdir(dirname($localPath), 0755);

		$fp = @fopen($localPath, 'wb');
		if (!$fp) {
			throw new Exception("Can not open file for writing: {$localPath}");
		}

		$ch = curl_init($remoteUrl);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 21600); // 6 hours should be enough, orrr??
		curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		fclose($fp);

		return new Response($ch, null);
	}

	/**
	 * @return string
	 */
	public function __toString() {
		$msg = "HTTP REQUEST {$this->getOption(CURLOPT_CUSTOMREQUEST)}={$this->getOption(CURLOPT_URL)} ";

		$constants = get_defined_constants(true);
		$flipped = array_flip($constants['curl']);
		$curlOpts = preg_grep('/^CURLOPT_/', $flipped);
		$curlInfos = preg_grep('/^CURLINFO_/', $flipped);

		$options = array();
		foreach ($this->options as $const => $value) {
			if (isset($curlOpts[$const])) {
				$options[$curlOpts[$const]] = $value;
			} else if (isset($curlInfos[$const])) {
				$options[$curlInfos[$const]] = $value;
			} else {
				$options[$const] = $value;
			}
		}

		$msg .= print_r($options, true);
		return $msg;
	}

}
