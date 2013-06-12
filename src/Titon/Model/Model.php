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
use Titon\Common\Traits\Instanceable;
use Titon\Model\Query;
use Titon\Model\Driver\Schema;

/**
 * Represents a database table.
 *
 * 	- Defines a schema
 * 	- Defines relations
 * 		- one to one, one to many, many to many
 * 	- Allows for queries to be built and executed
 * 		- Returns Entity objects for each record in the result
 *
 * @link http://en.wikipedia.org/wiki/Database_model
 * @link http://en.wikipedia.org/wiki/Relational_model
 */
class Model extends Base {
	use Instanceable, Attachable;

	/**
	 * Configuration.
	 *
	 * @type array {
	 * 		@type $string $connection		The connection login credentials key
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
	 * Instantiate a new query for inserting records.
	 *
	 * @param array $fields
	 * @return \Titon\Model\Query
	 */
	public static function insert(array $fields = []) {
		return self::getInstance()->query(Query::INSERT)->fields($fields);
	}

	/**
	 * Instantiate a new query for selecting records.
	 *
	 * @param array $fields
	 * @return \Titon\Model\Query
	 */
	public static function select(array $fields = []) {
		return self::getInstance()->query(Query::SELECT)->fields($fields);
	}

	/**
	 * Instantiate a new query for updating records.
	 *
	 * @param array $fields
	 * @return \Titon\Model\Query
	 */
	public static function update(array $fields = []) {
		return self::getInstance()->query(Query::UPDATE)->fields($fields);
	}

	/**
	 * Instantiate a new query for deleting records.
	 *
	 * @param array $fields
	 * @return \Titon\Model\Query
	 */
	public static function delete(array $fields = []) {
		return self::getInstance()->query(Query::DELETE)->fields($fields);
	}

	/**
	 * Instantiate a new query for describing a table.
	 *
	 * @return \Titon\Model\Query
	 */
	public static function describe() {
		return self::getInstance()->query(Query::DESCRIBE);
	}

	/**
	 * Add a relation between another model.
	 *
	 * @param \Titon\Model\Relation $relation
	 * @return \Titon\Model\Relation
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
		return $this->getConnection()->query($query)->count();
	}

	/**
	 * Return an entity for the first result from the query.
	 *
	 * @param \Titon\Model\Query $query
	 * @return \Titon\Model\Entity
	 */
	public function fetch(Query $query) {
		$result = $this->getConnection()->query($query)->fetch();

		if (!$result) {
			return null;
		}

		$entity = $this->config->entity;

		return new $entity($this->fetchRelations($query, $result));
	}

	/**
	 * Return a list of entities from the results of the query.
	 *
	 * @param \Titon\Model\Query $query
	 * @return \Titon\Model\Entity[]
	 */
	public function fetchAll(Query $query) {
		$results = $this->getConnection()->query($query)->fetchAll();

		if (!$results) {
			return [];
		}

		$entity = $this->config->entity;
		$entities = [];

		foreach ($results as $result) {
			$entities[] = new $entity($this->fetchRelations($query, $result));
		}

		return $entities;
	}

	/**
	 * Once the primary query has been executed and the results have been fetched,
	 * loop over all sub-queries and fetch related data.
	 *
	 * The related data will be appended into the array under the relation alias.
	 *
	 * @param \Titon\Model\Query $query
	 * @param array $result
	 * @return array
	 */
	public function fetchRelations(Query $query, array $result) {
		$queries = $query->getSubQueries();

		if (!$queries) {
			return $result;
		}

		foreach ($queries as $alias => $subQuery) {
			$relation = $this->getRelation($alias);
			$model = $this->getObject($alias);

			switch ($relation->getType()) {

				// The related model should be pointing to the parent model
				// So use the parent ID in the related foreign key
				// Since we only want one record, limit it and single fetch
				case Relation::ONE_TO_ONE:
					$result[$alias] = $subQuery
						->where($relation->getForeignKey(), $result[$model->getPrimaryKey()])
						->limit(1)
						->fetch();
				break;

				// The related models should be pointing to the parent model
				// So use the parent ID in the related foreign key
				// Since we want multiple records, fetch all with no limit
				case Relation::ONE_TO_MANY:
					$result[$alias] = $subQuery
						->where($relation->getForeignKey(), $result[$model->getPrimaryKey()])
						->fetchAll();
				break;

				case Relation::MANY_TO_ONE:

				break;

				case Relation::MANY_TO_MANY:


				break;
			}
		}

		return $result;
	}

	/**
	 * Return the connection and driver defined by key.
	 *
	 * @return \Titon\Model\Driver
	 */
	public function getConnection() {

		/** @type \Titon\Model\Driver $driver */
		$driver = Registry::factory('Titon\Model\Connection')->getDriver($this->config->connection);
		$driver->connect();

		return $driver;
	}

	/**
	 * Return the list of fields to use as the display field.
	 *
	 * @return array|string
	 */
	public function getDisplayField() {
		return $this->config->displayField;
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
	 * @return \Titon\Model\Relation
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
		if ($this->_schema) {
			return $this->_schema;
		}

		$fields = $this->query(Query::DESCRIBE)->fetchAll();
		$columns = [];

		foreach ($fields as $field) {
			$column = [];
			$column['null'] = ($field->Null === 'YES');
			$column['default'] = $column['null'] ? null : $field->Default;

			switch ($field->Key) {
				case 'PRI': $column['key'] = Schema::CONSTRAINT_PRIMARY; break;
				case 'UNI': $column['key'] = Schema::CONSTRAINT_UNIQUE; break;
				case 'MUL': $column['key'] = Schema::INDEX; break;
			}

			if ($field->Extra === 'auto_increment') {
				$column['ai'] = true;
			}

			$columns[$field->Field] = $column;
		}

		$this->_schema = new Schema($this->config->prefix . $this->config->table, $columns);

		return $this->_schema;
	}

	/**
	 * Return a count of how many rows were affected by the query.
	 *
	 * @param \Titon\Model\Query $query
	 * @return int
	 */
	public function save(Query $query) {
		return $this->getConnection()->query($query)->save();
	}

	/**
	 * Instantiate a new database query builder.
	 *
	 * @param int $type
	 * @return \Titon\Model\Query
	 */
	public function query($type) {
		$query = new Query($type, $this);
		$query->from($this->config->prefix . $this->config->table);

		return $query;
	}

}

// We should use a single model instance
Model::$singleton = true;