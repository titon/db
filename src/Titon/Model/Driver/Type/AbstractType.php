<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use Titon\Common\Registry;
use Titon\Model\Driver;
use Titon\Model\Driver\Type;
use Titon\Model\Exception\UnsupportedTypeException;
use \PDO;

/**
 * Provides default shared functionality for types.
 *
 * @package Titon\Model\Driver\Type
 */
abstract class AbstractType implements Type {

	/**
	 * {@inheritdoc}
	 *
	 * @throws \Titon\Model\Exception\UnsupportedTypeException
	 */
	public static function factory($type, Driver $driver) {
		$types = $driver->getSupportedTypes();

		if (isset($types[$type])) {
			return Registry::factory($types[$type]);
		}

		throw new UnsupportedTypeException(sprintf('Unsupported data type %s', $type));
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