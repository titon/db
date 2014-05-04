<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

use Titon\Utility\Time;
use \DateTime;

/**
 * Represents a "DATE" data type.
 *
 * @package Titon\Db\Driver\Type
 */
class DateType extends AbstractType {

    /**
     * The timestamp format to use.
     *
     * @type string
     */
    public $format = 'Y-m-d';

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions() {
        return ['null' => true, 'default' => null];
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return static::DATE;
    }

    /**
     * {@inheritdoc}
     *
     * @uses Titon\Utility\Time
     */
    public function to($value) {
        if ($value instanceof DateTime) {
            return $value->format($this->format);
        }

        // Convert array to UNIX timestamp
        if (is_array($value)) {
            $hour = isset($value['hour']) ? $value['hour'] : 0;

            if (isset($value['meridiem']) && strtolower($value['meridiem']) === 'pm') {
                $hour += 12;
            }

            $value = mktime(
                $hour,
                isset($value['minute']) ? $value['minute'] : 0,
                isset($value['second']) ? $value['second'] : 0,
                isset($value['month']) ? $value['month'] : date('m'),
                isset($value['day']) ? $value['day'] : date('d'),
                isset($value['year']) ? $value['year'] : date('Y'));
        }

        return date($this->format, Time::toUnix($value));
    }

}