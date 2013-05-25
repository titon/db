<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Model\Query\Clause;
use Titon\Model\Exception;
use Titon\Model\Source;
use \Closure;

/**
 * Functionality for query building using an OOP approach.
 */
class Query {

	// Order directions
	const ASC = 'ASC';
	const DESC = 'DESC';

	// Types
	const INSERT = 1;
	const SELECT = 2;
	const UPDATE = 3;
	const DELETE = 4;

	/**
	 * The fields to query for. An empty array will query all fields.
	 *
	 * @type string[]
	 */
	protected $_fields = [];

	/**
	 * What fields to group on.
	 *
	 * @type string[]
	 */
	protected $_groupBy = [];

	/**
	 * The having clause containing a list of parameters.
	 *
	 * @type \Titon\Model\Query\Clause
	 */
	protected $_having = [];

	/**
	 * How many records to return.
	 *
	 * @type int
	 */
	protected $_limit;

	/**
	 * What offset to start records from.
	 *
	 * @type int
	 */
	protected $_offset;

	/**
	 * How to order the query results.
	 * Field to direction mapping.
	 *
	 * @type string[]
	 */
	protected $_orderBy = [];

	/**
	 * That datasource to query against.
	 *
	 * @type \Titon\Model\Source
	 */
	protected $_source;

	/**
	 * The database table.
	 *
	 * @type string
	 */
	protected $_table;

	/**
	 * The type of database query.
	 *
	 * @type string
	 */
	protected $_type;

	/**
	 * The where clause containing a list of parameters.
	 *
	 * @type \Titon\Model\Query\Clause
	 */
	protected $_where;

	/**
	 * Set the type of query.
	 *
	 * @param int $type
	 * @param \Titon\Model\Source $source
	 * @throws \Titon\Model\Exception
	 */
	public function __construct($type, Source $source) {
		if ($type < self::INSERT || $type > self::DELETE) {
			throw new Exception(sprintf('Invalid query type %s', $type));
		}

		$this->_type = $type;
		$this->_source = $source;

		// Instantiate clauses
		$this->_where = new Clause();
		$this->_having = new Clause();
	}

	public function fetch() {
		$statement = $this->_source->buildStatement($this);
	}

	public function fetchMany() {
		$statement = $this->_source->buildStatement($this);
	}

	/**
	 * Set the list of fields to return.
	 *
	 * @param string $field,...
	 * @return \Titon\Model\Query
	 */
	public function fields() {
		$fields = func_get_args();

		if (is_array($fields[0])) {
			$fields = $fields[0];
		}

		$this->_fields = array_unique($fields);

		return $this;
	}

	/**
	 * Set the table to query against.
	 *
	 * @param string $table
	 * @return \Titon\Model\Query
	 */
	public function from($table) {
		$this->_table = (string) $table;

		return $this;
	}

	/**
	 * Return the list of fields and or values.
	 *
	 * @return string[]
	 */
	public function getFields() {
		return $this->_fields;
	}

	/**
	 * Return the group by fields.
	 *
	 * @return string[]
	 */
	public function getGroupBy() {
		return $this->_groupBy;
	}

	/**
	 * Return the having clause.
	 *
	 * @return \Titon\Model\Query\Clause
	 */
	public function getHaving() {
		return $this->_having;
	}

	/**
	 * Return the limit.
	 *
	 * @return int
	 */
	public function getLimit() {
		return $this->_limit;
	}

	/**
	 * Return the offset.
	 *
	 * @return int
	 */
	public function getOffset() {
		return $this->_offset;
	}

	/**
	 * Return the order by fields.
	 *
	 * @return string[]
	 */
	public function getOrderBy() {
		return $this->_orderBy;
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
	 * Return the type of query.
	 *
	 * @return int
	 */
	public function getType() {
		return $this->_type;
	}

	/**
	 * Return the where clause.
	 *
	 * @return \Titon\Model\Query\Clause
	 */
	public function getWhere() {
		return $this->_where;
	}

	/**
	 * Set what fields to group on.
	 *
	 * @param string $field,...
	 * @return \Titon\Model\Query
	 */
	public function groupBy() {
		$this->_groupBy = array_unique(array_merge($this->_groupBy, func_get_args()));

		return $this;
	}

	/**
	 * Define the parameters to use for the having clause.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return \Titon\Model\Query
	 */
	public function having($field, $value = null) {
		if ($field instanceof Closure) {
			$field = $field->bindTo($this->_having, 'Titon\Model\Query\Clause');
			$field();
		} else {
			$this->_having->also($field, $value);
		}

		return $this;
	}

	/**
	 * Set the record limit and offset.
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return \Titon\Model\Query
	 */
	public function limit($limit, $offset = 0) {
		$this->_limit = (int) $limit;
		$this->_offset = (int) $offset;

		return $this;
	}

	/**
	 * Set the fields and direction to order by.
	 *
	 * @param string|array $field
	 * @param string $direction
	 * @return \Titon\Model\Query
	 * @throws \Titon\Model\Exception
	 */
	public function orderBy($field, $direction = self::DESC) {
		if (is_array($field)) {
			foreach ($field as $key => $dir) {
				$this->orderBy($key, $dir);
			}
		} else {
			$direction = strtoupper($direction);

			if ($direction != self::ASC && $direction != self::DESC) {
				throw new Exception(sprintf('Invalid order direction %s for field %s', $direction, $field));
			}

			$this->_orderBy[$field] = $direction;
		}

		return $this;
	}

	/**
	 * Define the parameters to use for the where clause.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return \Titon\Model\Query
	 */
	public function where($field, $value = null) {
		if ($field instanceof Closure) {
			$field = $field->bindTo($this->_where, 'Titon\Model\Query\Clause');
			$field();
		} else {
			$this->_where->also($field, $value);
		}

		return $this;
	}

}