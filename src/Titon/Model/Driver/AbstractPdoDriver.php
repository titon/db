<?php

namespace Titon\Model\Driver\Dbo;

use Titon\Model\Query;
use Titon\Model\Exception;
use Titon\Model\Query\Clause;
use Titon\Model\Result\PdoResult;
use Titon\Model\Driver\AbstractDriver;
use Titon\Utility\String;
use \PDO;
use \PDOStatement;

/**
 * http://php.net/manual/en/pdo.drivers.php
 *
 * @property PDO $_connection
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
		'socket' => '',

		// Driver specific
		'quoteCharacter' => '`'
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
		$statements = $this->mapStatements();
		$type = $query->getType();

		if (empty($statements[$type])) {
			throw new Exception(sprintf('Invalid query type'));
		}

		switch ($type) {
			case Query::CREATE_TABLE:
				$params = [
					'table' => $this->formatTable($query->getTable())
				];
			break;
			case Query::DESCRIBE:
			case Query::TRUNCATE:
				$params = [
					'table' => $this->formatTable($query->getTable())
				];
			break;
			default:
				$params = [
					'table' => $this->formatTable($query->getTable()),
					'fields' => $this->formatFields($query->getFields(), $type),
					'values' => $this->formatValues($query->getFields(), $type),
					'where' => $this->formatWhere($query->getWhere()),
					'groupBy' => $this->formatGroupBy($query->getGroupBy()),
					'having' => $this->formatHaving($query->getHaving()),
					'orderBy' => $this->formatOrderBy($query->getOrderBy()),
					'limit' => $this->formatLimit($query->getLimit(), $query->getOffset()),
				];
			break;
		}

		$statement = $this->_connection->prepare(trim(String::insert($statements[$type], $params)));

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
	 * @return \Titon\Model\Result
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
	 * @return \Titon\Model\Result
	 */
	public function executeQuery(Query $query) {
		$statement = $this->buildStatement($query);
		$statement->execute();

		$this->_statement = $statement;

		return new PdoResult($statement);
	}

	/**
	 * Format the table name.
	 *
	 * @param string $table
	 * @return string
	 */
	public function formatTable($table) {
		return $this->quote($table);
	}

	/**
	 * Format the fields structure depending on the type of query.
	 *
	 * @param array $fields
	 * @param int $type
	 * @return string
	 */
	public function formatFields(array $fields, $type) {
		switch ($type) {
			case Query::INSERT:
				return '(' . $this->quoteFields(array_keys($fields)) . ')';
			break;

			case Query::SELECT:
				return empty($fields) ? '*' : $this->quoteFields($fields);
			break;

			case Query::UPDATE:
				$values = [];

				foreach ($fields as $key => $value) {
					$values[] = $this->quote($key) . ' = ?';
				}

				return implode(', ', $values);
			break;
		}

		return '';
	}

	/**
	 * Format the fields values structure depending on the type of query.
	 *
	 * @param array $fields
	 * @param int $type
	 * @return string
	 */
	public function formatValues(array $fields, $type) {
		switch ($type) {
			case Query::INSERT:
				return sprintf('(%s)', implode(', ', array_fill(0, count($fields), '?')));
			break;
		}

		return '';
	}

	/**
	 * Format the where clause.
	 *
	 * @param \Titon\Model\Query\Clause $where
	 * @return string
	 */
	public function formatWhere(Clause $where) {
		if ($where->getParams()) {
			return sprintf('WHERE %s', $this->formatClause($where));
		}

		return '';
	}

	/**
	 * Format the group by.
	 *
	 * @param array $groupBy
	 * @return string
	 */
	public function formatGroupBy(array $groupBy) {
		if ($groupBy) {
			return sprintf('GROUP BY %s', $this->quoteFields($groupBy));
		}

		return '';
	}

	/**
	 * Format the having clause.
	 *
	 * @param \Titon\Model\Query\Clause $having
	 * @return string
	 */
	public function formatHaving(Clause $having) {
		if ($having->getParams()) {
			return sprintf('HAVING %s', $this->formatClause($having));
		}

		return '';
	}

	/**
	 * Format the order by.
	 *
	 * @param array $orderBy
	 * @return string
	 */
	public function formatOrderBy(array $orderBy) {
		if ($orderBy) {
			$output = [];

			foreach ($orderBy as $field => $direction) {
				$output[] = $this->quote($field) . ' ' . strtoupper($direction);
			}

			return sprintf('ORDER BY %s', implode(', ', $output));
		}

		return '';
	}

	/**
	 * Format the limit and offset.
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return string
	 */
	public function formatLimit($limit, $offset = 0) {
		if ($limit) {
			if ($offset) {
				return sprintf('LIMIT %s,%s', (int) $offset, (int) $limit);
			}

			return sprintf('LIMIT %s', (int) $limit);
		}

		return '';
	}

	/**
	 * Format the clause object by grouping nested clauses and parameters.
	 *
	 * @param \Titon\Model\Query\Clause $clause
	 * @return string
	 */
	public function formatClause(Clause $clause) {
		$output = [];

		foreach ($clause->getParams() as $param) {
			if ($param instanceof Clause) {
				$output[] = sprintf('(%s)', $this->formatClause($param));

			} else {
				$field = $this->quote($param['field']);

				switch ($param['op']) {
					case Clause::IN:
					case Clause::NOT_IN:
						$value = sprintf('%s %s (%s)', $field, $param['op'], implode(', ', array_fill(0, count($param['value']), '?')));
					break;
					case Clause::NULL:
						$value = sprintf('%s IS NULL', $field);
					break;
					case Clause::NOT_NULL:
						$value = sprintf('%s IS NOT NULL', $field);
					break;
					case Clause::BETWEEN:
						$value = sprintf('%s BETWEEN ? AND ?', $field);
					break;
					case Clause::NOT_BETWEEN:
						$value = sprintf('%s NOT BETWEEN ? AND ?', $field);
					break;
					default:
						$value = sprintf('%s %s ?', $field, $param['op']);
					break;
				}

				$output[] = $value;
			}
		}

		return implode(' ' . $clause->getType() . ' ', $output);
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
	 * Return a list of skeleton SQL statements.
	 *
	 * @return array
	 */
	public function mapStatements() {
		return [
			Query::INSERT	=> 'INSERT INTO {table} {fields} VALUES {values}',
			Query::SELECT	=> 'SELECT {fields} FROM {table} {where} {groupBy} {having} {orderBy} {limit}',
			Query::UPDATE	=> 'UPDATE {table} SET {fields} {where} {orderBy} {limit}',
			Query::DELETE	=> 'DELETE FROM {table} {where} {orderBy} {limit}',
			Query::TRUNCATE	=> 'TRUNCATE {table}',
			Query::DESCRIBE	=> 'DESCRIBE {table}',
			Query::EXPLAIN	=> 'EXPLAIN EXTENDED SELECT {options}',

			Query::DROP_TABLE	=> false,

			Query::CREATE_TABLE	=> 'CREATE TABLE {table} ({fields}{indexes}) {params}',

			Query::ALTER_TABLE	=> false,
		];
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
	 * Quote an SQL identifier by wrapping with a driver specific character.
	 *
	 * @param string $value
	 * @return string
	 */
	public function quote($value) {
		$char = $this->config->quoteCharacter;

		return $char . trim($value, $char) . $char;
	}

	/**
	 * Quote an array of identifiers.
	 *
	 * @param array $values
	 * @return string
	 */
	public function quoteFields(array $values) {
		return implode(', ', array_map([$this, 'quote'], $values));
	}

	/**
	 * {@inheritdoc}
	 */
	public function reset() {
		if ($this->_statement instanceof PDOStatement) {
			$this->_statement->closeCursor();
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