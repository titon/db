<?php

namespace Titon\Model\Driver\Type;

class BlobType extends AbstractType {

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return self::BLOB;
	}

}