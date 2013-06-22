<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver;

use Titon\Model\Exception;
use Titon\Model\Driver\AbstractDriver;
use Titon\Model\Driver\Type\AbstractType;
use Titon\Model\Query;
use Titon\Model\Query\Predicate;
use Titon\Model\Query\Result\PdoResult;
use Titon\Utility\String;
use \PDO;
use \PDOStatement;

/**
 * Implements PDO based driver functionality.
 *
 * @link http://php.net/manual/en/pdo.drivers.php
 *
 * @package Titon\Model\Driver
 * @method \PDO getConnection()
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

		$statement = $this->getConnection()->prepare(call_user_func([$dialect, $method], $query));
		$params = $this->resolveParams($query);

		foreach ($params as $i => $value) {
			$statement->bindValue($i + 1, $value[0], $value[1]);
		}

		$statement->params = $params;

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
	 * {@inheritdoc}
	 */
	public function escape($value) {
		if ($value === null) {
			return 'NULL';
		}

		return $this->getConnection()->quote($value, $this->resolveType($value));
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
			}

			if ($encoding = $this->getEncoding()) {
				$params[] = 'charset=' . $encoding;
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

		// Prepare query
		if ($query instanceof Query) {
			$result = $this->buildStatement($query);
		} else {
			$result = $this->getConnection()->prepare($query);
		}

		$result = new PdoResult($result);

		$this->logQuery($result);

		// Return and cache result
		if ($storage) {
			$storage->set($cacheKey, $result, $cacheLength);
		}

		$this->_result = $result;

		return $result;
	}

	/**
	 * Resolve the list of values that will be required for PDO statement binding.
	 *
	 * @param \Titon\Model\Query $query
	 * @return array
	 */
	public function resolveParams(Query $query) {
		$binds = [];
		$type = $query->getType();

		// Grab the values from insert and update queries
		if ($type === Query::INSERT || $type === Query::UPDATE) {
			$schema = $query->getModel()->getSchema()->getColumns();

			foreach ($query->getFields() as $field => $value) {
				$dataType = AbstractType::factory($schema[$field]['type'], $this);

				$binds[] = [$dataType->to($value), $dataType->getBindingType()];
			}
		}

		// Grab values from the where and having predicate
		$driver = $this;
		$resolvePredicate = function(Predicate $predicate) use (&$resolvePredicate, &$binds, $driver) {
			foreach ($predicate->getParams() as $param) {
				if ($param instanceof Predicate) {
					$resolvePredicate($param);

				} else {
					if (is_array($param['value'])) {
						foreach ($param['value'] as $value) {
							$binds[] = [$value, $driver->resolveType($value)];
						}
					} else {
						$binds[] = [$param['value'], $driver->resolveType($param['value'])];
					}
				}
			}
		};

		$resolvePredicate($query->getWhere());
		$resolvePredicate($query->getHaving());

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
			if (is_float($value) || is_double($value)) {
				$type = PDO::PARAM_STR; // Uses string type

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