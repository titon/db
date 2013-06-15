<?php

namespace Titon\Model\Driver\Type;

use \PDO;

class IntType extends AbstractType {

	/**
	 * {@inheritdoc}
	 */
	public function from($value) {
		return (int) $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBindingType() {
		return PDO::PARAM_INT;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDriverType() {
		return 'integer';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::INT;
	}

	/**
	 * {@inheritdoc}
	 */
	public function to($value) {
		return (int) $value;
	}

}