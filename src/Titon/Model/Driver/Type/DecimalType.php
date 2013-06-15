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
	public function getDriverType() {
		return 'decimal';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::DECIMAL;
	}

}