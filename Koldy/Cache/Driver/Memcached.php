<?php namespace Koldy\Cache\Driver;

use Koldy\Application;
use Koldy\Exception;

/**
 * The Memcached driver defined in Koldy is using Memcached and not Memcache class. Notice the difference with "d" letter.
 * In order the use this driver, your PHP installation must have Memcached extension available. To be sure, run your
 * phpinfo() and check if Memcache driver is mentioned.
 *
 * @link http://koldy.net/docs/cache/memcached
 */
class Memcached extends AbstractCacheDriver {

	/**
	 * @var Memcached
	 */
	private $memcached = null;

	/**
	 * Construct the object by array of config properties. Config keys are set
	 * in config/cache.php and this array will contain only block for the
	 * requested cache driver. Yes, you can also build this manually, but that
	 * is not recommended.
	 *
	 * @param array $config
	 */
	public function __construct(array $config) {
		if (isset($config['default_duration']) && (int) $config['default_duration'] > 0) {
			$this->defaultDuration = (int) $config['default_duration'];
		}

		$this->config = $config;
	}

	/**
	 * @throws Exception
	 * @return \Memcached
	 */
	protected function getInstance() {
		if ($this->memcached === null) {
			if (!class_exists('\Memcached')) {
				throw new Exception('Memcached class not found');
			}

			$this->memcached = isset($this->config['persistent_id'])
				? new \Memcached($this->config['persistent_id'])
				: new \Memcached();

			if (count($this->config['servers']) == 0) {
				throw new Exception('There are no defined Memcache servers');
			}

			$this->memcached->addServers($this->config['servers']);

			if (isset($this->config['driver_options']) && is_array($this->config['driver_options'])) {
				$this->memcached->setOptions($this->config['driver_options']);
			}
		}

		return $this->memcached;
	}

	/**
	 * Get the key name for the storage into memcached
	 * @param string $key
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function getKeyName($key) {
		$prefixKey = null;
		if (isset($this->config['key'])) {
			$strlen = strlen($this->config['key']);
			if ($strlen > 0 && $strlen < 40) {
				$prefixKey = $this->config['key'];
			}
		}

		if ($prefixKey === null) {
			return Application::getConfig('application', 'key') . '_' . $key;
		} else {
			return $prefixKey . $key;
		}
	}

	/**
	 * Get the value from cache by given key
	 *
	 * @param string $key
	 *
	 * @return mixed value or null if key doesn't exists or cache is disabled
	 * @link http://koldy.net/docs/cache#get
	 */
	public function get($key) {
		$key = $this->getKeyName($key);
		$value = $this->getInstance()->get($key);
		return ($value === false) ? null : $value;
	}

	/**
	 * Set the value to cache identified by key
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param string $seconds [optional] if not set, default is used
	 *
	 * @return boolean True if set, null if cache is disabled
	 * @link http://koldy.net/docs/cache#set
	 */
	public function set($key, $value, $seconds = null) {
		$key = $this->getKeyName($key);
		return $this->getInstance()->set($key, $value, ($seconds === null ? $this->defaultDuration : $seconds));
	}

	/**
	 * Check if item under key name exists. It will return false if item expired.
	 *
	 * @param string $key
	 *
	 * @return boolean
	 * @link http://koldy.net/docs/cache#has
	 */
	public function has($key) {
		$key = $this->getKeyName($key);
		return !($this->getInstance()->get($key) === false);
	}

	/**
	 * Delete the item from cache
	 *
	 * @param string $key
	 *
	 * @return boolean True if file is deleted, False if not, null if there is nothing to delete
	 * @link http://koldy.net/docs/cache#delete
	 */
	public function delete($key) {
		$key = $this->getKeyName($key);
		return $this->getInstance()->delete($key);
	}

	/**
	 * Delete all cached items
	 */
	public function deleteAll() {
		$this->getInstance()->flush();
	}

	/**
	 * Delete all cache items older then ...
	 *
	 * @param int $olderThen [optional] if not set, then default duration is used
	 */
	public function deleteOld($olderThen = null) {
		// won't be implemented - you might potentially have a lot of keys stored and you really don't want to
		// accidentally iterate through it
	}

	/**
	 * Increment number value in cache. This will not work if item expired!
	 *
	 * @param string $key
	 * @param int $howMuch [optional] default 1
	 * @return boolean was it incremented or not
	 * @link http://koldy.net/docs/cache#increment-decrement
	 */
	public function increment($key, $howMuch = 1) {
		$key = $this->getKeyName($key);
		return !($this->getInstance()->increment($key, $howMuch) === false);
	}

	/**
	 * Decrement number value in cache. This will not work if item expired!
	 *
	 * @param string $key
	 * @param int $howMuch [optional] default 1
	 * @return boolean was it incremented or not
	 * @link http://koldy.net/docs/cache#increment-decrement
	 */
	public function decrement($key, $howMuch = 1) {
		$key = $this->getKeyName($key);
		return !($this->getInstance()->decrement($key, $howMuch) === false);
	}

}