<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

/**
 * Represents a "TIME" data type.
 *
 * @package Titon\Db\Driver\Type
 */
class TimeType extends DateType {

    /**
     * {@inheritdoc}
     */
    public $format = 'H:i:s';

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return static::TIME;
    }

}