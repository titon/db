<?php

namespace Titon\Model\Driver\Type;

use \PDO;

class BooleanType extends AbstractType {

	/**
	 * {@inheritdoc}
	 */
	public function from($value) {
		return (bool) $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBindingType() {
		return PDO::PARAM_BOOL;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDriverType() {
		return 'boolean';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::BOOLEAN;
	}

	/**
	 * {@inheritdoc}
	 */
	public function to($value) {
		return (bool) $value;
	}

}