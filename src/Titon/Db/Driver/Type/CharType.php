<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

/**
 * Represents a "CHAR" data type.
 *
 * @package Titon\Db\Driver\Type
 */
class CharType extends AbstractType {

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
        return self::CHAR;
    }

    /**
     * {@inheritdoc}
     */
    public function to($value) {
        return (string) $value;
    }

}