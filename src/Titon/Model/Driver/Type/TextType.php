<?php

namespace Titon\Model\Driver\Type;

use \PDO;

class TextType extends AbstractType {

	/**
	 * {@inheritdoc}
	 */
	public function getBindingType() {
		return PDO::PARAM_LOB;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::TEXT;
	}

}