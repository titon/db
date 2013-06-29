<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Common\Registry;
use Titon\Model\Driver\Schema;
use Titon\Model\Exception\ExistingPredicateException;
use Titon\Model\Exception\InvalidArgumentException;
use Titon\Model\Exception\InvalidRelationQueryException;
use Titon\Model\Model;
use Titon\Model\Query\Expr;
use Titon\Model\Query\Func;
use Titon\Model\Query\Predicate;
use Titon\Model\Query\SubQuery;
use Titon\Model\Traits\ExprAware;
use Titon\Model\Traits\FuncAware;
use Titon\Utility\Hash;
use \Closure;
use \Serializable;
use \JsonSerializable;

/**
 * Provides an object oriented interface for building an SQL query.
 * Once the query params have been defined, the query can call the parent model to prepare the SQL statement,
 * and finally execute the query and return the results.
 *
 * @package Titon\Model
 */
class Query implements Serializable, JsonSerializable {
	use ExprAware, FuncAware;

	// Order directions
	const ASC = 'asc';
	const DESC = 'desc';

	// Types
	const INSERT = 'insert';
	const MULTI_INSERT = 'multiInsert';
	const SELECT = 'select';
	const UPDATE = 'update';
	const DELETE = 'delete';
	const TRUNCATE = 'truncate';
	const DESCRIBE = 'describe';
	const DROP_TABLE = 'dropTable';
	const CREATE_TABLE = 'createTable';

	/**
	 * List of boolean attributes for the current query.
	 *
	 * @type array
	 */
	protected $_attributes = [];

	/**
	 * Unique cache key for this query.
	 *
	 * @type mixed
	 */
	protected $_cacheKey;

	/**
	 * How long to cache the query for.
	 *
	 * @type mixed
	 */
	protected $_cacheLength;

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
	 * The having predicate containing a list of parameters.
	 *
	 * @type \Titon\Model\Query\Predicate
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
	 * Additional queries to execute for relational data.
	 *
	 * @type \Titon\Model\Query[]
	 */
	protected $_relationQueries = [];

	/**
	 * Table schema used for complex queries.
	 *
	 * @type \Titon\Model\Driver\Schema
	 */
	protected $_schema;

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
	 * The where predicate containing a list of parameters.
	 *
	 * @type \Titon\Model\Query\Predicate
	 */
	protected $_where;

	/**
	 * Set the type of query.
	 *
	 * @param int $type
	 * @param \Titon\Model\Model $model
	 */
	public function __construct($type, Model $model) {
		$this->_type = $type;
		$this->_model = $model;
	}

