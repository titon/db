<?php

namespace Titon\Model\Driver\Type;

use Titon\Utility\Time;
use \DateTime;

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
	 */
	public function to($value) {
		if ($value instanceof DateTime) {
			return $value->format($this->format);
		}

		return date($this->format, Time::toUnix($value));
	}

}