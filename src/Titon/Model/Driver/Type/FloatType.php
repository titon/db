<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model\Driver\Type;

/**
 * Represents a "FLOAT" data type.
 *
 * @package Titon\Model\Driver\Type
 */
class FloatType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function from($value) {
        return (float) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return self::FLOAT;
    }

    /**
     * {@inheritdoc}
     */
    public function to($value) {
        return (float) $value;
    }

}