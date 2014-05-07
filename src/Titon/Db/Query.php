<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Db\Driver\Dialect;
use Titon\Db\Driver\Schema;
use Titon\Db\Exception\ExistingPredicateException;
use Titon\Db\Exception\InvalidArgumentException;
use Titon\Db\Exception\InvalidQueryException;
use Titon\Db\Exception\InvalidRelationQueryException;
use Titon\Db\Query\Expr;
use Titon\Db\Query\Func;
use Titon\Db\Query\Join;
use Titon\Db\Query\Predicate;
use Titon\Db\Query\SubQuery;
use Titon\Db\Traits\AliasAware;
use Titon\Db\Traits\ExprAware;
use Titon\Db\Traits\FuncAware;
use Titon\Db\Traits\RawExprAware;
use Titon\Db\Traits\RepositoryAware;
use \Closure;

/**
 * Provides an object oriented interface for building an SQL query.
 * Once the query params have been defined, the query can call the parent repository to prepare the SQL statement,
 * and finally execute the query and return the results.
 *
 * @package Titon\Db
 */
class Query {
    use AliasAware, ExprAware, FuncAware, RawExprAware, RepositoryAware;

    // Order directions
    const ASC = Dialect::ASC;
    const DESC = Dialect::DESC;

    // Types
    const INSERT = 'insert';
    const MULTI_INSERT = 'multiInsert';
    const SELECT = 'select';
    const UPDATE = 'update';
    const DELETE = 'delete';
    const TRUNCATE = 'truncate';
    const CREATE_TABLE = 'createTable';
    const CREATE_INDEX = 'createIndex';
    const DROP_TABLE = 'dropTable';
    const DROP_INDEX = 'dropIndex';

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
     * Queries to union, intersect, or except with.
     *
     * @type \Titon\Db\Query[]
     */
    protected $_compounds = [];

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
     * @type \Titon\Db\Query\Predicate
     */
    protected $_having;

    /**
     * List of joins.
     *
     * @type \Titon\Db\Query\Join[]
     */
    protected $_joins = [];

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
     * Additional queries to execute for relational data.
     *
     * @type \Titon\Db\Query[]
     */
    protected $_relationQueries = [];

    /**
     * Table schema used for complex queries.
     *
     * @type \Titon\Db\Driver\Schema
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
     * @type \Titon\Db\Query\Predicate
     */
    protected $_where;

    /**
     * Set the type of query.
     *
     * @param int $type
     * @param \Titon\Db\Repository $repo
     */
    public function __construct($type, Repository $repo = null) {
        $this->_type = $type;

        if ($repo) {
            $this->setRepository($repo);
        }
    }

    /**
     * When cloning, be sure to clone the sub-objects.
     * Leave the repository instance intact however.
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
     * Return all records from the results.
     *
     * @param array $options
     * @return \Titon\Db\EntityCollection
     */
    public function all(array $options = []) {
        return $this->find('all', $options);
    }

    /**
     * Set an attribute.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function attribute($key, $value = null) {
        if (is_array($key)) {
            $this->_attributes = array_replace($this->_attributes, $key);
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
     * @return $this
     */
    public function bindCallback($callback, $argument = null) {
        if ($callback instanceof Closure) {
            call_user_func_array($callback, [$this, $argument]);
        }

        return $this;
    }

    /**
     * Set the cache key and duration length.
     *
     * @param mixed $key
     * @param mixed $expires
     * @return $this
     */
    public function cache($key, $expires = null) {
        if ($this->getType() !== static::SELECT) {
            return $this;
        }

        $this->_cacheKey = $key;
        $this->_cacheLength = $expires;

        return $this;
    }

    /**
     * Pass the query to the repository to interact with the database.
     * Return the count of how many records exist.
     *
     * @return int
     */
    public function count() {
        $repo = $this->getRepository();

        $this->fields(Query::func('COUNT', [$repo->getPrimaryKey() => Func::FIELD]))->limit(0);

        return $repo->count($this);
    }

    /**
     * Mark a query with a distinct attribute.
     *
     * @return $this
     */
    public function distinct() {
        return $this->attribute('distinct', true);
    }

    /**
     * Add a select query as an except.
     *
     * @param \Titon\Db\Query $query
     * @param string $flag
     * @return $this
     */
    public function except(Query $query, $flag = null) {
        if ($flag === Dialect::ALL) {
            $query->attribute('flag', $flag);
        }

        return $this->_addCompound(Dialect::EXCEPT, $query);
    }

