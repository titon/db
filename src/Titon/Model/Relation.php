<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

/**
 * Represents a relation of one model to another.
 */
interface Relation {

	const ONE_TO_ONE = 'oneToOne'; // Has One
	const ONE_TO_MANY = 'oneToMany'; // Has Many
	const MANY_TO_ONE = 'manyToOne'; // Belongs To
	const MANY_TO_MANY = 'manyToMany'; // Has And Belongs To Many

	public function getAlias();

	public function getForeignKey();

	public function getModel();

	public function getType();

	public function isDependent();

	public function setAlias($alias);

	public function setDependent($state);

	public function setForeignKey($key);

	public function setModel($model);

}