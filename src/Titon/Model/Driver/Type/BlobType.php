<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use Titon\Model\Exception\ConversionFailureException;
use \PDO;

/**
 * Represents a "BLOB" data type.
 *
 * @package Titon\Model\Driver\Type
 */
class BlobType extends AbstractType {

	/**
	 * {@inheritdoc}
	 *
	 * @throws \Titon\Model\Exception\ConversionFailureException
	 */
	public function from($value) {
		if ($value === null) {
			return null;
		}

		if (is_string($value)) {
			$value = fopen('data://text/plain;base64,' . base64_encode($value), 'rb');
		}

		if (!is_resource($value)) {
			throw new ConversionFailureException('Failed to convert value to a binary resource');
		}

		return $value;
	}

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
		return self::BLOB;
	}

	/**
	 * {@inheritdoc}
	 */
	public function to($value) {
		if (is_resource($value)) {
			$contents = stream_get_contents($value, -1, 0);
			fclose($value);
			$value = $contents;
		}

		return $value;
	}

}