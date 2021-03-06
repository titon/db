<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver;

use Titon\Db\Driver\ResultSet\PdoResultSet;
use Titon\Db\Driver\ResultSet\SqlResultSet;
use Titon\Db\Exception\InvalidQueryException;
use Titon\Db\Exception\MissingDriverException;
use Titon\Db\Exception\UnsupportedQueryStatementException;
use Titon\Db\Repository;
use Titon\Db\Query;
use Titon\Db\Query\Func;
use Titon\Db\Query\RawExpr;
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

        // @codeCoverageIgnoreStart
        if (!$this->isEnabled()) {
            throw new MissingDriverException(sprintf('%s driver extension is not enabled', $this->getDriver()));
        }
        // @codeCoverageIgnoreEnd

        $this->_connections[$this->getContext()] = new PDO($this->getDsn(), $this->getUser(), $this->getPassword(), $this->getConfig('flags') + [
            PDO::ATTR_PERSISTENT => $this->isPersistent(),
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commitTransaction() {
        if ($this->_transactions === 1) {
            $this->logQuery(new SqlResultSet('COMMIT'));

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
    public function describeTable($table) {
        return $this->cacheQuery([__METHOD__, $table], function(AbstractPdoDriver $driver) use ($table) {
            $columns = $driver->executeQuery('SELECT * FROM information_schema.columns WHERE table_schema = ? AND table_name = ?;', [$driver->getDatabase(), $table])->find();
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
                $data = $driver->getType($type)->getDefaultOptions();

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
        }, '+1 year');
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
     * {@inheritdoc}
     *
     * @throws \Titon\Db\Exception\InvalidQueryException
     */
    public function executeQuery($query, array $params = []) {
        $this->connect();

        $cacheKey = null;
        $cacheLength = null;

        // Determine cache key and lengths
        if ($query instanceof Query) {
            $cacheKey = $query->getCacheKey();
            $cacheLength = $query->getCacheLength();

        } else if (!is_string($query)) {
            throw new InvalidQueryException('Query must be a raw SQL string or a Titon\Db\Query instance');
        }

        // Execute and return result
        return $this->_result = $this->cacheQuery($cacheKey, function(AbstractPdoDriver $driver) use ($query, $params) {
            $isQuery = ($query instanceof Query);

            // Prepare statement and bind parameters
            if ($isQuery) {
                $statement = $driver->buildStatement($query);
                $binds = $driver->resolveParams($query);

            } else {
                $statement = $driver->getConnection()->prepare($query);
                $binds = [];

                foreach ($params as $value) {
                    $binds[] = [$value, $driver->resolveType($value)];
                }
            }

            foreach ($binds as $i => $value) {
                $statement->bindValue($i + 1, $value[0], $value[1]);
            }

            $statement->params = $binds;

            // Gather and log result
            if ($isQuery) {
                $result = new PdoResultSet($statement, $query);
            } else {
                $result = new PdoResultSet($statement);
            }

            $driver->logQuery($result);

            return $result;
        }, $cacheLength);
    }

    /**
     * Return the PDO driver name.
     *
     * @return string
     * @codeCoverageIgnore
     */
    abstract public function getDriver();

    /**
     * Format and build the DSN based on the current configuration.
     *
     * @return string
     * @codeCoverageIgnore
     */
    abstract public function getDsn();

    /**
     * {@inheritdoc}
     */
    public function getLastInsertID(Repository $repo) {
        return $this->getConnection()->lastInsertId();
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
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

        return $this->cacheQuery([__METHOD__, $database], function(AbstractPdoDriver $driver) use ($database) {
            $tables = $driver->executeQuery('SELECT * FROM information_schema.tables WHERE table_schema = ?;', [$database])->find();
            $schema = [];

            if (!$tables) {
                return $schema;
            }

            foreach ($tables as $table) {
                $schema[] = $table['TABLE_NAME'];
            }

            return $schema;
        }, '+1 year');
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
        $type = null;

        // Exit early for functions since we don't have a valid field
        if ($field instanceof Func) {
            return [$value, $this->resolveType($value)];
        }

        // Use the raw value for binding
        if ($value instanceof RawExpr) {
            $value = $value->getValue();
        }

        // Type cast
        if ($value === null) {
            $type = PDO::PARAM_NULL;

        } else if (isset($schema[$field]['type'])) {
            $dataType = $this->getType($schema[$field]['type']);
            $value = $dataType->to($value);
            $type = $dataType->getBindingType();
        }

        // Fallback and resolve
        if (!$type) {
            $type = $this->resolveType($value);
        }

        return [$value, $type];
    }

    /**
     * Resolve the list of values that will be required for PDO statement binding.
     *
     * @param \Titon\Db\Query $query
     * @return array
     */
    public function resolveParams(Query $query) {
        $params = [];
        $schema = $query->getRepository()->getSchema()->getColumns();

        foreach ($query->getGroupedBindings() as $groupedBinds) {
            foreach ($groupedBinds as $binds) {
                $params[] = $this->resolveBind($binds['field'], $binds['value'], $schema);
            }
        }

        foreach ($query->getCompounds() as $compound) {
            $params = array_merge($params, $this->resolveParams($compound));
        }

        return $params;
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
            $this->logQuery(new SqlResultSet('ROLLBACK'));

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
            $this->logQuery(new SqlResultSet('BEGIN'));

            $status = $this->getConnection()->beginTransaction();
        } else {
            $status = true;
        }

        $this->_transactions++;

        return $status;
    }

}