<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver;

/**
 * Represents a database table schema.
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
	 * Table column constraints.
	 *
	 * @type array
	 */
	protected $_constraints = [];

	/**
	 * Table indexes.
	 *
	 * @type array
	 */
	protected $_indexes = [];

	/**
	 * Table name.
	 *
	 * @type string
	 */
	protected $_table;

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

	public function addColumn($name, array $options) {
		$options = $options + [
			'type' => '',
			'length' => '',
			'null' => false,
			'default' => '',
			'comment' => '',
			'key' => '',
			'ai' => false
		];

		$this->_columns[$name] = $options;

		switch ($options['key']) {
			case self::INDEX:

			break;
			case self::CONSTRAINT_PRIMARY:
			case self::CONSTRAINT_UNIQUE:

			break;
		}
	}

	public function addColumns(array $columns) {
		foreach ($columns as $name => $options) {
			$this->addColumn($name, $options);
		}
	}

	public function addConstraint($name) {

	}

	public function addIndex($name, $type, $columns) {

	}

	public function getColumn($name) {

	}

	/**
	 * @return array
	 */
	public function getColumns() {
		return $this->_columns;
	}

	public function getIndexes() {
		return $this->_indexes;
	}

	public function getPrimaryKey() {

	}

	public function getTable() {
		return $this->_table;
	}

	public function getUniqueKey() {

	}

	public function hasColumn($column) {
		return isset($this->_columns[$column]);
	}

}