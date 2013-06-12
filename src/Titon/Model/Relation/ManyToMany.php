<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opendriver.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Relation;

use Titon\Model\Relation;

/**
 * Represents a many-to-many model relationship.
 * Also known as a has and belongs to many.
 *
 * @link http://en.wikipedia.org/wiki/Many-to-many_%28data_model%29
 *
 * @package Titon\Model\Relation
 */
class ManyToMany extends AbstractRelation {

	/**
	 * Configuration.
	 *
	 * @type array {
	 * 		@type string $junctionModel	Fully qualified model name for the junction table
	 * }
	 */
	protected $_config = [
		'junctionModel' => ''
	];

	/**
	 * Return the junction model name.
	 *
	 * @return string
	 */
	public function getJunctionModel() {
		return $this->config->junctionModel;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getType() {
		return Relation::MANY_TO_MANY;
	}

	/**
	 * Set the junction model name.
	 *
	 * @param string $model
	 * @return \Titon\Model\Relation\ManyToMany
	 */
	public function setJunctionModel($model) {
		$this->config->junctionModel = $model;

		return $this;
	}

}