<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use Titon\Utility\Time;

/**
 * Represents a "YEAR(4)" data type.
 *
 * @package Titon\Model\Driver\Type
 */
class YearType extends DateType {

	/**
	 * {@inheritdoc}
	 */
	public $format = 'Y';

	/**
	 * {@inheritdoc}
	 */
	public function from($value) {
		return (int) $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDefaultOptions() {
		return ['null' => true, 'default' => null, 'length' => 4];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::YEAR;
	}

}