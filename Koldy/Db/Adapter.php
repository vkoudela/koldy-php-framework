<?php namespace Koldy\Db;

use PDO;
use PDOException;
use Koldy\Application;
use Koldy\Exception;
use Koldy\Log;

class Adapter {


	/**
	 * The config array used for this connection
	 * 
	 * @var array
	 */
	private $config = null;


	/**
	 * Which config key is used?
	 * 
	 * @var string
	 */
	private $configKey = null;


	/**
	 * @var PDO object
	 */
	public $pdo = null;


	/**
	 * @var string The last executed query
	 */
	private $lastQuery = null;


	/**
	 * Array of last values that were binded to the last query
	 * 
	 * @var array
	 */
	private $lastBindings = null;


	/**
	 * The last error
	 * 
	 * @var string
	 */
	private $lastError = null;


	/**
	 * The last execption
	 * 
	 * @var \PDOException
	 */
	private $lastException = null;


	/**
	 * Construct the adapter with config
	 * 
	 * @param array $config
	 * @param string $configKey [optional] The key from configuration under which config is defined (useful for debugging)
	 */
	public function __construct(array $config, $configKey = null) {
		$this->config = $config;
		$this->configKey = $configKey;
	}


	/**
	 * Try to connect to database with given config block
	 * 
	 * @param array $config
	 * @throws Exception
	 * @throws PDOException
	 */
	private function tryConnect(array $config) {
		switch($config['type']) {
			case 'mysql':
				
				$pdoConfig = array (
					PDO::ATTR_EMULATE_PREPARES => false,
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
					PDO::ATTR_PERSISTENT => $config['persistent'],
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
				);
				
				if (isset($config['driver_options'])) {
					foreach ($config['driver_options'] as $key => $value) {
						$pdoConfig[$key] = $value;
					}
				}

				$this->pdo = new PDO(
					"mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
					$config['username'],
					$config['password'],
					$pdoConfig
				);
				
				break;

				default:
					throw new Exception("Database type '{$config['type']}' is not supported");
					break;
		}
	}


	/**
	 * The PDO will be initialized only if needed, not on adapter initialization
	 * 
	 * @return PDO
	 */
	public function getAdapter() {
		if ($this->pdo === null) {
			try {
				$this->tryConnect($this->config);
			} catch (PDOException $e) {
				$this->lastException = $e;
				$this->lastError = $e->getMessage();
				$this->pdo = null;

				if (isset($this->config['backup_connections']) && is_array($this->config['backup_connections'])) {
					$sizeof = sizeof($this->config['backup_connections']);

					for ($i = 0; $i < $sizeof && $this->pdo === null; $i++) {
						$config = $this->config['backup_connections'][$i];
						if (isset($config['log_error']) && $config['log_error'] === true) {
							Log::error("Error connecting to primary database connection on key={$this->configKey}, will now try backup_connection #{$i} {$config['username']}@{$config['host']}");
							Log::exception($e); // log exception and continue
						} else {
							Log::notice("Error connecting to primary database connection on key={$this->configKey}, will now try backup_connection #{$i} {$config['username']}@{$config['host']}");
						}

						$this->pdo = null;

						if (isset($config['wait_before_connect'])) {
							usleep($config['wait_before_connect'] * 1000);
						}

						try {
							$this->tryConnect($config);
							Log::notice("Connected to backup connection #{$i} ({$config['type']}:{$config['username']}@{$config['host']})");
						} catch (PDOException $e) {
							$this->lastException = $e;
							$this->lastError = $e->getMessage();
							$this->pdo = null;
						}
					}
				}

				if ($this->pdo === null) {
					throw new Exception('Error connecting to database');
				}
			}
		}

		return $this->pdo;
	}


