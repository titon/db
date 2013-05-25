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
	 * 	connection		- (string) The connection login credentials key
	 * 	table			- (string) Database table name
	 * 	prefix			- (string) Prefix to prepend to the table name
	 * 	primaryKey		- (string) The field representing the primary key
	 * 	displayField	- (string) The field representing a description of the record
	 *
	 * @type array
	 */
	protected $_config = [
		'connection' => 'default',
		'table' => '',
		'prefix' => '',
		'primaryKey' => 'id',
		'displayField' => ['title', 'name', 'id']
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
	 * Return the connection and data source defined by key.
	 *
	 * @param string $key
	 * @return \Titon\Model\Source
	 */
	public function getConnection($key) {
		$source = Registry::factory('Titon\Model\Connection')->getSource($key);
		$source->connect();

		return $source;
	}

	/**
	 * Instantiate a new database query builder.
	 *
	 * @param int $type
	 * @return \Titon\Model\Query
	 */
	public function query($type) {
		$config = $this->config->all();

		$query = new Query($type, $this->getConnection($config['connection']));
		$query->from($config['prefix'] . $config['table']);

		return $query;
	}

}

// We should use a single model instance
Model::$singleton = true;