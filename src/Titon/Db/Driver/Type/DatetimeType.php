<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

/**
 * Represents a "DATETIME" data type.
 *
 * @package Titon\Db\Driver\Type
 */
class DatetimeType extends DateType {

    /**
     * {@inheritdoc}
     */
    public $format = 'Y-m-d H:i:s';

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return static::DATETIME;
    }

}