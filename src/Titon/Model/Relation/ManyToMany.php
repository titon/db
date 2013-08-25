<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Relation;

use Titon\Common\Registry;
use Titon\Model\Relation;
use Titon\Utility\Path;

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
	 * 		@type string $junctionClass		Fully qualified model class name for the junction table
	 * }
	 */
	protected $_config = [
		'junctionClass' => ''
	];

	/**
	 * Return the junction class name.
	 *
	 * @return string
	 */
	public function getJunctionClass() {
		return $this->config->junctionClass;
	}

	/**
	 * Return the junction model instance.
	 *
	 * @return \Titon\Model\Model
	 */
	public function getJunctionModel() {
		$class = $this->getJunctionClass();
		$alias = Path::className($class);
		$model = $this->getModel();

		if ($model->hasObject($alias)) {
			return $model->getObject($alias);
		}

		$model->attachObject($alias, Registry::factory($class));

		return $model->getObject($alias);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getType() {
		return Relation::MANY_TO_MANY;
	}

	/**
	 * Junction records should always be deleted.
	 *
	 * @return bool
	 */
	public function isDependent() {
		return true;
	}

	/**
	 * Set the junction class name.
	 *
	 * @param string $class
	 * @return \Titon\Model\Relation\ManyToMany
	 */
	public function setJunctionClass($class) {
		$this->config->junctionClass = $class;

		return $this;
	}

}