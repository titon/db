<?php

namespace Titon\Model\Driver\Type;

class StringType extends CharType {

	/**
	 * {@inheritdoc}
	 */
	public function getDefaultOptions() {
		return ['length' => 255, 'default' => ''];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::STRING;
	}

}