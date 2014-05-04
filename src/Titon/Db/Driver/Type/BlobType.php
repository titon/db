<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

use Titon\Db\Exception\ConversionFailureException;
use Titon\Db\Exception\UnsupportedTypeException;
use \PDO;

/**
 * Represents a "BLOB" data type.
 *
 * @package Titon\Db\Driver\Type
 */
class BlobType extends AbstractType {

    /**
     * {@inheritdoc}
     *
     * @throws \Titon\Db\Exception\ConversionFailureException
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
        return static::BLOB;
    }

    /**
     * {@inheritdoc}
     */
    public function to($value) {
        if ($value && !is_resource($value)) {
            throw new UnsupportedTypeException('Blob data must be wrapped in a stream');
        }

        return $value;
    }

}