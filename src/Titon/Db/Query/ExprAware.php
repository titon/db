<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query;

/**
 * Permits a class to instantiate new expressions.
 *
 * @package Titon\Db\Query
 */
trait ExprAware {

    /**
     * Instantiate a new database expression.
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return \Titon\Db\Query\Expr
     */
    public static function expr($field, $operator = null, $value = null) {
        return new Expr($field, $operator, $value);
    }

}