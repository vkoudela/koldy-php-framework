<?php namespace Koldy\Cache;

// TODO! CAN NOT TEST; EXTENSION NOT INSTALLED

use \Koldy\Application;
use \Koldy\Log;

class Memcached extends DriverAbstract {

	/**
	 * @var  Memcached
	 */
	protected $memcached = null;

	/**
	 * @var  array The array of loaded and/or data that will be stored
	 */
	private $data = array();

	/**
	 * Is this caching object enabled or not
	 */
	private $enabled = null;

	/**
	 * Construct the object by array of config properties. Config keys are set
	 * in config.cache.php and this array will contain only block for the
	 * requested cache driver. Yes, you can also build this manually, but that
	 * is not recommended.
	 * @param  array $config
	 */
	public function __construct(array $config) {
		$this->config = $config;

		$this->enabled = $config['enabled'];
		if (!$this->enabled) {
			return;
		}

		$this->memcached = new \Memcached();
		$this->memcached->addServers($config['servers']);

		$this->defaultDuration = $config['default_duration'];
	}

	/**
	 * Get the value from the cache by key
	 * @param  string $key
	 * @return  mixed value or null if key doesn't exists or cache is disabled
	 */
	public function get($key) {

	}

	/**
	 * Set the cache value by the key
	 * @param  string $key
	 * @param  string $value
	 * @param  integer $seconds
	 * @return  boolean True if set, null if cache is disabled
	 */
	public function set($key, $value, $seconds = null) {
		if ($seconds === null) {
			$seconds = $this->defaultDuration;
		}
		return $this->memcached->set($key, $value, $seconds);
	}

	/**
	 * The will add the value to the cache key only if it doesn't exists yet
	 * @param  string $key
	 * @param  mixed $value
	 * @param  integer $seconds
	 * @return  boolean True if set, false if it exists and null if cache is
	 * not enabled
	 */
	public function add($key, $value, $seconds = null) {
		if (!$this->enabled) {
			return null;
		}

		if ($this->has($key)) {
			return false;
		}

		return $this->set($key, $value, $seconds);
	}

	/**
	 * This will detect does the cache key exists on file system. If does, it
	 * will automatically detect is still valid or it has expired.
	 * @param  string $key
	 * @return  boolean
	 */
	public function has($key) {

	}

	/**
	 * Deletes the cache file.
	 * @return  boolean True if file is deleted, False if not, null if there is
	 * nothing to delete
	 */
	public function delete($key) {

	}

	/**
	 * Get the value from cache if exists, otherwise, set the value returned
	 * from the function you pass. The function may contain more steps, such as
	 * fetching data from database or etc.
	 * @param  string $key
	 * @param  function $functionOnSet
	 * @param  integer $seconds
	 * @return  mixed
	 */
	public function getOrSet($key, $functionOnSet, $seconds = null) {

	}

}