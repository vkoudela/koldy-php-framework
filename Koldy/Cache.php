<?php namespace Koldy;

/**
 * The cache class.
 *
 * @link http://koldy.net/docs/cache
 */
class Cache {

	/**
	 * The initialized drivers
	 *
	 * @var array
	 */
	protected static $drivers = null;

	/**
	 * The default driver key (the first key from cache array)
	 *
	 * @var string
	 */
	protected static $defaultDriver = null;

	/**
	 * Initialize the cache mechanism
	 */
	protected static function init() {
		if (static::$drivers === null) {
			static::$drivers = array();
			$config = Application::getConfig('cache');
			$default = array_keys($config);

			if (!isset($default[0])) {
				throw new Exception('You\'re trying to use cache without any driver defined in cache config!');
			}

			if (static::$defaultDriver === null) {
				static::$defaultDriver = $default[0];
			} else {
				if (!isset($config[static::$defaultDriver])) {
					Log::warning('Cache driver ' . static::$defaultDriver . ' is not set, using first one (' . $default[0] . ') instead');
					static::$defaultDriver = $default[0];
				}
			}
		}
	}

	/**
	 * Get the cache driver
	 *
	 * @param string $driver [optional]
	 *
	 * @return \Koldy\Cache\Driver\AbstractCacheDriver
	 * @throws \Koldy\Exception
	 */
	protected static function getDriver($driver = null) {
		static::init();
		if ($driver === null) {
			if (static::$defaultDriver === null) {
				throw new Exception('You\'re trying to use cache without any driver defined in cache config!');
			} else {
				$driver = static::$defaultDriver;
			}
		}

		if (isset(static::$drivers[$driver])) {
			return static::$drivers[$driver];
		}

		$config = Application::getAdapterConfig('cache', $driver);

		if (!is_array($config)) {
			Log::warning("Cache driver '{$driver}' doesn't exist");
			static::$drivers[$driver] = new Cache\Driver\DevNull(array());
		} else {
			if (!$config['enabled']) {
				static::$drivers[$driver] = new Cache\Driver\DevNull(array());
			} else {
				$constructor = array();
				$configOptions = $config;

				if (is_string($configOptions)) {
					$otherConfigKey = $configOptions;

					if (!isset($config[$otherConfigKey])) {
						throw new Exception("Cache driver '{$driver}'->'{$otherConfigKey}' is not defined in cache config");
					}

					$constructor = $config[$otherConfigKey];
				} else {
					if (isset($configOptions['options'])) {
						$constructor = $configOptions['options'];
					}
				}

				if (isset($config['module'])) {
					$module = $config['module'];

					if (is_array($module)) {
						foreach ($module as $moduleName) {
							if (is_string($moduleName) && strlen($moduleName) >= 1) {
								Application::registerModule($moduleName);
							} else {
								throw new Exception('Invalid module name in cache driver=' . $driver . ' modules; expected array of strings, got one item with the type of ' . gettype($moduleName));
							}
						}
					} else if (is_string($module) && strlen($module) >= 1) {
						Application::registerModule($module);
					} else {
						throw new Exception('Invalid module name in cache driver=' . $driver . '; expected string or array, got ' . gettype($module));
					}
				}

				$className = $config['driver_class'];

				if (!class_exists($className, true)) {
					throw new Exception("Unknown cache class={$className} under key={$driver}");
				}

				static::$drivers[$driver] = new $className($constructor);
			}
		}

		return static::$drivers[$driver];
	}

	/**
	 * Get the key from default cache engine
	 *
	 * @param string $key
	 *
	 * @return mixed
	 * @link http://koldy.net/docs/cache#get
	 */
	public static function get($key) {
		return static::getDriver()->get($key);
	}

	/**
	 * Get multiple keys from default cache engine
	 *
	 * @param array $keys
	 *
	 * @return mixed[]
	 * @link http://koldy.net/docs/cache#get-multi
	 */
	public static function getMulti(array $keys) {
		return static::getDriver()->getMulti($keys);
	}

