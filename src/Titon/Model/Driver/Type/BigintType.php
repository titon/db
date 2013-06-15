<?php

namespace Titon\Model\Driver\Type;

class Bigint extends IntType {

	/**
	 * {@inheritdoc}
	 */
	public function getDriverType() {
		return 'bigint';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::BIGINT;
	}

}