<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Closure;

/**
 * Represents a relation of one model to another.
 *
 * @package Titon\Model
 */
interface Relation {

	const ONE_TO_ONE = 'oneToOne'; // Has One
	const ONE_TO_MANY = 'oneToMany'; // Has Many
	const MANY_TO_ONE = 'manyToOne'; // Belongs To
	const MANY_TO_MANY = 'manyToMany'; // Has And Belongs To Many

	/**
	 * Return the relation alias name.
	 *
	 * @return string
	 */
	public function getAlias();

	/**
	 * Return the query conditions.
	 *
	 * @return \Closure
	 */
	public function getConditions();

	/**
	 * Return the name of the foreign key.
	 *
	 * @return string
	 */
	public function getForeignKey();

	/**
	 * Return the model class name as a string.
	 *
	 * @return string
	 */
	public function getModel();

	/**
	 * Return the name of the related foreign key.
	 *
	 * @return string
	 */
	public function getRelatedForeignKey();

	/**
	 * Return the type of relation.
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * Return true if the relation is dependent to the parent.
	 *
	 * @return bool
	 */
	public function isDependent();

	/**
	 * Set the alias name.
	 *
	 * @param string $alias
	 * @return \Titon\Model\Relation
	 */
	public function setAlias($alias);

	/**
	 * Set the query conditions for this relation.
	 *
	 * @param \Closure $callback
	 * @return \Titon\Model\Relation
	 */
	public function setConditions(Closure $callback);

	/**
	 * Set relation dependency.
	 *
	 * @param bool $state
	 * @return \Titon\Model\Relation
	 */
	public function setDependent($state);

	/**
	 * Set the foreign key for the current model.
	 *
	 * @param string $key
	 * @return \Titon\Model\Relation
	 */
	public function setForeignKey($key);

	/**
	 * Set the model class.
	 *
	 * @param string $model
	 * @return \Titon\Model\Relation
	 */
	public function setModel($model);

	/**
	 * Set the foreign key for the related model.
	 *
	 * @param string $key
	 * @return \Titon\Model\Relation
	 */
	public function setRelatedForeignKey($key);

}