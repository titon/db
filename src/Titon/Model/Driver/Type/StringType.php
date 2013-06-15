<?php

namespace Titon\Model\Driver\Type;

class String extends CharType {

	/**
	 * {@inheritdoc}
	 */
	public function getDefaultOptions() {
		return ['length' => 255, 'default' => ''];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDriverType() {
		return 'varchar';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::STRING;
	}

}