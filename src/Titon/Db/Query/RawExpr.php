<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query;

use Titon\Db\Traits\RawExprAware;
use \Serializable;
use \JsonSerializable;

/**
 * The RawExpr represents a raw database expression that is not modified by the DBAL.
 *
 * @package Titon\Db\Query
 */
class RawExpr implements Serializable, JsonSerializable {
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

    /**
     * Serialize the expression.
     *
     * @return string
     */
    public function serialize() {
        return serialize($this->jsonSerialize());
    }

    /**
     * Reconstruct the expression once unserialized.
     *
     * @param string $data
     */
    public function unserialize($data) {
        $this->_value = unserialize($data);
    }

    /**
     * Return all data for serialization.
     *
     * @return array
     */
    public function jsonSerialize() {
        return $this->getValue();
    }

}