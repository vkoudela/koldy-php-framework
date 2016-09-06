<?php namespace Koldy;

/**
 * Use this class for validating parameters sent from forms.
 * @example
 *
 *    $validator = Validator::create(array(
 *      'title' => 'required|min:2|max:255',
 *      'title_seo' => 'min:2|max:255',
 *      'category_id' => 'required|exists:News\Category'
 *    ));
 *
 * @todo test this 1000 times more and write some docs
 */
class Validator {

	/**
	 * The array of fields that are detected as invalid
	 *
	 * @var array
	 */
	protected $invalids = array();

	/**
	 * The array of valid fields after validation
	 *
	 * @var array
	 */
	protected $valids = array();

	/**
	 * The pretaken input variables
	 *
	 * @var array
	 */
	protected $input = null;

	/**
	 * The array of fields that should throw HTTP bad request
	 *
	 * @var array
	 */
	protected $badRequest = array();

	/**
	 * The array of error messages by error code
	 *
	 * @var array
	 */
	protected static $error = array(
		// 0 is success
		1 => 'This field is required',
		2 => 'This should be at least {min} characters',
		3 => 'This value should be at least {min}',
		4 => 'This is not valid IPv4 address',
		5 => 'This shouldn\'t have more then {max} characters',
		6 => 'This value should be lower then {max}',
		7 => 'This e-mail is invalid',
		8 => 'This value already exists in database',
		9 => 'This value should be the same as value in {name1} field',
		10 => 'This field should be exactly {length} characters longs',
		11 => 'This field must be integer',
		12 => 'This field must send an array of data',
		13 => 'This value should be identical to value in {name2} field',
		14 => 'This value doesn\'t exists in database.',
		15 => 'This value shouldn\'t be the same as {name2}',
		16 => 'Extension has to be one of the following: {extensions}',
		17 => 'Error uploading file.',
		18 => 'This field doesn\'t have requested value.', // {field}, {value}
		19 => 'This value should be decimal',
		20 => 'This mustn\'t have more then {limit} numbers after decimal sign',
		21 => 'This slug contains invalid characters or double dashes',
		22 => 'Invalid hexadecimal number',
		23 => 'Uploaded file size is too big', // {maxSize}, {maxSizeKB}, {maxSizeMB}
		24 => 'Uploaded file size is too small', // {minSize}, {minSizeKB}, {minSizeMB}
		25 => 'File is not an image',
		26 => 'Uploaded image is too small', // {minWidth}, {minHeight}
		27 => 'Uploaded image is too big',
		28 => 'Uploaded image is doesn\'t have square dimensions',
		29 => 'File is required',
		30 => 'CSRF token is not valid'
	);