    /**
     * Set the list of fields to return.
     *
     * @param string|array $fields
     * @param bool $merge
     * @return $this
     */
    public function fields($fields, $merge = false) {
        if (!is_array($fields)) {
            $fields = func_get_args();
            $merge = false;
        }

        if ($merge) {
            $fields = array_merge($this->_fields, $fields);
        }

        if ($this->getType() === static::SELECT) {
            $fields = array_values(array_unique($fields));
        }

        $this->_fields = $fields;

        return $this;
    }

    /**
     * Pass the find query to the repository to interact with the database.
     *
     * @param string $type
     * @param array $options
     * @return mixed
     */
    public function find($type, array $options = []) {
        return $this->getRepository()->find($this, $type, $options);
    }

    /**
     * Return the first record from the results.
     *
     * @param array $options
     * @return \Titon\Db\Entity
     */
    public function first(array $options = []) {
        return $this->limit(1)->find('first', $options);
    }

    /**
     * Set the table to query against.
     *
     * @param string $repo
     * @param string $alias
     * @return $this
     */
    public function from($repo, $alias = null) {
        $this->_table = (string) $repo;
        $this->asAlias($alias);

        return $this;
    }

    /**
     * Only return the alias if joins have been set or this is a sub-query.
     *
     * @return string
     */
    public function getAlias() {
        if ($this->getJoins() || $this instanceof SubQuery || in_array($this->getType(), [static::CREATE_INDEX, static::DROP_INDEX])) {
            return $this->_alias;
        }

        return null;
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
     * Return the compound queries.
     *
     * @return \Titon\Db\Query[]
     */
    public function getCompounds() {
        return $this->_compounds;
    }

    /**
     * Return the list of fields and or values.
     * Return all table fields if a join exists and no fields were whitelisted.
     *
     * @return string[]
     */
    public function getFields() {
        $fields = $this->_fields;

        if ($this->getJoins() && !$fields && $this->getType() === static::SELECT) {
            return $this->_mapTableFields($this->getRepository());
        }

        return $fields;
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
     * @return \Titon\Db\Query\Predicate
     */
    public function getHaving() {
        return $this->_having ?: new Predicate(Predicate::ALSO);
    }

    /**
     * Return the list of joins.
     *
     * @return \Titon\Db\Query\Join[]
     */
    public function getJoins() {
        return $this->_joins;
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
     * Return the relation queries.
     *
     * @return $this[]
     */
    public function getRelationQueries() {
        return $this->_relationQueries;
    }

    /**
     * Return the schema.
     *
     * @return \Titon\Db\Driver\Schema
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
     * @return \Titon\Db\Query\Predicate
     */
    public function getWhere() {
        return $this->_where ?: new Predicate(Predicate::ALSO);
    }

    /**
     * Set what fields to group on.
     *
     * @param string $fields,...
     * @return $this
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
     * @return $this
     */
    public function having($field, $op = null, $value = null) {
        return $this->_modifyPredicate($this->_having, Predicate::ALSO, $field, $op, $value);
    }

    /**
     * Add a new INNER join.
     *
     * @param string|array|\Titon\Db\Relation $repo
     * @param array $fields
     * @param array $on
     * @return $this
     */
    public function innerJoin($repo, array $fields = [], array $on = []) {
        return $this->_addJoin(Join::INNER, $repo, $fields, $on);
    }

    /**
     * Add a select query as an intersect.
     *
     * @param \Titon\Db\Query $query
     * @param string $flag
     * @return $this
     */
    public function intersect(Query $query, $flag = null) {
        if ($flag === Dialect::ALL) {
            $query->attribute('flag', $flag);
        }

        return $this->_addCompound(Dialect::INTERSECT, $query);
    }

    /**
     * Add a new LEFT join.
     *
     * @param string|array|\Titon\Db\Relation $repo
     * @param array $fields
     * @param array $on
     * @return $this
     */
    public function leftJoin($repo, array $fields = [], array $on = []) {
        return $this->_addJoin(Join::LEFT, $repo, $fields, $on);
    }

    /**
     * Set the record limit and offset.
     *
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function limit($limit, $offset = 0) {
        $this->_limit = (int) $limit;

        if ($offset) {
            $this->offset($offset);
        }

        return $this;
    }

    /**
     * Return all records as a key value list.
     *
     * @param string $value
     * @param string $key
     * @param array $options
     * @return array
     */
    public function lists($value = null, $key = null, array $options = []) {
        $repo = $this->getRepository();
        $options['key'] = $key ?: $repo->getPrimaryKey();
        $options['value'] = $value ?: $repo->getDisplayField();

        return $this->find('list', $options);
    }

    /**
     * Set the record offset.
     *
     * @param int $offset
     * @return $this
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
     * @return $this
     * @throws \Titon\Db\Exception\InvalidArgumentException
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

            if ($direction != static::ASC && $direction != static::DESC) {
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
     * @return $this
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
     * @return $this
     */
    public function orWhere($field, $op = null, $value = null) {
        return $this->_modifyPredicate($this->_where, Predicate::EITHER, $field, $op, $value);
    }

    /**
     * Add a new OUTER join.
     *
     * @param string|array|\Titon\Db\Relation $repo
     * @param array $fields
     * @param array $on
     * @return $this
     */
    public function outerJoin($repo, array $fields = [], array $on = []) {
        return $this->_addJoin(Join::OUTER, $repo, $fields, $on);
    }

    /**
     * Add a new RIGHT join.
     *
     * @param string|array|\Titon\Db\Relation $repo
     * @param array $fields
     * @param array $on
     * @return $this
     */
    public function rightJoin($repo, array $fields = [], array $on = []) {
        return $this->_addJoin(Join::RIGHT, $repo, $fields, $on);
    }

    /**
     * Pass the query to the repository to interact with the database.
     * Return the count of how many records were affected.
     *
     * @return int
     */
    public function save() {
        return $this->getRepository()->save($this);
    }

    /**
     * Set the table schema.
     *
     * @param \Titon\Db\Driver\Schema $schema
     * @return $this
     */
    public function schema(Schema $schema) {
        $this->_schema = $schema;

        return $this;
    }

    /**
     * Add a new STRAIGHT join.
     *
     * @param string|array|\Titon\Db\Relation $repo
     * @param array $fields
     * @param array $on
     * @return $this
     */
    public function straightJoin($repo, array $fields, array $on = []) {
        return $this->_addJoin(Join::STRAIGHT, $repo, $fields, $on);
    }

    /**
     * Instantiate a new query object that will be used for sub-queries.
     *
     * @return \Titon\Db\Query\SubQuery
     */
    public function subQuery() {
        $query = new SubQuery(Query::SELECT, $this->getRepository());
        $query->fields(func_get_args());

        return $query;
    }

    /**
     * Return a unique MD5 hash of the query.
     *
     * @return string
     */
    public function toString() {
        return spl_object_hash($this);
    }

    /**
     * Add a select query as a union.
     *
     * @param \Titon\Db\Query $query
     * @param string $flag
     * @return $this
     */
    public function union(Query $query, $flag = null) {
        if ($flag === Dialect::ALL || $flag === Dialect::DISTINCT) {
            $query->attribute('flag', $flag);
        }

        return $this->_addCompound(Dialect::UNION, $query);
    }

    /**
     * Will modify or create a where predicate using the AND conjunction.
     *
     * @param string $field
     * @param string $op
     * @param mixed $value
     * @return $this
     */
    public function where($field, $op = null, $value = null) {
        return $this->_modifyPredicate($this->_where, Predicate::ALSO, $field, $op, $value);
    }

    /**
     * Include a repository relation by querying and joining the records.
     *
     * @param string|string[] $alias
     * @param \Titon\Db\Query|\Closure $query
     * @return $this
     * @throws \Titon\Db\Exception\InvalidRelationQueryException
     */
    public function with($alias, $query = null) {
        if ($this->getType() !== static::SELECT) {
            throw new InvalidRelationQueryException('Only select queries can join related data');
        }

        // Allow an array of aliases to easily be set
        if (is_array($alias)) {
            foreach ($alias as $name) {
                $this->with($name);
            }

            return $this;
        }

        $relation = $this->getRepository()->getRelation($alias); // Do relation check

        if ($query instanceof Query) {
            $relatedQuery = $query;

            if ($query->getType() !== static::SELECT) {
                throw new InvalidRelationQueryException('Only select sub-queries are permitted for related data');
            }
        } else {
            $relatedQuery = $relation->getRelatedRepository()->select();

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
     * @return $this
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
     * @return $this
     */
    public function xorWhere($field, $op = null, $value = null) {
        return $this->_modifyPredicate($this->_where, Predicate::MAYBE, $field, $op, $value);
    }

    /**
     * Add a new compound query. Only select queries can be used with compounds.
     *
     * @param string $type
     * @param \Titon\Db\Query $query
     * @return $this
     * @throws \Titon\Db\Exception\InvalidQueryException
     */
    protected function _addCompound($type, Query $query) {
        if ($query->getType() !== self::SELECT) {
            throw new InvalidQueryException(sprintf('Only a select query can be used with %s', $type));
        }

        $query->attribute('compound', $type);

        $this->_compounds[] = $query;

        return $this;
    }

    /**
     * Add a new join type. If table is a relation instance, introspect the correct values.
     *
     * @param string $type
     * @param string|array|\Titon\Db\Relation $table
     * @param array $fields
     * @param array $on
     * @return $this
     * @throws \Titon\Db\Exception\InvalidRelationQueryException
     */
    protected function _addJoin($type, $table, $fields = [], $on = []) {
        $repo = $this->getRepository();
        $join = new Join($type);

        if ($table instanceof Relation) {
            $relation = $table;
            $relatedRepo = $relation->getRelatedRepository();
            $alias = $relation->getAlias();

            if (!$fields && $this->getType() === self::SELECT) {
                $fields = $this->_mapTableFields($relatedRepo);
            }

            $join
                ->from($relatedRepo->getTable(), $alias)
                ->fields($fields);

            switch ($relation->getType()) {
                case Relation::MANY_TO_ONE:
                    // primary.foreign_key = join.id
                    $join->on($repo->getAlias() . '.' . $relation->getForeignKey(), $alias . '.' . $relatedRepo->getPrimaryKey());
                break;
                case Relation::ONE_TO_ONE:
                    // primary.id = join.foreign_key
                    $join->on($repo->getAlias() . '.' . $repo->getPrimaryKey(), $alias . '.' . $relation->getRelatedForeignKey());
                break;
                default:
                    throw new InvalidRelationQueryException('Only many-to-one and one-to-one relations can join data');
                break;
            }
        } else {
            if (is_array($table)) {
                $alias = $table[1];
                $table = $table[0];
            } else {
                $alias = $table;
            }

            // Auto-populate fields if tables are the same
            if (!$fields && $table === $repo->getTable() && $this->getType() === static::SELECT) {
                $fields = $this->_mapTableFields($repo);
            }

            $conditions = [];

            foreach ($on as $pfk => $rfk) {
                if (strpos($pfk, '.') === false) {
                    $pfk = $repo->getAlias() . '.' . $pfk;
                }

                if (strpos($rfk, '.') === false) {
                    $rfk = $alias . '.' . $rfk;
                }

                $conditions[$pfk] = $rfk;
            }

            $join->from($table, $alias)->on($conditions)->fields($fields);
        }

        $this->_joins[] = $join;

        return $this;
    }

    /**
     * Return all fields for a table. This is required for joins and complex queries.
     *
     * @param \Titon\Db\Repository $repo
     * @return array
     */
    protected function _mapTableFields(Repository $repo) {
        $fields = [];

        if ($schema = $repo->getSchema()) {
            $fields = array_keys($schema->getColumns());
        }

        return $fields;
    }

    /**
     * Modify a predicate by adding additional clauses.
     *
     * @param \Titon\Db\Query\Predicate $predicate
     * @param int $type
     * @param string $field
     * @param mixed $op
     * @param mixed $value
     * @return $this
     * @throws \Titon\Db\Exception\ExistingPredicateException
     */
    protected function _modifyPredicate(&$predicate, $type, $field, $op, $value) {
        if (!$predicate) {
            $predicate = new Predicate($type);

        } else if ($predicate->getType() !== $type) {
            throw new ExistingPredicateException(sprintf('Predicate clause already created using "%s" conjunction', $predicate));
        }

        if ($field instanceof Closure) {
            $predicate->bindCallback($field, $this);

        } else if ($value !== null || in_array($op, [Expr::NULL, Expr::NOT_NULL], true)) {
            $predicate->add($field, $op, $value);

        } else if ($op === '!=') {
            $predicate->notEq($field, $value);

        } else {
            $predicate->eq($field, $op);
        }

        return $this;
    }

}