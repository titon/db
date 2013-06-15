<?php

namespace Titon\Model\Driver\Type;

use Titon\Utility\Time;
use \DateTime;

class TimeType extends DateType {

	/**
	 * {@inheritdoc}
	 */
	public function getDriverType() {
		return 'time';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::TIME;
	}

	/**
	 * {@inheritdoc}
	 */
	public function to($value) {
		if ($value instanceof DateTime) {
			return $value->format('H:i:s');
		}

		return date('H:i:s', Time::toUnix($value));
	}

}