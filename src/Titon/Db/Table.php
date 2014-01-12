<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Common\Base;
use Titon\Common\Registry;
use Titon\Common\Traits\Attachable;
use Titon\Common\Traits\Cacheable;
use Titon\Event\Event;
use Titon\Event\Listener;
use Titon\Event\Traits\Emittable;
use Titon\Db\Driver\Dialect;
use Titon\Db\Driver\Schema;
use Titon\Db\Driver\Type\AbstractType;
use Titon\Db\Exception\InvalidQueryException;
use Titon\Db\Exception\InvalidRelationStructureException;
use Titon\Db\Exception\MissingBehaviorException;
use Titon\Db\Exception\MissingRelationException;
use Titon\Db\Exception\QueryFailureException;
use Titon\Db\Query;
use Titon\Db\Relation\OneToOne;
use Titon\Db\Relation\OneToMany;
use Titon\Db\Relation\ManyToOne;
use Titon\Db\Relation\ManyToMany;
use Titon\Utility\Hash;
use Titon\Utility\Path;
use \Exception;
use \Closure;

/**
 * Represents a database table.
 *
 *      - Defines a schema
 *      - Defines relations
 *          - One-to-one, One-to-many, Many-to-one, Many-to-many
 *      - Allows for queries to be built and executed
 *          - Returns Entity objects for each record in the result
 *
 * @link http://en.wikipedia.org/wiki/Database_model
 * @link http://en.wikipedia.org/wiki/Relational_model
 *
 * @package Titon\Db
 */
class Table extends Base implements Callback, Listener {
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
     *      @type $string $table        Database table name
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
     * Driver instance.
     *
     * @type \Titon\Db\Driver
     */
    protected $_driver;

    /**
     * Table to table relationships.
     *
     * @type \Titon\Db\Relation[]
     */
    protected $_relations = [];

    /**
     * Database table schema object, or an array of column data.
     *
     * @type \Titon\Db\Driver\Schema|array
     */
    protected $_schema;

    /**
     * Initialize class and events.
     *
     * @param array $config
     */
    public function __construct(array $config = []) {
        parent::__construct($config);

        $this->on('db', $this);
    }

    /**
     * Add a behavior.
     *
     * @param \Titon\Db\Behavior $behavior
     * @return \Titon\Db\Behavior
     */
    public function addBehavior(Behavior $behavior) {
        $behavior->setTable($this);

        $this->_behaviors[$behavior->getAlias()] = $behavior;

        if ($behavior instanceof Listener) {
            $this->on('db', $behavior);
        }

        $this->attachObject(str_replace('Behavior', '', Path::className(get_class($behavior))), $behavior);

        return $behavior;
    }

    /**
     * Add a relation between another table.
     *
     * @param \Titon\Db\Relation $relation
     * @return \Titon\Db\Relation|\Titon\Db\Relation\ManyToMany
     */
    public function addRelation(Relation $relation) {
        $relation->setTable($this);

        $this->_relations[$relation->getAlias()] = $relation;

        $this->attachObject([
            'alias' => $relation->getAlias(),
            'interface' => 'Titon\Db\Table'
        ], function() use ($relation) {
            return $relation->getRelatedTable();
        });

        return $relation;
    }

    /**
     * Add a many-to-one relationship.
     *
     * @param string $alias
     * @param string|\Titon\Db\Table $table
     * @param string $foreignKey
     * @return \Titon\Db\Relation\ManyToOne
     */
    public function belongsTo($alias, $table, $foreignKey) {
        return $this->addRelation(new ManyToOne($alias, $table))
            ->setForeignKey($foreignKey);
    }

    /**
     * Add a many-to-many relationship.
     *
     * @param string $alias
     * @param string|\Titon\Db\Table $table
     * @param string $junction
     * @param string $foreignKey
     * @param string $relatedKey
     * @return \Titon\Db\Relation\ManyToMany
     */
    public function belongsToMany($alias, $table, $junction, $foreignKey, $relatedKey) {
        return $this->addRelation(new ManyToMany($alias, $table))
            ->setJunctionClass($junction)
            ->setForeignKey($foreignKey)
            ->setRelatedForeignKey($relatedKey);
    }

