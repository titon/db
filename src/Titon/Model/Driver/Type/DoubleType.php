<?php

namespace Titon\Model\Driver\Type;

class DoubleType extends AbstractType {

	/**
	 * {@inheritdoc}
	 */
	public function from($value) {
		return (double) $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::DOUBLE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function to($value) {
		return (double) $value;
	}

}