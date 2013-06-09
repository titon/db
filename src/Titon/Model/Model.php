<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Common\Base;
use Titon\Common\Registry;
use Titon\Common\Traits\Instanceable;
use Titon\Model\Query;

/**
 * Represents a database table.
 *
 * 	- Defines a schema
 * 	- Defines relations
 * 		- one to one, one to many, many to many
 * 	- Allows for queries to be built and executed
 * 		- Returns Entity objects for each record in the result
 *
 * http://en.wikipedia.org/wiki/Database_model
 * http://en.wikipedia.org/wiki/Relational_model
 */
class Model extends Base {
	use Instanceable;

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

		return new $entity($result);
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
			$entities[] = new $entity($result);
		}

		return $entities;
	}

	/**
	 * Return the connection and data source defined by key.
	 *
	 * @return \Titon\Model\Source
	 */
	public function getConnection() {
		$source = Registry::factory('Titon\Model\Connection')->getSource($this->config->connection);
		$source->connect();

		return $source;
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