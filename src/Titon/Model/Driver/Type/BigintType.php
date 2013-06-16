<?php

namespace Titon\Model\Driver\Type;

class BigintType extends AbstractType {

	/**
	 * {@inheritdoc}
	 */
	public function from($value) {
		return (string) $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::BIGINT;
	}

	/**
	 * {@inheritdoc}
	 */
	public function to($value) {
		return (string) $value;
	}

}