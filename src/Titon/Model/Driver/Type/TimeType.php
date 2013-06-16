<?php

namespace Titon\Model\Driver\Type;

use Titon\Utility\Time;
use \DateTime;

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