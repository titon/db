<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

/**
 * Represents a "BLOB" data type.
 *
 * @package Titon\Model\Driver\Type
 */
class BlobType extends AbstractType {

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::BLOB;
	}

}