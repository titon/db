<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Behavior;

/**
 * A behavior that will update a field with a timestamp anytime a record is created or updated.
 *
 * @package Titon\Model\Behavior
 */
class TimestampableBehavior extends AbstractBehavior {

	/**
	 * Configuration.
	 *
	 * @type array {
	 * 		@type string $onCreateField		Field to update when a record is created
	 * 		@type string $onUpdateField		Field to update when a record is updated
	 * }
	 */
	protected $_config = [
		'onCreateField' => 'created',
		'onUpdateField' => 'updated'
	];

	/**
	 * Append the current timestamp to the data.
	 *
	 * @param int|int[] $id
	 * @param array $data
	 * @return array
	 */
	public function preSave($id, array $data) {
		$field = $id ? $this->config->onUpdateField : $this->config->onCreateField;
		$data[$field] = time();

		return $data;
	}

}