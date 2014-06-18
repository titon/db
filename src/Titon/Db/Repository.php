<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Common\Base;
use Titon\Common\Traits\Attachable;
use Titon\Common\Traits\Cacheable;
use Titon\Db\Driver\Dialect;
use Titon\Db\Driver\Schema;
use Titon\Db\Exception\InvalidQueryException;
use Titon\Db\Exception\MissingBehaviorException;
use Titon\Db\Exception\MissingFinderException;
use Titon\Db\Finder;
use Titon\Db\Finder\FirstFinder;
use Titon\Db\Finder\AllFinder;
use Titon\Db\Finder\ListFinder;
use Titon\Db\Query;
use Titon\Db\Query\Expr;
use Titon\Db\Query\Func;
use Titon\Event\Event;
use Titon\Event\Listener;
use Titon\Event\Traits\Emittable;
use Titon\Type\Contract\Arrayable;
use Titon\Utility\Hash;
use \Closure;

/**
 * Represents a database table.
 *
 *      - Defines a schema
 *      - Allows for queries to be built and executed
 *      - Returns Entity objects for each record in the result
 *
 * @link http://en.wikipedia.org/wiki/Database_model
 *
 * @package Titon\Db
 * @events
 *      db.preFind(Query $query, $finder)
 *      db.preSave,
 *      db.preCreate,
 *      db.preUpdate(Query $query, $id, &$data)
 *      db.preDelete(Query $query, $id)
 *      db.postFind(&$results, $finder)
 *      db.postSave,
 *      db.postCreate,
 *      db.postUpdate($id, $count)
 *      db.postDelete($id, $count)
 */
class Repository extends Base implements Listener {
    use Attachable, Cacheable, Emittable;

    /**
     * ID(s) of records before and after an insert, update, or delete.
     *
     * @type int|int[]
     */
    public $id;

    /**
     * List of attached behaviors.
     *
     * @type \Titon\Db\Behavior[]
     */
    protected $_behaviors = [];

    /**
     * Configuration.
     *
     * @type array {
     *      @type string $connection    The connection driver key
     *      @type string $table         Database table name
     *      @type string $prefix        Prefix to prepend to the table name
     *      @type string $primaryKey    The field representing the primary key
     *      @type string $displayField  The field representing a readable label
     *      @type string $entity        The Entity class to wrap results in
     *      @type string $collection    The Collection class to wrap entities in
     * }
     */
    protected $_config = [
        'connection' => 'default',
        'table' => '',
        'prefix' => '',
        'primaryKey' => 'id',
        'displayField' => ['title', 'name', 'id'],
        'entity' => 'Titon\Db\Entity',
        'collection' => 'Titon\Db\EntityCollection'
    ];

    /**
     * Database instance.
     *
     * @type \Titon\Db\Database
     */
    protected $_database;

    /**
     * Driver instance.
     *
     * @type \Titon\Db\Driver
     */
    protected $_driver;

    /**
     * List of finders.
     *
     * @type \Titon\Db\Finder[]
     */
    protected $_finders = [];

    /**
     * Database table schema object, or an array of column data.
     *
     * @type \Titon\Db\Driver\Schema|array
     */
    protected $_schema = [];

    /**
     * Initialize class and events.
     *
     * @param array $config
     */
    public function __construct(array $config = []) {
        parent::__construct($config);

        $this->on('db', $this);
        $this->addFinder('first', new FirstFinder());
        $this->addFinder('all', new AllFinder());
        $this->addFinder('list', new ListFinder());
    }

    /**
     * Add a behavior.
     *
     * @param \Titon\Db\Behavior $behavior
     * @return $this
     */
    public function addBehavior(Behavior $behavior) {
        $behavior->setRepository($this);

        $this->_behaviors[$behavior->getAlias()] = $behavior;

        $this->attachObject($behavior->getAlias(), $behavior);

        if ($behavior instanceof Listener) {
            $this->on('db', $behavior);
        }

        return $this;
    }

