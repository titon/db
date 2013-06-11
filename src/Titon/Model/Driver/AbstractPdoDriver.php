<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Dbo;

use Titon\Model\Query;
use Titon\Model\Exception;
use Titon\Model\Query\Clause;
use Titon\Model\Query\Result\PdoResult;
use Titon\Model\Driver\AbstractDriver;
use Titon\Utility\String;
use \PDO;
use \PDOStatement;

/**
 * Implements PDO based driver functionality.
 *
 * @link http://php.net/manual/en/pdo.drivers.php
 *
 * @property \PDO $_connection
 */
abstract class AbstractPdoDriver extends AbstractDriver {

	/**
	 * Configuration.
	 *
	 * @type array
	 */
	protected $_config = [
		'database' => '',
		'host' => 'localhost',
		'port' => '',
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

		$statement = $this->_connection->prepare(call_user_func([$dialect, $method], $query));

		foreach ($this->resolveBinds($query) as $i => $value) {
			$statement->bindValue($i + 1, $value);
		}

		return $statement;
	}

	/**
	 * Connect to the database using PDO.
	 */
	public function connect() {
		$this->disconnect();

		$this->_connection = new PDO($this->getDsn(), $this->getUser(), $this->getPassword(), $this->getFlags() + [
			PDO::ATTR_PERSISTENT => $this->isPersistent(),
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		]);

		$this->_connected = true;
	}

	/**
	 * Escape a value using PDO parameter binding and quoting.
	 *
	 * @param mixed $value
	 * @param int $type
	 * @return mixed
	 */
	public function escape($value, $type = null) {
		if (!$type) {
			if ($value === null) {
				$type = PDO::PARAM_NULL;

			} else if (is_numeric($value)) {
				if (is_float($value) || is_float(floatval($value))) {
					$type = PDO::PARAM_STR; // Floats use string type
					$value = (float) $value;
				} else {
					$type = PDO::PARAM_INT;
					$value = (int) $value;
				}

			} else if (is_bool($value)) {
				$type = PDO::PARAM_BOOL;
				$value = (int) $value; // Turn into 0 and 1

			} else if (is_string($value)) {
				$type = PDO::PARAM_STR;
				$value = (string) $value;
			}
		}

		if ($type) {
			return $this->_connection->quote($value, $type);
		}

		return $value;
	}

	/**
	 * Execute a raw string SQL statement.
	 *
	 * @param string $sql
	 * @return \Titon\Model\Query\Result
	 */
	public function executeSql($sql) {
		$statement = $this->_connection->query($sql);

		$this->_statement = $statement;

		return new PdoResult($statement);
	}

	/**
	 * Execute a Query object. The query will be converted into a PDOStatement beforehand.
	 *
	 * @param \Titon\Model\Query $query
	 * @return \Titon\Model\Query\Result
	 */
	public function executeQuery(Query $query) {
		$statement = $this->buildStatement($query);
		$statement->execute();

		$this->_statement = $statement;

		return new PdoResult($statement);
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
		if ($query instanceof Query) {
			return $this->executeQuery($query);

		} else if (is_string($query)) {
			return $this->executeSql($query);
		}

		throw new Exception('Query must be a raw SQL string or a Titon\Model\Query instance');
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
				$binds[] = $this->escape($value);
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
							$binds[] = $this->escape($value);
						}
					} else {
						$binds[] = $this->escape($param['value']);
					}
				}
			}
		};

		$resolveClause($query->getWhere());
		$resolveClause($query->getHaving());

		return $binds;
	}

}