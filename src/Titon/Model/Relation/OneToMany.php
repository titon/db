<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opendriver.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Relation;

use Titon\Model\Relation;

class OneToMany extends AbstractRelation {

	/**
	 * {@inheritdoc}
	 */
	public function getType() {
		return Relation::ONE_TO_MANY;
	}

}