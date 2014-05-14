<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query;

/**
 * Permits a class to instantiate new raw expressions.
 *
 * @package Titon\Db\Query
 */
trait RawExprAware {

    /**
     * Instantiate a new raw database expression.
     *
     * @param string $value
     * @return \Titon\Db\Query\RawExpr
     */
    public static function raw($value) {
        return new RawExpr($value);
    }

}