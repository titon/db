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
		return (binary) $value;
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
		return (binary) $value;
	}

}