<?php

namespace Titon\Model\Driver\Type;

class Smallint extends IntType {

	/**
	 * {@inheritdoc}
	 */
	public function getDriverType() {
		return 'smallint';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::SMALLINT;
	}

}