    /**
     * Add a query finder.
     *
     * @param string $key
     * @param \Titon\Db\Finder $finder
     * @return $this
     */
    public function addFinder($key, Finder $finder) {
        $this->_finders[$key] = $finder;

        return $this;
    }

    /**
     * Perform an aggregation on the database and return the calculated value.
     * The currently supported aggregates are `avg`, `count`, `min`, `max`, and `sum`.
     *
     * @param \Titon\Db\Query $query
     * @param string $function
     * @param string $field
     * @return int
     */
    public function aggregate(Query $query, $function, $field) {
        $query->fields(
            Query::func(strtoupper($function), [$field => Func::FIELD])->asAlias('aggregate')
        );

        $results = $this->getDriver()
            ->setContext('read')
            ->executeQuery($query)
            ->find();

        if (isset($results[0])) {
            return (int) $results[0]['aggregate'];
        }

        return 0;
    }

    /**
     * Type cast the results and wrap each result in an entity after a find operation.
     *
     * @param \Titon\Event\Event $event
     * @param array $results
     * @param string $finder
     */
    public function castResults(Event $event, array &$results, $finder) {
        $columns = $this->getSchema()->getColumns();
        $driver = $this->getDriver();
        $entityClass = $this->getEntity();

        foreach ($results as $i => $result) {
            foreach ($result as $field => $value) {
                if (isset($columns[$field])) {
                    $result[$field] = $driver->getType($columns[$field]['type'])->from($value);
                }

                if (!is_array($value)) {
                    continue;
                }

                $result[$field] = new Entity($value);
            }

            $results[$i] = new $entityClass($result);
        }
    }

    /**
     * Insert data into the database as a new record.
     * If any related data exists, insert new records after joining them to the original record.
     * Validate schema data and related data structure before inserting.
     *
     * @param array|\Titon\Type\Contract\Arrayable $data
     * @param array $options
     * @return int The record ID on success, 0 on failure
     */
    public function create($data, array $options = []) {
        return $this->query(Query::INSERT)->save($data, $options);
    }

    /**
     * Insert multiple records into the database using a single query.
     * Missing fields will be added with an empty value or the schema default value.
     * Does not support callbacks or transactions.
     *
     * @uses Titon\Utility\Hash
     *
     * @param array $data Multi-dimensional array of records
     * @param bool $allowPk If true will allow primary key fields, else will remove them
     * @param array $options
     * @return int The count of records inserted
     */
    public function createMany(array $data, $allowPk = false, array $options = []) {
        $pk = $this->getPrimaryKey();
        $columns = $this->getSchema()->getColumns();
        $records = [];
        $defaults = [];

        if ($columns) {
            foreach ($columns as $key => $column) {
                $defaults[$key] = array_key_exists('default', $column) ? $column['default'] : '';
            }

            unset($defaults[$pk]);
        }

        foreach ($data as $record) {

            // Convert from an entity
            if ($record instanceof Arrayable) {
                $record = $record->toArray();
            }

            // Merge in defaults
            $record = Hash::merge($defaults, $record);

            // Remove primary key
            if (!$allowPk) {
                unset($record[$pk]);
            }

            // Filter out invalid columns
            if ($columns) {
                $record = array_intersect_key($record, $columns);
            }

            $records[] = $record;
        }

        return $this->query(Query::MULTI_INSERT)->save($records, $options);
    }

    /**
     * Create a database table and indexes based off the tables schema.
     * The schema must be an array of column data.
     *
     * @param array $options
     * @param array $attributes
     * @return bool
     */
    public function createTable(array $options = [], array $attributes = []) {
        $schema = $this->getSchema();
        $schema->addOptions($options);

        // Create the table
        $status = (bool) $this->query(Query::CREATE_TABLE)
            ->attribute($attributes)
            ->schema($schema)
            ->save();

        // Create the indexes
        if ($status) {
            foreach ($schema->getIndexes() as $index => $columns) {
                $this->query(Query::CREATE_INDEX)
                    ->from($schema->getTable(), $index)
                    ->save($columns);
            }
        }

        return $status;
    }

