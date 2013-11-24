<?php namespace Koldy;
/**
 * Use this class for validating POST parameters sent from forms.
 * @author Vlatko Koudela
 * @example
 * 
 * 		$validator = Validator::create(array(
 *			'title' => 'required|min:2|max:255',
 *			'title_seo' => 'min:2|max:255',
 *			'category_id' => 'required|exists:News\Category'
 *		));
 *
 */
class Validator {
	
	/**
	 * The array of fields that are detected as invalid
	 * @var array
	 */
	protected $invalids = array();
	
	/**
	 * The array of valid fields after validation
	 * @var array
	 */
	protected $valids = array();
	
	/**
	 * The array of error messages by error code
	 * @var array
	 */
	protected static $error = array(
		// 0 is success
		1 => 'This field is required',
		2 => 'This field should be longer then {min} characters',
		3 => 'This value should be greater then {min}',
		4 => 'This is not valid IPv4 address',
		5 => 'This should be lower then {max}',
		6 => 'This should be shorter then {max} characters',
		7 => 'This e-mail is invalid',
		8 => 'This value already exists in database',
		9 => 'This value should be the same as value in {name1} field',
		10 =>'This field should be exactly {length} characters longs',
		11 =>'This field must be integer',
		12 =>'This field must send an array of data',
		13 =>'This value should be identical to value in {name2} field',
		14 =>'This value doesn\'t exists in database.',
		15 =>'This value shouldn\'t be the same as {name2}',
		16 =>'Extension has to be one of the following: {extensions}',
		17 =>'Error uploading file.'
	);
	
	/**
	 * The pretaken input variables
	 * @var array
	 */
	private $input = null;
	
	/**
	 * The array of fields that should throw bad request
	 * @var array
	 */
	private $badRequest = array();
	
	public function __construct(array $params) {
		$this->input = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
		foreach ($params as $param => $validators) {
			if ($validators === null) {
				if (isset($this->input[$param])) {
					$v = trim($this->input[$param]);
					if (strlen($v) > 0) {
						$this->valids[$param] = $v;
					} else {
						$this->valids[$param] = null;
					}
				} else {
					$this->valids[$param] = null;
				}
			} else {
				$validators = explode('|', $validators);
				foreach ($validators as $validator) {
					$colonPos = strpos($validator, ':');
					if ($colonPos !== false) {
						$method = substr($validator, 0, $colonPos);
						$settings = substr($validator, $colonPos +1);
					} else {
						$method = $validator;
						$settings = null;
					}

					$method = str_replace(' ', '', ucwords(str_replace('_', ' ', trim($method))));
					$method = "validate{$method}";
				
					$result = $this->$method($param, $settings);
					if ($result !== true) {
						$this->invalids[$param] = $result;
					} else if (isset($this->input[$param])) {
						$value = trim($this->input[$param]);
						$this->valids[$param] = ($value == '') ? null : $value;
					} else {
						$this->valids[$param] = null;
					}
				}
			}
		}
		
		if (sizeof($this->badRequest) > 0) {
			Log::error('Missing parameters: ' . implode(', ', $this->badRequest));
			Application::throwError(400, 'Bad request. Missing parameters');
		}
	}
	
	/**
	 * Shorthand for initializing new Validator object
	 * @param array $params
	 * @return \Koldy\Validator
	 */
	public static function create(array $params) {
		return new static($params);
	}
	
	/**
	 * Set the error messages array
	 * @param array $errorMessages
	 * @see \Validator::$error
	 */
	public static function setMessages(array $errorMessages) {
		static::$error = $errorMessages;
	}
	
	/**
	 * Get the error message according to given code
	 * @param int $code
	 * @param array $params
	 * @return string|NULL
	 */
	protected static function getErrorMessage($code, array $params = null) {
		if (isset(static::$error[$code])) {
			$message = static::$error[$code];
			if ($params !== null) {
				foreach ($params as $key => $value) {
					$message = str_replace("{{$key}}", $value, $message);
				}
			}
			
			return $message;
		}
		
		return null;
	}
	
	/**
	 * Validate if parameter exists or not
	 * @param string $param
	 * @param string $settings
	 * @return bool|string
	 */
	protected function validateRequired($param, $settings = null) {
		if (!isset($this->invalids[$param])) {
			if (!isset($this->input[$param])) {
				$this->badRequest[] = $param;
				return static::getErrorMessage(1);
			} else if (trim($this->input[$param]) == '') {
				return static::getErrorMessage(1);
			}
		}
		
		return true;
	}
	
