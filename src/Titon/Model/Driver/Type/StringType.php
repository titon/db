<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

/**
 * Represents a "VARCHAR" data type.
 *
 * @package Titon\Model\Driver\Type
 */
class StringType extends CharType {

	/**
	 * {@inheritdoc}
	 */
	public function getDefaultOptions() {
		return ['length' => 255] + parent::getDefaultOptions();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::STRING;
	}

}