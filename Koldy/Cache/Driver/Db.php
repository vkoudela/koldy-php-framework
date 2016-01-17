<?php namespace Koldy\Cache\Driver;

use Koldy\Db\Select;
use Koldy\Db\Insert;
use Koldy\Db\Update;
use Koldy\Db\Delete;

/**
 * This cache driver will store your cache data into database.
 * 
 * @link http://koldy.net/docs/cache/db
 */
class Db extends AbstractCacheDriver {

	/**
	 * Construct the database cache adapter
	 * 
	 * @param array $config
	 */
	public function __construct(array $config) {
		// set some defaults if it wasn't set in config

		if (!array_key_exists('connection', $config)) {
			$config['connection'] = null;
		}

		if (!isset($config['table'])) {
			$config['table'] = 'cache';
		}

		parent::__construct($config);
	}

	/**
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public function get($key) {
		$this->checkKey($key);

		$select = new Select($this->config['table']);
		$select->setConnection($this->config['connection']);
		$select->where('id', $key)
			->where('expires_at', '>=', time());

		$record = $select->fetchFirst(\PDO::FETCH_ASSOC);

		if ($record === false) {
			return null;
		} else {
			return unserialize($record['data']);
		}
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param null $seconds
	 *
	 * @return bool
	 * @throws \Koldy\Exception
	 */
	public function set($key, $value, $seconds = null) {
		$this->checkKey($key);

		if ($seconds === null) {
			$seconds = $this->defaultDuration;
		}

		$update = new Update($this->config['table']);
		$update->setConnection($this->config['connection']);
		$ok = $update
			->set('updated_at', gmdate('Y-m-d H:i:s'))
			->set('expires_at', time() + $seconds)
			->set('data', serialize($value))
			->where('id', $key)
			->exec();

		if ($ok === false || ($ok === 0 && !$this->has($key))) {
			$insert = new Insert($this->config['table']);
			$insert->setConnection($this->config['connection']);
			$insert->add(array(
				'id' => $key,
				'updated_at' => gmdate('Y-m-d H:i:s'),
				'expires_at' => time() + $seconds,
				'data' => serialize($value)
			));
			$insert->exec();
		}

		return true;
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has($key) {
		$this->checkKey($key);

		$select = new Select($this->config['table']);
		$select->setConnection($this->config['connection']);
		$select
			->field('expires_at')
			->where('id', $key);

		$cacheRecord = $select->fetchFirst();
		if ($cacheRecord === false) {
			return false;
		}

		return ($cacheRecord['expires_at'] > time());
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 * @throws \Koldy\Exception
	 */
	public function delete($key) {
		$this->checkKey($key);

		$delete = new Delete($this->config['table']);
		$delete->setConnection($this->config['connection']);
		$delete->where('id', $key)
			->exec();

		return true;
	}

	/**
	 * @return array|int
	 * @throws \Koldy\Exception
	 */
	public function deleteAll() {
		$delete = new Delete($this->config['table']);
		return $delete->exec($this->config['connection']);
	}

	/**
	 * @param int $olderThenSeconds
	 */
	public function deleteOld($olderThenSeconds = null) {
		if ($olderThenSeconds === null) {
			$olderThenSeconds = $this->defaultDuration;
		}

		$delete = new Delete($this->config['table']);
		$delete
			->setConnection($this->config['connection'])
			->where('expires_at', '<=', time())
			->exec();
	}

}
