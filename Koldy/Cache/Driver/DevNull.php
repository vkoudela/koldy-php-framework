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
