<?php namespace Koldy\Cache\Driver;

/**
 * This cache driver holds cached data only in request's scope (memory). As soon as request ends, everything will disappear.
 *
 * @link http://koldy.net/docs/cache/request
 */
class Request extends AbstractCacheDriver {

	/**
	 * The array of loaded and/or data that will be stored
	 * 
	 * @var array
	 */
	private $data = array();

	/**
	 * Get the value from the cache by key
	 * 
	 * @param string $key
	 * @return mixed value or null if key doesn't exists or cache is disabled
	 */
	public function get($key) {
		if ($this->has($key)) {
			return $this->data[$key]->data;
		}

		return null;
	}

	/**
	 * Get the array of values from cache by given keys
	 *
	 * @param array $keys
	 *
	 * @return mixed value or null if key doesn't exists or cache is disabled
	 * @link http://koldy.net/docs/cache#get-multi
	 */
	public function getMulti(array $keys) {
		$result = array();

		foreach (array_values($keys) as $key) {
			$result[$key] = $this->get($key);
		}

		return $result;
	}

	/**
	 * Set the cache value by the key
	 * 
	 * @param string $key
	 * @param string $value
	 * @param integer $seconds
	 * @return boolean True if set, null if cache is disabled
	 */
	public function set($key, $value, $seconds = null) {
		$this->data[$key] = $value;
		return true;
	}

	/**
	 * Set multiple values to default cache engine and overwrite if keys already exists
	 *
	 * @param array $keyValuePairs
	 * @param string $seconds [optional] if not set, default is used
	 *
	 * @return boolean True if set
	 * @link http://koldy.net/docs/cache#set-multi
	 */
	public function setMulti(array $keyValuePairs, $seconds = null) {
		foreach ($keyValuePairs as $key => $value) {
			$this->set($key, $value, $seconds);
		}

		return true;
	}

	/**
	 * The will add the value to the cache key only if it doesn't exists yet
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @param integer $seconds
	 * @return boolean True if set, false if it exists and null if cache is not enabled
	 */
	public function add($key, $value, $seconds = null) {
		if ($this->has($key)) {
			return false;
		}

		return $this->set($key, $value, $seconds);
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has($key) {
		return array_key_exists($key, $this->data);
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function delete($key) {
		if ($this->has($key)) {
			unset($this->data[$key]);
		}
		return true;
	}

	/**
	 * Delete multiple items from cache engine
	 *
	 * @param array $keys
	 *
	 * @return boolean True if all item removal requests were returned success, false otherwise
	 * @link http://koldy.net/docs/cache#delete-multi
	 */
	public function deleteMulti(array $keys) {
		foreach (array_values($keys) as $key) {
			$this->delete($key);
		}

		return true;
	}

	/**
	 * Delete all
	 */
	public function deleteAll() {
		$this->data = array();
	}

	/**
	 * @param int $olderThenSeconds
	 */
	public function deleteOld($olderThenSeconds = null) {
		// nothing to do
	}

}
