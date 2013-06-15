<?php

namespace Titon\Model\Driver\Type;

class FloatType extends AbstractType {

	/**
	 * {@inheritdoc}
	 */
	public function from($value) {
		return (float) $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDriverType() {
		return 'float';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::FLOAT;
	}

	/**
	 * {@inheritdoc}
	 */
	public function to($value) {
		return (float) $value;
	}

}