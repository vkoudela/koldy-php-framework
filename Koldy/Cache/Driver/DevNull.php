<?php namespace Koldy\Cache\Driver;

/**
 * If you don't want to use your cache driver, you can redirect all cache data into black whole! Learn more at http://en.wikipedia.org/wiki//dev/null
 * 
 * This class handles the cache driver instance, but using it, nothing will happen. This class will be initialized if you try to use driver that is disabled.
 *
 * @link http://koldy.net/docs/cache/devnull
 */
class DevNull extends AbstractCacheDriver {

	/**
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public function get($key) {
		$this->checkKey($key);
		return null;
	}

	/**
	 * Get the array of values from cache by given keys
	 *
	 * @param array $keys
	 *
	 * @return mixed[]
	 * @link http://koldy.net/docs/cache#get-multi
	 */
	public function getMulti(array $keys) {
		foreach (array_values($keys) as $key) {
			$this->checkKey($key);
		}

		return array();
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param int $seconds
	 *
	 * @return bool
	 */
	public function set($key, $value, $seconds = null) {
		$this->checkKey($key);
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
		foreach (array_keys($keyValuePairs) as $key) {
			$this->checkKey($key);
		}

		return true;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param int $seconds
	 *
	 * @return bool
	 */
	public function add($key, $value, $seconds = null) {
		$this->checkKey($key);
		return true;
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has($key) {
		$this->checkKey($key);
		return false;
	}

	/**
	 * @param string $key
	 *
	 * @return bool|null
	 */
	public function delete($key) {
		$this->checkKey($key);
		return null;
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
			$this->checkKey($key);
		}

		return true;
	}

	/**
	 * Delete all
	 */
	public function deleteAll() {
		// nothing to delete
	}

	/**
	 * @param int $olderThen
	 */
	public function deleteOld($olderThen = null) {
		// nothing to delete
	}

	/**
	 * @param string $key
	 * @param \Closure $functionOnSet
	 * @param int $seconds
	 *
	 * @return mixed
	 */
	public function getOrSet($key, \Closure $functionOnSet, $seconds = null) {
		$this->checkKey($key);
		return call_user_func($functionOnSet);
	}

	/**
	 * @param string $key
	 * @param int $howMuch
	 *
	 * @return bool
	 */
	public function increment($key, $howMuch = 1) {
		$this->checkKey($key);
		return true;
	}

	/**
	 * @param string $key
	 * @param int $howMuch
	 *
	 * @return bool
	 */
	public function decrement($key, $howMuch = 1) {
		$this->checkKey($key);
		return true;
	}

}