    /**
     * Return a count of results from the query.
     *
     * @param \Titon\Db\Query $query
     * @return int
     */
    public function count(Query $query) {
        return $this->getDriver()->query($query)->count();
    }

    /**
     * Insert data into the database as a new record.
     * If any related data exists, insert new records after joining them to the original record.
     * Validate schema data and related data structure before inserting.
     *
     * @param array $data
     * @param array $options
     * @return int The record ID on success, 0 on failure
     * @throws \Titon\Db\Exception\QueryFailureException
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
     * @return int The count of records inserted
     */
    public function createMany(array $data, $hasPk = false) {
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

        return $this->query(Query::MULTI_INSERT)->fields($records)->save();
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
     * Delete a record by ID.
     *
     * @param int|int[] $id
     * @param mixed $options
     * @return int The count of records deleted
     */
    public function delete($id, $options = true) {
        $query = $this->query(Query::DELETE)->where($this->getPrimaryKey(), $id);

        return $this->_processDelete($query, $id, $options);
    }

    /**
     * Loop through all table relations and delete dependent records using the ID as a base.
     * Will return a count of how many dependent records were deleted.
     *
     * @param int|int[] $id
     * @param bool $cascade Will delete related records if true
     * @return int The count of records deleted
     * @throws \Titon\Db\Exception\QueryFailureException
     * @throws \Exception
     */
    public function deleteDependents($id, $cascade = true) {
        $count = 0;
        $driver = $this->getDriver();

        if (!$driver->startTransaction()) {
            throw new QueryFailureException('Failed to start database transaction');
        }

        try {
            foreach ($this->getRelations() as $relation) {
                if (!$relation->isDependent()) {
                    continue;
                }

                switch ($relation->getType()) {
                    case Relation::ONE_TO_ONE:
                    case Relation::ONE_TO_MANY:
                        $relatedTable = $relation->getRelatedTable();
                        $results = [];

                        // Fetch IDs before deletion
                        // Only delete if relations exist
                        if ($cascade && $relatedTable->hasRelations()) {
                            $results = $relatedTable
                                ->select($relatedTable->getPrimaryKey())
                                ->where($relation->getRelatedForeignKey(), $id)
                                ->fetchAll(false);
                        }

                        // Delete all records at once
                        $count += $relatedTable
                            ->query(Query::DELETE)
                            ->where($relation->getRelatedForeignKey(), $id)
                            ->save();

                        // Loop through the records and cascade delete dependents
                        if ($results) {
                            $count += $relatedTable->deleteDependents(Hash::pluck($results, $relatedTable->getPrimaryKey()), $cascade);
                        }
                    break;

                    case Relation::MANY_TO_MANY:
                        /** @type \Titon\Db\Table $junctionTable */
                        $junctionTable = $relation->getJunctionTable();

                        // Only delete the junction records
                        // The related records should stay
                        $count += $junctionTable
                            ->query(Query::DELETE)
                            ->where($relation->getForeignKey(), $id)
                            ->save();
                    break;
                }
            }

            $driver->commitTransaction();

        // Rollback and re-throw exception
        } catch (Exception $e) {
            $driver->rollbackTransaction();

            throw $e;
        }

        return $count;
    }

    /**
     * Delete multiple records with conditions.
     *
     * @param \Closure $conditions
     * @param mixed $options
     * @return int The count of records deleted
     * @throws \Titon\Db\Exception\InvalidQueryException
     */
    public function deleteMany(Closure $conditions, $options = true) {
        $pk = $this->getPrimaryKey();
        $ids = $this->select($pk)->bindCallback($conditions)->fetchList($pk, $pk);
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
     * Return an entity for the first result from the query.
     *
     * @param \Titon\Db\Query $query
     * @param array $options
     * @return \Titon\Db\Entity|array
     */
    public function fetch(Query $query, array $options = []) {
        return $this->_processFetch($query, __FUNCTION__, $options);
    }

    /**
     * Return a list of entities from the results of the query.
     *
     * @param \Titon\Db\Query $query
     * @param array $options
     * @return \Titon\Db\Entity[]|array
     */
    public function fetchAll(Query $query, array $options = []) {
        return $this->_processFetch($query, __FUNCTION__, $options);
    }

    /**
     * Return the results as a list of key values.
     *
     * @param \Titon\Db\Query $query
     * @param string $key The field to use as the array key
     * @param string $value The field to use as the array value
     * @param array $options
     * @return array
     */
    public function fetchList(Query $query, $key = null, $value = null, array $options = []) {
        $results = $this->_processFetch($query, __FUNCTION__, $options);

        $key = $key ?: $this->getPrimaryKey();
        $value = $value ?: $this->getDisplayField();
        $list = [];

        foreach ($results as $result) {
            $data = $result->toArray();
            $list[Hash::extract($data, $key)] = Hash::extract($data, $value);
        }

        return $list;
    }

    /**
     * Once the primary query has been executed and the results have been fetched,
     * loop over all sub-queries and fetch related data.
     *
     * The related data will be added to the array indexed by the relation alias.
     *
     * @uses Titon\Utility\Hash
     *
     * @param \Titon\Db\Query $query
     * @param Entity $result
     * @param array $options
     * @return \Titon\Db\Entity
     */
    public function fetchRelations(Query $query, Entity $result, array $options = []) {
        $queries = $query->getRelationQueries();

        if (!$queries) {
            return $result;
        }

        foreach ($queries as $alias => $subQuery) {
            $newQuery = clone $subQuery;
            $relation = $this->getRelation($alias);
            $relatedTable = $relation->getRelatedTable();
            $relatedClass = get_class($relatedTable);

            switch ($relation->getType()) {

                // Has One
                // The related table should be pointing to this table
                // So use this ID in the related foreign key
                // Since we only want one record, limit it and single fetch
                case Relation::ONE_TO_ONE:
                    $foreignValue = $result[$this->getPrimaryKey()];

                    $newQuery
                        ->where($relation->getRelatedForeignKey(), $foreignValue)
                        ->cache([$relatedClass, 'fetchOneToOne', $foreignValue])
                        ->limit(1);

                    $result->set($alias, function() use ($newQuery, $options) {
                        return $newQuery->fetch($options);
                    });
                break;

                // Has Many
                // The related tables should be pointing to this table
                // So use this ID in the related foreign key
                // Since we want multiple records, fetch all with no limit
                case Relation::ONE_TO_MANY:
                    $foreignValue = $result[$this->getPrimaryKey()];

                    $newQuery
                        ->where($relation->getRelatedForeignKey(), $foreignValue)
                        ->cache([$relatedClass, 'fetchOneToMany', $foreignValue]);

                    $result->set($alias, function() use ($newQuery, $options) {
                        return $newQuery->fetchAll($options);
                    });
                break;

                // Belongs To
                // This table should be pointing to the related table
                // So use the foreign key as the related ID
                // We should only be fetching a single record
                case Relation::MANY_TO_ONE:
                    $foreignValue = $result[$relation->getForeignKey()];

                    $newQuery
                        ->where($relatedTable->getPrimaryKey(), $foreignValue)
                        ->cache([$relatedClass, 'fetchManyToOne', $foreignValue])
                        ->limit(1);

                    $result->set($alias, function() use ($newQuery, $options) {
                        return $newQuery->fetch($options);
                    });
                break;

                // Has And Belongs To Many
                // This table points to a related table through a junction table
                // Query the junction table for lookup IDs pointing to the related data
                case Relation::MANY_TO_MANY:
                    $foreignValue = $result[$this->getPrimaryKey()];

                    if (!$foreignValue) {
                        continue;
                    }

                    $result->set($alias, function() use ($relation, $newQuery, $foreignValue, $options) {
                        $relatedTable = $relation->getRelatedTable();
                        $relatedClass = get_class($relatedTable);
                        $lookupIDs = [];

                        // Fetch the related records using the junction IDs
                        $junctionTable = $relation->getJunctionTable();
                        $junctionResults = $junctionTable
                            ->select()
                            ->where($relation->getForeignKey(), $foreignValue)
                            ->cache([get_class($junctionTable), 'fetchManyToMany', $foreignValue])
                            ->fetchAll();

                        if (!$junctionResults) {
                            return [];
                        }

                        foreach ($junctionResults as $result) {
                            $lookupIDs[] = $result->get($relation->getRelatedForeignKey());
                        }

                        $m2mResults = $newQuery
                            ->where($relatedTable->getPrimaryKey(), $lookupIDs)
                            ->cache([$relatedClass, 'fetchManyToMany', $lookupIDs])
                            ->fetchAll($options);

                        // Include the junction data
                        foreach ($m2mResults as $i => $m2mResult) {
                            foreach ($junctionResults as $junctionResult) {
                                if ($junctionResult[$relation->getRelatedForeignKey()] == $m2mResult[$relatedTable->getPrimaryKey()]) {
                                    $m2mResults[$i]->set('Junction', $junctionResult);
                                }
                            }
                        }

                        return $m2mResults;
                    });
                break;
            }

            unset($newQuery);
        }

        return $result;
    }

    /**
     * Return an alias for the table. Usually the class name.
     *
     * @return string
     */
    public function getAlias() {
        return $this->info->shortClassName;
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
    public function getConnection() {
        return $this->config->connection;
    }

    /**
     * Return the field used as the display field.
     *
     * @return string
     */
    public function getDisplayField() {
        return $this->cache(__METHOD__, function() {
            $fields = $this->config->displayField;
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

        /** @type \Titon\Db\Driver $driver */
        $driver = Registry::factory('Titon\Db\Connection')->getDriver($this->getConnection());
        $driver->connect();

        $this->_driver = $driver;

        return $driver;
    }

    /**
     * Return the entity class name.
     *
     * @return string
     */
    public function getEntity() {
        return $this->config->entity ?: 'Titon\Db\Entity';
    }

    /**
     * Return the field used as the primary, usually the ID.
     *
     * @return string
     */
    public function getPrimaryKey() {
        return $this->cache(__METHOD__, function() {
            $pk = $this->config->primaryKey;
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
     * Return a relation by alias.
     *
     * @param string $alias
     * @return \Titon\Db\Relation|\Titon\Db\Relation\ManyToMany
     * @throws \Titon\Db\Exception\MissingRelationException
     */
    public function getRelation($alias) {
        if ($this->hasRelation($alias)) {
            return $this->_relations[$alias];
        }

        throw new MissingRelationException(sprintf('Table relation %s does not exist', $alias));
    }

    /**
     * Return all relations, or all relations by type.
     *
     * @param int $type
     * @return \Titon\Db\Relation[]
     */
    public function getRelations($type = 0) {
        if (!$type) {
            return $this->_relations;
        }

        $relations = [];

        foreach ($this->_relations as $relation) {
            if ($relation->getType() === $type) {
                $relations[$relation->getAlias()] = $relation;
            }
        }

        return $relations;
    }

    /**
     * Return a schema object that represents the database table.
     *
     * @return \Titon\Db\Driver\Schema
     */
    public function getSchema() {
        if ($this->_schema instanceof Schema) {
            return $this->_schema;
        }

        // Manually defined columns
        // Allows for full schema and key/index support
        if (is_array($this->_schema)) {
            $columns = $this->_schema;

        // Inspect database for columns
        // This approach should only be used for validating columns and types
        } else {
            $columns = $this->getDriver()->describeTable($this->getTableName());
        }

        $this->setSchema(new Schema($this->getTableName(), $columns));

        return $this->_schema;
    }

    /**
     * Return the full table name including prefix.
     *
     * @return string
     */
    public function getTableName() {
        return $this->config->prefix . $this->config->table;
    }

    /**
     * Return only the table prefix.
     *
     * @return string
     */
    public function getTablePrefix() {
        return $this->config->prefix;
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
     * Add a one-to-one relationship.
     *
     * @param string $alias
     * @param string|\Titon\Db\Table $table
     * @param string $relatedKey
     * @return \Titon\Db\Relation\OneToOne
     */
    public function hasOne($alias, $table, $relatedKey) {
        return $this->addRelation(new OneToOne($alias, $table))
            ->setRelatedForeignKey($relatedKey);
    }

    /**
     * Add a one-to-many relationship.
     *
     * @param string $alias
     * @param string|\Titon\Db\Table $table
     * @param string $relatedKey
     * @return \Titon\Db\Relation\OneToMany
     */
    public function hasMany($alias, $table, $relatedKey) {
        return $this->addRelation(new OneToMany($alias, $table))
            ->setRelatedForeignKey($relatedKey);
    }

    /**
     * Check if the relation exists.
     *
     * @param string $alias
     * @return bool
     */
    public function hasRelation($alias) {
        return isset($this->_relations[$alias]);
    }

    /**
     * Check if any relation has been set.
     *
     * @return bool
     */
    public function hasRelations() {
        return (count($this->_relations) > 0);
    }

    /**
     * Return results for the current query and a count of the results.
     *
     * @param \Titon\Db\Query $query
     * @return array
     */
    public function paginate(Query $query) {
        $count = clone $query;
        $count->limit(null, null);

        return [
            'count' => $count->count(),
            'results' => $query->fetchAll()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function preDelete(Event $event, $id, &$cascade) {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function preFetch(Event $event, Query $query, $fetchType) {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function preSave(Event $event, $id, array &$data) {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function postDelete(Event $event, $id) {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function postFetch(Event $event, array &$results, $fetchType) {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function postSave(Event $event, $id, $created = false) {
        return;
    }

    /**
     * Instantiate a new query builder.
     *
     * @param int $type
     * @return \Titon\Db\Query
     */
    public function query($type) {
        $this->data = [];

        $query = new Query($type, $this);
        $query->from($this->getTableName(), $this->getAlias());

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
            ->fetch($options);
    }

    /**
     * {@inheritdoc}
     */
    public function registerEvents() {
        return [
            'db.preSave' => ['method' => 'preSave', 'priority' => 1],
            'db.postSave' => ['method' => 'postSave', 'priority' => 1],
            'db.preDelete' => ['method' => 'preDelete', 'priority' => 1],
            'db.postDelete' => ['method' => 'postDelete', 'priority' => 1],
            'db.preFetch' => ['method' => 'preFetch', 'priority' => 1],
            'db.postFetch' => ['method' => 'postFetch', 'priority' => 1]
        ];
    }

    /**
     * Return a count of how many rows were affected by the query.
     *
     * @param \Titon\Db\Query $query
     * @return int
     */
    public function save(Query $query) {
        return $this->getDriver()->query($query)->save();
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
     * Set and merge result data into the table.
     *
     * @uses Titon\Utility\Hash
     *
     * @param array $data
     * @return \Titon\Db\Table
     */
    public function setData(array $data) {
        $this->data = Hash::merge($this->_mapDefaults(), $this->data, $data);

        return $this;
    }

    /**
     * Set the schema for this table.
     *
     * @param \Titon\Db\Driver\Schema $schema
     * @return \Titon\Db\Table
     */
    public function setSchema(Schema $schema) {
        $this->_schema = $schema;

        return $this;
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
        $ids = $this->select($pk)->bindCallback($conditions)->fetchList($pk, $pk);
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
     * Either update or insert related data for the primary table's ID.
     * Each relation will handle upserting differently.
     *
     * @param int $id
     * @param array $data
     * @param array $options
     * @return int
     * @throws \Titon\Db\Exception\QueryFailureException
     * @throws \Exception
     */
    public function upsertRelations($id, array $data, array $options = []) {
        $upserted = 0;
        $driver = $this->getDriver();

        if (!$data) {
            return $upserted;
        }

        if (!$driver->startTransaction()) {
            throw new QueryFailureException('Failed to start database transaction');
        }

        try {
            foreach ($data as $alias => $relatedData) {
                if (empty($data[$alias])) {
                    continue;
                }

                $relation = $this->getRelation($alias);
                $relatedTable = $relation->getRelatedTable();
                $fk = $relation->getForeignKey();
                $rfk = $relation->getRelatedForeignKey();
                $rpk = $relatedTable->getPrimaryKey();

                switch ($relation->getType()) {
                    // Append the foreign key with the current ID
                    case Relation::ONE_TO_ONE:
                        $relatedData[$rfk] = $id;
                        $relatedData[$rpk] = $relatedTable->upsert($relatedData, null, $options);

                        if (!$relatedData[$rpk]) {
                            throw new QueryFailureException(sprintf('Failed to upsert %s relational data', $alias));
                        }

                        $relatedData = $relatedTable->data;

                        $upserted++;
                    break;

                    // Loop through and append the foreign key with the current ID
                    case Relation::ONE_TO_MANY:
                        foreach ($relatedData as $i => $hasManyData) {
                            $hasManyData[$rfk] = $id;
                            $hasManyData[$rpk] = $relatedTable->upsert($hasManyData, null, $options);

                            if (!$hasManyData[$rpk]) {
                                throw new QueryFailureException(sprintf('Failed to upsert %s relational data', $alias));
                            }

                            $hasManyData = $relatedTable->data;
                            $relatedData[$i] = $hasManyData;

                            $upserted++;
                        }
                    break;

                    // Loop through each set of data and upsert to gather an ID
                    // Use that foreign ID with the current ID and save in the junction table
                    case Relation::MANY_TO_MANY:
                        $junctionTable = $relation->getJunctionTable();
                        $jpk = $junctionTable->getPrimaryKey();

                        foreach ($relatedData as $i => $habtmData) {
                            $junctionData = [$fk => $id];

                            // Existing record by junction foreign key
                            if (isset($habtmData[$rfk])) {
                                $foreign_id = $habtmData[$rfk];
                                unset($habtmData[$rfk]);

                                if ($habtmData) {
                                    $foreign_id = $relatedTable->upsert($habtmData, $foreign_id, $options);
                                }

                            // Existing record by relation primary key
                            // New record
                            } else {
                                $foreign_id = $relatedTable->upsert($habtmData, null, $options);
                                $habtmData = $relatedTable->data;
                            }

                            if (!$foreign_id) {
                                throw new QueryFailureException(sprintf('Failed to upsert %s relational data', $alias));
                            }

                            $junctionData[$rfk] = $foreign_id;

                            // Only create the record if the junction doesn't already exist
                            $exists = $junctionTable->select()
                                ->where($fk, $id)
                                ->where($rfk, $foreign_id)
                                ->fetch(false);

                            if (!$exists) {
                                $junctionData[$jpk] = $junctionTable->upsert($junctionData, null, $options);

                                if (!$junctionData[$jpk]) {
                                    throw new QueryFailureException(sprintf('Failed to upsert %s junction data', $alias));
                                }
                            } else {
                                $junctionData = $exists;
                            }

                            $habtmData['Junction'] = $junctionData;
                            $relatedData[$i] = $habtmData;

                            $upserted++;
                        }
                    break;

                    // Can not save belongs to relations
                    case Relation::MANY_TO_ONE:
                        continue;
                    break;
                }

                $this->setData([$alias => $relatedData]);
            }

            $driver->commitTransaction();

        // Rollback and re-throw exception
        } catch (Exception $e) {
            $driver->rollbackTransaction();

            throw $e;
        }

        return $upserted;
    }

    /**
     * Wrap results in the defined entity class and wrap joined data in the originating tables entity class.
     *
     * @param \Titon\Db\Query $query
     * @param array $results
     * @param mixed $options
     * @return \Titon\Db\Entity[]
     */
    public function wrapEntities(Query $query, array $results, $options) {
        $entityClass = $this->getEntity();

        foreach ($results as $i => $result) {
            $entity = new $entityClass($result);
            $entity = $this->fetchRelations($query, $entity, $options);

            // Wrap data pulled through a join
            /*foreach ($result as $key => $value) {
                if (!is_array($value)) {
                    continue;
                }

                // Don't wrap collections of entities
                if (isset($value[0]) && $value[0] instanceof Entity) {
                    continue;
                }

                if ($this->hasRelation($key)) {
                    $relatedEntity = $this->getRelation($key)->getRelatedTable()->getEntity() ?: $entity;

                    $result[$key] = new $relatedEntity($value);
                }
            }*/

            $results[$i] = $entity;
        }

        return $results;
    }

    /**
     * Extract related table data from an array of complex data.
     * Filter out non-schema columns from the data.
     *
     * @param array $data
     * @return array
     */
    protected function _filterData(array &$data) {
        $aliases = array_keys($this->getRelations());
        $related = [];

        foreach ($aliases as $alias) {
            if (isset($data[$alias])) {
                $related[$alias] = $data[$alias];
                unset($data[$alias]);
            }
        }

        if ($columns = $this->getSchema()->getColumns()) {
            $data = array_intersect_key($data, $columns);
        }

        return $related;
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
     * Will wrap all delete queries in a transaction call.
     * Will delete related data if $cascade is true.
     * Triggers callbacks before and after.
     *
     * @param \Titon\Db\Query $query
     * @param int|int[] $id
     * @param mixed $options {
     *      @type bool $cascade         Will delete related dependent records
     *      @type bool $preCallback     Will trigger before callbacks
     *      @type bool $postCallback    Will trigger after callbacks
     * }
     * @return int The count of records deleted
     * @throws \Titon\Db\Exception\QueryFailureException
     * @throws \Exception
     */
    protected function _processDelete(Query $query, $id, $options = []) {
        if (is_bool($options)) {
            $options = ['cascade' => $options];
        }

        $options = $options + [
            'cascade' => true,
            'preCallback' => true,
            'postCallback' => true
        ];

        if ($options['preCallback']) {
            $event = $this->emit('db.preDelete', [$id, &$options['cascade']]);
            $state = $event->getData();

            if ($state !== null && !$state) {
                return 0;
            }
        }

        // Use transactions for cascading
        if ($options['cascade']) {
            $driver = $this->getDriver();

            if (!$driver->startTransaction()) {
                throw new QueryFailureException('Failed to start database transaction');
            }

            try {
                $count = $query->save();

                if ($count === false) {
                    throw new QueryFailureException(sprintf('Failed to delete %s record with ID %s', get_class($this), implode(', ', (array) $id)));
                }

                $this->deleteDependents($id, $options['cascade']);

                $driver->commitTransaction();

            // Rollback and re-throw exception
            } catch (Exception $e) {
                $driver->rollbackTransaction();

                throw $e;
            }

        // No transaction needed for single query
        } else {
            $count = $query->save();

            if ($count === false) {
                return 0;
            }
        }

        $this->data = [];

        if ($options['postCallback']) {
            $event = $this->emit('db.postDelete', [$id]);
        }

        return $count;
    }

    /**
     * All-in-one method for fetching results from a query.
     * Before the query is executed, the preFetch() method is called.
     * After the query is executed, relations will be fetched, and then postFetch() will be called.
     * If wrap option is true, all results will be wrapped in an Entity class.
     *
     * Depending on the $fetchType, the returned results will differ.
     * If the type is "fetch" a single item will be returned, either an array or entity.
     * All other types currently return an array or an array of entities.
     *
     * @uses Titon\Db\Type\AbstractType
     *
     * @param \Titon\Db\Query $query
     * @param string $fetchType
     * @param mixed $options {
     *      @type bool $preCallback     Will trigger before callbacks
     *      @type bool $postCallback    Will trigger after callbacks
     * }
     * @return array|\Titon\Db\Entity|\Titon\Db\Entity[]
     */
    protected function _processFetch(Query $query, $fetchType, array $options = []) {
        $options = $options + [
            'preCallback' => true,
            'postCallback' => true
        ];

        // Use the return of preFetch() if applicable
        if ($options['preCallback']) {
            $event = $this->emit('db.preFetch', [$query, $fetchType]);
            $state = $event->getData();

            if ($state !== null && !$state) {
                return [];
            }
        }

        if (isset($state) && is_array($state)) {
            $results = $state;

            if (!isset($results[0])) {
                $results = [$results];
            }
        } else {
            $results = $this->getDriver()->query($query)->fetchAll();
        }

        if (!$results) {
            return [];
        }

        // Apply type casting before postFetch()
        if ($query->getType() === Query::SELECT) {
            $schema = $this->getSchema()->getColumns();

            foreach ($results as $result) {
                foreach ($result as $field => $value) {
                    if (isset($schema[$field])) {
                        $result[$field] = AbstractType::factory($schema[$field]['type'], $this->getDriver())->from($value);
                    }
                }
            }
        }

        if ($options['postCallback']) {
            $event = $this->emit('db.postFetch', [&$results, $fetchType]);
        }

        // Wrap the results in entities
        $this->data = $results = $this->wrapEntities($query, $results, $options);

        // Reset the driver local cache
        $this->getDriver()->reset();

        // Return early for single records
        if ($fetchType === 'fetch') {
            return $results[0];
        }

        return $results;
    }

    /**
     * Primary method that handles the processing of update queries.
     * Will wrap all delete queries in a transaction call.
     * If any related data exists, update those records after verifying required IDs.
     * Validate schema data and related data structure before updating.
     *
     * @param \Titon\Db\Query $query
     * @param int|int[] $id
     * @param array $data
     * @param mixed $options {
     *      @type bool $preCallback     Will trigger before callbacks
     *      @type bool $postCallback    Will trigger after callbacks
     * }
     * @return int The count of records updated
     * @throws \Titon\Db\Exception\QueryFailureException
     * @throws \Exception
     */
    protected function _processSave(Query $query, $id, array $data, $options = []) {
        $isCreate = !$id;
        $options = $options + [
            'preCallback' => true,
            'postCallback' => true
        ];

        if ($options['preCallback']) {
            $event = $this->emit('db.preSave', [$id, &$data]);
            $state = $event->getData();

            if ($state !== null && !$state) {
                return 0;
            }
        }

        $this->_validateRelationData($data);

        // Filter the data
        $relatedData = $this->_filterData($data);

        // Set the data
        $query->fields($data);

        // Update the records using transactions
        $driver = $this->getDriver();

        if ($relatedData) {
            if (!$driver->startTransaction()) {
                throw new QueryFailureException('Failed to start database transaction');
            }

            try {
                $count = $query->save();

                if ($count === false) {
                    throw new QueryFailureException(sprintf('Failed to update %s record with ID %s', get_class($this), $id));
                }

                if ($isCreate) {
                    $id = $driver->getLastInsertID($this);
                }

                $this->upsertRelations($id, $relatedData, $options);

                $driver->commitTransaction();

            // Rollback and re-throw exception
            } catch (Exception $e) {
                $driver->rollbackTransaction();

                throw $e;
            }

        // No transaction needed for single query
        } else {
            $count = $query->save();

            if ($count === false) {
                return 0;
            }

            if ($isCreate) {
                $id = $driver->getLastInsertID($this);
            }
        }

        if (!is_array($id)) {
            $this->id = $id;
            $this->setData([$this->getPrimaryKey() => $id] + $data);
        }

        if ($options['postCallback']) {
            $event = $this->emit('db.postSave', [$id, $isCreate]);
        }

        if ($isCreate) {
            return $id;
        }

        return $count;
    }

    /**
     * Validate that relation data is structured correctly.
     * Will only validate the top-level dimensions.
     *
     * @uses Titon\Utility\Hash
     *
     * @param array $data
     * @throws \Titon\Db\Exception\InvalidRelationStructureException
     */
    protected function _validateRelationData(array $data) {
        foreach ($this->getRelations() as $alias => $relation) {
            if (empty($data[$alias])) {
                continue;
            }

            $relatedData = $data[$alias];
            $type = $relation->getType();

            switch ($type) {
                // Only child records can be validated
                case Relation::MANY_TO_ONE:
                    continue;
                break;

                // Both require a numerical indexed array
                // With each value being an array of data
                case Relation::ONE_TO_MANY:
                case Relation::MANY_TO_MANY:
                    if (!Hash::isNumeric(array_keys($relatedData))) {
                        throw new InvalidRelationStructureException(sprintf('%s related data must be structured in a numerical multi-dimension array', $alias));
                    }

                    if ($type === Relation::MANY_TO_MANY) {
                        $isNotArray = Hash::some($relatedData, function($value) {
                            return !is_array($value);
                        });

                        if ($isNotArray) {
                            throw new InvalidRelationStructureException(sprintf('%s related data values must be structured arrays', $alias));
                        }
                    }
                break;

                // A single dimension of data
                case Relation::ONE_TO_ONE:
                    if (Hash::isNumeric(array_keys($relatedData))) {
                        throw new InvalidRelationStructureException(sprintf('%s related data must be structured in a single-dimension array', $alias));
                    }
                break;
            }
        }
    }

}