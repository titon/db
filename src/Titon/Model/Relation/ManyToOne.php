<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Relation;

use Titon\Model\Relation;

/**
 * Represents a many-to-one model relationship.
 * Also known as a belongs to.
 *
 * @link http://en.wikipedia.org/wiki/Cardinality_%28data_modeling%29
 *
 * @package Titon\Model\Relation
 */
class ManyToOne extends AbstractRelation {

	/**
	 * {@inheritdoc}
	 */
	public function getType() {
		return Relation::MANY_TO_ONE;
	}

	/**
	 * Belongs to should not delete parent records.
	 *
	 * @return bool
	 */
	public function isDependent() {
		return false;
	}

}