	/**
	 * When cloning, be sure to clone the sub-objects.
	 * Leave the model instance intact however.
	 */
	public function __clone() {
		if ($this->_where) {
			$this->_where = clone $this->_where;
		}

		if ($this->_having) {
			$this->_having = clone $this->_having;
		}
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
	 * @return \Titon\Model\Query
	 */
	public function bindCallback(Closure $callback, $argument = null) {
		$callback = $callback->bindTo($this, 'Titon\Model\Query');
		$callback($argument);

		return $this;
	}

	/**
	 * Set the cache key and duration length.
	 *
	 * @param mixed $key
	 * @param mixed $expires
	 * @return \Titon\Model\Query
	 */
	public function cache($key, $expires = null) {
		if ($this->getType() !== self::SELECT) {
			return $this;
		}

		$this->_cacheKey = $key;
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
		$this
			->fields($this->func('COUNT', [$this->getModel()->getPrimaryKey() => Func::FIELD]))
			->limit(0);

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
	 * @param string|array $fields
	 * @param bool $merge
	 * @return \Titon\Model\Query
	 */
	public function fields($fields, $merge = false) {
		if (!is_array($fields)) {
			$fields = func_get_args();
			$merge = false;
		}

		if ($merge) {
			$fields = array_merge($this->_fields, $fields);
		}

		if ($this->getType() === self::SELECT) {
			$fields = array_values(array_unique($fields));
		}

		$this->_fields = $fields;

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
		return $this->_cacheKey;
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
	 * Return the having predicate.
	 *
	 * @return \Titon\Model\Query\Predicate
	 */
	public function getHaving() {
		return $this->_having ?: new Predicate(Predicate::ALSO);
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
	 * Return the relation queries.
	 *
	 * @return \Titon\Model\Query[]
	 */
	public function getRelationQueries() {
		return $this->_relationQueries;
	}

	/**
	 * Return the schema.
	 *
	 * @return \Titon\Model\Driver\Schema
	 */
	public function getSchema() {
		return $this->_schema;
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
	 * Return the where predicate.
	 *
	 * @return \Titon\Model\Query\Predicate
	 */
	public function getWhere() {
		return $this->_where ?: new Predicate(Predicate::ALSO);
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
	 * Will modify or create a having predicate using the AND conjunction.
	 *
	 * @param string $field
	 * @param string $op
	 * @param mixed $value
	 * @return \Titon\Model\Query
	 */
	public function having($field, $op = null, $value = null) {
		return $this->_modifyPredicate($this->_having, Predicate::ALSO, $field, $op, $value);
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
		$this->offset($offset);

		return $this;
	}

	/**
	 * Set the record offset.
	 *
	 * @param int $offset
	 * @return \Titon\Model\Query
	 */
	public function offset($offset) {
		$this->_offset = (int) $offset;

		return $this;
	}

	/**
	 * Set the fields and direction to order by.
	 *
	 * @param string|array $field
	 * @param string $direction
	 * @return \Titon\Model\Query
	 * @throws \Titon\Model\Exception\InvalidArgumentException
	 */
	public function orderBy($field, $direction = self::DESC) {
		if (is_array($field)) {
			foreach ($field as $key => $dir) {
				$this->orderBy($key, $dir);
			}

		} else if ($field === 'RAND') {
			$this->_orderBy[] = $this->func('RAND');

		} else if ($field instanceof Func) {
			$this->_orderBy[] = $field;

		} else {
			$direction = strtolower($direction);

			if ($direction != self::ASC && $direction != self::DESC) {
				throw new InvalidArgumentException(sprintf('Invalid order direction %s for field %s', $direction, $field));
			}

			$this->_orderBy[$field] = $direction;
		}

		return $this;
	}

	/**
	 * Will modify or create a having predicate using the OR conjunction.
	 *
	 * @param string $field
	 * @param string $op
	 * @param mixed $value
	 * @return \Titon\Model\Query
	 * @throws \Titon\Model\Exception\ExistingPredicateException
	 */
	public function orHaving($field, $op = null, $value = null) {
		return $this->_modifyPredicate($this->_having, Predicate::EITHER, $field, $op, $value);
	}

	/**
	 * Will modify or create a where predicate using the OR conjunction.
	 *
	 * @param string $field
	 * @param string $op
	 * @param mixed $value
	 * @return \Titon\Model\Query
	 */
	public function orWhere($field, $op = null, $value = null) {
		return $this->_modifyPredicate($this->_where, Predicate::EITHER, $field, $op, $value);
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
	 * Set the table schema.
	 *
	 * @param \Titon\Model\Driver\Schema $schema
	 * @return \Titon\Model\Query
	 */
	public function schema(Schema $schema) {
		$this->_schema = $schema;

		return $this;
	}

	/**
	 * Instantiate a new query object that will be used for sub-queries.
	 *
	 * @return \Titon\Model\Query\SubQuery
	 */
	public function subQuery() {
		$query = new SubQuery(Query::SELECT, $this->getModel());
		$query->fields(func_get_args());

		return $query;
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
	 * Will modify or create a where predicate using the AND conjunction.
	 *
	 * @param string $field
	 * @param string $op
	 * @param mixed $value
	 * @return \Titon\Model\Query
	 */
	public function where($field, $op = null, $value = null) {
		return $this->_modifyPredicate($this->_where, Predicate::ALSO, $field, $op, $value);
	}

	/**
	 * Include a model relation by querying and joining the records.
	 *
	 * @param string|string[] $alias
	 * @param \Titon\Model\Query|\Closure $query
	 * @return \Titon\Model\Query
	 * @throws \Titon\Model\Exception\InvalidRelationQueryException
	 */
	public function with($alias, $query = null) {
		if ($this->getType() !== self::SELECT) {
			throw new InvalidRelationQueryException('Only select queries can join related data');
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

			if ($query->getType() !== self::SELECT) {
				throw new InvalidRelationQueryException('Only select sub-queries are permitted for related data');
			}
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

		// Add foreign key to field list
		if ($this->_fields) {
			if ($relation->getType() === Relation::MANY_TO_ONE) {
				$this->fields([$relation->getForeignKey()], true);
			}
		}

		$this->_relationQueries[$alias] = $relatedQuery;

		return $this;
	}

	/**
	 * Will modify or create a having predicate using the XOR conjunction.
	 *
	 * @param string $field
	 * @param string $op
	 * @param mixed $value
	 * @return \Titon\Model\Query
	 */
	public function xorHaving($field, $op = null, $value = null) {
		return $this->_modifyPredicate($this->_having, Predicate::MAYBE, $field, $op, $value);
	}

	/**
	 * Will modify or create a where predicate using the XOR conjunction.
	 *
	 * @param string $field
	 * @param string $op
	 * @param mixed $value
	 * @return \Titon\Model\Query
	 */
	public function xorWhere($field, $op = null, $value = null) {
		return $this->_modifyPredicate($this->_where, Predicate::MAYBE, $field, $op, $value);
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

		if ($data['cacheLength']) {
			$this->cacheFor($data['cacheLength']);
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

		if ($data['relationQueries']) {
			$this->_relationQueries = $data['relationQueries'];
		}
	}

	/**
	 * Return all data for serialization.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		$fields = $this->getFields();

		foreach ($fields as $field => $value) {
			if (is_resource($value)) {
				$fields[$field] = stream_get_contents($value, -1, 0);
			}
		}

		return [
			'attributes' => $this->getAttributes(),
			'cacheLength' => $this->getCacheLength(),
			'fields' => $fields,
			'groupBy' => $this->getGroupBy(),
			'having' => $this->getHaving(),
			'limit' => $this->getLimit(),
			'model' => get_class($this->getModel()),
			'offset' => $this->getOffset(),
			'orderBy' => $this->getOrderBy(),
			'relationQueries' => $this->getRelationQueries(),
			'table' => $this->getTable(),
			'type' => $this->getType(),
			'where' => $this->getWhere()
		];
	}

	/**
	 * Modify a predicate by adding additional clauses.
	 *
	 * @param \Titon\Model\Query\Predicate $predicate
	 * @param int $type
	 * @param string $field
	 * @param mixed $op
	 * @param mixed $value
	 * @throws \Titon\Model\Exception\ExistingPredicateException
	 * @return \Titon\Model\Query
	 */
	protected function _modifyPredicate(&$predicate, $type, $field, $op, $value) {
		if (!$predicate) {
			$predicate = new Predicate($type);

		} else if ($predicate->getType() !== $type) {
			throw new ExistingPredicateException(sprintf('Predicate clause already created using "%s" conjunction', $predicate));
		}

		if ($field instanceof Closure) {
			$predicate->bindCallback($field);

		} else if ($value !== null || in_array($op, [Expr::NULL, Expr::NOT_NULL])) {
			$predicate->add($field, $op, $value);

		} else {
			$predicate->eq($field, $op);
		}

		return $this;
	}

}