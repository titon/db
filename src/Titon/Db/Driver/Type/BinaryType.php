<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

use \PDO;

/**
 * Represents an "BIT", "BINARY" data type.
 *
 * @package Titon\Db\Driver\Type
 */
class BinaryType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function from($value) {
        return pack('H*', base_convert($value, 2, 16));
    }

    /**
     * {@inheritdoc}
     */
    public function getBindingType() {
        return PDO::PARAM_STR;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return static::BINARY;
    }

    /**
     * {@inheritdoc}
     */
    public function to($value) {
        if (preg_match('/^[01]+$/', $value)) {
            return $value;
        }

        return base_convert(unpack('H*', (string) $value)[1], 16, 2);
    }

}