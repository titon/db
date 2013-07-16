<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use \PDO;

/**
 * Represents a "TEXT" data type.
 *
 * @package Titon\Model\Driver\Type
 */
class TextType extends AbstractType {

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
		return self::TEXT;
	}

}