<?php namespace Koldy\Cache\Driver;

use Koldy\Db\Select;
use Koldy\Db\Delete;
use Koldy\Db\Insert;
use Koldy\Exception;
use Koldy\Db\Update;

/**
 * Db driver
 *
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
	 * Get the value from the cache by key
	 * 
	 * @param string $key
	 * @return mixed value or null if key doesn't exists or cache is disabled
	 */
	public function get($key) {
		$select = new Select($this->config['table']);
		$select->setConnection($this->config['connection']);
		$select->where('id', $key)
			->where('expires_at', '>=', time());

		$record = $select->fetchFirst(\PDO::FETCH_ASSOC);

		if ($record === false) {
			return null;
		} else {
			return $record['data'];
		}
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
		if ($seconds === null) {
			$seconds = $this->defaultDuration;
		}

		$update = new Update($this->config['table']);
		$update->setConnection($this->config['connection']);
		$ok = $update
			->set('expires_at', time() + $seconds)
			->set('data', serialize($value))
			->where('id', $key)
			->exec();

		if ($ok === 0) {
			$insert = new Insert($this->config['table']);
			$insert->setConnection($this->config['connection']);
			$insert->add(array(
				'id' => $key,
				'expires_at' => time() + $seconds,
				'data' => serialize($value)
			));
			$insert->exec();
		}

		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see \Koldy\Cache\Driver\AbstractCacheDriver::has()
	 */
	public function has($key) {
		$select = new Select($this->config['table']);
		$select->setConnection($this->config['connection']);
		$select
			->field('id')
			->where('id', $key)
			->where('expires_at', '>=', time());

		return $select->fetchFirst(\PDO::FETCH_ASSOC) !== false;
	}

	/**
	 * (non-PHPdoc)
	 * @see \Koldy\Cache\Driver\AbstractCacheDriver::delete()
	 */
	public function delete($key) {
		$delete = new Delete($this->config['table']);
		$delete->setConnection($this->config['connection']);
		$delete->where('id', $key)
			->exec();

		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see \Koldy\Cache\Driver\AbstractDriver::deleteAll()
	 */
	public function deleteAll() {
		$delete = new Delete($this->config['table']);
		return $delete->exec($this->config['connection']);
	}

	/**
	 * (non-PHPdoc)
	 * @see \Koldy\Cache\Driver\AbstractDriver::deleteOld()
	 */
	public function deleteOld($olderThenSeconds = null) {
		$delete = new Delete($this->config['table']);
		$delete
			->setConnection($this->config['connection'])
			->where('expires_at', '<', time())
			->exec();
	}

}