    /**
     * Decrement the value of a field(s) using a step number.
     * Will update all records, or a single record.
     *
     * @param int|int[]|\Closure $id
     * @param array $fields
     * @return int
     */
    public function decrement($id, array $fields) {
        $data = [];

        foreach ($fields as $field => $step) {
            $data[$field] = Query::expr($field, '-', $step);
        }

        $query = $this->query(Query::UPDATE);

        if ($id instanceof Closure) {
            $query->bindCallback($id);

        } else if ($id) {
            $query->where($this->getPrimaryKey(), $id);
        }

        return $query->save($data);
    }

    /**
     * Delete a record by ID.
     *
     * @param int|int[] $id
     * @param array $options
     * @return int The count of records deleted
     */
    public function delete($id, array $options = []) {
        return $this->query(Query::DELETE)
            ->where($this->getPrimaryKey(), $id)
            ->save([], $options);
    }

    /**
     * Delete multiple records with conditions.
     *
     * @param \Closure $conditions
     * @param array $options
     * @return int The count of records deleted
     * @throws \Titon\Db\Exception\InvalidQueryException
     */
    public function deleteMany(Closure $conditions, array $options = []) {
        $query = $this->query(Query::DELETE)->bindCallback($conditions);

        // Validate that this won't delete all records
        $where = $query->getWhere()->getParams();

        if (empty($where)) {
            throw new InvalidQueryException('No where clause detected, will not delete all records');
        }

        return $query->save([], $options);
    }

    /**
     * Drop a database table.
     *
     * @return bool
     */
    public function dropTable() {
        return (bool) $this->query(Query::DROP_TABLE)->save();
    }

    /**
     * Check if a record with an ID exists.
     *
     * @param int $id
     * @return bool
     */
    public function exists($id) {
        return (bool) $this->select()->where($this->getPrimaryKey(), $id)->count();
    }

    /**
     * Filter out invalid columns before a save operation.
     *
     * @param \Titon\Event\Event $event
     * @param \Titon\Db\Query $query
     * @param int|int[] $id
     * @param array $data
     * @return bool
     */
    public function filterData(Event $event, Query $query, $id, array &$data) {
        if ($columns = $this->getSchema()->getColumns()) {
            $data = array_intersect_key($data, $columns);
        }

        return true;
    }

    /**
     * All-in-one method for fetching results from a query.
     * Depending on the type of finder, the returned results will differ.
     *
     * Before a fetch is executed, a `preFind` event will be triggered.
     * If this event returns a falsey value, the find will exit and
     * return a `noResults` value based on the current finder.
     * If this event returns an array of data, the find will exit and
     * return the array as the results instead of querying the driver.
     *
     * Before executing against the driver, the finders `before` method
     * will be called allowing the current query to be modified.
     * The driver connection context will also be set to `read`.
     * If no results are returned from the driver, the finders `noResults`
     * method will be called.
     *
     * After a fetch is executed (or a `preFind` event returns data),
     * a `postFind` event will be triggered. This event allows
     * the results to be modified via references.
     *
     * Finally, before the results are returned, wrap each row in an
     * entity object and pass it through the finders `after` method.
     *
     * @param \Titon\Db\Query $query
     * @param string $type
     * @param mixed $options {
     *      @type bool $before  Will trigger before callbacks
     *      @type bool $after   Will trigger after callbacks
     * }
     * @return array|\Titon\Db\Entity|\Titon\Db\EntityCollection
     */
    public function find(Query $query, $type, array $options = []) {
        $options = $options + [
            'before' => true,
            'after' => true,
            'collection' => $this->getConfig('collection')
        ];

        $finder = $this->getFinder($type);
        $state = null;

        if ($options['before']) {
            $event = $this->emit('db.preFind', [$query, $type]);
            $state = $event->getData();

            if ($state !== null && !$state) {
                return $finder->noResults($options);
            }
        }

        // Use the event response as the results
        if (is_array($state)) {
            $results = $state;

        // Query the driver for results
        } else {
            $finder->before($query, $options);

            // Update the connection context
            $results = $this->getDriver()
                ->setContext('read')
                ->executeQuery($query)
                ->find();
        }

        if (!$results) {
            return $finder->noResults($options);
        }

        if ($options['after']) {
            $this->emit('db.postFind', [&$results, $type]);
        }

        return $finder->after($results, $options);
    }

