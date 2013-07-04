<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Relation;

use Titon\Common\Base;
use Titon\Model\Model;
use Titon\Model\Relation;
use Titon\Model\Traits\ModelAware;
use \Closure;

/**
 * Provides shared functionality for relations.
 *
 * @package Titon\Model\Relation
 */
abstract class AbstractRelation extends Base implements Relation {
	use ModelAware;

	/**
	 * Configuration.
	 *
	 * @type array {
	 * 		@type string $alias				The alias name to join models on
	 * 		@type string $class				Fully qualified model class name to join with
	 * 		@type string $foreignKey		Name of the foreign key in the current model
	 * 		@type string $relatedForeignKey	Name of the foreign key in the related model
	 * 		@type bool $dependent			Is the relation dependent on the parent
	 * }
	 */
	protected $_config = [
		'alias' => '',
		'class' => '',
		'foreignKey' => '',
		'relatedForeignKey' => '',
		'dependent' => true
	];

	/**
	 * A callback that modifies a query.
	 *
	 * @type \Closure
	 */
	protected $_conditions;

	/**
	 * Store the alias and class name.
	 *
	 * @param string $alias
	 * @param string $class
	 * @param array $config
	 */
	public function __construct($alias, $class, array $config = []) {
		parent::__construct($config);

		$this->setAlias($alias);
		$this->setClass($class);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAlias() {
		return $this->config->alias;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getClass() {
		return $this->config->class;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getConditions() {
		return $this->_conditions;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getForeignKey() {
		return $this->config->foreignKey;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRelatedForeignKey() {
		return $this->config->relatedForeignKey;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRelatedModel() {
		return $this->getModel()->getObject($this->getAlias());
	}

	/**
	 * {@inheritdoc}
	 */
	public function isDependent() {
		return $this->config->dependent;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setAlias($alias) {
		$this->config->alias = $alias;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setClass($class) {
		$this->config->class = $class;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setConditions(Closure $callback) {
		$this->_conditions = $callback;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setDependent($state) {
		$this->config->dependent = (bool) $state;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setForeignKey($key) {
		$this->config->foreignKey = $key;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setRelatedForeignKey($key) {
		$this->config->relatedForeignKey = $key;

		return $this;
	}

}