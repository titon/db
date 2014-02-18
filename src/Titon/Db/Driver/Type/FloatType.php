<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

/**
 * Represents a "FLOAT" data type.
 *
 * @package Titon\Db\Driver\Type
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