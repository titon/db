<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use Titon\Utility\Time;

/**
 * Represents a "TIME" data type.
 *
 * @package Titon\Model\Driver\Type
 */
class TimeType extends DateType {

	/**
	 * {@inheritdoc}
	 */
	public $format = 'H:i:s';

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::TIME;
	}

}