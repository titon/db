<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

/**
 * Represents a single entity of data, usually a record from a database.
 *
 * 	- Data mapping functionality during creation
 * 	- Getter and setter support
 *
 * http://en.wikipedia.org/wiki/Entity_class
 */
class Entity {

	public function __construct(array $data = []) {
		$this->mapData($data);
	}

	public function mapData(array $data) {
		foreach ($data as $key => $value) {
			$this->{$key} = $value;
		}
	}

	public function mapRelation($key, Entity $entity) {

	}

}