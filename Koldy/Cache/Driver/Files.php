<?php namespace Koldy\Cache\Driver;

use Koldy\Application;
use Koldy\Directory;
use Koldy\Exception;
use Koldy\Log;

/**
 * This cache driver will store all of your data into files somewhere on the server's filesystem. Every stored key represents one file on filesystem.
 *
 * @link http://koldy.net/docs/cache/files
 */
class Files extends AbstractCacheDriver {

	/**
	 * The path to the folder where cache files will be stored
	 * 
	 * @var string
	 */
	protected $path = null;

	/**
	 * The array of loaded and/or data that will be stored
	 * 
	 * @var array
	 */
	protected $data = array();

	/**
	 * Flag if shutdown function is registered or not
	 * 
	 * @var boolean
	 */
	protected $shutdown = false;

	/**
	 * Construct the object by array of config properties. Config keys are set
	 * in config/cache.php and this array will contain only block for the
	 * requested cache driver. Yes, you can also build this manually, but that
	 * is not recommended.
	 * 
	 * @param array $config
	 * @throws \Koldy\Exception
	 */
	public function __construct(array $config) {
		// because if cache is not enabled, then lets not do anything else

		if (!isset($config['path']) || $config['path'] === null) {
			$this->path = Application::getStoragePath('cache/');
		} else {
			$this->path = $config['path'];
		}
		
		if (substr($this->path, -1) != '/') {
			$this->path .= '/';
		}

		if (!is_dir($this->path) && !Directory::mkdir($this->path, 0777)) {
			throw new Exception("Cache directory '{$this->path}' doesn't exists and can't be created");
		}

		parent::__construct($config);
	}

	/**
	 * Get path to the cache file by $key
	 * 
	 * @param string $key
	 * @return string
	 */
	protected function getPath($key) {
		return $this->path . $key . '_' . md5($key . Application::getConfig('application', 'key'));
	}

	/**
	 * Load the data from the file and store it in this request's memory
	 * 
	 * @param string $key
	 * @return \stdClass or false if cache doesn't exists
	 */
	protected function load($key) {
		$this->checkKey($key);
		$path = $this->getPath($key);

		if (is_file($path)) {
			$object = new \stdClass;
			$object->path = $path;

			$file = file_get_contents($path);
			$firstLine = substr($file, 0, strpos($file, "\n"));
			$firstLine = explode(';', $firstLine);

			$object->created = strtotime($firstLine[0]);
			$object->seconds = $firstLine[1];
			$object->data = substr($file, strpos($file, "\n") +1);
			$object->action = null;
			$object->type = $firstLine[2];

			switch($object->type) {
				case 'array':
				case 'object':
					$object->data = unserialize($object->data);
					break;
			}

			$this->data[$key] = $object;
			return $object;
		}

		return false;
	}

	/**
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public function get($key) {
		$this->checkKey($key);

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
	 * @return mixed[]
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
	 * @param string $key
	 * @param mixed $value
	 * @param int $seconds [optional]
	 *
	 * @return bool
	 */
	public function set($key, $value, $seconds = null) {
		$this->checkKey($key);

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
		$object->type = gettype($value);
		$this->data[$key] = $object;

		$this->initShutdown();
		return true;
	}

	/**
	 * Set multiple values to default cache engine and overwrite if keys already exists
	 *
	 * @param array $keyValuePairs
	 * @param string $seconds [optional] if not set, default is used
	 *
	 * @return boolean True if all keys were successfully stored
	 * @link http://koldy.net/docs/cache#set-multi
	 */
	public function setMulti(array $keyValuePairs, $seconds = null) {
		$allOk = true;
		
		foreach ($keyValuePairs as $key => $value) {
			if (!$this->set($key, $value, $seconds)) {
				$allOk = false;
			}
		}
		
		return $allOk;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param int $seconds [optional]
	 *
	 * @return bool
	 */
	public function add($key, $value, $seconds = null) {
		$this->checkKey($key);

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
		$this->checkKey($key);

		if (!isset($this->data[$key])) {
			$object = $this->load($key);
			if ($object === false) {
				return false;
			}
		} else {
			$object = $this->data[$key];
		}

		$ok = $object->created + $object->seconds > time();
		if (!$ok) {
			$this->data[$key]->action = 'delete';
			$this->initShutdown();
		}

		return $ok;
	}

	/**
	 * Deletes the item from cache engine
	 *
	 * @param string $key
	 * @return boolean True if item was deleted from cache, False otherwise
	 * @link http://koldy.net/docs/cache#delete
	 */
	public function delete($key) {
		$this->checkKey($key);

		if (!is_file($this->getPath($key))) {
			return null;
		}

		if (isset($this->data[$key])) {
			$this->data[$key]->action = 'delete';
			$this->initShutdown();
			// cache files will be deleted after request ends
			return true;
		} else {
			return @unlink($this->getPath($key));
		}
	}

	/**
	 * Delete multiple items from cache engine
	 *
	 * @param array $keys
	 * @return boolean True if all item removal requests were returned success, false otherwise
	 * @link http://koldy.net/docs/cache#delete-multi
	 */
	public function deleteMulti(array $keys) {
		$allOk = true;

		foreach (array_values($keys) as $key) {
			if (!$this->delete($key)) {
				$allOk = false;
			}
		}

		return $allOk;
	}

	/**
	 * Delete all files under cached folder
	 */
	public function deleteAll() {
		Directory::emptyDirectory($this->path);
	}

	/**
	 * @param int $olderThenSeconds
	 */
	public function deleteOld($olderThenSeconds = null) {
		if ($olderThenSeconds === null) {
			$olderThenSeconds = $this->defaultDuration;
		}

		clearstatcache();

		/**
		 * This is probably not good since lifetime is written in file
		 * But going into every file and read might be even worse idea
		 * TODO: Think about this
		 */
		foreach (Directory::read($this->path) as $fullPath => $fileName) {
			$timeCreated = @filemtime($fullPath);
			if ($timeCreated !== false) {
				// successfully red the file modification time

				if (time() - $olderThenSeconds > $timeCreated) {
					// it is old enough to be removed

					if (!@unlink($fullPath)) {
						Log::warning("Can not delete cached file on path {$fullPath}");
					}
				}
			}
		}
	}

	/**
	 * Initialize the shutdown function when request execution ends
	 */
	protected function initShutdown() {
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
	 * changes (new keys and keys that exists and needs to be deleted) will be
	 * applied here, on request shutdown
	 */
	public function shutdown() {
		foreach ($this->data as $key => $object) {
			switch ($object->action) {
				case 'set':
					switch($object->type) {
						default:
							$data = $object->data;
							break;

						case 'array':
						case 'object':
							$data = serialize($object->data);
							break;
					}

					file_put_contents(
						$object->path,
						sprintf("%s;%d;%s\n%s",
							gmdate('r', $object->created),
							$object->seconds,
							$object->type,
							$data
						)
					);
					break;

				case 'delete':
					unlink($object->path);
					break;
			}
		}
	}

}
