<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Common\Base;
use Titon\Common\Registry;
use Titon\Common\Traits\Attachable;
use Titon\Common\Traits\Cacheable;
use Titon\Common\Traits\Instanceable;
use Titon\Model\Driver\Schema;
use Titon\Model\Driver\Type\AbstractType;
use Titon\Model\Exception\InvalidRelationStructureException;
use Titon\Model\Exception\MissingRelationException;
use Titon\Model\Exception\QueryFailureException;
use Titon\Model\Query;
use Titon\Model\Relation\ManyToMany;
use Titon\Utility\Hash;
use \Exception;

/**
 * Represents a database table.
 *
 * 	- Defines a schema
 * 	- Defines relations
 * 		- One-to-one, One-to-many, Many-to-one, Many-to-many
 * 	- Allows for queries to be built and executed
 * 		- Returns Entity objects for each record in the result
 *
 * @link http://en.wikipedia.org/wiki/Database_model
 * @link http://en.wikipedia.org/wiki/Relational_model
 *
 * @package Titon\Model
 */
class Model extends Base {
	use Instanceable, Attachable, Cacheable;

	/**
	 * Data or results from the last query.
	 *
	 * @type array
	 */
	public $data = [];

	/**
	 * Configuration.
	 *
	 * @type array {
	 * 		@type $string $connection		The connection driver key
	 * 		@type $string $table			Database table name
	 * 		@type $string $prefix			Prefix to prepend to the table name
	 * 		@type $string $primaryKey		The field representing the primary key
	 * 		@type $string $displayField		The field representing a readable label
	 * 		@type $string $entity			The Entity class to wrap results in
	 * }
	 */
	protected $_config = [
		'connection' => 'default',
		'table' => '',
		'prefix' => '',
		'primaryKey' => 'id',
		'displayField' => ['title', 'name', 'id'],
		'entity' => 'Titon\Model\Entity'
	];

	/**
	 * Driver instance.
	 *
	 * @type \Titon\Model\Driver
	 */
	protected $_driver;

	/**
	 * Model to model relationships.
	 *
	 * @type \Titon\Model\Relation[]
	 */
	protected $_relations = [];

	/**
	 * Database table schema object, or an array of column data.
	 *
	 * @type \Titon\Model\Driver\Schema|array
	 */
	protected $_schema;

	/**
	 * Add a relation between another model.
	 *
	 * @param \Titon\Model\Relation $relation
	 * @return \Titon\Model\Relation|\Titon\Model\Relation\ManyToMany
	 */
	public function addRelation(Relation $relation) {
		$this->_relations[$relation->getAlias()] = $relation;

		$this->attachObject([
			'alias' => $relation->getAlias(),
			'class' => $relation->getModel(),
			'interface' => 'Titon\Model\Model'
		]);

		return $relation;
	}

	/**
	 * Return a count of results from the query.
	 *
	 * @param \Titon\Model\Query $query
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
	 * @return int The record ID on success, 0 on failure
	 * @throws \Titon\Model\Exception\QueryFailureException
	 * @throws \Exception
	 */
	public function create(array $data) {
		$data = $this->preSave(null, $data);

		if (!$data) {
			return 0;
		}

		$this->_validateRelationData($data);

		// Filter the data
		$relatedData = $this->_filterData($data);

		// Prepare query
		$query = $this->query(Query::INSERT)->fields($data);

		// Insert the record using transactions
		$driver = $this->getDriver();

		if ($relatedData) {
			if (!$driver->startTransaction()) {
				throw new QueryFailureException('Failed to start database transaction');
			}

			try {
				if (!$query->save()) {
					throw new QueryFailureException(sprintf('Failed to create new %s record', get_class($this)));
				}

				$id = $driver->getLastInsertID();

				$this->upsertRelations($id, $relatedData);

				$driver->commitTransaction();

			// Rollback and re-throw exception
			} catch (Exception $e) {
				$driver->rollbackTransaction();

				throw $e;
			}

		// No transaction needed for single query
		} else {
			if (!$query->save()) {
				return 0;
			}

			$id = $driver->getLastInsertID();
		}

		$this->setData([$this->getPrimaryKey() => $id] + $data);
		$this->postSave($id, true);

		return $id;
	}

