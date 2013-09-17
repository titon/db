<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model\Driver\Type;

use Titon\Utility\Time;

/**
 * Represents a "DATETIME" data type.
 *
 * @package Titon\Model\Driver\Type
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
        return self::DATETIME;
    }

}