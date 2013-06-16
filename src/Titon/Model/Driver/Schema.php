<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver;

use Titon\Model\Exception;

/**
 * Represents a database table schema and provides mapping for columns, indexes, types and constraints.
 *
 * @package Titon\Model\Driver
 */
class Schema {

	const INDEX = 'index';
	const INDEX_FULLTEXT = 'fulltext';

	const CONSTRAINT_PRIMARY = 'primary';
	const CONSTRAINT_UNIQUE = 'unique';
	const CONSTRAINT_FOREIGN = 'foreign';

	const ACTION_CASCADE = 'cascade';
	const ACTION_RESTRICT = 'restrict';
	const ACTION_SET_NULL = 'setNull';
	const ACTION_NONE = 'noAction';

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
	 * 		@type string $type		The column data type (one of Titon\Model\Driver\Type)
	 * 		@type int $length		The column data length
	 * 		@type mixed $default	The default value
	 * 		@type string $comment	The comment
	 * 		@type bool $null		Does the column allow nulls
	 * 		@type bool $ai			Is this an auto incrementing column
	 * 		@type mixed $index		Is this an index
	 * 		@type mixed $primary	Is this a primary key
	 * 		@type mixed $unique		Is this a unique key
	 * 		@type mixed $foreign	Is this a foreign key
	 * }
	 * @return \Titon\Model\Driver\Schema
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
			'null' => false,
			'ai' => false,
			'index' => false,		// KEY index (field[, field])
			'primary' => false,		// [CONSTRAINT symbol] PRIMARY KEY (field[, field])
			'unique' => false,		// [CONSTRAINT symbol] UNIQUE KEY index (field[, field])
			'foreign' => false		// [CONSTRAINT symbol] FOREIGN KEY (field) REFERENCES table(field) [ON DELETE CASCADE, etc]
		];

		$this->_columns[$column] = array_filter($options);

		if ($options['index']) {
			$this->addIndex($column, $options['index']);

		} else if ($options['primary']) {
			$this->addConstraint(self::CONSTRAINT_PRIMARY, $column, $options['primary']);

		} else if ($options['unique']) {
			$this->addConstraint(self::CONSTRAINT_UNIQUE, $column, $options['unique']);

		} else if ($options['foreign']) {
			$this->addConstraint(self::CONSTRAINT_FOREIGN, $column, $options['foreign']);
		}

		return $this;
	}

	/**
	 * Add multiple columns. Index is the column name, the value is the array of options.
	 *
	 * @param array $columns
	 * @return \Titon\Model\Driver\Schema
	 */
	public function addColumns(array $columns) {
		foreach ($columns as $column => $options) {
			$this->addColumn($column, $options);
		}

		return $this;
	}

	/**
	 * Add a constraint for a column. The constraint is either a primary key, unique key or foreign key.
	 * Each type of constraint requires different options, they are:
	 *
	 * Primary, Unique {
	 * 		@type string $constraint	(Optional) Provide a name to reference the constraint by
	 * }
	 *
	 * Unique {
	 * 		@type string $index			Custom name for the index key, defaults to the column name
	 * }
	 *
	 * Foreign {
	 * 		@type string $references	A table and field that the foreign key references, should be in a "user.id" format
	 * 		@type string $onUpdate		Action to use for ON UPDATE clauses
	 * 		@type string $onDelete		Action to use for ON DELETE clauses
	 * }
	 *
	 * @param string $type
	 * @param string $column
	 * @param string|array $key
	 * @return \Titon\Model\Driver\Schema
	 * @throws \Titon\Model\Exception
	 */
	public function addConstraint($type, $column, $key = null) {
		$symbol = '';
		$index = $column;

		// These values are optional
		// So only grab them if the data is an array
		if (is_array($key)) {
			if (isset($key['constraint'])) {
				$symbol = $key['constraint'];
			}

			if (isset($key['index'])) {
				$index = $key['index'];
			}
		}

		switch ($type) {

			// Only one primary key can exist
			// However, multiple columns can exist in a primary key
			case self::CONSTRAINT_PRIMARY:
				if (is_string($key)) {
					$symbol = $key;
				}

				if (empty($this->_primaryKey)) {
					$this->_primaryKey = [
						'constraint' => $symbol,
						'columns' => [$column]
					];
				} else {
					$this->_primaryKey['columns'][] = $column;
				}
			break;

			// Multiple unique keys can exist
			// Group by index
			case self::CONSTRAINT_UNIQUE:
				if (is_string($key)) {
					$index = $key;
				}

				if (empty($this->_uniqueKeys[$index])) {
					$this->_uniqueKeys[$index] = [
						'constraint' => $symbol,
						'columns' => [$column]
					];
				} else {
					$this->_uniqueKeys[$index]['columns'][] = $column;
				}
			break;

			// Multiple foreign keys can exist
			// Group by column
			case self::CONSTRAINT_FOREIGN:
				if (is_string($key)) {
					$key = ['references' => $key];
				}

				if (empty($key['references'])) {
					throw new Exception(sprintf('Foreign key for %s must reference an external table', $column));
				}

				$this->_foreignKeys[$column] = $key + [
					'constraint' => $symbol,
					'onUpdate' => false,
					'onDelete' => false
				];
			break;

			// Invalid constraint
			default:
				throw new Exception(sprintf('Invalid constraint type for %s', $column));
			break;
		}

		return $this;
	}

	/**
	 * Add an index for a column. If $group is provided, allows for grouping of columns.
	 *
	 * @param string $column
	 * @param string $group
	 * @return \Titon\Model\Driver\Schema
	 */
	public function addIndex($column, $group = null) {
		if (!is_string($group)) {
			$group = $column;
		}

		$this->_indexes[$group][] = $column;

		return $this;
	}

	/**
	 * Return column options by name.
	 *
	 * @param string $name
	 * @return array
	 * @throws \Titon\Model\Exception
	 */
	public function getColumn($name) {
		if ($this->hasColumn($name)) {
			return $this->_columns[$name];
		}

		throw new Exception(sprintf('Table column %s does not exist', $name));
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

}