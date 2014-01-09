<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Traits;

use Titon\Db\Query\Expr;

/**
 * Permits a class to instantiate new expressions.
 *
 * @package Titon\Db\Traits
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