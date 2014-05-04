<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

use \PDO;

/**
 * Represents an "INTEGER" data type.
 *
 * @package Titon\Db\Driver\Type
 */
class IntType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function from($value) {
        return (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getBindingType() {
        return PDO::PARAM_INT;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return static::INT;
    }

    /**
     * {@inheritdoc}
     */
    public function to($value) {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

}