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
use Titon\Model\Exception\MissingDriverException;
use Titon\Model\Exception\UnsupportedQueryStatementException;
use Titon\Model\Model;
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
	 * 		@type string $dsn		Custom DSN that would take precedence
	 * }
	 */
	protected $_config = [
		'dsn' => '',
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
	 * @throws \Titon\Model\Exception\MissingDriverException
	 */
	public function connect() {
		if ($this->isConnected()) {
			return true;
		}

		if (!$this->isEnabled()) {
			throw new MissingDriverException(sprintf('%s driver extension is not enabled', $this->getDriver()));
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
	public function describeDatabase($database = null) {
		$database = $database ?: $this->getDatabase();

		return $this->cache([__METHOD__, $database], function() use ($database) {
			$tables = $this->query('SELECT * FROM information_schema.tables WHERE table_schema = ?;', [$database])->fetchAll(false);
			$schema = [];

			if (!$tables) {
				return $schema;
			}

			foreach ($tables as $table) {
				$name = $table['TABLE_NAME'];

				$schema[$name] = [
					'table' => $name,
					'engine' => $table['ENGINE'],
					'format' => $table['ROW_FORMAT'],
					'rows' => $table['TABLE_ROWS'],
					'autoIncrement' => $table['AUTO_INCREMENT'],
					'collate' => $table['TABLE_COLLATION'],
					'comment' => $table['TABLE_COMMENT'],
					'avgRowLength' => $table['AVG_ROW_LENGTH'],
					'dataLength' => $table['DATA_LENGTH'],
					'dataFree' => $table['DATA_FREE'],
					'maxDataLength' => $table['MAX_DATA_LENGTH'],
					'indexLength' => $table['INDEX_LENGTH'],
					'created' => $table['CREATE_TIME'],
					'updated' => $table['UPDATE_TIME']
				];
			}

			return $schema;
		});
	}

	/**
	 * {@inheritdoc}
	 *
	 * @uses Titon\Model\Type\AbstractType
	 */
	public function describeTable($table) {
		return $this->cache([__METHOD__, $table], function() use ($table) {
			$columns = $this->query('SELECT * FROM information_schema.columns WHERE table_schema = ? AND table_name = ?;', [$this->getDatabase(), $table])->fetchAll(false);
			$schema = [];

			if (!$columns) {
				return $schema;
			}

			foreach ($columns as $column) {
				$field = $column['COLUMN_NAME'];
				$type = strtolower($column['COLUMN_TYPE']);
				$length = '';

				// Determine type and length
				if (preg_match('/([a-z]+)(?:\(([0-9,]+)\))?/is', $type, $matches)) {
					$type = $matches[1];

					if (isset($matches[2])) {
						$length = $matches[2];
					}
				}

				// Inherit type defaults
				$data = AbstractType::factory($type, $this)->getDefaultOptions();

				// Overwrite with custom
				$data = [
					'field' => $field,
					'type' => $type,
					'length' => $length,
					'null' => ($column['IS_NULLABLE'] === 'YES'),
				] + $data;

				foreach ([
					'default' => 'COLUMN_DEFAULT',
					'charset' => 'CHARACTER_SET_NAME',
					'collate' => 'COLLATION_NAME',
					'comment' => 'COLUMN_COMMENT'
				] as $key => $search) {
					if (!empty($column[$search])) {
						$data[$key] = $column[$search];
					}
				}

				switch (strtoupper($column['COLUMN_KEY'])) {
					case 'PRI': $data['primary'] = true; break;
					case 'UNI': $data['unique'] = true; break;
					case 'MUL': $data['index'] = true; break;
				}

				if ($column['EXTRA'] === 'auto_increment') {
					$data['ai'] = true;
				}

				$schema[$field] = $data;
			}

			return $schema;
		});
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
	abstract public function getDsn();

	/**
	 * {@inheritdoc}
	 */
	public function getLastInsertID(Model $model) {
		return $this->getConnection()->lastInsertId();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSupportedTypes() {
		return [
			'tinyint' => 'Titon\Model\Driver\Type\IntType',
			'smallint' => 'Titon\Model\Driver\Type\IntType',
			'mediumint' => 'Titon\Model\Driver\Type\IntType',
			'int' => 'Titon\Model\Driver\Type\IntType',
			'integer' => 'Titon\Model\Driver\Type\IntType',
			'bigint' => 'Titon\Model\Driver\Type\BigintType',
			'real' => 'Titon\Model\Driver\Type\FloatType',
			'float' => 'Titon\Model\Driver\Type\FloatType',
			'double' => 'Titon\Model\Driver\Type\DoubleType',
			'decimal' => 'Titon\Model\Driver\Type\DecimalType',
			'boolean' => 'Titon\Model\Driver\Type\BooleanType',
			'date' => 'Titon\Model\Driver\Type\DateType',
			'datetime' => 'Titon\Model\Driver\Type\DatetimeType',
			'timestamp' => 'Titon\Model\Driver\Type\DatetimeType',
			'time' => 'Titon\Model\Driver\Type\TimeType',
			'year' => 'Titon\Model\Driver\Type\YearType',
			'char' => 'Titon\Model\Driver\Type\CharType',
			'varchar' => 'Titon\Model\Driver\Type\StringType',
			'tinytext' => 'Titon\Model\Driver\Type\TextType',
			'mediumtext' => 'Titon\Model\Driver\Type\TextType',
			'text' => 'Titon\Model\Driver\Type\TextType',
			'longtext' => 'Titon\Model\Driver\Type\TextType',
			'tinyblob' => 'Titon\Model\Driver\Type\BlobType',
			'mediumblob' => 'Titon\Model\Driver\Type\BlobType',
			'blob' => 'Titon\Model\Driver\Type\BlobType',
			'longblob' => 'Titon\Model\Driver\Type\BlobType',
			'bit' => 'Titon\Model\Driver\Type\BinaryType',
			'binary' => 'Titon\Model\Driver\Type\BinaryType',
			'varbinary' => 'Titon\Model\Driver\Type\BinaryType',
			'serial' => 'Titon\Model\Driver\Type\SerialType'
		];
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
		if ($query instanceof Query) {
			$this->_result = new PdoResult($statement, $query);

		} else {
			$this->_result = new PdoResult($statement);
		}

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
	 * @uses Titon\Model\Type\AbstractType
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