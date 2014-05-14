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
use Titon\Db\Driver\Type\AbstractType;
use Titon\Db\Exception\InvalidQueryException;
use Titon\Db\Exception\MissingBehaviorException;
use Titon\Db\Exception\MissingFinderException;
use Titon\Db\Finder;
use Titon\Db\Finder\FirstFinder;
use Titon\Db\Finder\AllFinder;
use Titon\Db\Finder\ListFinder;
use Titon\Db\Query;
use Titon\Event\Event;
use Titon\Event\Listener;
use Titon\Event\Traits\Emittable;
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
 */
class Repository extends Base implements Listener {
    use Attachable, Cacheable, Emittable;

    /**
     * ID of last updated or inserted record.
     *
     * @type int
     */
    public $id;

    /**
     * Data or results from the last query.
     *
     * @type array
     */
    public $data = [];

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
     *      @type $string $connection   The connection driver key
     *      @type $string $repo         Database table name
     *      @type $string $prefix       Prefix to prepend to the table name
     *      @type $string $primaryKey   The field representing the primary key
     *      @type $string $displayField The field representing a readable label
     *      @type $string $entity       The Entity class to wrap results in
     * }
     */
    protected $_config = [
        'connection' => 'default',
        'table' => '',
        'prefix' => '',
        'primaryKey' => 'id',
        'displayField' => ['title', 'name', 'id'],
        'entity' => 'Titon\Db\Entity'
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
     * Type cast the results after a find operation.
     *
     * @param \Titon\Event\Event $event
     * @param array $results
     * @param string $finder
     */
    public function castResults(Event $event, array &$results, $finder) {
        $schema = $this->getSchema()->getColumns();

        if (!$schema) {
            return;
        }

        $driver = $this->getDriver();

        // TODO - Type cast the results first
        // I feel like this should be on the driver layer, but where and how?
        // Was thinking of using events on the driver, but no access to the repository or schema...
        foreach ($results as $i => $result) {
            foreach ($result as $field => $value) {
                if (isset($schema[$field])) {
                    $results[$i][$field] = AbstractType::factory($schema[$field]['type'], $driver)->from($value);
                }
            }
        }
    }

    /**
     * Return a count of results from the query.
     *
     * @param \Titon\Db\Query $query
     * @return int
     */
    public function count(Query $query) {
        return $this->getDriver()->executeQuery($query)->count();
    }

    /**
     * Insert data into the database as a new record.
     * If any related data exists, insert new records after joining them to the original record.
     * Validate schema data and related data structure before inserting.
     *
     * @param array $data
     * @param array $options
     * @return int The record ID on success, 0 on failure
     */
    public function create(array $data, array $options = []) {
        return $this->_processSave($this->query(Query::INSERT), null, $data, $options);
    }

    /**
     * Insert multiple records into the database using a single query.
     * Missing fields will be added with an empty value or the schema default value.
     * Does not support callbacks or transactions.
     *
     * @uses Titon\Utility\Hash
     *
     * @param array $data Multi-dimensional array of records
     * @param bool $hasPk If true will allow primary key fields, else will remove them
     * @param array $options
     * @return int The count of records inserted
     */
    public function createMany(array $data, $hasPk = false, array $options = []) {
        $records = [];
        $defaults = $this->_mapDefaults();
        $pk = $this->getPrimaryKey();

        foreach ($data as $record) {
            $record = Hash::merge($defaults, $record);

            if (!$hasPk) {
                unset($record[$pk]);
            }

            $records[] = $record;
        }

        return $this->_processSave($this->query(Query::MULTI_INSERT), null, $records, $options);
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
                    ->fields($columns)
                    ->save();
            }
        }

        return $status;
    }

    /**
     * Decrement the value of a field(s) using a step number.
     * Will update all records, or a single record.
     *
     * @param int|int[] $id
     * @param array $fields
     * @return int
     */
    public function decrement($id, array $fields) {
        $query = $this->query(Query::UPDATE);

        if ($id) {
            $query->where($this->getPrimaryKey(), $id);
        }

        $data = [];

        foreach ($fields as $field => $step) {
            $data[$field] = Query::expr($field, '-', $step);
        }

        return $query->fields($data)->save();
    }

    /**
     * Delete a record by ID.
     *
     * @param int|int[] $id
     * @param array $options
     * @return int The count of records deleted
     */
    public function delete($id, array $options = []) {
        $query = $this->query(Query::DELETE)->where($this->getPrimaryKey(), $id);

        return $this->_processDelete($query, $id, $options);
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
        $pk = $this->getPrimaryKey();
        $ids = $this->select($pk)->bindCallback($conditions)->lists($pk, $pk);
        $query = $this->query(Query::DELETE)->bindCallback($conditions);

        // Validate that this won't delete all records
        $where = $query->getWhere()->getParams();

        if (empty($where)) {
            throw new InvalidQueryException('No where clause detected, will not delete all records');
        }

        return $this->_processDelete($query, $ids, $options);
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
     * All-in-one method for fetching results from a query.
     * Depending on the type of finder, the returned results will differ.
     *
     * @param \Titon\Db\Query $query
     * @param string $type
     * @param mixed $options {
     *      @type bool $preCallback     Will trigger before callbacks
     *      @type bool $postCallback    Will trigger after callbacks
     * }
     * @return array|\Titon\Db\Entity|\Titon\Db\EntityCollection
     */
    public function find(Query $query, $type, array $options = []) {
        $options = $options + [
            'preCallback' => true,
            'postCallback' => true
        ];

        $finder = $this->getFinder($type);
        $state = null;

        // Use the return of preFind() if applicable
        if ($options['preCallback']) {
            $event = $this->emit('db.preFind', [$query, $type]);
            $state = $event->getData();

            if ($state !== null && !$state) {
                return $finder->noResults();
            }
        }

        // If the event returns custom data, use it
        if (is_array($state)) {
            $results = $state;

            if (!isset($results[0])) {
                $results = [$results];
            }

        // Else find new records
        } else {
            $finder->before($query, $options);

            // Update the connection context
            $driver = $this->getDriver();
            $driver->setContext('read');

            $results = $driver->executeQuery($query)->find();
        }

        if (!$results) {
            return $finder->noResults();
        }

        if ($options['postCallback']) {
            $this->emit('db.postFind', [&$results, $type]);
        }

        // Wrap the results in entities
        $this->data = $results = $this->wrapEntities($results);

        // Reset the driver local cache
        $this->getDriver()->reset();

        return $finder->after($results, $options);
    }

    /**
     * Return an alias for the table. Usually the class name.
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
     * Increment the value of a field(s) using a step number.
     * Will update all records, or a single record.
     *
     * @param int|int[] $id
     * @param array $fields
     * @return int
     */
    public function increment($id, array $fields) {
        $query = $this->query(Query::UPDATE);

        if ($id) {
            $query->where($this->getPrimaryKey(), $id);
        }

        $data = [];

        foreach ($fields as $field => $step) {
            $data[$field] = Query::expr($field, '+', $step);
        }

        return $query->fields($data)->save();
    }

    /**
     * Instantiate a new query builder.
     *
     * @param string $type
     * @return \Titon\Db\Query
     */
    public function query($type) {
        $this->data = [];

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
            'db.postFind' => ['method' => 'castResults', 'priority' => 1]
        ];
    }

    /**
     * Return a count of how many rows were affected by the query.
     *
     * @param \Titon\Db\Query $query
     * @return int
     */
    public function save(Query $query) {
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
     * Set and merge result data into the repository.
     *
     * @uses Titon\Utility\Hash
     *
     * @param array $data
     * @return $this
     */
    public function setData(array $data) {
        $this->data = Hash::merge($this->_mapDefaults(), $this->data, $data);

        return $this;
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
     * Set the schema for this repository table.
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
     * @param array $data
     * @param array $options
     * @return int The count of records updated
     */
    public function update($id, array $data, array $options = []) {
        $query = $this->query(Query::UPDATE)->where($this->getPrimaryKey(), $id);

        return $this->_processSave($query, $id, $data, $options);
    }

    /**
     * Update multiple records with conditions.
     *
     * @param array $data
     * @param \Closure $conditions
     * @param array $options
     * @return int The count of records updated
     * @throws \Titon\Db\Exception\InvalidQueryException
     */
    public function updateMany(array $data, Closure $conditions, array $options = []) {
        $pk = $this->getPrimaryKey();
        $ids = $this->select($pk)->bindCallback($conditions)->lists($pk, $pk);
        $query = $this->query(Query::UPDATE)->bindCallback($conditions);

        // Validate that this won't update all records
        $where = $query->getWhere()->getParams();

        if (empty($where)) {
            throw new InvalidQueryException('No where clause detected, will not update all records');
        }

        return $this->_processSave($query, $ids, $data, $options);
    }

    /**
     * Either update or insert a record by checking for ID and record existence.
     *
     * @param array $data
     * @param int $id
     * @param array $options
     * @return int The record ID on success, 0 on failure
     */
    public function upsert(array $data, $id = null, array $options = []) {
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

            // Do false check since updating can return 0 rows affected
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
     * Wrap results in the defined entity class and wrap joined data in the originating tables entity class.
     *
     * @param array $results
     * @return \Titon\Db\Entity[]
     */
    public function wrapEntities(array $results) {
        $entityClass = $this->getEntity();

        foreach ($results as $i => $result) {
            foreach ($result as $key => $value) {
                if (!is_array($value)) {
                    continue;
                }

                $result[$key] = new Entity($value);
            }

            $results[$i] = new $entityClass($result);
        }

        return $results;
    }

    /**
     * Map the schema with empty fields.
     *
     * @return array
     */
    protected function _mapDefaults() {
        $defaults = [];

        if ($schema = $this->getSchema()) {
            foreach ($schema->getColumns() as $column => $data) {
                $defaults[$column] = array_key_exists('default', $data) ? $data['default'] : '';
            }
        }

        return $defaults;
    }

    /**
     * Primary method that handles the processing of delete queries.
     *
     * @param \Titon\Db\Query $query
     * @param int|int[] $id
     * @param mixed $options {
     *      @type bool $preCallback     Will trigger before callbacks
     *      @type bool $postCallback    Will trigger after callbacks
     * }
     * @return int The count of records deleted
     */
    protected function _processDelete(Query $query, $id, array $options = []) {
        $options = $options + [
            'preCallback' => true,
            'postCallback' => true
        ];

        // If a falsey value is returned, exit early
        // If an integer is returned, return it
        if ($options['preCallback']) {
            $event = $this->emit('db.preDelete', [$id, &$options['cascade']]);
            $state = $event->getData();

            if ($state !== null) {
                if (!$state) {
                    return 0;
                } else if (is_numeric($state)) {
                    return $state;
                }
            }
        }

        // Update the connection group
        $driver = $this->getDriver();
        $driver->setContext('delete');

        // Execute the query
        $count = $query->save();

        if ($count === false) {
            return 0;
        }

        $this->data = [];

        if ($options['postCallback']) {
            $this->emit('db.postDelete', [$id]);
        }

        return $count;
    }

    /**
     * Primary method that handles the processing of insert and update queries.
     *
     * @param \Titon\Db\Query $query
     * @param int|int[] $id
     * @param array $data
     * @param mixed $options {
     *      @type bool $preCallback     Will trigger before callbacks
     *      @type bool $postCallback    Will trigger after callbacks
     * }
     * @return int
     *      - The count of records updated if an update
     *      - The ID of the record if an insert
     */
    protected function _processSave(Query $query, $id, array $data, $options = []) {
        $isCreate = ($query->getType() === Query::INSERT);
        $options = $options + [
            'preCallback' => true,
            'postCallback' => true
        ];

        // Trigger before events
        if ($options['preCallback']) {
            $event = $this->emit('db.preSave', [$id, &$data]);
            $state = $event->getData();

            if ($state !== null && !$state) {
                return 0;
            }
        }

        // Filter and set the data
        if ($query->getType() !== Query::MULTI_INSERT) {
            if ($columns = $this->getSchema()->getColumns()) {
                $data = array_intersect_key($data, $columns);
            }
        }

        $query->fields($data);

        // Update the connection context
        $driver = $this->getDriver();
        $driver->setContext('write');

        // Execute the query
        $count = $query->save();

        if ($count === false) {
            return 0;
        }

        if ($isCreate) {
            $id = $driver->getLastInsertID($this);
        }

        if (!is_array($id)) {
            $this->id = $id;
            $this->setData([$this->getPrimaryKey() => $id] + $data);
        }

        // Trigger after events
        if ($options['postCallback']) {
            $this->emit('db.postSave', [$id, $isCreate]);
        }

        // Return ID for create, or count for update
        if ($isCreate) {
            return $id;
        }

        return $count;
    }

}