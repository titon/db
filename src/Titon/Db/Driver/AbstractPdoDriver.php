<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver;

use Titon\Db\Driver\Type\AbstractType;
use Titon\Db\Exception\InvalidQueryException;
use Titon\Db\Exception\MissingDriverException;
use Titon\Db\Exception\UnsupportedQueryStatementException;
use Titon\Db\Table;
use Titon\Db\Query;
use Titon\Db\Query\Expr;
use Titon\Db\Query\Predicate;
use Titon\Db\Query\Result\PdoResult;
use Titon\Db\Query\Result\SqlResult;
use Titon\Db\Query\SubQuery;
use \PDO;

/**
 * Implements PDO based driver functionality.
 *
 * @link http://php.net/manual/en/pdo.drivers.php
 *
 * @package Titon\Db\Driver
 * @method \PDO getConnection()
 */
abstract class AbstractPdoDriver extends AbstractDriver {

    /**
     * Configuration.
     *
     * @type array {
     *      @type string $dsn   Custom DSN that would take precedence
     * }
     */
    protected $_config = [
        'dsn' => ''
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
     * @throws \Titon\Db\Exception\UnsupportedQueryStatementException
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
     * @throws \Titon\Db\Exception\MissingDriverException
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
            $this->logQuery(new SqlResult('COMMIT'));

            $status = $this->getConnection()->commit();
        } else {
            $status = true;
        }

        $this->_transactions--;

        return $status;
    }

    /**
     * {@inheritdoc}
     *
     * @uses Titon\Db\Type\AbstractType
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
    public function getLastInsertID(Table $table) {
        return $this->getConnection()->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedTypes() {
        return [
            'tinyint' => 'Titon\Db\Driver\Type\IntType',
            'smallint' => 'Titon\Db\Driver\Type\IntType',
            'mediumint' => 'Titon\Db\Driver\Type\IntType',
            'int' => 'Titon\Db\Driver\Type\IntType',
            'integer' => 'Titon\Db\Driver\Type\IntType',
            'bigint' => 'Titon\Db\Driver\Type\BigintType',
            'real' => 'Titon\Db\Driver\Type\FloatType',
            'float' => 'Titon\Db\Driver\Type\FloatType',
            'double' => 'Titon\Db\Driver\Type\DoubleType',
            'decimal' => 'Titon\Db\Driver\Type\DecimalType',
            'boolean' => 'Titon\Db\Driver\Type\BooleanType',
            'date' => 'Titon\Db\Driver\Type\DateType',
            'datetime' => 'Titon\Db\Driver\Type\DatetimeType',
            'timestamp' => 'Titon\Db\Driver\Type\DatetimeType',
            'time' => 'Titon\Db\Driver\Type\TimeType',
            'year' => 'Titon\Db\Driver\Type\YearType',
            'char' => 'Titon\Db\Driver\Type\CharType',
            'varchar' => 'Titon\Db\Driver\Type\StringType',
            'tinytext' => 'Titon\Db\Driver\Type\TextType',
            'mediumtext' => 'Titon\Db\Driver\Type\TextType',
            'text' => 'Titon\Db\Driver\Type\TextType',
            'longtext' => 'Titon\Db\Driver\Type\TextType',
            'tinyblob' => 'Titon\Db\Driver\Type\BlobType',
            'mediumblob' => 'Titon\Db\Driver\Type\BlobType',
            'blob' => 'Titon\Db\Driver\Type\BlobType',
            'longblob' => 'Titon\Db\Driver\Type\BlobType',
            'bit' => 'Titon\Db\Driver\Type\BinaryType',
            'binary' => 'Titon\Db\Driver\Type\BinaryType',
            'varbinary' => 'Titon\Db\Driver\Type\BinaryType',
            'serial' => 'Titon\Db\Driver\Type\SerialType'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function listTables($database = null) {
        $database = $database ?: $this->getDatabase();

        return $this->cache([__METHOD__, $database], function() use ($database) {
            $tables = $this->query('SELECT * FROM information_schema.tables WHERE table_schema = ?;', [$database])->fetchAll(false);
            $schema = [];

            if (!$tables) {
                return $schema;
            }

            foreach ($tables as $table) {
                $schema[] = $table['TABLE_NAME'];
            }

            return $schema;
        });
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Titon\Db\Exception\InvalidQueryException
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
            throw new InvalidQueryException('Query must be a raw SQL string or a Titon\Db\Query instance');
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
     * @uses Titon\Db\Type\AbstractType
     *
     * @param string $field
     * @param mixed $value
     * @param array $schema
     * @return array
     */
    public function resolveBind($field, $value, array $schema = []) {
        $type = null;

        // Don't convert expressions
        if ($value instanceof Expr) {
            return [$value->getValue(), $this->resolveType($value->getValue())];

        // Type cast using schema
        } else if ($value !== null && isset($schema[$field]['type'])) {
            $dataType = AbstractType::factory($schema[$field]['type'], $this);
            $value = $dataType->to($value);
            $type = $dataType->getBindingType();
        }

        if ($value === null) {
            $type = PDO::PARAM_NULL;
        } else if (!$type) {
            $type = $this->resolveType($value);
        }

        // Return scalar type
        return [$value, $type];
    }

    /**
     * Resolve the list of values that will be required for PDO statement binding.
     *
     * @param \Titon\Db\Query $query
     * @return array
     */
    public function resolveParams(Query $query) {
        $binds = [];
        $type = $query->getType();
        $schema = $query->getTable()->getSchema()->getColumns();

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
            $this->logQuery(new SqlResult('ROLLBACK'));

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
            $this->logQuery(new SqlResult('BEGIN'));

            $status = $this->getConnection()->beginTransaction();
        } else {
            $status = true;
        }

        $this->_transactions++;

        return $status;
    }

}