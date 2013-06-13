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
use Titon\Model\Query;
use Titon\Model\Driver\Schema;
use Titon\Model\Relation\ManyToMany;
use Titon\Utility\Hash;

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
 */
class Model extends Base {
	use Instanceable, Attachable, Cacheable;

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
	 * Model to model relationships.
	 *
	 * @type \Titon\Model\Relation[]
	 */
	protected $_relations = [];

	/**
	 * Database table schema object.
	 *
	 * @type \Titon\Model\Driver\Schema
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
	 * Delete a record by ID. If $cascade is true, delete all related records.
	 *
	 * @param int $id
	 * @param bool $cascade
	 * @return int
	 */
	public function delete($id, $cascade = true) {
		$count = $this->query(Query::DELETE)->where($this->getPrimaryKey(), $id)->save();

		if ($count && $cascade) {
			$this->deleteDependents($id);
		}

		return $count;
	}

	/**
	 * Loop through all model relations and delete dependent records using the ID as a base.
	 * Will return a count of how many dependent records were deleted.
	 *
	 * @param int $id
	 * @return int
	 */
	public function deleteDependents($id) {
		$count = 0;

		foreach ($this->getRelations() as $alias => $relation) {
			if (!$relation->isDependent()) {
				continue;
			}

			/** @type \Titon\Model\Model $relatedModel */
			$relatedModel = $this->getObject($alias);
			$primaryKey = $relatedModel->getPrimaryKey();
			$results = [];

			switch ($relation->getType()) {
				case Relation::ONE_TO_ONE:
				case Relation::ONE_TO_MANY:
					$results = $relatedModel
						->query(Query::SELECT)
						->fields($primaryKey)
						->where($relation->getRelatedForeignKey(), $id)
						->fetchAll();

					// Delete all the records
					$count += $relatedModel
						->query(Query::DELETE)
						->where($relation->getRelatedForeignKey(), $id)
						->save();
				break;

				case Relation::MANY_TO_MANY:
					if ($lookupIds = $this->_lookupManyToMany($relation, $id)) {
						$results = $relatedModel
							->query(Query::SELECT)
							->fields($primaryKey)
							->where($primaryKey, $lookupIds)
							->fetchAll();

						// Delete junction records
						Registry::factory($relation->getJunctionModel())->delete(array_keys($lookupIds), false);

						// Delete all the records
						$count += $relatedModel->delete($lookupIds, false);
					}
				break;
			}

			// Loop through the records and cascade delete
			foreach ($results as $result) {
				$count += $relatedModel->deleteDependents($result[$relatedModel->getPrimaryKey()]);
			}
		}

		return $count;
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
							->where($relation->getForeignKey(), $foreignValue)
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

					// Fetch the related records using the lookup IDs
					$lookupIds = $this->_lookupManyToMany($relation, $foreignValue);

					if (!$lookupIds) {
						$result[$alias] = [];
						continue;
					}

					$result[$alias] = $newQuery
						->where($relatedModel->getPrimaryKey(), $lookupIds)
						->fetchAll($wrap);
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

		/** @type \Titon\Model\Driver $driver */
		$driver = Registry::factory('Titon\Model\Connection')->getDriver($this->getConnection());
		$driver->connect();

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
		return $this->config->primaryKey;
	}

	/**
	 * Return a relation by alias.
	 *
	 * @param string $alias
	 * @return \Titon\Model\Relation|\Titon\Model\Relation\ManyToMany
	 * @throws \Titon\Model\Exception
	 */
	public function getRelation($alias) {
		if (isset($this->_relations[$alias])) {
			return $this->_relations[$alias];
		}

		throw new Exception(sprintf('Model relation %s does not exist', $alias));
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
				$relations[] = $relation;
			}
		}

		return $relations;
	}

	/**
	 * Return a schema object that represents the database table.
	 *
	 * @return \Titon\Model\Driver\Schema
	 */
	public function getSchema() {
		if ($this->_schema instanceof Schema) {
			return $this->_schema;
		}

		// Manually defined columns
		if (is_array($this->_schema)) {
			$columns = $this->_schema;

		// Inspect database for columns
		} else {
			$fields = $this->query(Query::DESCRIBE)->fetchAll(false);
			$columns = [];

			// TODO type, length

			foreach ($fields as $field) {
				$column = [];
				$column['null'] = ($field['Null'] === 'YES');
				$column['default'] = $column['null'] ? null : $field['Default'];

				switch ($field['Key']) {
					case 'PRI': $column['primary'] = true; break;
					case 'UNI': $column['unique'] = true; break;
					case 'MUL': $column['index'] = true; break;
				}

				if ($field['Extra'] === 'auto_increment') {
					$column['ai'] = true;
				}

				$columns[$field['Field']] = $column;
			}
		}

		$this->_schema = new Schema($this->getTable(), $columns);

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
	 * Modify the results after a query has executed.
	 *
	 * @param array $results
	 * @param string $queryType
	 * @return array
	 */
	public function postQuery(array $results, $queryType) {
		return $results;
	}

	/**
	 * Modify the query before it executes.
	 * If a value is returned, that will take precedence for the results.
	 *
	 * @param \Titon\Model\Query $query
	 * @param string $fetchType
	 * @return mixed
	 */
	public function preQuery(Query $query, $fetchType) {
		return;
	}

	/**
	 * Instantiate a new query builder.
	 *
	 * @param int $type
	 * @return \Titon\Model\Query
	 */
	public function query($type) {
		$query = new Query($type, $this);
		$query->from($this->getTable());

		return $query;
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
	 * All-in-one method for fetching results from a query.
	 * Before the query is executed, the preQuery() method is called.
	 * After the query is executed, relations will be fetched, and then postQuery() will be called.
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
		$result = $this->preQuery($query, $fetchType);

		// Use the return of preQuery() if applicable
		if ($result) {
			return $result;
		}

		$results = $this->getDriver()->query($query)->fetchAll();

		if (!$results) {
			return [];
		}

		// Apply relations before postQuery()
		foreach ($results as &$result) {
			$result = $this->fetchRelations($query, $result, $wrap);
		}

		$results = $this->postQuery($results, $query->getType());

		// Wrap the results in entities
		if ($wrap) {
			$entity = $this->getEntity();

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
	 * Looks up foreign keys within a junction table and returns a list of IDs.
	 *
	 * @param \Titon\Model\Relation\ManyToMany $relation
	 * @param int $id
	 * @return array
	 */
	protected function _lookupManyToMany(ManyToMany $relation, $id) {
		$model = Registry::factory($relation->getJunctionModel());

		return $model
			->query(Query::SELECT)
			->where($relation->getForeignKey(), $id)
			->fetchList($model->getPrimaryKey(), $relation->getRelatedForeignKey());
	}

}

// We should use a single model instance
Model::$singleton = true;