	/**
	 * Create a database table based off the models schema.
	 * The schema must be an array of column data.
	 *
	 * @param array $attributes
	 * @return bool
	 */
	public function createTable(array $attributes = []) {
		$attributes = $attributes + [
			'engine' => 'InnoDB',
			'characterSet' => $this->getDriver()->getEncoding()
		];

		return (bool) $this->query(Query::CREATE_TABLE)
			->schema($this->getSchema())
			->attribute($attributes)
			->save();
	}

	/**
	 * Delete a record by ID. If $cascade is true, delete all related records.
	 *
	 * @param int|array $id
	 * @param bool $cascade
	 * @return bool
	 * @throws \Titon\Model\Exception\QueryFailureException
	 * @throws \Exception
	 */
	public function delete($id, $cascade = true) {
		$state = $this->preDelete($id, $cascade);

		if (!$state) {
			return false;
		}

		// Prepare query
		$query = $this->query(Query::DELETE)->where($this->getPrimaryKey(), $id);

		// Use transactions for cascading
		if ($cascade) {
			$driver = $this->getDriver();

			if (!$driver->startTransaction()) {
				throw new QueryFailureException('Failed to start database transaction');
			}

			try {
				if (!$query->save()) {
					throw new QueryFailureException(sprintf('Failed to delete %s record with ID %s', get_class($this), implode(', ', (array) $id)));
				}

				$this->deleteDependents($id, $cascade);

				$driver->commitTransaction();

			// Rollback and re-throw exception
			} catch (Exception $e) {
				$driver->rollbackTransaction();

				throw $e;
			}

		// No transaction needed for single query
		} else {
			if (!$query->save()) {
				return false;
			}
		}

		$this->data = [];
		$this->postDelete($id);

		return true;
	}

