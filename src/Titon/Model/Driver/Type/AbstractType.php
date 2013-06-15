<?php

namespace Titon\Model\Driver\Type;

use Titon\Model\Driver\Type;
use \PDO;

abstract class AbstractType implements Type {

	/**
	 * {@inheritdoc}
	 */
	public function from($value) {
		return $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBindingType() {
		return PDO::PARAM_STR;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDefaultOptions() {
		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function to($value) {
		return $value;
	}

}