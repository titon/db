<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

/**
 * Represents a "DECIMAL" data type.
 *
 * @package Titon\Db\Driver\Type
 */
class DecimalType extends FloatType {

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions() {
        return ['length' => '8,2'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return self::DECIMAL;
    }

}