    /**
     * Find the a primary key value within a query. Begin by looping through the where clause and match any value
     * that equates to the PK field. If none can be found, do a select query for a list of IDs.
     *
     * @param \Titon\Db\Query $query
     * @return int|int[]
     */
    public function findID(Query $query) {
        $pk = $this->getPrimaryKey();

        // Gather ID from where clause
        foreach ($query->getWhere()->getParams() as $param) {
            if ($param instanceof Expr && $param->getField() === $pk && in_array($param->getOperator(), ['=', 'in'])) {
                return $param->getValue();
            }
        }

        // Query for the ID then
        $select = clone $query;
        $results = array_values($select->setType(Query::SELECT)->fields($pk)->lists($pk, $pk, [
            'before' => false,
            'after' => false
        ]));

        if (count($results) > 1) {
            return $results;

        } else if (count($results) === 1) {
            return $results[0];
        }

        return null;
    }

    /**
     * Return an alias for the repository. Usually the class name.
     *
     * @return string
     */
    public function getAlias() {
        return $this->inform('shortClassName');
    }

    /**
     * Return a behavior by alias.
     *
     * @param string $alias
     * @return \Titon\Db\Behavior
     * @throws \Titon\Db\Exception\MissingBehaviorException
     */
    public function getBehavior($alias) {
        if ($this->hasBehavior($alias)) {
            return $this->_behaviors[$alias];
        }

        throw new MissingBehaviorException(sprintf('Behavior %s does not exist', $alias));
    }

    /**
     * Return all behaviors.
     *
     * @return \Titon\Db\Behavior[]
     */
    public function getBehaviors() {
        return $this->_behaviors;
    }

    /**
     * Return the connection driver key.
     *
     * @return string
     */
    public function getConnectionKey() {
        return $this->getConfig('connection');
    }

    /**
     * Return the database class.
     * If none has been defined, register one.
     *
     * @return \Titon\Db\Database
     */
    public function getDatabase() {
        if (!$this->_database) {
            $this->setDatabase(Database::registry());
        }

        return $this->_database;
    }

    /**
     * Return the field used as the display field.
     *
     * @return string
     */
    public function getDisplayField() {
        return $this->cache(__METHOD__, function() {
            $fields = $this->getConfig('displayField');
            $schema = $this->getSchema();

            foreach ((array) $fields as $field) {
                if ($schema->hasColumn($field)) {
                    return $field;
                }
            }

            return $this->getPrimaryKey();
        });
    }

    /**
     * Return the driver defined by key.
     *
     * @uses Titon\Common\Registry
     *
     * @return \Titon\Db\Driver
     */
    public function getDriver() {
        if ($this->_driver) {
            return $this->_driver;
        }

        return $this->_driver = $this->getDatabase()->getDriver($this->getConnectionKey());
    }

    /**
     * Return the entity class name.
     *
     * @return string
     */
    public function getEntity() {
        return $this->getConfig('entity', 'Titon\Db\Entity');
    }

    /**
     * Return a finder by name.
     *
     * @param string $key
     * @return \Titon\Db\Finder
     * @throws \Titon\Db\Exception\MissingFinderException
     */
    public function getFinder($key) {
        if (isset($this->_finders[$key])) {
            return $this->_finders[$key];
        }

        throw new MissingFinderException(sprintf('Finder %s does not exist', $key));
    }

