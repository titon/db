<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Model\Model;
use Titon\Model\Exception;
use Titon\Model\Query\Clause;
use \Closure;

/**
 * Provides an object oriented interface for building an SQL query.
 * Once the query params have been defined, the query can call the parent model to prepare the SQL statement,
 * and finally execute the query and return the results.
 */
class Query {

	// Order directions
	const ASC = 'ASC';
	const DESC = 'DESC';

	// Types
	const INSERT = 'insert';
	const SELECT = 'select';
	const UPDATE = 'update';
	const DELETE = 'delete';
	const TRUNCATE = 'truncate';
	const DESCRIBE = 'describe';
	const EXPLAIN = 'explain';
	const DROP_TABLE = 'dropTable';
	const CREATE_TABLE = 'createTable';
	const ALTER_TABLE = 'alterTable';

	/**
	 * List of boolean attributes for the current query.
	 *
	 * @type array
	 */
	protected $_attributes = [];

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
	protected $_having;

	/**
	 * How many records to return.
	 *
	 * @type int
	 */
	protected $_limit;

	/**
	 * That parent model instance.
	 *
	 * @type \Titon\Model\Model
	 */
	protected $_model;

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
	 * Additional sub-queries to execute, usually for join type data.
	 *
	 * @type \Titon\Model\Query[]
	 */
	protected $_subQueries = [];

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
	 * @param \Titon\Model\Model $model
	 * @throws \Titon\Model\Exception
	 */
	public function __construct($type, Model $model) {
		$this->_type = $type;
		$this->_model = $model;
		$this->_where = new Clause();
		$this->_having = new Clause();
	}

	/**
	 * Set an attribute.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return \Titon\Model\Query
	 */
	public function attribute($key, $value) {
		$this->_attributes[$key] = $value;

		return $this;
	}

	/**
	 * Pass the query to the model to interact with the database.
	 * Return the count of how many records exist.
	 *
	 * @return \Titon\Model\Entity
	 */
	public function count() {
		return $this->_model->count($this);
	}

	/**
	 * Turn on distinct query statement.
	 *
	 * @return \Titon\Model\Query
	 */
	public function distinct() {
		$this->attribute('distinct', true);

		return $this;
	}

	/**
	 * Pass the query to the model to interact with the database.
	 * Return the first record from the results.
	 *
	 * @return \Titon\Model\Entity
	 */
	public function fetch() {
		return $this->_model->fetch($this);
	}

	/**
	 * Pass the query to the model to interact with the database.
	 * Return all records from the results.
	 *
	 * @return \Titon\Model\Entity[]
	 */
	public function fetchAll() {
		return $this->_model->fetchAll($this);
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
	 * Return the list of attributes.
	 *
	 * @return array
	 */
	public function getAttributes() {
		return $this->_attributes;
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
	 * Return the sub-queries.
	 *
	 * @return \Titon\Model\Query[]
	 */
	public function getSubQueries() {
		return $this->_subQueries;
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
	 * Pass the query to the model to interact with the database.
	 * Return the count of how many records were affected.
	 *
	 * @return int
	 */
	public function save() {
		return $this->_model->save($this);
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

	/**
	 * Include a model relation by querying and joining the records.
	 *
	 * @param string $alias
	 * @param \Titon\Model\Query|\Closure $query
	 * @return \Titon\Model\Query
	 */
	public function with($alias, $query) {
		$this->_model->getRelation($alias); // Do relation check

		if ($query instanceof Closure) {
			$relatedQuery = $this->_model->getObject($alias)->query(Query::SELECT);

			$query = $query->bindTo($relatedQuery, 'Titon\Model\Query');
			$query();

			$this->_subQueries[$alias] = $relatedQuery;

		} else if ($query instanceof Query) {
			$this->_subQueries[$alias] = $query;
		}

		return $this;
	}

}