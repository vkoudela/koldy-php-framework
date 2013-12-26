<?php namespace Koldy\Db;

use PDO;
use Koldy\Log;

class Adapter {

	/**
	 * The config array used for this connection
	 */
	private $config = null;
	
	private $configKey = null;

	/**
	 * @var PDO object
	 */
	public $pdo = null;

	/**
	 * @var  string The last executed query
	 */
	private $lastQuery = null;

	/**
	 * @var  array of last values that were binded to the last query
	 */
	private $lastBindings = null;

	/**
	 * Construct the adapter with config
	 * @param array $config
	 * @param string $configKey The key from configuration under which config is defined (useful for debugging)
	 */
	public function __construct(array $config, $configKey = null) {
		$this->config = $config;
		$this->configKey = $configKey;
	}

	/**
	 * The PDO will be initialized only if needed, not on adapter initialization
	 * @return PDO
	 */
	public function getAdapter() {
		if ($this->pdo === null) {
			switch($this->config['type']) {
				case 'mysql':
					try {
						$this->pdo = new PDO(
							"mysql:host={$this->config['host']};dbname={$this->config['database']};charset={$this->config['charset']}",
							$this->config['username'],
							$this->config['password'],
							array(
								PDO::ATTR_EMULATE_PREPARES => false,
								PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
							)
						);
					} catch (\PDOException $e) {
						if (\Application::inDevelopment()) {
							throw new \Exception($e->getMessage());
						} else {
							\Application::throwError(500, 'Error connecting to database');
						}
					}
					break;

				default:
					throw new \Exception("Database type '{$this->config['type']}' is not supported");
					break;
			}

			$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
		}

		return $this->pdo;
	}

	/**
	 * Execute the query
	 * @param string $query
	 * @param array $bindings OPTIONAL, but very recommended
	 * @param integer $fetchMode pass only PDO::FETCH_* constants
	 * @link  http://www.php.net/manual/en/pdo.constants.php
	 * @return boolean|int False if query failes; number of affected rows if query passed
	 */
	public function query($query, array $bindings = null, $fetchMode = null) {
		$query = trim($query);
		$this->lastQuery = $query;
		$this->lastBindings = $bindings;

		$adapter = $this->getAdapter();
		$stmt = $adapter->prepare($query);
		$stmt->setFetchMode($fetchMode !== null ? $fetchMode : \PDO::FETCH_OBJ);

		try {
			if ($bindings === null) {
				$ok = $stmt->execute();
			} else {
				$ok = $stmt->execute($bindings);
			}

			if (LOG) {
				if ($this->configKey === null) {
					Log::sql($this->__toString());
				} else {
					Log::sql("{$this->configKey}>>{$this->__toString()}");
				}
			}
		} catch (\PDOException $e) {
			Log::error("{$e->getMessage()}\n{$this->getLastQuery()}\n{$e->getTraceAsString()}");
			return false;
		}

		if ($ok) {
			if (strtoupper(substr($query, 0, 6)) == 'SELECT') {
				return $stmt->fetchAll();
			} else {
				return $stmt->rowCount();
			}
		} else {
			return false;
		}
	}

	/**
	 * Get the last executed query with filled parameters in case you used
	 * bindings array. This is useful for debugging. Otherwise, don't use this.
	 * @return  string
	 */
	public function getLastQuery() {
		if ($this->lastBindings === null) {
			return $this->lastQuery;
		}

		$query = $this->lastQuery;

		if ((bool) count(array_filter(array_keys($this->lastBindings), 'is_string'))) {
			foreach ($this->lastBindings as $key => $value) {
				if (!(is_numeric($value) && substr($value, 0, 1) != '0')) {
					$value = sprintf('\'%s\'', addslashes($value));
				}
				$query = str_replace(':' . $key, $value, $query);
			}
		} else {
			foreach ($this->lastBindings as $value) {
				if (!(is_numeric($value) && substr($value, 0, 1) != '0')) {
					$value = sprintf('\'%s\'', addslashes($value));
				}
				$query = substr_replace($query, $value, strpos($query, '?'), 1);
			}
		}

		return $query;
	}

	/**
	 * If your last query was INSERT on table where you have auto incrementing
	 * field, then you can use this method to fetch the incremented ID
	 * @return  integer
	 */
	public function getLastInsertId() {
		return $this->getAdapter()->lastInsertId();
	}

	/**
	 * If you try to print the adapter object as string, you'll get the last
	 * executed query. Be careful to not to expose the queries to your users in
	 * production mode.
	 * @return  string
	 */
	public function __toString() {
		return $this->getLastQuery();
	}
}