	/**
	 * Set the value to default cache engine and overwrite if keys already exists
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $seconds [optional]
	 *
	 * @return boolean true if set
	 * @link http://koldy.net/docs/cache#set
	 */
	public static function set($key, $value, $seconds = null) {
		return static::getDriver()->set($key, $value, $seconds);
	}

	/**
	 * Set multiple values to default cache engine and overwrite if keys already exists
	 *
	 * @param array $keyValuePairs
	 * @param int $seconds [optional]
	 *
	 * @return boolean true if set
	 * @link http://koldy.net/docs/cache#set-multi
	 */
	public static function setMulti(array $keyValuePairs, $seconds = null) {
		return static::getDriver()->setMulti($keyValuePairs, $seconds);
	}

	/**
	 * Add the key to the cache engine only if that key doesn't already exists
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $seconds
	 *
	 * @return true if set, false otherwise
	 * @link http://koldy.net/docs/cache#add
	 */
	public static function add($key, $value, $seconds = null) {
		return static::getDriver()->add($key, $value, $seconds);
	}

	/**
	 * Is there a key under default cache
	 *
	 * @param string $key
	 *
	 * @return boolean
	 * @link http://koldy.net/docs/cache#has
	 */
	public static function has($key) {
		return static::getDriver()->has($key);
	}

	/**
	 * Delete the key from cache
	 *
	 * @param string $key
	 *
	 * @return boolean
	 * @link http://koldy.net/docs/cache#delete
	 */
	public static function delete($key) {
		return static::getDriver()->delete($key);
	}

	/**
	 * Delete multiple keys from cache
	 *
	 * @param array $keys
	 *
	 * @return boolean
	 * @link http://koldy.net/docs/cache#delete-multi
	 */
	public static function deleteMulti(array $keys) {
		return static::getDriver()->deleteMulti($keys);
	}

	/**
	 * Get or set the key's value
	 *
	 * @param string $key
	 * @param \Closure $functionOnSet
	 * @param int $seconds
	 *
	 * @link http://koldy.net/docs/cache#get-or-set
	 * @return mixed
	 */
	public static function getOrSet($key, \Closure $functionOnSet, $seconds = null) {
		return static::getDriver()->getOrSet($key, $functionOnSet, $seconds);
	}

	/**
	 * Increment value in cache
	 *
	 * @param string $key
	 * @param int $howMuch
	 *
	 * @return bool was it incremented or not
	 * @link http://koldy.net/docs/cache#increment-decrement
	 */
	public static function increment($key, $howMuch = 1) {
		return static::getDriver()->increment($key, $howMuch);
	}

	/**
	 * Decrement value in cache
	 *
	 * @param string $key
	 * @param int $howMuch
	 *
	 * @return bool was it decremented or not
	 * @link http://koldy.net/docs/cache#increment-decrement
	 */
	public static function decrement($key, $howMuch = 1) {
		return static::getDriver()->decrement($key, $howMuch);
	}

	/**
	 * Get the cache driver that isn't default
	 *
	 * @param string $driver
	 *
	 * @return \Koldy\Cache\Driver\AbstractCacheDriver
	 * @link http://koldy.net/docs/cache#engines
	 */
	public static function driver($driver) {
		return static::getDriver($driver);
	}

	/**
	 * Does given driver exists (this will also return true if driver is disabled)
	 *
	 * @param string $driver
	 *
	 * @return boolean
	 * @link http://koldy.net/docs/cache#engines
	 */
	public static function hasDriver($driver) {
		return (Application::getConfig('cache', $driver) !== null);
	}

	/**
	 * Is given cache driver enabled or not? If driver is instance of
	 * DevNull, it will also return false so be careful about that
	 *
	 * @param string $driver
	 *
	 * @return boolean
	 */
	public static function isEnabled($driver = null) {
		return !(static::getDriver($driver) instanceof Cache\Driver\DevNull);
	}

}
