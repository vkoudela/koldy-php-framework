<?php namespace Koldy;

class Mail {

	private static $drivers = null;

	/**
	 * @var  string The default driver - key of the first config block
	 * in config.mail.php
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
				Log::error('Can not use mail when there is no drivers defined!');
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
	 * @param array $config OPTIONAL, default config.mail.php
	 * @return \Koldy\Mail\Driver\AbstractDriver or false
	 * @example Use this kind of initialization in your controllers
	 *
	 * 	if (Mail::isEnabled()) {
	 *  	$mail = Mail::create();
	 *  }
	 *
	 *  Or maybe something like this:
	 *
	 *  if ($mail = Mail::create()) {...}
	 */
	public static function create($driver = null) {
		self::init();

		if ($driver === null) {
			$driver = self::$defaultDriver;
		}

		$config = Application::getConfig('mail');

		if (!isset($config[$driver])) {
			Log::error("Mail driver '{$driver}' is not defined in config");
			Application::throwError(500, "Mail driver '{$driver}' is not defined in config");
		}

		$config = $config[$driver];

		if (!$config['enabled']) {
			return false;
		}

		$className = $config['driver_class'];
		return new $className($config);
	}

	/**
	 * Is sending mails enabled or not.
	 * @param array $config The same config as for method create(), but
	 * usually, you won't need to pass anything
	 * @return boolean
	 */
	public static function isEnabled($driver = null) {
		self::init();

		if ($driver === null) {
			$driver = self::$defaultDriver;
		}

		$config = Application::getConfig('mail');

		if (!isset($config[$driver])) {
			Log::error("Mail driver '{$driver}' is not defined in config");
			Application::throwError(500, "Mail driver '{$driver}' is not defined in config");
		}

		return $config[$driver]['enabled'];
	}
}