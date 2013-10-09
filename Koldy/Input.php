<?php namespace Koldy;

class Input {

	/**
	 * Fetch the value from the resource
	 * @param string $resourceName
	 * @param string $name parameter name
	 * @param string $default [optional] default value if parameter doesn't exists
	 * @param array $allowed [optional] allowed values; if resource value doesn't contain one of values in this array, default is returned
	 * @return string
	 */
	private static function fetch($resourceName, $name, $default = null, array $allowed = null) {
		switch ($resourceName) {
			case 'GET':
				$resource = $_GET;
				break;

			case 'POST':
				if (!isset($_POST)) {
					return $default;
				}

				$resource = $_POST;
				break;
		}

		if (isset($resource[$name])) {
			if (is_array($resource[$name])) {
				return $resource[$name];
			}
			
			$value = trim($resource[$name]);

			if ($value === '') {
				return null;
			}

			if ($allowed !== null) {
				return (in_array($value, $allowed)) ? $value : $default;
			}

			return $value;
		} else {
			return $default;
		}
	}

	/**
	 * Returns the GET parameter
	 * @param string $name
	 * @param string $default
	 * @param array $allowed
	 * @return string
	 */
	public static function get($name = null, $default = null, array $allowed = null) {
		if ($name === null) {
			return $_GET;
		}
		
		return self::fetch('GET', $name, $default, $allowed);
	}

	/**
	 * Returns the POST parameter
	 * @param string $name
	 * @param string $default
	 * @param array $allowed
	 * @return string
	 */
	public static function post($name = null, $default = null, array $allowed = null) {
		if ($name === null) {
			return $_POST;
		}
		
		return self::fetch('POST', $name, $default, $allowed);
	}

	/**
	 * Get the required POST parameters. Return bad request if any of them is missing.
	 * @param variable
	 * @return \stdClass
	 * 
	 * @example
	 * 
	 * 		$params = Input::requirePostParams('id', 'email');
	 * 		echo $params->email;
	 */
	public static function requirePostParams() {
		$params = func_get_args();
		$class = new \stdClass;
		foreach ($params as $param) {
			if (!self::hasPost($param)) {
				if (Application::inDevelopment()) {
					Log::debug("Missing POST parameter {$param}");
				}
				Application::throwError(400, 'Missing one of the parameters');
			}

			$class->$param = self::post($param);
		}

		return $class;
	}

	/**
	 * Validate given parameters and return the array of invalid params.
	 * @param variable
	 * @return array
	 * @example
	 * 
	 * 		$fieldsInError = Input::requireNotEmpty('email', 'first_name');
	 * 		// and then, if first_name is ok and email doesn't has value, the output will be
	 * 		print_r($fieldsInError); // outputs array('email')
	 */
	public static function requireNotEmpty() {
		$params = func_get_args();
		$inError = array();
		foreach ($params as $param) {
			$value = trim(self::post($param));
			if ($value == '' || empty($value)) {
				$inError[] = $param;
			}
		}

		return $inError;
	}
	
	/**
	 * Does GET parameter exists or not
	 * @param string $name
	 * @return boolean
	 */
	public static function hasGet($name) {
		return isset($_GET) && isset($_GET[$name]);
	}

	/**
	 * Does POST parameter exists or not
	 * @param string $name
	 * @return boolean
	 */
	public static function hasPost($name) {
		return isset($_POST) && isset($_POST[$name]);
	}

	/**
	 * Checks is given name in GET or POST parameters. Be aware the GET has priority over POST
	 * @param string $name the parameter name
	 * @return  boolean
	 */
	public static function has($name) {
		return (isset($_GET) && isset($_GET[$name])) || (isset($_POST) && isset($_POST[$name]));
	}
	
	/**
	 * Get the part from nice formated URI
	 * @param int $position
	 * @param string $default [optional]
	 * @return string or $default if not set
	 */
	public static function uriPosition($position, $default = null) {
		$uri = explode('/', Application::getUri());
		array_shift($uri); // because the first value will be always empty
		return isset($uri[$position]) ? $uri[$position] : $default;
	}
}