<?php

namespace Titon\Model\Driver\Type;

class DecimalType extends FloatType {

	/**
	 * {@inheritdoc}
	 */
	public function getDefaultOptions() {
		return ['length' => '8,2'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::DECIMAL;
	}

}