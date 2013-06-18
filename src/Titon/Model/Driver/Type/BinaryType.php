<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use \PDO;

/**
 * Represents an "BIT", "BINARY" data type.
 *
 * @package Titon\Model\Driver\Type
 */
class BinaryType extends AbstractType {

	/**
	 * {@inheritdoc}
	 */
	public function from($value) {
		return pack('H*', base_convert($value, 2, 16));
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
	public function getName() {
		return self::BINARY;
	}

	/**
	 * {@inheritdoc}
	 */
	public function to($value) {
		if (preg_match('/^[01]+$/', $value)) {
			return $value;
		}

		return base_convert(unpack('H*', (string) $value)[1], 16, 2);
	}

}