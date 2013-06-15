<?php

namespace Titon\Model\Driver\Type;

class CharType extends AbstractType {

	/**
	 * {@inheritdoc}
	 */
	public function from($value) {
		return (string) $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDriverType() {
		return 'char';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::CHAR;
	}

	/**
	 * {@inheritdoc}
	 */
	public function to($value) {
		return (string) $value;
	}

}