    /**
     * Return all finders.
     *
     * @return \Titon\Db\Finder[]
     */
    public function getFinders() {
        return $this->_finders;
    }

    /**
     * Return the field used as the primary, usually the ID.
     *
     * @return string
     */
    public function getPrimaryKey() {
        return $this->cache(__METHOD__, function() {
            $pk = $this->getConfig('primaryKey');
            $schema = $this->getSchema();

            if ($schema->hasColumn($pk)) {
                return $pk;
            }

            if ($pk = $schema->getPrimaryKey()) {
                return $pk['columns'][0];
            }

            return 'id';
        });
    }

    /**
     * Return a schema object that represents the database table.
     *
     * @return \Titon\Db\Driver\Schema
     */
    public function getSchema() {
        if ($this->_schema instanceof Schema) {
            return $this->_schema;

        // Manually defined columns
        // Allows for full schema and key/index support
        } else if ($this->_schema && is_array($this->_schema)) {
            $columns = $this->_schema;

        // Inspect database for columns
        // This approach should only be used for validating columns and types
        } else {
            $columns = $this->getDriver()->describeTable($this->getTable());
        }

        $this->setSchema(new Schema($this->getTable(), $columns));

        return $this->_schema;
    }

    /**
     * Return the full table name including prefix.
     *
     * @return string
     */
    public function getTable() {
        return $this->getTablePrefix() . $this->getConfig('table');
    }

    /**
     * Return only the table prefix.
     *
     * @return string
     */
    public function getTablePrefix() {
        return $this->getConfig('prefix');
    }

    /**
     * Check if the behavior exists.
     *
     * @param string $alias
     * @return bool
     */
    public function hasBehavior($alias) {
        return isset($this->_behaviors[$alias]);
    }

    /**
     * Check if any behavior has been set.
     *
     * @return bool
     */
    public function hasBehaviors() {
        return (count($this->_behaviors) > 0);
    }

    /**
     * Check if the finder exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasFinder($key) {
        return isset($this->_finders[$key]);
    }

    /**
     * Check if any finder has been set.
     *
     * @return bool
     */
    public function hasFinders() {
        return (count($this->_finders) > 0);
    }

    /**
     * Increment the value of a field(s) using a step number.
     * Will update all records, or a single record.
     *
     * @param int|int[]|\Closure $id
     * @param array $fields
     * @return int
     */
    public function increment($id, array $fields) {
        $data = [];

        foreach ($fields as $field => $step) {
            $data[$field] = Query::expr($field, '+', $step);
        }

        $query = $this->query(Query::UPDATE);

        if ($id instanceof Closure) {
            $query->bindCallback($id);

        } else if ($id) {
            $query->where($this->getPrimaryKey(), $id);
        }

        return $query->save($data);
    }

    /**
     * Instantiate a new query builder.
     *
     * @param string $type
     * @return \Titon\Db\Query
     */
    public function query($type) {
        $query = $this->getDriver()->newQuery($type);
        $query->setRepository($this);
        $query->from($this->getTable(), $this->getAlias());

        return $query;
    }

    /**
     * Fetch a single record by ID.
     *
     * @param int $id
     * @param array $options
     * @param \Closure $callback
     * @return \Titon\Db\Entity|array
     */
    public function read($id, array $options = [], Closure $callback = null) {
        return $this->select()
            ->where($this->getPrimaryKey(), $id)
            ->bindCallback($callback)
            ->first($options);
    }

    /**
     * {@inheritdoc}
     */
    public function registerEvents() {
        return [
            'db.postFind' => ['method' => 'castResults', 'priority' => 1],
            'db.preSave' => ['method' => 'filterData', 'priority' => 1]
        ];
    }

