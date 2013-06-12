<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opendriver.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Relation;

use Titon\Model\Relation;

/**
 * Represents a one-to-many model relationship.
 * Also known as a has many.
 *
 * @link http://en.wikipedia.org/wiki/Cardinality_%28data_modeling%29
 *
 * @package Titon\Model\Relation
 */
class OneToMany extends AbstractRelation {

	/**
	 * {@inheritdoc}
	 */
	public function getType() {
		return Relation::ONE_TO_MANY;
	}

}