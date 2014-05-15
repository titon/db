<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query;

use Titon\Db\Driver\Dialect;

/**
 * The Expr class represents a mathematical or logical expression that can be calculated depending on context.
 * Will properly quote identifiers and values.
 *
 * @package Titon\Db\Query
 */
class Expr extends RawExpr {
    use ExprAware;

    const NULL = Dialect::IS_NULL;
    const NOT_NULL = Dialect::IS_NOT_NULL;
    const IN = Dialect::IN;
    const NOT_IN = Dialect::NOT_IN;
    const BETWEEN = Dialect::BETWEEN;
    const NOT_BETWEEN = Dialect::NOT_BETWEEN;
    const LIKE = Dialect::LIKE;
    const NOT_LIKE = Dialect::NOT_LIKE;
    const REGEXP = Dialect::REGEXP;
    const NOT_REGEXP = Dialect::NOT_REGEXP;
    const RLIKE = Dialect::RLIKE;
    const AS_ALIAS = Dialect::AS_ALIAS;

    /**
     * Field name.
     *
     * @type string
     */
    protected $_field;

    /**
     * Expression operator.
     *
     * @type string
     */
    protected $_operator;

    /**
     * Store all values required by the expression.
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     */
    public function __construct($field, $operator = null, $value = null) {
        $this->_field = $field;
        $this->_operator = $operator;
        $this->_value = $value;
    }

    /**
     * Return the field name.
     *
     * @return string
     */
    public function getField() {
        return $this->_field;
    }

    /**
     * Return the operator.
     *
     * @return string
     */
    public function getOperator() {
        return $this->_operator;
    }

    /**
     * Return true if the value should be used for binds.
     *
     * @return bool
     */
    public function useValue() {
        return ($this->getOperator() !== null && $this->getValue() !== null);
    }

}