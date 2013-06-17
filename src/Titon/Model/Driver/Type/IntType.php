<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use \PDO;

/**
 * Represents an "INTEGER" data type.
 *
 * @package Titon\Model\Driver\Type
 */
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