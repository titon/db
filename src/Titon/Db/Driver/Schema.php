<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver;

use Titon\Db\Exception\InvalidArgumentException;
use Titon\Db\Exception\MissingColumnException;

/**
 * Represents a database table schema and provides mapping for columns, indexes, types and constraints.
 *
 * @package Titon\Db\Driver
 */
class Schema {

    /**
     * Table columns.
     *
     * @type array
     */
    protected $_columns = [];

    /**
     * Foreign keys mappings.
     *
     * @type array
     */
    protected $_foreignKeys = [];

    /**
     * Table indexes.
     *
     * @type array
     */
    protected $_indexes = [];

    /**
     * List of table options.
     *
     * @type array
     */
    protected $_options = [];

    /**
     * Primary key mapping.
     *
     * @type array
     */
    protected $_primaryKey = [];

    /**
     * Table name.
     *
     * @type string
     */
    protected $_table;

    /**
     * Unique keys mappings.
     *
     * @type array
     */
    protected $_uniqueKeys = [];

    /**
     * Set the table and add optional columns.
     *
     * @param string $table
     * @param array $columns
     */
    public function __construct($table, array $columns = []) {
        $this->_table = $table;

        $this->addColumns($columns);
    }

    /**
     * Add a column to the table schema.
     *
     * @param string $column
     * @param array $options {
     *      @type string $type      The column data type (one of Titon\Db\Driver\Type)
     *      @type int $length       The column data length
     *      @type mixed $default    The default value
     *      @type string $comment   The comment
     *      @type string $charset   The character set for encoding
     *      @type string $collation The collation set
     *      @type bool $null        Does the column allow nulls
     *      @type bool $ai          Is this an auto incrementing column
     *      @type mixed $index      Is this an index
     *      @type mixed $primary    Is this a primary key
     *      @type mixed $unique     Is this a unique key
     *      @type mixed $foreign    Is this a foreign key
     * }
     * @return $this
     */
    public function addColumn($column, $options) {
        if (is_string($options)) {
            $options = ['type' => $options];
        }

        $options = $options + [
            'field' => $column,
            'type' => '',
            'length' => '',
            'default' => '',
            'comment' => '',
            'charset' => '',
            'collate' => '',
            'null' => true,
            'ai' => false,
            'index' => false,        // KEY index (field[, field])
            'primary' => false,      // [CONSTRAINT symbol] PRIMARY KEY (field[, field])
            'unique' => false,       // [CONSTRAINT symbol] UNIQUE KEY index (field[, field])
            'foreign' => false       // [CONSTRAINT symbol] FOREIGN KEY (field) REFERENCES table(field) [ON DELETE CASCADE, etc]
        ];

        // Force to NOT NULL for primary or auto increment columns
        if ($options['primary'] || $options['ai']) {
            $options['null'] = false;
        }

        // Filter out values so that type defaults can be inherited
        $this->_columns[$column] = array_filter($options, function($value) {
            return ($value !== '' && $value !== false);
        });

        if ($options['primary']) {
            $this->addPrimary($column, $options['primary']);

        } else if ($options['unique']) {
            $this->addUnique($column, $options['unique']);

        } else if ($options['foreign']) {
            $this->addForeign($column, $options['foreign']);
        }

        if ($options['index']) {
            $this->addIndex($column, $options['index']);
        }

        return $this;
    }

    /**
     * Add multiple columns. Index is the column name, the value is the array of options.
     *
     * @param array $columns
     * @return $this
     */
    public function addColumns(array $columns) {
        foreach ($columns as $column => $options) {
            $this->addColumn($column, $options);
        }

        return $this;
    }

    /**
     * Add a foreign key for a column.
     * Multiple foreign keys can exist so group by column.
     *
     * @param string $column
     * @param string|array $options {
     *      @type string $references    A table and field that the foreign key references, should be in a "user.id" format
     *      @type string $onUpdate      Action to use for ON UPDATE clauses
     *      @type string $onDelete      Action to use for ON DELETE clauses
     * }
     * @return $this
     * @throws \Titon\Db\Exception\InvalidArgumentException
     */
    public function addForeign($column, $options = []) {
        if (is_string($options)) {
            $options = ['references' => $options];
        }

        if (empty($options['references'])) {
            throw new InvalidArgumentException(sprintf('Foreign key for %s must reference an external table', $column));
        }

        $this->_foreignKeys[$column] = $options + [
            'column' => $column,
            'constraint' => ''
        ];
    }