	/**
	 * Construct the object
	 *
	 * @param array $params array of parameter definitions
	 * @param array $data
	 */
	public function __construct(array $params, array $data = null) {
		$this->input = ($data === null) ? Input::all() : $data;

		// TODO: Prepare parameters before inspection, detect if they need to be taken from request or from $_FILES

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
					if (!isset($this->invalids[$param])) {
						$colonPos = strpos($validator, ':');
						if ($colonPos !== false) {
							$method = substr($validator, 0, $colonPos);
							$settings = substr($validator, $colonPos + 1);
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
							if (!is_array($this->input[$param])) {
								$value = trim($this->input[$param]);
								$this->valids[$param] = ($value == '') ? null : stripslashes($value);
							} else {
								$this->valids[$param] = stripslashes($this->input[$param]);
							}
						} else {
							$this->valids[$param] = null;
						}
					}
				}
			}
		}
	}

	/**
	 * Shorthand for initializing new Validator object
	 *
	 * @param array $params
	 * @param array $data
	 *
	 * @return \Koldy\Validator
	 */
	public static function create(array $params, array $data = null) {
		return new static($params, $data);
	}

	/**
	 * Create Validator class and require exactly the parameters provided in $params, throw exception otherwise.
	 * So, if you require parameters 'first_name' and 'last_name' only, and someone sends 'first_name', 'last_name' and 'email', it'll throw an Exception.
	 * This is highly recommended way of checking input parameters.
	 *
	 * @param array $params
	 * @param array|null $data
	 *
	 * @return Validator
	 * @throws Exception
	 */
	public static function only(array $params, array $data = null) {
		$data = ($data === null) ? Input::all() : $data;

		$countParams = count($params);
		$countData = count($data);
		if ($countParams != $countData) {
			Log::debug("Wrong parameter count, expected {$countParams} parameter(s), got {$countData} parameter(s)");
			Application::error(400);
		}

		foreach (array_keys($data) as $receivedParameter) {
			if (!array_key_exists($receivedParameter, $params)) {
				Log::debug("Detected unwanted parameter {$receivedParameter} in your request; expected only: " . implode(', ', array_keys($params)));
				Application::error(400);
			}
		}

		$static = new static($params, $data);
		$missingParams = $static->getMissingParameters();
		$missingParamsCount = count($missingParams);

		if ($missingParamsCount > 0) {
			Log::debug("Invalid parameters count; there are {$missingParamsCount} missing parameter(s): " . implode(', ', $missingParams));
			Application::error(400);
		}

		return $static;
	}

	/**
	 * Set the error messages array - probably translations
	 *
	 * @param array $errorMessages
	 *
	 * @see \Validator::$error
	 */
	public static function setMessages(array $errorMessages) {
		static::$error = $errorMessages;
	}

	/**
	 * @param int $index
	 * @param string $message
	 */
	public static function setMessage($index, $message) {
		static::$error[$index] = $message;
	}

	/**
	 * Get the error message according to given code
	 *
	 * @param int $code
	 * @param array $params
	 *
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
	 * Validate if parameter exists in this HTTP request or not
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return bool|string
	 */
	protected function validateRequired($param, $settings = null) {
		if (!isset($this->invalids[$param])) {
			if (!isset($this->input[$param])) {
				if (!isset($_FILES) && !isset($_FILES[$param])) {
					$this->badRequest[] = $param;
					return static::getErrorMessage(1);
				}
			} else {
				if (is_array($this->input[$param])) {
					if (sizeof($this->input[$param]) == 0) {
						return static::getErrorMessage(1);
					}
				} else if (trim($this->input[$param]) == '') {
					return static::getErrorMessage(1);
				}
			}
		}

		return true;
	}

	/**
	 * TODO: Review this
	 *
	 * @param $param
	 * @param null $settings
	 *
	 * @return bool|NULL|string
	 * @deprecated validateNotEmpty is deprecated in favour of 'required'
	 */
	protected function validateNotEmpty($param, $settings = null) {
		if (isset($this->input[$param]) && trim($this->input[$param]) == '' && !isset($this->invalids[$param])) {
			return static::getErrorMessage(1);
		}

		return true;
	}

	/**
	 * Throw error if value is lower then given minimum. Works on numeric only.
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 * @example 'number' => 'min:10'
	 */
	protected function validateMin($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$min = (int)$settings;
			$value = $this->input[$param];
			if ($value < $min) {
				return static::getErrorMessage(3, array('min' => $min));
			}
		}

		return true;
	}

	/**
	 * Throw error if string is shorter then given minimum length
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 * @example 'field' => 'minLength:10'
	 */
	protected function validateMinLength($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$min = (int)$settings;
			$value = $this->input[$param];
			if (strlen($value) < $min) {
				return static::getErrorMessage(2, array('min' => $min));
			}
		}

		return true;
	}

	/**
	 * Throw error if value is greater/longer then given maximum value/length. Works on numerics and strings.
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 * @example 'number' => 'max:10000' // will fail if given number is greater then 10000
	 */
	protected function validateMax($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$max = (int)$settings;
			$value = $this->input[$param];
			if ($value > $max) {
				return static::getErrorMessage(6, array('max' => $max));
			}
		}

		return true;
	}

	/**
	 * Throw error if string is longer then given maximum length
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 * @example 'field' => 'maxLength:255'
	 */
	protected function validateMaxLength($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$max = (int)$settings;
			$value = $this->input[$param];
			if (strlen($value) > $max) {
				return static::getErrorMessage(5, array('max' => $max));
			}
		}

		return true;
	}

	/**
	 * Throw error if string is not long as given length. Works on strings.
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 * @example 'field' => 'length:5' // will fail if string doesn't have exactly 5 characters
	 */
	protected function validateLength($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$length = (int)$settings;
			$value = $this->input[$param];
			if (strlen($value) != $length) {
				return static::getErrorMessage(10, array('length' => $length));
			}
		}

		return true;
	}

	/**
	 * Throw error if value is not numeric and not integer.
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 * @example 'number' => 'integer'
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
	 * Throw error if value is not numeric and not decimal. This is good for price points.
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @throws Exception
	 * @return true|string
	 * @example 'number' => 'decimal:2' // - allows numbers with two decimal points
	 */
	protected function validateDecimal($param, $settings = null) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {

			// is it decimal? if there is comma, then we'll need to make it as dot
			$value = str_replace(',', '.', trim($this->input[$param]));

			// but if its not numeric now, then something is wrong
			if (!is_numeric($value)) {
				return static::getErrorMessage(19);
			}

			if ($settings !== null) {
				$settings = explode(',', $settings);
				if (count($settings) > 0) {
					// if there are settings, then on first place, it is the number of digits after dot
					if (!is_numeric($settings[0]) && !is_int($settings[0])) {
						throw new Exception('Invalid setting defined for decimal definition');
					}

					// it should fail if there are more numbers after dot then defined number
					if (strpos($value, '.') !== false) {
						// it will validate only if there is a dot, otherwise, there is nothing to validate
						$decimals = (int)$settings[0];
						$tmp = explode('.', $value);
						if (strlen($tmp[1]) > $decimals) {
							return static::getErrorMessage(20, array(
								'limit' => $decimals
							));
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Throw error if input is not the array of fields. This will only check if parameter is array, not the values in array.
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 */
	protected function validateArray($param, $settings = null) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param])) {
			if ($this->input[$param] != '' && !is_array($this->input[$param])) {
				return static::getErrorMessage(12);
			}
		}

		return true;
	}

	/**
	 * Is given string valid IPv4 address
	 *
	 * @param string $IPv4
	 *
	 * @return boolean
	 * @deprecated Use isIPv4() instead
	 */
	public static function isIp($IPv4) {
		return (bool)preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]?|[0-9])$/', $IPv4);
	}

	/**
	 * @param string $IPv4
	 *
	 * @return boolean
	 */
	public static function isIPv4($IPv4) {
		return static::isIp($IPv4);
	}

	/**
	 * Validate the IPv4
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 */
	protected function validateIp($param, $settings = null) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			if (!static::isIp($this->input[$param])) {
				return static::getErrorMessage(4, array('ip' => $this->input[$param]));
			}
		}
		return true;
	}

	/**
	 * Is given string valid e-mail address?
	 *
	 * @param string $email
	 *
	 * @return boolean
	 * @link http://koldy.net/docs/validators/helpers#is-email
	 */
	public static function isEmail($email) {
		return (bool)preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,63})$/', $email);
	}

	/**
	 * Validate the email address
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 */
	protected function validateEmail($param, $settings = null) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			if (!static::isEmail($this->input[$param])) {
				return static::getErrorMessage(7, array('email' => $this->input[$param]));
			}
		}
		return true;
	}

	/**
	 * Is given variable good formatted "slug".
	 * The "slug" is usually text used in URLs that uniquely defines some object.
	 *
	 * @example this-is-good-formatted-123-slug
	 * @example This-is-NOT-good-formatted-slug--contains-uppercase
	 * @example slug-should never contain any-spaces
	 * @example slug-should-never-contain-any-other-characters-like-šđčćž
	 * @example this--is--bad--slug--because-it-has-double-dashes
	 *
	 * @param String $slug
	 *
	 * @return bool
	 */
	public static function isSlug($slug) {
		return (bool)preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug);
	}

	/**
	 * Validate the URL "slug"
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 */
	protected function validateSlug($param, $settings = null) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			if (!static::isSlug($this->input[$param])) {
				return static::getErrorMessage(21);
			}
		}
		return true;
	}

	/**
	 * Is given string valid hexadecimal number
	 *
	 * @param string $string
	 *
	 * @return bool
	 */
	public static function isHex($string) {
		return ctype_xdigit($string);
	}

	/**
	 * Validate the hexadecimal number
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 * @example 'number' => 'hex'
	 */
	protected function validateHex($param, $settings = null) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			if (!static::isHex($this->input[$param])) {
				return static::getErrorMessage(22);
			}
		}
		return true;
	}

	/**
	 * Throw error if value is not unique in database
	 *
	 * @param string $param
	 * @param string $settings (Class\Name,uniqueField[,exceptionValue][,exceptionField])
	 *
	 * @throws Exception
	 * @return true|string
	 * @example 'email' => 'email|unique:\Db\User,email,my@email.com'
	 * @example 'id' => 'required|integer|min:1', 'email' => 'email|unique:\Db\User,email,field:id,id' // check if email exists in \Db\User model, but exclude ID with value from param 'id'
	 */
	protected function validateUnique($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$settings = explode(',', $settings);

			$settingsCount = count($settings);
			if ($settingsCount < 2) {
				if (LOG && Application::inDevelopment()) {
					Log::debug('validateUnique got only this: ' . print_r($settings, true));
				}
				throw new Exception('Bad parameters count in Validator::validateUnique method; expected at least 2, got ' . $settingsCount);
			}

			/** @var \Koldy\Db\Model $class */
			$class = $settings[0];
			$field = $settings[1];
			$exceptionValue = isset($settings[2]) ? $settings[2] : null;

			if (substr($exceptionValue, 0, 6) == 'field:') {
				$exceptionFieldName = substr($exceptionValue, 6);
				if (isset($this->input[$exceptionFieldName])) {
					$exceptionValue = $this->input[$exceptionFieldName];
				}
			}

			$exceptionField = isset($settings[3]) ? $settings[3] : null;

			if (!$class::isUnique($field, trim($this->input[$param]), $exceptionValue, $exceptionField)) {
				return static::getErrorMessage(8, array('value' => $this->input[$param]));
			}
		}
		return true;
	}

	/**
	 * Throw error if value does not exists in database
	 *
	 * @param string $param
	 * @param string $settings (Class\Name[,fieldToQuery])
	 *
	 * @throws Exception
	 * @return true|string
	 * @example 'user_id' => 'required|integer|exists:\Db\User,id' // e.g. user_id = 5, so this will check if there is record in \Db\User model under id=5
	 */
	protected function validateExists($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$settings = explode(',', $settings);

			if (sizeof($settings) < 1) {
				throw new Exception('Bad parameters in Validator::validateExists method');
			}

			/** @var \Koldy\Db\Model $class */
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
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 * @example 'field1' => 'required', 'field2' => 'same:field1'
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
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 * @example 'fieldName' => 'not_same:otherFieldName'
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
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @throws Exception
	 * @return true|string
	 * @example 'password' => 'required|minLength:8', 'password' => 'identical:password'
	 */
	protected function validateIdentical($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param])) {
			$param2 = $settings;

			if (!array_key_exists($param2, $this->input)) {
				// you must set another field to be compared to
				throw new Exception('Invalid "identical" identifier; field ' . $param2 . ' doesn\'t exists within the request; check your validation rules');
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
	 *
	 * @return true|string
	 *
	 * @example 'file' => 'extensions:gif,jpg,jpeg,txt,csv'
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
	 * Throw error if given input doesn't have given extension.
	 * If you pass post parameter, then this will be validated on string and second,
	 * if you pass the name of file, then file name will be validated.
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 *
	 * @example 'file' => 'fileSize:1024,2048'
	 * @example 'file' => 'fileSize:,2048' or 'fileSize:0,2048'
	 */
	protected function validateFileSize($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			if (isset($_FILES) && isset($_FILES[$param])) {
				$size = $_FILES[$param]['size'];
			} else {
				$size = null;
			}

			if ($size !== null) {
				$settings = explode(',', $settings);

				for ($i = 0; $i < 2; $i++) {
					if (!array_key_exists($i, $settings)) {
						$settings[$i] = 0;
					}
				}

				list($minSize, $maxSize) = $settings;

				$minSize = (int)$minSize;
				$maxSize = (int)$maxSize;

				if ($minSize > 0 && $size < $minSize) {
					return static::getErrorMessage(24, array(
						'minSize' => $minSize,
						'minSizeKB' => round($minSize / 1024),
						'minSizeMB' => round($minSize / 1024 / 1024, 2)
					));
				} else if ($maxSize > 0 && $size > $maxSize) {
					return static::getErrorMessage(23, array(
						'maxSize' => $minSize,
						'maxSizeKB' => round($minSize / 1024),
						'maxSizeMB' => round($minSize / 1024 / 1024, 2)
					));
				}
			}
		}

		return true;
	}

	/**
	 * Throw error if this field is not image or doesn't fit to given constraints
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 *
	 * @example 'image' => 'image'
	 */
	protected function validateImage($param, $settings) {
		$input = $_FILES;
		if (isset($input[$param]) && !isset($this->invalids[$param])) {
			$file = $input[$param];
			if ($file['error'] != 0) {
				return static::getErrorMessage(17);
			}

			if (!in_array($file['type'], array(
					'image/jpeg',
					'image/gif',
					'image/png'
				)) || @getimagesize($file['tmp_name']) === false
			) {
				return static::getErrorMessage(16, array(
					'extensions' => 'jpg, png, gif'
				));
			}
		}
		return true;
	}

	/**
	 * Throw error if given image is not in defined dimensions
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 *
	 * @example 'image' => 'imageSize:minWidth,minHeight,maxWidth,maxHeight'
	 * @example to set just maxWidth: 'imageSize:0,0,800' or 'imageSize:,,800'
	 * @example 'image' => 'imageSize:200,100,2500,2200'
	 */
	protected function validateImageSize($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			if (isset($_FILES) && isset($_FILES[$param])) {
				$path = $_FILES[$param]['tmp_name'];
			} else {
				$path = null;
			}

			if ($path !== null) {
				$info = getimagesize($path);

				if ($info === false) {
					return static::getErrorMessage(25);
				}

				$settings = explode(',', $settings);

				for ($i = 0; $i < 4; $i++) {
					if (!array_key_exists($i, $settings)) {
						$settings[$i] = 0;
					}
				}

				list($width, $height) = $info;
				list($minWidth, $minHeight, $maxWidth, $maxHeight) = $settings;

				$minWidth = (int)$minWidth;
				$minHeight = (int)$minHeight;
				$maxWidth = (int)$maxWidth;
				$maxHeight = (int)$maxHeight;

				if (($minWidth > 0 && $width < $minWidth) || ($minHeight > 0 && $height < $minHeight)) {
					return static::getErrorMessage(26, array(
						'minWidth' => $minWidth,
						'minHeight' => $minHeight
					));
				} else if (($maxWidth > 0 && $width > $maxWidth) || ($maxHeight > 0 && $height > $maxHeight)) {
					return static::getErrorMessage(27, array(
						'maxWidth' => $maxWidth,
						'maxHeight' => $maxHeight
					));
				}
			}
		}

		return true;
	}

	/**
	 * Throw error if uploaded image doesn't have square dimensions / the same width & height
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 *
	 * @example 'image' => 'imageSquare'
	 */
	protected function validateImageSquare($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			if (isset($_FILES) && isset($_FILES[$param])) {
				$path = $_FILES[$param]['tmp_name'];
			} else {
				$path = null;
			}

			if ($path !== null) {
				$info = getimagesize($path);

				if ($info === false) {
					return static::getErrorMessage(25);
				}

				list($width, $height) = $info;

				if ($width != $height) {
					return static::getErrorMessage(28);
				}
			}
		}

		return true;
	}

	/**
	 * Throw error if this field value doesn't have given value
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 * @example 'field' => 'is:yes' // will fail if parameter field doesn't have value "yes"
	 */
	protected function validateIs($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$is = $settings;
			$value = $this->input[$param];
			if ($value != $is) {
				return static::getErrorMessage(18, array(
					'field' => $param,
					'value' => $is
				));
			}
		}

		return true;
	}

	/**
	 * Throw error if this field value doesn't have valid CSRF token. This will throw exception if token is not set previously!
	 *
	 * @param string $param
	 * @param string $settings
	 *
	 * @return true|string
	 * @example 'field' => 'csrf' // will fail if sent CSRF token doesn't match the generated token on server
	 */
	protected function validateCsrf($param, $settings) {
		if (isset($this->input[$param]) && !isset($this->invalids[$param]) && trim($this->input[$param]) != '') {
			$value = $this->input[$param];

			$csrf = Security::getCsrfToken();

			if ($csrf == null) {
				throw new Exception('CSRF token wasn\'t set on server!');
			}

			// the value we got is URL encoded csrf value, so first, let's urldecode and then:
			// the value we got is encrypted cookie value, so first, let's decrypt it it so we can compare it with the value in session
			$value = Crypt::decrypt(urldecode($value));

			if ($value !== $csrf) {
				return static::getErrorMessage(30);
			}
		}

		return true;
	}

	/**
	 * Is there any or specific field failed in this validator object?
	 *
	 * @param string $field [optional] field name
	 *
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
	 *
	 * @return array
	 */
	public function getParams() {
		return $this->valids;
	}

	/**
	 * Get the value from given name
	 *
	 * @param string $field
	 *
	 * @return string or null if parameter doesn't exists
	 */
	public function getParam($field) {
		return (isset($this->valids[$field])) ? $this->valids[$field] : null;
	}

	/**
	 * Get all valid parameters as object
	 *
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
	 *
	 * @return array
	 */
	public function getMessages() {
		return $this->invalids;
	}

	/**
	 * Get the error message for the given field
	 *
	 * @param string $field
	 *
	 * @return string or null if field is not in error
	 */
	public function getMessage($field) {
		return isset($this->invalids[$field]) ? $this->invalids[$field] : null;
	}

	/**
	 * Get the array of missing parameters
	 *
	 * @return array
	 */
	public function getMissingParameters() {
		return $this->badRequest;
	}

}
