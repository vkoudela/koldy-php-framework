<?php namespace Koldy;
/**
 * The cache class.
 * @author vkoudela
 *
 */
class Cache {

	/**
	 * The initialized drivers
	 * @var array
	 */
	protected static $drivers = null;

	/**
	 * The default driver key (the first key from cache array)
	 * @var string
	 */
	protected static $defaultDriver = null;

	/**
	 * Initialize the cache mechanizm
	 */
	protected static function init() {
		if (static::$drivers === null) {
			static::$drivers = array();
			$config = Application::getConfig('cache');
			$default = array_keys($config);

			if (!isset($default[0])) {
				Log::error('Can not use cache when there is no drivers defined!');
			}
			
			var_dump($default[0]);

			static::$defaultDriver = $default[0];
		}
	}

	/**
	 * Get the cache driver
	 * @param string $driver [optional]
	 * @reutrn \Koldy\Cache\DriverAbstract
	 */
	private static function getDriver($driver = null) {
		if ($driver === null) {
			$driver = static::$defaultDriver;
		}
		
		$config = Application::getConfig('cache');
		if (!isset(static::$drivers[$driver])) {
			if (!isset($config[$driver])) {
				Log::error("Cache driver '{$driver}' is not defined in config");
				Application::throwError(500, "Cache driver '{$driver}' is not defined in config");
			}
			
			if (!$config[$driver]['enabled']) {
				return false;
			}

			$config = $config[$driver];
			$className = $config['driver_class'];
			static::$drivers[$driver] = new $className($config);
		} else if (!$config[$driver]['enabled']) {
			return false;
		}

		return static::$drivers[$driver];
	}

	/**
	 * Get the key from default cache driver
	 * @param string $key
	 * @return mixed
	 */
	public static function get($key) {
		static::init();
		return static::getDriver()->get($key);
	}

	/**
	 * Set the value to default cache
	 * @param string $key
	 * @param mixed $value
	 * @param int $seconds
	 * @return true if set
	 */
	public static function set($key, $value, $seconds = null) {
		static::init();
		return static::getDriver()->set($key, $value, $seconds);
	}

	/**
	 * Add the key to the cache
	 * @param string $key
	 * @param mixed $value
	 * @param int $seconds
	 * @return true if set
	 */
	public static function add($key, $value, $seconds = null) {
		static::init();
		return static::getDriver()->add($key, $value, $seconds);
	}

	/**
	 * Is there a key under default cache
	 * @param string $key
	 * @return boolean
	 */
	public static function has($key) {
		static::init();
		return static::getDriver()->has($key);
	}

	/**
	 * Delete the key from cache
	 * @param string $key
	 * @return boolean
	 */
	public static function delete($key) {
		static::init();
		return static::getDriver()->delete($key);
	}

	/**
	 * Get or set the key's value
	 * @param string $key
	 * @param function $functionOnSet
	 * @param int $seconds
	 */
	public static function getOrSet($key, $functionOnSet, $seconds = null) {
		static::init();
		return static::getDriver()->getOrSet($key, $functionOnSet, $seconds);
	}

	/**
	 * Get the cache driver that isn't default
	 * @param string $driver
	 * @return \Koldy\Cache\DriverAbstract
	 */
	public static function driver($driver) {
		static::init();
		return static::getDriver($driver);
	}
	
	/**
	 * Does given driver exists
	 * @param string $driver
	 * @return boolean
	 */
	public static function hasDriver($driver) {
		return (static::getDriver($driver) !== false);
	}
}