<?php
// HABTM

namespace Titon\Model\Relation;

use Titon\Model\Relation;

class ManyToMany extends AbstractRelation {

	public function getType() {
		return Relation::MANY_TO_MANY;
	}

}