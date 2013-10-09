<?php namespace Koldy;

class Cache {

	private static $drivers = null;

	private static $defaultDriver = null;

	private static function init() {
		if (self::$drivers === null) {
			self::$drivers = array();
			$config = Application::getConfig('cache');
			$default = array_keys($config);

			if (!isset($default[0])) {
				Log::error('Can not use cache when there is no drivers defined!');
			}

			self::$defaultDriver = $default[0];
		}
	}

	private static function getDriver($driver = null) {
		if ($driver === null) {
			$driver = self::$defaultDriver;
		}

		if (!isset(self::$drivers[$driver])) {
			$config = Application::getConfig('cache');

			if (!isset($config[$driver])) {
				Log::error("Cache driver '{$driver}' is not defined in config");
				Application::throwError(500, "Cache driver '{$driver}' is not defined in config");
			}

			$config = $config[$driver];
			$className = $config['driver_class'];
			self::$drivers[$driver] = new $className($config);
		}

		return self::$drivers[$driver];
	}

	public static function get($key) {
		self::init();
		return self::getDriver()->get($key);
	}

	public static function set($key, $value, $seconds = null) {
		self::init();
		return self::getDriver()->set($key, $value, $seconds);
	}

	public static function add($key, $value, $seconds = null) {
		self::init();
		return self::getDriver()->add($key, $value, $seconds);
	}

	public static function has($key) {
		self::init();
		return self::getDriver()->has($key);
	}

	public static function delete($key) {
		self::init();
		return self::getDriver()->delete($key);
	}

	public static function getOrSet($key, $functionOnSet, $seconds = null) {
		self::init();
		return self::getDriver()->getOrSet($key, $functionOnSet, $seconds);
	}

	public static function driver($driver) {
		self::init();
		return self::getDriver($driver);
	}
}