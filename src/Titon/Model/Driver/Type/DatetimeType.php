<?php

namespace Titon\Model\Driver\Type;

use Titon\Utility\Time;
use \DateTime;

class DatetimeType extends DateType {

	/**
	 * {@inheritdoc}
	 */
	public $format = 'Y-m-d H:i:s';

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::DATETIME;
	}

}