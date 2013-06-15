<?php

namespace Titon\Model\Driver\Type;

use Titon\Utility\Time;
use \DateTime;

class DateType extends AbstractType {

	/**
	 * {@inheritdoc}
	 */
	public function getDefaultOptions() {
		return ['null' => true, 'default' => null];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDriverType() {
		return 'date';
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
			return $value->format('Y-m-d');
		}

		return date('Y-m-d', Time::toUnix($value));
	}

}