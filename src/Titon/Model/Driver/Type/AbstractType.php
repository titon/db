<?php

namespace Titon\Model\Driver\Type;

use Titon\Common\Registry;
use Titon\Model\Driver;
use Titon\Model\Driver\Type;
use Titon\Model\Exception;
use \PDO;

abstract class AbstractType implements Type {

	/**
	 * {@inheritdoc}
	 */
	public static function factory($type, Driver $driver) {
		$types = $driver->getSupportedTypes();

		if (isset($types[$type])) {
			return Registry::factory($types[$type]);
		}

		throw new Exception(sprintf('Unsupported data type %s', $type));
	}

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