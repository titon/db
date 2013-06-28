<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver;

use Titon\Model\Driver\AbstractDriver;
use Titon\Model\Driver\Type\AbstractType;
use Titon\Model\Exception\InvalidQueryException;
use Titon\Model\Exception\UnsupportedQueryStatementException;
use Titon\Model\Query;
use Titon\Model\Query\Expr;
use Titon\Model\Query\Predicate;
use Titon\Model\Query\Result\PdoResult;
use Titon\Model\Query\SubQuery;
use Titon\Utility\String;
use \PDO;

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
	 * Current nested transaction depth.
	 *
	 * @type int
	 */
	protected $_transactions = 0;

	/**
	 * {@inheritdoc}
	 *
	 * @throws \Titon\Model\Exception\UnsupportedQueryStatementException
	 */
	public function buildStatement(Query $query) {
		$type = $query->getType();
		$method = 'build' . ucfirst($type);
		$dialect = $this->getDialect();

		if (!method_exists($dialect, $method)) {
			throw new UnsupportedQueryStatementException(sprintf('Query statement %s does not exist or has not been implemented', $type));
		}

		return $this->getConnection()->prepare(call_user_func([$dialect, $method], $query));
	}

	/**
	 * Connect to the database using PDO.
	 *
	 * @return bool
	 */
	public function connect() {
		if ($this->isConnected()) {
			return true;
		}

		$this->_connection = new PDO($this->getDsn(), $this->getUser(), $this->getPassword(), $this->config->flags + [
			PDO::ATTR_PERSISTENT => $this->isPersistent(),
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		]);

		$this->_connected = true;

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function commitTransaction() {
		if ($this->_transactions === 1) {
			$status = $this->getConnection()->commit();
		} else {
			$status = true;
		}

		$this->_transactions--;

		return $status;
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
	 * @throws \Titon\Model\Exception\InvalidQueryException
	 */
	public function query($query, array $params = []) {
		$storage = $this->getStorage();
		$cacheKey = null;
		$cacheLength = null;

		// Determine cache key and lengths
		if ($query instanceof Query) {
			$cacheKey = $query->getCacheKey();
			$cacheLength = $query->getCacheLength();

		} else if (!is_string($query)) {
			throw new InvalidQueryException('Query must be a raw SQL string or a Titon\Model\Query instance');
		}

		// Use the storage engine first
		if ($cacheKey) {
			if ($storage && $storage->has($cacheKey)) {
				return $storage->get($cacheKey);

			// Fallback to driver cache
			// This is used to cache duplicate queries
			} else if ($this->hasCache($cacheKey)) {
				return $this->getCache($cacheKey);
			}
		}

		// Prepare statement and bind parameters
		if ($query instanceof Query) {
			$statement = $this->buildStatement($query);
			$binds = $this->resolveParams($query);

		} else {
			$statement = $this->getConnection()->prepare($query);
			$binds = [];

			foreach ($params as $value) {
				$binds[] = [$value, $this->resolveType($value)];
			}
		}

		foreach ($binds as $i => $value) {
			$statement->bindValue($i + 1, $value[0], $value[1]);
		}

		$statement->params = $binds;

		// Gather and log result
		$this->_result = new PdoResult($statement);

		$this->logQuery($this->_result);

		// Return and cache result
		if ($cacheKey) {
			if ($storage) {
				$storage->set($cacheKey, $this->_result, $cacheLength);
			} else {
				$this->setCache($cacheKey, $this->_result);
			}
		}

		return $this->_result;
	}

	/**
	 * Resolve the bind value and the PDO binding type.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @param array $schema
	 * @return array
	 */
	public function resolveBind($field, $value, array $schema = []) {

		// Don't convert expressions
		if ($value instanceof Expr) {
			return [$value->getValue(), $this->resolveType($value->getValue())];

		// Don't convert null values
		} else if ($value === null) {
			return [null, PDO::PARAM_NULL];

		// Type cast using schema
		} else if (isset($schema[$field]['type'])) {
			$dataType = AbstractType::factory($schema[$field]['type'], $this);

			return [$dataType->to($value), $dataType->getBindingType()];
		}

		// Return scalar type
		return [$value, $this->resolveType($value)];
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
		$schema = $query->getModel()->getSchema()->getColumns();

		// Grab the values from insert and update queries
		if ($type === Query::INSERT || $type === Query::UPDATE) {
			foreach ($query->getFields() as $field => $value) {
				$binds[] = $this->resolveBind($field, $value, $schema);
			}

		} else if ($type === Query::MULTI_INSERT) {
			foreach ($query->getFields() as $record) {
				foreach ($record as $field => $value) {
					$binds[] = $this->resolveBind($field, $value, $schema);
				}
			}
		}

		// Grab values from the where and having predicate
		$driver = $this;
		$resolvePredicate = function(Predicate $predicate) use (&$resolvePredicate, &$binds, $driver) {
			foreach ($predicate->getParams() as $param) {
				if ($param instanceof Predicate) {
					$resolvePredicate($param);

				} else if ($param instanceof Expr) {
					$values = $param->getValue();

					if (is_array($values)) {
						foreach ($values as $value) {
							$binds[] = [$value, $driver->resolveType($value)];
						}

					} else if ($values instanceof SubQuery) {
						$resolvePredicate($values->getWhere());
						$resolvePredicate($values->getHaving());

					} else if ($param->useValue()) {
						$binds[] = [$values, $driver->resolveType($values)];
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

		} else if (is_resource($value)) {
			$type = PDO::PARAM_LOB;

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

	/**
	 * {@inheritdoc}
	 */
	public function rollbackTransaction() {
		if ($this->_transactions === 1) {
			$status = $this->getConnection()->rollBack();

			$this->_transactions = 0;

		} else {
			$status = true;

			$this->_transactions--;
		}

		return $status;
	}

	/**
	 * {@inheritdoc}
	 */
	public function startTransaction() {
		if (!$this->_transactions) {
			$status = $this->getConnection()->beginTransaction();
		} else {
			$status = true;
		}

		$this->_transactions++;

		return $status;
	}

}