	/**
	 * Loop through all model relations and delete dependent records using the ID as a base.
	 * Will return a count of how many dependent records were deleted.
	 *
	 * @param int|array $id
	 * @param bool $cascade
	 * @return int
	 * @throws \Titon\Model\Exception\QueryFailureException
	 * @throws \Exception
	 */
	public function deleteDependents($id, $cascade = true) {
		$count = 0;
		$driver = $this->getDriver();

		if (!$driver->startTransaction()) {
			throw new QueryFailureException('Failed to start database transaction');
		}

		try {
			foreach ($this->getRelations() as $alias => $relation) {
				if (!$relation->isDependent()) {
					continue;
				}

				/** @type \Titon\Model\Model $relatedModel */
				$relatedModel = $this->getObject($alias);

				switch ($relation->getType()) {
					case Relation::ONE_TO_ONE:
					case Relation::ONE_TO_MANY:
						$results = [];

						// Fetch IDs before deletion
						// Only delete if relations exist
						if ($cascade && $relatedModel->hasRelations()) {
							$results = $relatedModel
								->select($relatedModel->getPrimaryKey())
								->where($relation->getRelatedForeignKey(), $id)
								->fetchAll(false);
						}

						// Delete all records at once
						$count += $relatedModel
							->query(Query::DELETE)
							->where($relation->getRelatedForeignKey(), $id)
							->save();

						// Loop through the records and cascade delete dependents
						if ($results) {
							$count += $relatedModel->deleteDependents(Hash::pluck($results, $relatedModel->getPrimaryKey()), $cascade);
						}
					break;

					case Relation::MANY_TO_MANY:
						/** @type \Titon\Model\Model $junctionModel */
						$junctionModel = Registry::factory($relation->getJunctionModel());

						// Only delete the junction records
						// The related records should stay
						$count += $junctionModel
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
	 * @param \Titon\Model\Query $query
	 * @param bool $wrap
	 * @return \Titon\Model\Entity|array
	 */
	public function fetch(Query $query, $wrap = true) {
		return $this->_fetchResults($query, __FUNCTION__, $wrap);
	}

	/**
	 * Return a list of entities from the results of the query.
	 *
	 * @param \Titon\Model\Query $query
	 * @param bool $wrap
	 * @return \Titon\Model\Entity[]|array
	 */
	public function fetchAll(Query $query, $wrap = true) {
		return $this->_fetchResults($query, __FUNCTION__, $wrap);
	}

	/**
	 * Return the results as a list of key values.
	 *
	 * @param \Titon\Model\Query $query
	 * @param string $key The field to use as the array key
	 * @param string $value The field to use as the array value
	 * @return array
	 */
	public function fetchList(Query $query, $key = null, $value = null) {
		$results = $this->_fetchResults($query, __FUNCTION__, false);

		$key = $key ?: $this->getPrimaryKey();
		$value = $value ?: $this->getDisplayField();
		$list = [];

		foreach ($results as $result) {
			$list[Hash::extract($result, $key)] = Hash::extract($result, $value);
		}

		return $list;
	}

	/**
	 * Once the primary query has been executed and the results have been fetched,
	 * loop over all sub-queries and fetch related data.
	 *
	 * The related data will be added to the array indexed by the relation alias.
	 *
	 * @param \Titon\Model\Query $query
	 * @param array $result
	 * @param bool $wrap
	 * @return array
	 */
	public function fetchRelations(Query $query, array $result, $wrap = true) {
		$queries = $query->getSubQueries();

		if (!$queries) {
			return $result;
		}

		foreach ($queries as $alias => $subQuery) {
			$newQuery = clone $subQuery;
			$relation = $this->getRelation($alias);
			$relatedModel = $this->getObject($alias);

			switch ($relation->getType()) {

				// Has One
				// The related model should be pointing to this model
				// So use this ID in the related foreign key
				// Since we only want one record, limit it and single fetch
				case Relation::ONE_TO_ONE:
					$foreignValue = $result[$this->getPrimaryKey()];

					if ($foreignValue) {
						$result[$alias] = $newQuery
							->where($relation->getRelatedForeignKey(), $foreignValue)
							->limit(1)
							->fetch($wrap);
					} else {
						$result[$alias] = [];
					}
				break;

				// Has Many
				// The related models should be pointing to this model
				// So use this ID in the related foreign key
				// Since we want multiple records, fetch all with no limit
				case Relation::ONE_TO_MANY:
					$foreignValue = $result[$this->getPrimaryKey()];

					if ($foreignValue) {
						$result[$alias] = $newQuery
							->where($relation->getRelatedForeignKey(), $foreignValue)
							->fetchAll($wrap);
					} else {
						$result[$alias] = [];
					}
				break;

				// Belongs To
				// This model should be pointing to the related model
				// So use the foreign key as the related ID
				// We should only be fetching a single record
				case Relation::MANY_TO_ONE:
					$foreignValue = $result[$relation->getForeignKey()];

					if ($foreignValue) {
						$result[$alias] = $newQuery
							->where($relatedModel->getPrimaryKey(), $foreignValue)
							->limit(1)
							->fetch($wrap);
					} else {
						$result[$alias] = [];
					}
				break;

				// Has And Belongs To Many
				// This model points to a related model through a junction table
				// Query the junction table for lookup IDs pointing to the related data
				case Relation::MANY_TO_MANY:
					$foreignValue = $result[$this->getPrimaryKey()];

					if (!$foreignValue) {
						$result[$alias] = [];
						continue;
					}

					// Fetch the related records using the junction IDs
					$junctionModel = Registry::factory($relation->getJunctionModel());
					$junctionResults = $junctionModel
						->select()
						->where($relation->getForeignKey(), $foreignValue)
						->fetchAll(false);

					if (!$junctionResults) {
						$result[$alias] = [];
						continue;
					}

					$m2mResults = $newQuery
						->where($relatedModel->getPrimaryKey(), Hash::pluck($junctionResults, $relation->getRelatedForeignKey()))
						->fetchAll($wrap);

					// Include the junction data
					foreach ($m2mResults as $i => $m2mResult) {
						foreach ($junctionResults as $junctionResult) {
							if ($junctionResult[$relation->getRelatedForeignKey()] == $m2mResult[$relatedModel->getPrimaryKey()]) {
								$m2mResults[$i]['Junction'] = $junctionResult;
							}
						}
					}

					$result[$alias] = $m2mResults;
				break;
			}

			unset($newQuery);
		}

		return $result;
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
	 * @return \Titon\Model\Driver
	 */
	public function getDriver() {
		if ($this->_driver) {
			return $this->_driver;
		}

		/** @type \Titon\Model\Driver $driver */
		$driver = Registry::factory('Titon\Model\Connection')->getDriver($this->getConnection());
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
		return $this->config->entity;
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
	 * @return \Titon\Model\Relation|\Titon\Model\Relation\ManyToMany
	 * @throws \Titon\Model\Exception\MissingRelationException
	 */
	public function getRelation($alias) {
		if (isset($this->_relations[$alias])) {
			return $this->_relations[$alias];
		}

		throw new MissingRelationException(sprintf('Model relation %s does not exist', $alias));
	}

	/**
	 * Return all relations, or all relations by type.
	 *
	 * @param int $type
	 * @return \Titon\Model\Relation[]
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
	 * TODO move to driver
	 *
	 * @return \Titon\Model\Driver\Schema
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
			$fields = $this->query(Query::DESCRIBE)->fetchAll(false);
			$columns = [];

			foreach ($fields as $field) {
				$column = [];

				if (preg_match('/([a-z]+)(?:\(([0-9]+)\))?/is', $field['Type'], $matches)) {
					$column['type'] = strtolower($matches[1]);

					if (isset($matches[2])) {
						$column['length'] = $matches[2];
					}
				}

				$dataType = AbstractType::factory($column['type'], $this->getDriver());

				$column = $dataType->getDefaultOptions() + $column;
				$column['null'] = ($field['Null'] === 'YES');
				$column['default'] = $field['Default'];

				switch (strtolower($field['Key'])) {
					case 'pri': $column['primary'] = true; break;
					case 'uni': $column['unique'] = true; break;
					case 'mul': $column['index'] = true; break;
				}

				if ($field['Extra'] === 'auto_increment') {
					$column['ai'] = true;
				}

				$columns[$field['Field']] = $column;
			}
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
	 * Callback called after a delete query.
	 *
	 * @param int $id
	 */
	public function postDelete($id) {
		return;
	}

	/**
	 * Callback called after a select query.
	 *
	 * @param array $results The results of the query
	 * @param string $fetchType Type of fetch used
	 * @return array
	 */
	public function postFetch(array $results, $fetchType) {
		return $results;
	}

	/**
	 * Callback called after an insert or update query.
	 *
	 * @param int $id The last insert ID
	 * @param bool $created If the record was created
	 */
	public function postSave($id, $created = false) {
		return;
	}

	/**
	 * Callback called before a delete query.
	 * Modify cascading by overwriting the value.
	 * Return a falsey value to stop the process.
	 *
	 * @param int $id
	 * @param bool $cascade
	 * @return mixed
	 */
	public function preDelete($id, &$cascade) {
		return true;
	}

	/**
	 * Callback called before a select query.
	 * Return an array of data to use instead of the fetch results.
	 *
	 * @param \Titon\Model\Query $query
	 * @param string $fetchType
	 * @return mixed
	 */
	public function preFetch(Query $query, $fetchType) {
		return;
	}

	/**
	 * Callback called before an insert or update query.
	 * Return a falsey value to stop the process.
	 *
	 * @param int $id
	 * @param array $data
	 * @return mixed
	 */
	public function preSave($id, array $data) {
		return $data;
	}

	/**
	 * Instantiate a new query builder.
	 *
	 * @param int $type
	 * @return \Titon\Model\Query
	 */
	public function query($type) {
		$this->data = [];

		$query = new Query($type, $this);
		$query->from($this->getTable());

		return $query;
	}

	/**
	 * Fetch a single record by ID.
	 *
	 * @param int $id
	 * @return \Titon\Model\Entity
	 */
	public function read($id) {
		return $this->select()->where($this->getPrimaryKey(), $id)->fetch();
	}

	/**
	 * Return a count of how many rows were affected by the query.
	 *
	 * @param \Titon\Model\Query $query
	 * @return int
	 */
	public function save(Query $query) {
		return $this->getDriver()->query($query)->save();
	}

	/**
	 * Instantiate a new select query.
	 *
	 * @return \Titon\Model\Query
	 */
	public function select() {
		return $this->query(Query::SELECT)->fields(func_get_args());
	}

	/**
	 * Set and merge result data into the model.
	 *
	 * @param array $data
	 * @return \Titon\Model\Model
	 */
	public function setData(array $data) {
		$base = [];

		// Add empty values for missing fields
		if ($schema = $this->getSchema()) {
			$base = array_map(function() {
				return '';
			}, $schema->getColumns());
		}

		$this->data = Hash::merge($base, $this->data, $data);

		return $this;
	}

	/**
	 * Set the schema for this model.
	 *
	 * @param \Titon\Model\Driver\Schema $schema
	 * @return \Titon\Model\Model
	 */
	public function setSchema(Schema $schema) {
		$this->_schema = $schema;

		return $this;
	}

	/**
	 * Update a database record based on ID.
	 * If any related data exists, update those records after verifying required IDs.
	 * Validate schema data and related data structure before updating.
	 *
	 * @param int $id
	 * @param array $data
	 * @return bool
	 * @throws \Exception
	 */
	public function update($id, array $data) {
		$data = $this->preSave($id, $data);

		if (!$data) {
			return false;
		}

		$this->_validateRelationData($data);

		// Filter the data
		$relatedData = $this->_filterData($data);

		// Prepare the query
		$query = $this->query(Query::UPDATE)
			->fields($data)
			->where($this->getPrimaryKey(), $id);

		// Update the records using transactions
		if ($relatedData) {
			$driver = $this->getDriver();

			if (!$driver->startTransaction()) {
				throw new QueryFailureException('Failed to start database transaction');
			}

			try {
				$count = $query->save();

				if ($count === false) {
					throw new QueryFailureException(sprintf('Failed to update %s record with ID %s', get_class($this), $id));
				}

				$this->upsertRelations($id, $relatedData);

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
				return false;
			}
		}

		$this->setData([$this->getPrimaryKey() => $id] + $data);
		$this->postSave($id);

		return true;
	}

	/**
	 * Either update or insert a record by checking for ID and record existence.
	 *
	 * @param array $data
	 * @param int $id
	 * @return int The record ID on success, 0 on failure
	 */
	public function upsert(array $data, $id = null) {
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
			if (!$this->update($id, $data)) {
				return 0;
			}

		// Or insert
		} else {
			$id = $this->create($data);
		}

		return $id;
	}

	/**
	 * Either update or insert related data for the primary model's ID.
	 * Each relation will handle upserting differently.
	 *
	 * @param int $id
	 * @param array $data
	 * @return int
	 * @throws \Titon\Model\Exception\QueryFailureException
	 * @throws \Exception
	 */
	public function upsertRelations($id, array $data) {
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
				$fk = $relation->getForeignKey();
				$rfk = $relation->getRelatedForeignKey();

				/** @type \Titon\Model\Model $relatedModel */
				$relatedModel = $this->getObject($alias);
				$pk = $this->getPrimaryKey();
				$rpk = $relatedModel->getPrimaryKey();

				switch ($relation->getType()) {
					// Append the foreign key with the current ID
					case Relation::ONE_TO_ONE:
						$relatedData[$rfk] = $id;
						$relatedData[$rpk] = $relatedModel->upsert($relatedData);

						if (!$relatedData[$rpk]) {
							throw new QueryFailureException(sprintf('Failed to upsert %s relational data', $alias));
						}

						$relatedData = $relatedModel->data;

						$upserted++;
					break;

					// Loop through and append the foreign key with the current ID
					case Relation::ONE_TO_MANY:
						foreach ($relatedData as $i => $hasManyData) {
							$hasManyData[$rfk] = $id;
							$hasManyData[$rpk] = $relatedModel->upsert($hasManyData);

							if (!$hasManyData[$rpk]) {
								throw new QueryFailureException(sprintf('Failed to upsert %s relational data', $alias));
							}

							$hasManyData = $relatedModel->data;
							$relatedData[$i] = $hasManyData;

							$upserted++;
						}
					break;

					// Loop through each set of data and upsert to gather an ID
					// Use that foreign ID with the current ID and save in the junction table
					case Relation::MANY_TO_MANY:
						$junctionModel = Registry::factory($relation->getJunctionModel());
						$jpk = $junctionModel->getPrimaryKey();

						foreach ($relatedData as $i => $habtmData) {
							$junctionData = [$fk => $id];

							// Existing record by junction foreign key
							if (isset($habtmData[$rfk])) {
								$foreign_id = $habtmData[$rfk];
								unset($habtmData[$rfk]);

								if ($habtmData) {
									$foreign_id = $relatedModel->upsert($habtmData, $foreign_id);
								}

							// Existing record by relation primary key
							// New record
							} else {
								$foreign_id = $relatedModel->upsert($habtmData);
								$habtmData = $relatedModel->data;
							}

							if (!$foreign_id) {
								throw new QueryFailureException(sprintf('Failed to upsert %s relational data', $alias));
							}

							$junctionData[$rfk] = $foreign_id;

							// Only create the record if the junction doesn't already exist
							$exists = $junctionModel->select()
								->where($fk, $id)
								->where($rfk, $foreign_id)
								->fetch(false);

							if (!$exists) {
								$junctionData[$jpk] = $junctionModel->upsert($junctionData);

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
	 * All-in-one method for fetching results from a query.
	 * Before the query is executed, the preFetch() method is called.
	 * After the query is executed, relations will be fetched, and then postFetch() will be called.
	 * If wrap is true, all results will be wrapped in an Entity class.
	 *
	 * Depending on the $fetchType, the returned results will differ.
	 * If the type is "fetch" a single item will be returned, either an array or entity.
	 * All other types currently return an array or an array of entities.
	 *
	 * @param \Titon\Model\Query $query
	 * @param string $fetchType
	 * @param bool $wrap
	 * @return array|\Titon\Model\Entity|\Titon\Model\Entity[]
	 */
	protected function _fetchResults(Query $query, $fetchType, $wrap) {
		$result = $this->preFetch($query, $fetchType);

		// Use the return of preFetch() if applicable
		if (is_array($result)) {
			return $result;

		} else if (!$result) {
			return [];
		}

		$results = $this->getDriver()->query($query)->fetchAll();

		if (!$results) {
			return [];
		}

		// Apply relations and type casting before postFetch()
		$schema = $this->getSchema()->getColumns();
		$isSelect = ($query->getType() === Query::SELECT);

		foreach ($results as $i => $result) {

			// Only type cast on results from a select query
			if ($isSelect) {
				foreach ($result as $field => $value) {
					if ($value === null) {
						continue;
					}

					if (isset($schema[$field])) {
						$result[$field] = AbstractType::factory($schema[$field]['type'], $this->getDriver())->from($value);
					}
				}
			}

			$results[$i] = $this->fetchRelations($query, $result, $wrap);
		}

		$results = $this->postFetch($results, $fetchType);

		$this->data = $results;

		// Wrap the results in entities
		$entity = $this->getEntity();

		if ($wrap && $entity) {
			foreach ($results as $i => $result) {
				$results[$i] = new $entity($result);
			}
		}

		// Return early for single records
		if ($fetchType === 'fetch') {
			return $results[0];
		}

		return $results;
	}

	/**
	 * Extract related model data from an array of complex data.
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

		if ($schema = $this->getSchema()) {
			$data = array_intersect_key($data, $schema->getColumns());
		}

		return $related;
	}

	/**
	 * Validate that relation data is structured correctly.
	 * Will only validate the top-level dimensions.
	 *
	 * @param array $data
	 * @throws \Titon\Model\Exception\InvalidRelationStructureException
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

// We should use a single model instance
Model::$singleton = true;