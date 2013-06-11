<?php
// has many

namespace Titon\Model\Relation;

use Titon\Model\Relation;

class OneToMany extends AbstractRelation {

	public function getType() {
		return Relation::ONE_TO_MANY;
	}

}