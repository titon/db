<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Common\Registry;
use Titon\Model\Model;
use Titon\Model\Exception;
use Titon\Model\Query\Clause;
use \Closure;
use \Serializable;
use \JsonSerializable;

/**
 * Provides an object oriented interface for building an SQL query.
 * Once the query params have been defined, the query can call the parent model to prepare the SQL statement,
 * and finally execute the query and return the results.
 */
class Query implements Serializable, JsonSerializable {

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
	 * How long to cache the query for.
	 *
	 * @type mixed
	 */
	protected $_cacheLength = null;

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
	 * When cloning, be sure to clone the sub-objects.
	 * Leave the model instance intact however.
	 */
	public function __clone() {
		$this->_where = clone $this->_where;
		$this->_having = clone $this->_having;
	}

	/**
	 * Magic method for toString().
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}

	/**
	 * Set an attribute.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return \Titon\Model\Query
	 */
	public function attribute($key, $value = null) {
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				$this->attribute($k, $v);
			}
		} else {
			$this->_attributes[$key] = $value;
		}

		return $this;
	}

	/**
	 * Bind a Closure callback to this query and execute it.
	 *
	 * @param \Closure $callback
	 * @param mixed $argument
	 */
	public function bindCallback(Closure $callback, $argument) {
		$callback = $callback->bindTo($this, 'Titon\Model\Query');
		$callback($argument);
	}

	/**
	 * Set the cache duration length.
	 *
	 * @param mixed $expires
	 * @return \Titon\Model\Query
	 */
	public function cacheFor($expires) {
		$this->_cacheLength = $expires;

		return $this;
	}

	/**
	 * Pass the query to the model to interact with the database.
	 * Return the count of how many records exist.
	 *
	 * @return \Titon\Model\Entity
	 */
	public function count() {
		return $this->getModel()->count($this);
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
	 * @param bool $wrap
	 * @return \Titon\Model\Entity
	 */
	public function fetch($wrap = true) {
		return $this->getModel()->fetch($this, $wrap);
	}

	/**
	 * Pass the query to the model to interact with the database.
	 * Return all records from the results.
	 *
	 * @param bool $wrap
	 * @return \Titon\Model\Entity[]
	 */
	public function fetchAll($wrap = true) {
		return $this->getModel()->fetchAll($this, $wrap);
	}

	/**
	 * Pass the query to the model to interact with the database.
	 * Return all records as a key value list.
	 *
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	public function fetchList($key = null, $value = null) {
		return $this->getModel()->fetchList($this, $key, $value);
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
	 * Generate a unique cache key for this query.
	 *
	 * @return string
	 */
	public function getCacheKey() {
		return get_class($this) . '-' . $this->toString();
	}

	/**
	 * Return the cache length.
	 *
	 * @return string
	 */
	public function getCacheLength() {
		return $this->_cacheLength;
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
	 * Return the parent model.
	 *
	 * @return \Titon\Model\Model
	 */
	public function getModel() {
		return $this->_model;
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
		$fields = func_get_args();

		if (is_array($fields[0])) {
			$fields = $fields[0];
		}

		$this->_groupBy = array_unique(array_merge($this->_groupBy, $fields));

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
		return $this->getModel()->save($this);
	}

	/**
	 * Return a unique MD5 hash of the query.
	 *
	 * @return string
	 */
	public function toString() {
		return md5(json_encode($this->jsonSerialize()));
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
	 * @param string|string[] $alias
	 * @param \Titon\Model\Query|\Closure $query
	 * @return \Titon\Model\Query
	 * @throws \Titon\Model\Exception
	 */
	public function with($alias, $query = null) {
		if ($this->getType() !== self::SELECT) {
			throw new Exception('Only select queries can join related data');
		}

		// Allow an array of aliases to easily be set
		if (is_array($alias)) {
			foreach ($alias as $name) {
				$this->with($name);
			}

			return $this;
		}

		$relation = $this->getModel()->getRelation($alias); // Do relation check

		if ($query instanceof Query) {
			$relatedQuery = $query;

		} else {
			/** @type \Titon\Model\Query $relatedQuery */
			$relatedQuery = $this->getModel()->getObject($alias)->select();

			// Apply relation conditions
			if ($conditions = $relation->getConditions()) {
				$relatedQuery->bindCallback($conditions, $relation);
			}

			// Apply with conditions
			if ($query instanceof Closure) {
				$relatedQuery->bindCallback($query, $relation);
			}
		}

		$this->_subQueries[$alias] = $relatedQuery;

		return $this;
	}

	/**
	 * Serialize the query.
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize($this->jsonSerialize());
	}

	/**
	 * Reconstruct the query once unserialized.
	 *
	 * @param array $data
	 */
	public function unserialize($data) {
		$data = unserialize($data);

		$this->__construct($data['type'], Registry::factory($data['model']));

		if ($data['attributes']) {
			$this->attribute($data['attributes']);
		}

		if ($data['fields']) {
			$this->fields($data['fields']);
		}

		if ($data['groupBy']) {
			$this->groupBy($data['groupBy']);
		}

		if ($data['limit']) {
			$this->limit($data['limit'], $data['offset']);
		}

		if ($data['orderBy']) {
			$this->orderBy($data['orderBy']);
		}

		if ($data['table']) {
			$this->from($data['table']);
		}

		if ($data['having']) {
			$this->_having = $data['having'];
		}

		if ($data['where']) {
			$this->_where = $data['where'];
		}

		if ($data['subQueries']) {
			$this->_subQueries = $data['subQueries'];
		}
	}

	/**
	 * Return all data for serialization.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'attributes' => $this->getAttributes(),
			'fields' => $this->getFields(),
			'groupBy' => $this->getGroupBy(),
			'having' => $this->getHaving(),
			'limit' => $this->getLimit(),
			'model' => (string) $this->getModel(),
			'offset' => $this->getOffset(),
			'orderBy' => $this->getOrderBy(),
			'subQueries' => $this->getSubQueries(),
			'table' => $this->getTable(),
			'type' => $this->getType(),
			'where' => $this->getWhere()
		];
	}

}