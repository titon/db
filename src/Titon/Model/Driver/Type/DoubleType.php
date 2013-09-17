<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model\Driver\Type;

/**
 * Represents a "DOUBLE" data type.
 *
 * @package Titon\Model\Driver\Type
 */
class DoubleType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function from($value) {
        return (double) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return self::DOUBLE;
    }

    /**
     * {@inheritdoc}
     */
    public function to($value) {
        return (double) $value;
    }

}