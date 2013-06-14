<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver;

use Titon\Model\Query;
use Titon\Model\Exception;
use Titon\Model\Query\Clause;
use Titon\Model\Query\Log;
use Titon\Model\Query\Result\PdoResult;
use Titon\Model\Driver\AbstractDriver;
use Titon\Utility\String;
use \PDO;
use \PDOStatement;

/**
 * Implements PDO based driver functionality.
 *
 * @link http://php.net/manual/en/pdo.drivers.php
 */
abstract class AbstractPdoDriver extends AbstractDriver {

	/**
	 * Configuration.
	 *
	 * @type array {
	 * 		@type string $database	The database name
	 * 		@type string $host		The hostname or IP to connect to
	 * 		@type int $port			The port to connect with
	 * 		@type string $user		Login user name
	 * 		@type string $pass		Login user password
	 * 		@type string $dsn		Custom DSN that would take precedence
	 * 		@type string $socket	Path to unix socket to connect with
	 * }
	 */
	protected $_config = [
		'database' => '',
		'host' => 'localhost',
		'port' => 0,
		'user' => '',
		'pass' => '',
		'dsn' => '',
		'socket' => ''
	];

	/**
	 * The last queried PDOStatement.
	 *
	 * @type \PDOStatement
	 */
	protected $_statement;

	/**
	 * {@inheritdoc}
	 *
	 * @throws \Titon\Model\Exception
	 */
	public function buildStatement(Query $query) {
		$type = $query->getType();
		$method = 'build' . ucfirst($type);
		$dialect = $this->getDialect();

		if (!method_exists($dialect, $method)) {
			throw new Exception(sprintf('Query statement %s does not exist or has not been implemented', $type));
		}

		/** @type \PDOStatement $statement */
		$statement = $this->getConnection()->prepare(call_user_func([$dialect, $method], $query));
		$binds = $this->resolveBinds($query);

		foreach ($binds as $i => $value) {
			$statement->bindValue($i + 1, $value, $this->resolveType($value));
		}

		$statement->params = $binds;

		return $statement;
	}

	/**
	 * Connect to the database using PDO.
	 */
	public function connect() {
		$this->disconnect();

		$this->_connection = new PDO($this->getDsn(), $this->getUser(), $this->getPassword(), $this->config->flags + [
			PDO::ATTR_PERSISTENT => $this->isPersistent(),
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		]);

		$this->_connected = true;
	}

	/**
	 * Return the database name.
	 *
	 * @return string
	 */
	public function getDatabase() {
		return $this->config->database;
	}

	/**
	 * Return the PDO driver name.
	 *
	 * @return string
	 */
	abstract public function getDriver();

	/**
	 * Format and build the DSN based on the current configuration.
	 *
	 * @return string
	 */
	public function getDsn() {
		$dsn = $this->config->dsn;

		if (!$dsn) {
			$params = ['dbname=' . $this->getDatabase()];

			if ($socket = $this->getSocket()) {
				$params[] = 'unix_socket=' . $socket;
			} else {
				$params[] = 'host=' . $this->getHost();
				$params[] = 'port=' . $this->getPort();
				$params[] = 'charset=' . $this->getEncoding();
			}

			$dsn = $this->getDriver() . ':' . implode(';', $params);
		}

		return $dsn;
	}

	/**
	 * Return the database host.
	 *
	 * @return string
	 */
	public function getHost() {
		return $this->config->host;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLastInsertID() {
		return $this->getConnection()->lastInsertId();
	}

	/**
	 * Return the database password.
	 *
	 * @return string
	 */
	public function getPassword() {
		return $this->config->pass;
	}

	/**
	 * Return the database port.
	 *
	 * @return int
	 */
	public function getPort() {
		return $this->config->port;
	}

	/**
	 * Return the database user.
	 *
	 * @return string
	 */
	public function getUser() {
		return $this->config->user;
	}

	/**
	 * Return the unix socket path to connect with.
	 *
	 * @return string
	 */
	public function getSocket() {
		return $this->config->socket;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \Titon\Model\Exception
	 */
	public function query($query) {
		$storage = $this->getStorage();
		$cacheLength = null;
		$startTime = microtime();

		// Check the cache first
		if ($query instanceof Query) {
			$cacheKey = $query->getCacheKey();
			$cacheLength = $query->getCacheLength();

		} else if (is_string($query)) {
			$cacheKey = 'SQL-' . md5($query);

		} else {
			throw new Exception('Query must be a raw SQL string or a Titon\Model\Query instance');
		}

		if ($storage) {
			if ($cache = $storage->get($cacheKey)) {
				return $cache;
			}
		}

		// Execute query
		if ($query instanceof Query) {
			$statement = $this->buildStatement($query);
			$statement->execute();

		} else {
			$statement = $this->getConnection()->query($query);
		}

		// Log the statement
		$statement->startTime = $startTime;

		$this->_statement = $statement;
		$this->logQuery(new Log\PdoLog($statement));

		// Return and cache result
		$result = new PdoResult($statement);

		if ($storage) {
			$storage->set($cacheKey, $result, $cacheLength);
		}

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function reset() {
		if ($this->_statement instanceof PDOStatement) {
			$this->_statement->closeCursor();
			$this->_statement = null;
		}
	}

	/**
	 * Resolve the list of values that will be required for PDO statement binding.
	 *
	 * @param \Titon\Model\Query $query
	 * @return array
	 */
	public function resolveBinds(Query $query) {
		$binds = [];
		$type = $query->getType();

		// Grab the values from insert and update queries
		if ($type === Query::INSERT || $type === Query::UPDATE) {
			foreach ($query->getFields() as $value) {
				$binds[] = $value;
			}
		}

		// Grab values from the where and having clauses
		$resolveClause = function(Clause $clause) use (&$resolveClause, &$binds) {
			foreach ($clause->getParams() as $param) {
				if ($param instanceof Clause) {
					$resolveClause($param);

				} else {
					if (is_array($param['value'])) {
						foreach ($param['value'] as $value) {
							$binds[] = $value;
						}
					} else {
						$binds[] = $param['value'];
					}
				}
			}
		};

		$resolveClause($query->getWhere());
		$resolveClause($query->getHaving());

		return $binds;
	}

	/**
	 * Resolve the value type for PDO parameter binding and quoting.
	 *
	 * @param mixed $value
	 * @return int
	 */
	public function resolveType($value) {
		if ($value === null) {
			$type = PDO::PARAM_NULL;

		} else if (is_numeric($value)) {
			if (is_float($value) || is_float(floatval($value))) {
				$type = PDO::PARAM_STR; // Floats use string type

			} else {
				$type = PDO::PARAM_INT;
			}

		} else if (is_bool($value)) {
			$type = PDO::PARAM_BOOL;

		} else {
			$type = PDO::PARAM_STR;
		}

		return $type;
	}

}