	// hmmmm
	protected function validateNotEmpty($param, $settings = null) {
		if (isset($this->input[$param]) && trim($this->input[$param]) == '' && !isset($this->invalids[$param])) {
			return static::getErrorMessage(1);
		}
		
		return true;
	}
	
	/**
	 * Throw error if value is lower then given minimum. Works on numerics and strings.
	 * @param string $param
	 * @param string $settings
	 * @return true|string
	 */
	protected function validateMin($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$min = (int) $settings;
			$value = $this->input[$param];
			if (is_numeric($value)) {
				if ($value < $min) {
					return static::getErrorMessage(3, array('min' => $min));
				}
			} else if (strlen($value) < $min) {
				return static::getErrorMessage(2, array('min' => $min));
			}
		}
		
		return true;
	}
	
	/**
	 * Throw error if value is greater/longer then given maximum value/length. Works on numerics and strings.
	 * @param string $param
	 * @param string $settings
	 * @return true|string
	 */
	protected function validateMax($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$max = (int) $settings;
			$value = $this->input[$param];
			if (is_numeric($value)) {
				if ($value > $max) {
					return static::getErrorMessage(5, array('max' => $max));
				}
			} else if (strlen($value) > $max) {
				return static::getErrorMessage(6, array('max' => $max));
			}
		}
		
		return true;
	}
	
	/**
	 * Throw error if string is not long as given length. Works on strings.
	 * @param string $param
	 * @param string $settings
	 * @return true|string
	 */
	protected function validateLength($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$length = (int) $settings;
			$value = $this->input[$param];
			if (strlen($value) != $length) {
				return static::getErrorMessage(10, array('length' => $length));
			}
		}
		
		return true;
	}
	
	/**
	 * Throw error if value is not numeric and not integer.
	 * @param string $param
	 * @param string $settings
	 * @return true|string
	 */
	protected function validateInteger($param, $settings = null) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			if (!is_numeric($this->input[$param]) && !is_int($this->input[$param])) {
				return static::getErrorMessage(11);
			}
		}
		
		return true;
	}
	
	/**
	 * Throw error if input is not the array of fields. This will only check the array not, not the values in array.
	 * @param string $param
	 * @param string $settings
	 * @return true|string
	 */
	protected function validateArray($param, $settings = null) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param])) {
			if (!is_array($this->input[$param])) {
				return static::getErrorMessage(12);
			}
		}
		
		return true;
	}
	
	/**
	 * Validate the IPv4
	 * @param string $param
	 * @param string $settings
	 * @return true|string
	 */
	protected function validateIp($param, $settings = null) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			if (! (bool) preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]?|[0-9])$/', $this->input[$param])) {
				return static::getErrorMessage(4, array('ip' => $this->input[$param]));
			}
		}
		return true;
	}
	
	/**
	 * Validate the email address
	 * @param string $param
	 * @param string $settings
	 * @return true|string
	 */
	protected function validateEmail($param, $settings = null) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			if (! (bool) preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,6})$/', $this->input[$param])) {
				return static::getErrorMessage(7, array('email' => $this->input[$param]));
			}
		}
		return true;
	}
	
	/**
	 * Throw error if value is not unique in database
	 * @param string $param
	 * @param string $settings (Class\Name,uniqueField[,exceptionValue][,exceptionField])
	 * @return true|string
	 */
	protected function validateUnique($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$settings = explode(',', $settings);
			if (sizeof($settings) < 2) {
				Application::throwError(500, 'Bad parameters in Validator::validateUnique method');
			}
			$class = $settings[0];
			$field = $settings[1];
			$exceptionValue = isset($settings[2]) ? $settings[2] : null;
			$exceptionField = isset($settings[3]) ? $settings[3] : null;
			if (!$class::isUnique($field, $this->input[$param], $exceptionValue, $exceptionField)) {
				return static::getErrorMessage(8, array('value' => $this->input[$param]));
			}
		}
		return true;
	}
	
	/**
	 * Throw error if value is does not exists in database
	 * @param string $param
	 * @param string $settings (Class\Name,requiredValue[,fieldToQuery])
	 * @return true|string
	 */
	protected function validateExists($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$settings = explode(',', $settings);
			if (sizeof($settings) < 1) {
				Application::throwError(500, 'Bad parameters in Validator::validateExists method');
			}
			$class = $settings[0];
			$value = $this->input[$param];
			$field = isset($settings[1]) ? $settings[1] : null;
			
			if ($field === null) {
				$r = $class::fetchOne($value);
			} else {
				$r = $class::fetchOne($field, $value);
			}
			
			if ($r === false) {
				return static::getErrorMessage(14, array('value' => $value, 'field' => $field, 'class' => $class));
			}
		}
		return true;
	}
	
	/**
	 * Throw error if this field is not the same as other field in validators
	 * @param string $param
	 * @param string $settings
	 * @return true|string
	 */
	protected function validateSame($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$param2 = $settings;
			if (!isset($this->input[$param2])) {
				return static::getErrorMessage(1);
			}
			
			if ($this->input[$param] != $this->input[$param2]) {
				return static::getErrorMessage(9, array(
					'name1' => $param,
					'name2' => $param2,
					'value1' => $this->input[$param],
					'value2' => $this->input[$param2]
				));
			}
		}
		return true;
	}
	
	/**
	 * Throw error if this field is the same as other field in validators
	 * @param string $param
	 * @param string $settings
	 * @return true|string
	 * @example 'fieldName' => 'not_same:otherField'
	 */
	protected function validateNotSame($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$param2 = $settings;
			if (!isset($this->input[$param2])) {
				return static::getErrorMessage(1);
			}
			
			if ($this->input[$param] == $this->input[$param2]) {
				return static::getErrorMessage(15, array(
					'name1' => $param,
					'name2' => $param2,
					'value1' => $this->input[$param],
					'value2' => $this->input[$param2]
				));
			}
		}
		return true;
	}
	
	/**
	 * Throw error if this field is not identical as other field in validators. Best for validating password inputs.
	 * @param string $param
	 * @param string $settings
	 * @return true|string
	 */
	protected function validateIdentical($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param])) {
			$param2 = $settings;
			if (!isset($this->input[$param2])) {
				return static::getErrorMessage(1);
			}
			
			if ($this->input[$param] !== $this->input[$param2]) {
				return static::getErrorMessage(13, array(
					'name1' => $param,
					'name2' => $param2,
					'value1' => $this->input[$param],
					'value2' => $this->input[$param2]
				));
			}
		}
		return true;
	}
	
	/**
	 * Throw error if given input doesn't have given extension.
	 * If you pass post parameter, then this will be validated on string and second,
	 * if you pass the name of file, then file name will be validated.
	 * 
	 * @param string $param
	 * @param string $settings
	 * @return true|string
	 */
	protected function validateExtensions($param, $settings) {
		if (isset($this->input[$param])) {
			$string = $this->input[$param];
		} else if (isset($_FILES) && isset($_FILES[$param])) {
			$string = $_FILES[$param]['name'];
		} else {
			$string = null;
		}
		
		if ($string !== null) {
			$tmp = explode('.', $string);
			$ext = strtolower(array_pop($tmp));
			$extensions = explode(',', $settings);
			if (!in_array($ext, $extensions)) {
				return static::getErrorMessage(16, array(
					'extensions' => implode(', ', $extensions)
				));
			}
		}
		
		return true;
	}
	
	/**
	 * Throw error if this field is not image or doesn't fit to given contraints
	 * @param string $param
	 * @param string $settings
	 * @return true|string
	 */
	protected function validateImage($param, $settings) {
		$input = $_FILES;
		if (isset($input[$param]) && !isset($this->invalids[$param])) {
			$file = $input[$param];
			if ($file['error'] != 0) {
				return static::getErrorMessage(17);
			}
			
			if (!in_array($file['type'], array('image/jpeg', 'image/gif', 'image/png'))) {
				return static::getErrorMessage(16, array(
					'extensions' => 'jpg, png, gif'
				));
			}
		}
		return true;
	}
	
	/**
	 * Is there any or specific field failed in this validator object?
	 * @param string $field [optional] field name
	 * @return boolean
	 */
	public function failed($field = null) {
		if ($field === null) {
			return (sizeof($this->invalids) > 0);
		} else {
			return isset($this->invalids[$field]);
		}
	}
	
	/**
	 * Get all valid parameters
	 * @return array
	 */
	public function getParams() {
		return $this->valids;
	}
	
	/**
	 * Get all valid parameters as object
	 * @return \stdClass
	 */
	public function getParamsObj() {
		$obj = new \stdClass();
		foreach ($this->valids as $param => $value) {
			$obj->$param = $value;
		}
		
		return $obj;
	}
	
	/**
	 * Get error messages
	 * @return array
	 */
	public function getMessages() {
		return $this->invalids;
	}
	
}