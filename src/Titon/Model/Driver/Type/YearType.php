<?php

namespace Titon\Model\Driver\Type;

use Titon\Utility\Time;
use \DateTime;

class YearType extends AbstractType {

	/**
	 * {@inheritdoc}
	 */
	public $format = 'Y';

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