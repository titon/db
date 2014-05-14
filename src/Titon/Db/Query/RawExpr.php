<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query;

/**
 * The RawExpr represents a raw database expression that is not modified by the DBAL.
 *
 * @package Titon\Db\Query
 */
class RawExpr {
    use RawExprAware;

    /**
     * Raw expression.
     *
     * @type string
     */
    protected $_value;

    /**
     * Store the expression.
     *
     * @param string $value
     */
    public function __construct($value) {
        $this->_value = $value;
    }

    /**
     * Return the expression when cast as a string.
     *
     * @return string
     */
    public function __toString() {
        return $this->getValue();
    }

    /**
     * Return the expression.
     *
     * @return string
     */
    public function getValue() {
        return $this->_value;
    }

}