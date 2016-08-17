<?php namespace Koldy;

/**
 * Send e-mail using config/mail.php.
 * 
 * @example
 * 	Mail::create()
 * 		->subject('Test mail')
 * 		->to('some@mail.com')
 * 		->from('your@mail.com')
 * 		->body('Your text')
 * 		->send();
 *
 */
class Mail {

	/**
	 * Array of initialized drivers
	 * 
	 * @var array
	 */
	private static $drivers = null;

	/**
	 * The default driver - key of the first config block in config/mail.php
	 * 
	 * @var string
	 */
	private static $defaultDriver = null;

	/**
	 * Initialize some common stuff on first call
	 */
	private static function init() {
		if (self::$drivers === null) {
			self::$drivers = array();
			$config = Application::getConfig('mail');
			$default = array_keys($config);

			if (!isset($default[0])) {
				throw new Exception('Can not use mail when there is no drivers defined!');
			}

			self::$defaultDriver = $default[0];
		}
	}

	/**
	 * This will create mail driver object for you by the config you pass.
	 * Otherwise, config.mail.php will be used. You should handle the case in
	 * your web app when mail is not enabled so before creating the Mail
	 * object, check it with isEnabled() method. If you're sure that mail will
	 * always be enabled, then you don't need to check this.
	 *
	 * @param null $driver
	 *
	 * @return Mail\Driver\AbstractDriver or false
	 * @throws Exception
	 * @internal param array $config OPTIONAL, default config/mail.php
	 * @example Use this kind of initialization in your controllers
	 *
	 *  if (Mail::isEnabled()) {
	 *    $mail = Mail::create();
	 *  }
	 *
	 *  Or maybe something like this:
	 *
	 *  if ($mail = Mail::create()) {...}
	 *
	 * @link http://koldy.net/docs/mail#create
	 */
	public static function create($driver = null) {
		self::init();

		if ($driver == null) {
			$driver = self::$defaultDriver;
		}

		$config = Application::getAdapterConfig('mail', $driver);

		if ($config === false) {
			Log::error("Mail driver '{$driver}' is not defined in config");
			Application::error(500, "Mail driver '{$driver}' is not defined in config");
			return null;
		}

		if (isset($config['module'])) {
			$module = $config['module'];

			if (is_array($module)) {
				foreach ($module as $moduleName) {
					if (is_string($moduleName) && strlen($moduleName) >= 1) {
						Application::registerModule($moduleName);
					} else {
						throw new Exception('Invalid module name in mail driver=' . $driver . ' modules; expected array of strings, got one item with the type of ' . gettype($moduleName));
					}
				}
			} else if (is_string($module) && strlen($module) >= 1) {
				Application::registerModule($module);
			} else {
				throw new Exception('Invalid module name in mail driver=' . $driver . '; expected string or array, got ' . gettype($module));
			}
		}

		$className = $config['driver_class'];
		if (!class_exists($className, true)) {
			throw new Exception("Can not use mail driver class={$className} under key={$driver}");
		}

		$constructor = isset($config['options']) ? $config['options'] : array();
		return new $className($constructor);
	}

	/**
	 * You can check if configure mail driver is enabled or not.
	 * 
	 * @param string $driver [optional] will use default if not set
	 * @return boolean
	 */
	public static function isEnabled($driver = null) {
		self::init();

		if ($driver === null) {
			$driver = self::$defaultDriver;
		}

		$config = Application::getAdapterConfig('mail', $driver);

		if ($config === false) {
			Log::error("Mail driver '{$driver}' is not defined in config");
			Application::error(500, "Mail driver '{$driver}' is not defined in config");
			return null;
		}

		return $config['enabled'];
	}

}
