<?php namespace Koldy\Cache;

use \Koldy\Application;
use \Koldy\Log;

class Files extends DriverAbstract {

	/**
	 * @var  string The path to the folder where cache files will be stored
	 */
	protected $path = null;

	/**
	 * @var  array The array of loaded and/or data that will be stored
	 */
	private $data = array();

	/**
	 * Flag if shutdown function is registered or not
	 */
	private $shutdown = false;

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

		if (!isset($config['path']) || $config['path'] === null) {
			$this->path = Application::getStoragePath() . 'cache/';
		} else {
			$this->path = $config['path'];
			if (substr($this->path, -1) != '/') {
				$this->path .= '/';
			}
		}

		if (!is_dir($this->path)) {
			Log::error("Cache directory '{$this->path}' doesn't exists");
		}

		if (isset($config['default_duration'])) {
			$duration = (int) $config['default_duration'];
			if ($duration > 0) {
				$this->defaultDuration = $duration;
			}
		}

		$this->enabled = $config['enabled'];
	}

	/**
	 * Generate path to cache file
	 * @param  string $key
	 * @return  string
	 */
	private function getPath($key) {
		return $this->path . md5($key);
	}

	/**
	 * Load the data from the file and store it in the object for later use
	 * @param  string $key
	 * @return  stdClass or false if cache doesn't exists
	 */
	private function load($key) {
		$path = $this->getPath($key);
		if (is_file($path)) {
			$object = new \stdClass;
			$object->path = $path;

			$file = file_get_contents($path);
			$firstLine = substr($file, 0, strpos($file, "\n"));
			$semicolon = strpos($firstLine, ';');

			$object->created = strtotime(substr($firstLine, 0, $semicolon));
			$object->seconds = substr($firstLine, $semicolon +1);
			$object->data = substr($file, strpos($file, "\n") +1);
			$object->action = null;
			$this->data[$key] = $object;
			return $object;
		}

		return false;
	}

	/**
	 * Get the value from the cache by key
	 * @param  string $key
	 * @return  mixed value or null if key doesn't exists or cache is disabled
	 */
	public function get($key) {
		if (!$this->enabled) {
			return null;
		}

		if ($this->has($key)) {
			return $this->data[$key]->data;
		}

		return null;
	}

	/**
	 * Set the cache value by the key
	 * @param  string $key
	 * @param  string $value
	 * @param  integer $seconds
	 * @return  boolean True if set, null if cache is disabled
	 */
	public function set($key, $value, $seconds = null) {
		if (!$this->enabled) {
			return null;
		}

		if ($seconds === null) {
			$seconds = $this->defaultDuration;
		}

		if (isset($this->data[$key])) {
			$object = $this->data[$key];
		} else {
			$object = new \stdClass;
			$object->path = $this->getPath($key);
		}

		$object->created = time();
		$object->seconds = $seconds;
		$object->data = $value;
		$object->action = 'set';
		$this->data[$key] = $object;

		$this->initShutdown();
		return true;
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
		if (!$this->enabled) {
			return null;
		}

		if (!isset($this->data[$key])) {
			$object = $this->load($key);
			if ($object === false) {
				return false;
			}
		} else {
			$object = $this->data[$key];
		}

		return ($object->created + $object->seconds > time());
	}

	/**
	 * Deletes the cache file.
	 * @return  boolean True if file is deleted, False if not, null if there is
	 * nothing to delete
	 */
	public function delete($key) {
		if (!$this->enabled || !is_file($this->getPath($key))) {
			return null;
		}

		if (isset($this->data[$key])) {
			$this->data[$key]->action = 'delete';
			$this->initShutdown();
			return true;
		} else {
			return @unlink($this->getPath($key));
		}
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
		if ($this->has($key)) {
			return $this->get($key);
		} else {
			$value = $functionOnSet();
			$this->set($key, $value, $seconds);
			return $value;
		}
	}

	/**
	 * Initialize the shutdown function when request execution ends
	 */
	private function initShutdown() {
		if (!$this->shutdown) {
			$this->shutdown = true;
			$self = $this;
			register_shutdown_function(function() use ($self) {
				$self->shutdown();
			});
		}
	}

	/**
	 * Execute this method on request's execution end. When you're working with
	 * cache, the idea is not to work all the time with the filesystem. All
	 * changes (new keys and keys that existis and needs to be deleted) will be
	 * applied here.
	 */
	public function shutdown() {
		foreach ($this->data as $key => $object) {
			switch ($object->action) {
				case 'set':
					@file_put_contents(
						$object->path,
						sprintf("%s;%d\n%s",
							date('r', $object->created),
							$object->seconds,
							$object->data
						)
					);
					break;

				case 'delete':
					@unlink($object->path);
					break;
			}
		}
	}
}