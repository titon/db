<?php
// belongs to

namespace Titon\Model\Relation;

use Titon\Model\Relation;

class ManyToOne extends AbstractRelation {

	public function getType() {
		return Relation::MANY_TO_ONE;
	}

}