	/**
	 * Execute the query
	 * 
	 * @param string $query
	 * @param array $bindings OPTIONAL, but very recommended
	 * @param integer $fetchMode pass only PDO::FETCH_* constants
	 * @return boolean|int False if query failes; number of affected rows if query passed
	 * 
	 * @link http://koldy.net/docs/database/basics#query
	 * @link http://www.php.net/manual/en/pdo.constants.php
	 */
	public function query($query, array $bindings = null, $fetchMode = null) {
		$sql = is_object($query) ? $query->__toString() : trim($query);
		$this->lastQuery = $sql;
		$this->lastBindings = $bindings;

		$adapter = $this->getAdapter();

		try {
			$stmt = $adapter->prepare($sql);
		} catch (PDOException $e) {
			// the SQL syntax might fail here

			$this->lastException = $e;
			$this->lastError = $e->getMessage();

			if ($query instanceof Query) {
				Log::error("{$e->getMessage()}\n\n{$query->debug()}\n\n{$e->getTraceAsString()}");
			} else {
				Log::error("{$e->getMessage()}\n\n{$sql}\n\n{$e->getTraceAsString()}");
			}

			Log::exception($e);

			return false;
		}

		$stmt->setFetchMode($fetchMode !== null ? $fetchMode : PDO::FETCH_OBJ);
		$logSql = false;

		try {
			if ($bindings === null) {
				$ok = $stmt->execute();
			} else {
				$ok = $stmt->execute($bindings);
			}

			$logSql = true;
		} catch (PDOException $e) {
			$this->lastException = $e;
			$this->lastError = $e->getMessage();

			if ($query instanceof Query) {
				Log::error("Error executing query:\n{$query->debug()}");
			} else {
				Log::error("Error executing query:\n{$sql}");
			}

			Log::exception($e);
			return false;
		}

		$return = null;

		if ($ok) {
			if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
				$return = $stmt->fetchAll();
			} else {
				$return = $stmt->rowCount();
			}
		} else {
			$return = false;
		}

		if (LOG && $logSql) {
			if ($this->configKey === null) {
				Log::sql($this->__toString());
			} else {
				Log::sql("{$this->configKey}>>{$this->__toString()}");
			}
		}

		return $return;
	}


	/**
	 * Get the last executed query with filled parameters in case you used
	 * bindings array. This is useful for debugging. Otherwise, don't use this.
	 * 
	 * @return string
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
	 * 
	 * @return integer
	 */
	public function getLastInsertId() {
		return $this->getAdapter()->lastInsertId();
	}


	/**
	 * Get the last error
	 * 
	 * @return string
	 */
	public function getLastError() {
		return $this->lastError;
	}


	/**
	 * Get last exception
	 * 
	 * @return PDOException
	 */
	public function getLastException() {
		return $this->lastException;
	}


	/**
	 * Close connection
	 * 
	 * @return \Koldy\Db\Adapter
	 */
	public function close() {
		$this->pdo = null;
		return $this;
	}


	/**
	 * Reconnect to server
	 */
	public function reconnect() {
		$this->close()
			->getAdapter()
			->prepare('SELECT 1')
			->execute();
	}


	/**
	 * Begin transaction
	 * 
	 * @return boolean
	 */
	public function beginTransaction() {
		$pdo = $this->getAdapter();
		return (($pdo instanceof \PDO) && $pdo->beginTransaction());
	}


	/**
	 * Commit transaction
	 * 
	 * @return boolean
	 */
	public function commit() {
		$pdo = $this->getAdapter();
		return (($pdo instanceof \PDO) && $pdo->commit());
	}


	/**
	 * Rollback transaction
	 * 
	 * @return boolean
	 */
	public function rollBack() {
		$pdo = $this->getAdapter();
		return (($pdo instanceof \PDO) && $pdo->rollBack());
	}


	/**
	 * If you try to print the adapter object as string, you'll get the last
	 * executed query. Be careful to not to expose the queries to your users in
	 * production mode.
	 * 
	 * @return string
	 */
	public function __toString() {
		return $this->getLastQuery();
	}

}
