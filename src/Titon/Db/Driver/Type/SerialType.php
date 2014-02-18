<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

/**
 * Represents a "SERIAL" data type.
 *
 * @package Titon\Db\Driver\Type
 */
class SerialType extends BigintType {

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions() {
        return ['type' => 'bigint', 'null' => false, 'unsigned' => true, 'ai' => true, 'primary' => true];
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return self::SERIAL;
    }

}