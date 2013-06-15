<?php

namespace Titon\Model\Driver\Type;

class BlobType extends AbstractType {

	/**
	 * {@inheritdoc}
	 */
	public function getDriverType() {
		return 'blob';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::BLOB;
	}

}