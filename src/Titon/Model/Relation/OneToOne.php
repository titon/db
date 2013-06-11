<?php
// Has one

namespace Titon\Model\Relation;

use Titon\Model\Relation;

class OneToOne extends AbstractRelation {

	public function getType() {
		return Relation::ONE_TO_ONE;
	}

}