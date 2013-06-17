<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

/**
 * Represents a "SERIAL" data type.
 *
 * @package Titon\Model\Driver\Type
 */
class SerialType extends BigintType {

	/**
	 * {@inheritdoc}
	 */
	public function getDefaultOptions() {
		return ['type' => 'bigint', 'null' => false, 'unsigned' => true, 'ai' => true, 'unique' => true];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::SERIAL;
	}

}