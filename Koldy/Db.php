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
	
	public static function init() {
		if (static::$config === null) {
			static::$config = Application::getConfig('database');
			$keys = array_keys(static::$config);
			static::$defaultKey = $keys[0];
		}
	}

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
	
	public static function addAdapter($name, array $config) {
		static::$config[$name] = $config;
	}
	
	public static function setDefaultKeys(array $defaultKeys) {
		static::$defaultKeys = $defaultKeys;
	}
	
	public static function getDefaultKey() {
		return static::$defaultKey;
	}

	public static function query($query, array $bindings = null) {
		$adapter = static::getAdapter();
		return $adapter->query($query, $bindings);
	}

	public static function getLastQuery($connection = null) {
		return static::getAdapter($connection)->getLastQuery();
	}

	public static function expr($expr) {
		return new Db\Expr($expr);
	}
}