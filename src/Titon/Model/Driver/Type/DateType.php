<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use Titon\Utility\Time;
use \DateTime;

/**
 * Represents a "DATE" data type.
 *
 * @package Titon\Model\Driver\Type
 */
class DateType extends AbstractType {

	/**
	 * The timestamp format to use.
	 *
	 * @type string
	 */
	public $format = 'Y-m-d';

	/**
	 * {@inheritdoc}
	 */
	public function getDefaultOptions() {
		return ['null' => true, 'default' => null];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::DATE;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @uses Titon\Utility\Time
	 */
	public function to($value) {
		if ($value instanceof DateTime) {
			return $value->format($this->format);
		}

		return date($this->format, Time::toUnix($value));
	}

}