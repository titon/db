<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

use \PDO;

/**
 * Represents a "BOOLEAN" data type.
 *
 * @package Titon\Db\Driver\Type
 */
class BooleanType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function from($value) {
        return (bool) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getBindingType() {
        return PDO::PARAM_BOOL;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return self::BOOLEAN;
    }

    /**
     * {@inheritdoc}
     */
    public function to($value) {
        return (bool) $value;
    }

}