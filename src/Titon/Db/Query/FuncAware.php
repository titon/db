<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query;

/**
 * Permits a class to instantiate new function.
 *
 * @package Titon\Db\Query
 */
trait FuncAware {

    /**
     * Instantiate a new database function.
     *
     * @param string $name
     * @param string|array $arguments
     * @param string $separator
     * @return \Titon\Db\Query\Func
     */
    public static function func($name, $arguments = [], $separator = ', ') {
        return new Func($name, $arguments, $separator);
    }

}