    /**
     * Add an index for a column. If $group is provided, allows for grouping of columns.
     *
     * @param string $column
     * @param string $group
     * @return $this
     */
    public function addIndex($column, $group = null) {
        if (!is_string($group)) {
            $group = $column;
        }

        $this->_indexes[$group][] = $column;

        return $this;
    }

    /**
     * Add a table option.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addOption($key, $value) {
        $this->_options[$key] = $value;

        return $this;
    }

    /**
     * Add multiple table options.
     *
     * @param array $options
     * @return $this
     */
    public function addOptions(array $options) {
        $this->_options = array_replace($this->_options, $options);

        return $this;
    }

    /**
     * Add a primary key for a column. Only one primary key can exist.
     * However, multiple columns can exist in a primary key.
     *
     * @param string $column
     * @param string|bool $options  Provide a name to reference the constraint by
     * @return $this
     */
    public function addPrimary($column, $options = false) {
        $symbol = is_string($options) ? $options : '';

        if (empty($this->_primaryKey)) {
            $this->_primaryKey = [
                'constraint' => $symbol,
                'columns' => [$column]
            ];
        } else {
            $this->_primaryKey['columns'][] = $column;
        }

        return $this;
    }

    /**
     * Add a unique key for a column.
     * Multiple unique keys can exist, so group by index.
     *
     * @param string $column
     * @param string|array $options {
     *      @type string $constraint    Provide a name to reference the constraint by
     *      @type string $index         Custom name for the index key, defaults to the column name
     * }
     * @return $this
     */
    public function addUnique($column, $options = []) {
        $symbol = '';
        $index = $column;

        if (is_array($options)) {
            if (isset($options['constraint'])) {
                $symbol = $options['constraint'];
            }

            if (isset($options['index'])) {
                $index = $options['index'];
            }
        } else if (is_string($options)) {
            $index = $options;
        }

        if (empty($this->_uniqueKeys[$index])) {
            $this->_uniqueKeys[$index] = [
                'index' => $index,
                'constraint' => $symbol,
                'columns' => [$column]
            ];
        } else {
            $this->_uniqueKeys[$index]['columns'][] = $column;
        }
    }

    /**
     * Return column options by name.
     *
     * @param string $name
     * @return array
     * @throws \Titon\Db\Exception\MissingColumnException
     */
    public function getColumn($name) {
        if ($this->hasColumn($name)) {
            return $this->_columns[$name];
        }

        throw new MissingColumnException(sprintf('Repository column %s does not exist', $name));
    }

    /**
     * Return all columns and their options.
     *
     * @return array
     */
    public function getColumns() {
        return $this->_columns;
    }

    /**
     * Return all foreign keys.
     *
     * @return array
     */
    public function getForeignKeys() {
        return $this->_foreignKeys;
    }

    /**
     * Return all indexes.
     *
     * @return array
     */
    public function getIndexes() {
        return $this->_indexes;
    }

    /**
     * Return the table options.
     *
     * @return array
     */
    public function getOptions() {
        return $this->_options;
    }

    /**
     * Return the primary key.
     *
     * @return array
     */
    public function getPrimaryKey() {
        return $this->_primaryKey;
    }

    /**
     * Return the table name.
     *
     * @return string
     */
    public function getTable() {
        return $this->_table;
    }

    /**
     * Return all the unique keys.
     *
     * @return array
     */
    public function getUniqueKeys() {
        return $this->_uniqueKeys;
    }

    /**
     * Check if a column exists.
     *
     * @param string $column
     * @return bool
     */
    public function hasColumn($column) {
        return isset($this->_columns[$column]);
    }

    /**
     * Return true if any column has been defined.
     *
     * @return bool
     */
    public function hasColumns() {
        return !empty($this->_columns);
    }

}