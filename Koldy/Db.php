<?php namespace Koldy;

use Koldy\Db\Adapter;

class Db {

	private static $config = null;

	private static $adapter = array();
	
	/**
	 * The array of adapters that will be tried to use. If connection parameters
	 * doesn't exists, the next one will be tried
	 * @var array
	 */
	private static $defaultKeys = null;

	private static $defaultKey = null;
	
	/**
	 * Initialize the config for database(s)
	 */
	public static function init() {
		if (static::$config === null) {
			static::$config = Application::getConfig('database');
			$keys = array_keys(static::$config);
			static::$defaultKey = $keys[0];
		}
	}

	/**
	 * Get adapter. If parameter not set, default is returned
	 * @param string $whatAdapter [optional]
	 * @return \Koldy\Db\Adapter
	 */
	public static function getAdapter($whatAdapter = null) {
		static::init();

		if ($whatAdapter === null) {
			if (static::$defaultKeys === null) {
				$adapter = static::$defaultKey;
			} else {
				$adapter = null;
				foreach (static::$defaultKeys as $adapterName) {
					if ($adapter === null && isset(static::$config[$adapterName])) {
						$adapter = $adapterName;
					}
				}
				
				if ($adapter === null) {
					$adapter = static::$defaultKey;
				}
			}
		} else {
			$adapter = $whatAdapter;
		}
		
		if (!isset(static::$adapter[$adapter])) {
			$config = static::$config[$adapter];
			static::$adapter[$adapter] = new Adapter($config, $adapter);
		}
		
		return static::$adapter[$adapter];
	}
	
	/**
	 * Add adapter manually to the list of registered adapters
	 * @param string $name
	 * @param array $config
	 */
	public static function addAdapter($name, array $config) {
		static::$config[$name] = $config;
	}
	
	/**
	 * Set the array of default adapter keys. This is useful if you're using adapter
	 * keys that sometimes are not registered. In that case, system will try to lookup
	 * for next adapter key.
	 * @param array $defaultKeys
	 */
	public static function setDefaultKeys(array $defaultKeys) {
		static::$defaultKeys = $defaultKeys;
	}
	
	/**
	 * Get the default key
	 * @return string
	 */
	public static function getDefaultKey() {
		return static::$defaultKey;
	}

	/**
	 * Execute the query on default adapter
	 * @param string $query
	 * @param array $bindings
	 * @return mixed
	 */
	public static function query($query, array $bindings = null) {
		$adapter = static::getAdapter();
		return $adapter->query($query, $bindings);
	}

	/**
	 * Get the last executed query
	 * @param string $connection
	 * @return string
	 */
	public static function getLastQuery($connection = null) {
		return static::getAdapter($connection)->getLastQuery();
	}

	/**
	 * Get raw expression string
	 * @param string $expr
	 * @return \Koldy\Db\Expr
	 */
	public static function expr($expr) {
		return new Db\Expr($expr);
	}
}