<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

/**
 * Represents a "BIGINT" data type.
 *
 * @package Titon\Db\Driver\Type
 */
class BigintType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function from($value) {
        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return static::BIGINT;
    }

    /**
     * {@inheritdoc}
     */
    public function to($value) {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

}