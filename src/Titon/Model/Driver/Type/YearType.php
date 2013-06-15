<?php

namespace Titon\Model\Driver\Type;

use Titon\Utility\Time;
use \DateTime;

class YearType extends AbstractType {

	/**
	 * {@inheritdoc}
	 */
	public function getDefaultOptions() {
		return ['null' => true, 'default' => null, 'length' => 4];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDriverType() {
		return 'year';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::YEAR;
	}

	/**
	 * {@inheritdoc}
	 */
	public function to($value) {
		if ($value instanceof DateTime) {
			return $value->format('Y');
		}

		return date('Y', Time::toUnix($value));
	}

}