    /**
     * Return a count of how many rows were affected by the query.
     *
     * @param \Titon\Db\Query $query
     * @param array $options
     * @return int
     */
    public function save(Query $query, array $options = []) {
        $type = $query->getType();

        if ($type === Query::DELETE) {
            return $this->_processDelete($query, $options);

        } else if ($type === Query::INSERT) {
            return $this->_processCreate($query, $options);

        } else if ($type === Query::UPDATE) {
            return $this->_processUpdate($query, $options);
        }

        // No processing or events, just execute it directly
        return $this->getDriver()->executeQuery($query)->save();
    }

    /**
     * Instantiate a new select query.
     *
     * @return \Titon\Db\Query
     */
    public function select() {
        return $this->query(Query::SELECT)->fields(func_get_args());
    }

    /**
     * Set the database class.
     *
     * @param \Titon\Db\Database $database
     * @return $this
     */
    public function setDatabase(Database $database) {
        $this->_database = $database;

        return $this;
    }

    /**
     * Set the schema for this repository.
     *
     * @param \Titon\Db\Driver\Schema $schema
     * @return $this
     */
    public function setSchema(Schema $schema) {
        $this->_schema = $schema;

        return $this;
    }

    /**
     * Truncate a database and remove all records.
     *
     * @return bool
     */
    public function truncate() {
        return (bool) $this->query(Query::TRUNCATE)->save();
    }

    /**
     * Update a database record based on ID.
     *
     * @param int $id
     * @param array|\Titon\Type\Contract\Arrayable $data
     * @param array $options
     * @return int The count of records updated
     */
    public function update($id, $data, array $options = []) {
        return $this->query(Query::UPDATE)
            ->where($this->getPrimaryKey(), $id)
            ->save($data, $options);
    }

    /**
     * Update multiple records with conditions.
     *
     * @param array|\Titon\Type\Contract\Arrayable $data
     * @param \Closure $conditions
     * @param array $options
     * @return int The count of records updated
     * @throws \Titon\Db\Exception\InvalidQueryException
     */
    public function updateMany($data, Closure $conditions, array $options = []) {
        return $this->query(Query::UPDATE)
            ->bindCallback($conditions)
            ->save($data, $options);
    }

    /**
     * Either update or insert a record by checking for ID and record existence.
     *
     * @param array|\Titon\Type\Contract\Arrayable $data
     * @param int $id
     * @param array $options
     * @return int The record ID on success, 0 on failure
     */
    public function upsert($data, $id = null, array $options = []) {
        $pk = $this->getPrimaryKey();
        $update = false;

        // Check for an ID in the data
        if (!$id && isset($data[$pk])) {
            $id = $data[$pk];
        }

        unset($data[$pk]);

        // Check for record existence
        if ($id) {
            $update = $this->exists($id);
        }

        // Either update
        if ($update) {
            if ($this->update($id, $data, $options) === false) {
                return 0;
            }

        // Or insert
        } else {
            $id = $this->create($data, $options);
        }

        return $id;
    }

    /**
     * Primary method that handles the processing of insert queries.
     *
     * Before a save is executed, a `preSave` and `preCreate` event will be triggered.
     * This event allows data to be modified before saving via references.
     * If this event returns a falsey value, the save will exit early and
     * return a 0. This allows behaviors and events to cease save operations.
     *
     * Before the driver is queried, the connection context will be set to `write`.
     *
     * After the query has executed, and no rows have been affected, the method
     * will exit early with a 0 response. Otherwise, a `postSave` and `postCreate` event will be triggered.
     *
     * @param \Titon\Db\Query $query
     * @param mixed $options {
     *      @type bool $before  Will trigger before callbacks
     *      @type bool $after   Will trigger after callbacks
     * }
     * @return int
     *      - The ID of the record if successful
     *      - 0 if save operation failed
     */
    protected function _processCreate(Query $query, array $options = []) {
        $data = $query->getData();
        $options = $options + [
            'before' => true,
            'after' => true
        ];

        if ($options['before']) {
            foreach (['db.preSave', 'db.preCreate'] as $event) {
                $event = $this->emit($event, [$query, null, &$data]);
                $state = $event->getData();

                if ($state !== null && !$state) {
                    return 0;
                }
            }
        }

        // Reset the modified data
        $query->data($data);

        // Update the connection context
        $driver = $this->getDriver();
        $driver->setContext('write');

        // Execute the query
        $count = $driver->executeQuery($query)->save();

        // Exit early if save failed
        if ($count === false) {
            return 0;
        }

        $id = $driver->getLastInsertID($this);

        if ($options['after']) {
            $this->emit('db.postSave db.postCreate', [$id, $count]);
        }

        return $this->id = $id;
    }

