<?php

namespace Titon\Model\Driver\Type;

use Titon\Utility\Time;
use \DateTime;

class DatetimeType extends DateType {

	/**
	 * {@inheritdoc}
	 */
	public function getDriverType() {
		return 'datetime';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::DATETIME;
	}

	/**
	 * {@inheritdoc}
	 */
	public function to($value) {
		if ($value instanceof DateTime) {
			return $value->format('Y-m-d H:i:s');
		}

		return date('Y-m-d H:i:s', Time::toUnix($value));
	}

}