    /**
     * Primary method that handles the processing of delete queries.
     *
     * Before a delete is executed, a `preDelete` event will be triggered.
     * If a falsey value is returned, exit early with a 0. If a numeric value
     * is returned, exit early and return the number, which acts as a virtual
     * affected row count (permitting behaviors to short circuit the process).
     *
     * Before the driver is queried, the connection context will be set to `delete`.
     *
     * After a delete has executed successfully, a `postDelete` event will be triggered.
     *
     * @param \Titon\Db\Query $query
     * @param mixed $options {
     *      @type bool $before  Will trigger before callbacks
     *      @type bool $after   Will trigger after callbacks
     * }
     * @return int The count of records deleted
     */
    protected function _processDelete(Query $query, array $options = []) {
        $options = $options + [
            'before' => true,
            'after' => true
        ];

        // Fetch ID
        $this->id = $id = $this->findID($query);

        if ($options['before']) {
            $event = $this->emit('db.preDelete', [$query, $id]);
            $state = $event->getData();

            if ($state !== null) {
                if (!$state) {
                    return 0;
                } else if (is_numeric($state)) {
                    return (int) $state;
                }
            }
        }

        // Update the connection context and execute the query
        $count = $this->getDriver()
            ->setContext('delete')
            ->executeQuery($query)
            ->save();

        // Only trigger callback if something was deleted
        if ($count && $options['after']) {
            $this->emit('db.postDelete', [$id, $count]);
        }

        return (int) $count;
    }

    /**
     * Primary method that handles the processing of update queries.
     *
     * Before a save is executed, a `preSave` and `preUpdate` event will be triggered.
     * This event allows data to be modified before saving via references.
     * If this event returns a falsey value, the save will exit early and
     * return a 0. This allows behaviors and events to cease save operations.
     *
     * Before the driver is queried, the connection context will be set to `write`.
     *
     * After the query has executed, and no rows have been affected, the method
     * will exit early with a 0 response. Otherwise, a `postSave` and `postUpdate` event will be triggered.
     *
     * @param \Titon\Db\Query $query
     * @param mixed $options {
     *      @type bool $before  Will trigger before callbacks
     *      @type bool $after   Will trigger after callbacks
     * }
     * @return int
     *      - The count of records updated
     *      - 0 if save operation failed
     */
    protected function _processUpdate(Query $query, array $options = []) {
        $data = $query->getData();
        $options = $options + [
            'before' => true,
            'after' => true
        ];

        // Fetch ID
        $this->id = $id = $this->findID($query);

        if ($options['before']) {
            foreach (['db.preSave', 'db.preUpdate'] as $event) {
                $event = $this->emit($event, [$query, $id, &$data]);
                $state = $event->getData();

                if ($state !== null && !$state) {
                    return 0;
                }
            }
        }

        // Reset the modified data
        $query->data($data);

        // Update the connection context and execute the query
        $count = $this->getDriver()
            ->setContext('write')
            ->executeQuery($query)
            ->save();

        // Exit early if save failed
        if ($count === false) {
            return false;
        }

        if ($options['after']) {
            $this->emit('db.postSave db.postUpdate', [$id, $count]);
        }

        return $count;
    }

}