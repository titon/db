<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query;

use Titon\Db\Driver\Dialect;
use \Closure;

/**
 * Functionality for handling advanced query predicates: where, having
 *
 * @package Titon\Db\Query
 */
class Predicate {
    use BindingAware;

    const ALSO = Dialect::ALSO; // AND
    const EITHER = Dialect::EITHER; // OR
    const NEITHER = Dialect::NEITHER; // NOR
    const MAYBE = Dialect::MAYBE; // XOR

    /**
     * Type of predicate, either AND or OR.
     *
     * @type string
     */
    protected $_type;

    /**
     * List of parameters for this predicate.
     *
     * @type \Titon\Db\Query\Expr[]|\Titon\Db\Query\Predicate[]
     */
    protected $_params = [];

    /**
     * Set the predicate type.
     *
     * @param int $type
     */
    public function __construct($type) {
        $this->_type = $type;
    }

    /**
     * Process a parameter before adding it to the list.
     * Set the primary predicate type if it has not been set.
     *
     * @param string $field
     * @param string $op
     * @param mixed $value
     * @return $this
     */
    public function add($field, $op, $value) {
        $param = new Expr($field, $op, $value);

        $this->_params[] = $param;

        if ($param->useValue()) {
            $this->addBinding($field, $value);
        }

        return $this;
    }

    /**
     * Generate a new sub-grouped AND predicate.
     *
     * @param \Closure $callback
     * @return $this
     */
    public function also(Closure $callback) {
        $predicate = new Predicate(self::ALSO);
        $predicate->bindCallback($callback);

        $this->_params[] = $predicate;

        $this->addBinding(null, $predicate);

        return $this;
    }

    /**
     * Adds a between range "BETWEEN" expression.
     *
     * @param string $field
     * @param int $start
     * @param int $end
     * @return $this
     */
    public function between($field, $start, $end) {
        $this->add($field, Expr::BETWEEN, [$start, $end]);

        return $this;
    }

    /**
     * Bind a Closure callback to this predicate and execute it.
     *
     * @param \Closure $callback
     * @param \Titon\Db\Query $query
     * @return $this
     */
    public function bindCallback(Closure $callback, $query = null) {
        call_user_func_array($callback, [$this, $query]);

        return $this;
    }

    /**
     * Generate a new sub-grouped OR predicate.
     *
     * @param \Closure $callback
     * @return $this
     */
    public function either(Closure $callback) {
        $predicate = new Predicate(self::EITHER);
        $predicate->bindCallback($callback);

        $this->_params[] = $predicate;

        $this->addBinding(null, $predicate);

        return $this;
    }

    /**
     * Adds an equals "=" expression.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function eq($field, $value) {
        if (is_array($value)) {
            $this->in($field, $value);

        } else if ($value === null) {
            $this->null($field);

        } else {
            $this->add($field, '=', $value);
        }

        return $this;
    }

    /**
     * Alias for add().
     *
     * @param string $field
     * @param string $op
     * @param mixed $value
     * @return $this
     */
    public function expr($field, $op, $value) {
        return $this->add($field, $op, $value);
    }

    /**
     * Return the predicate parameters.
     *
     * @return array
     */
    public function getParams() {
        return $this->_params;
    }

    /**
     * Return the type of predicate.
     *
     * @return string
     */
    public function getType() {
        return $this->_type ?: self::ALSO;
    }

    /**
     * Adds a greater than ">" expression.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function gt($field, $value) {
        $this->add($field, '>', $value);

        return $this;
    }

    /**
     * Adds a greater than or equals to ">=" expression.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function gte($field, $value) {
        $this->add($field, '>=', $value);

        return $this;
    }

    /**
     * Return true if a field has been used in a param.
     *
     * @param string $field
     * @return bool
     */
    public function hasParam($field) {
        foreach ($this->getParams() as $param) {
            if ($param instanceof Expr && $param->getField() === $field) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds an in array "IN()" expression.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function in($field, $value) {
        $this->add($field, Expr::IN, (array) $value);

        return $this;
    }

    /**
     * Adds a like wildcard "LIKE" expression.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function like($field, $value) {
        $this->add($field, Expr::LIKE, $value);

        return $this;
    }

    /**
     * Adds a less than "<" expression.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function lt($field, $value) {
        $this->add($field, '<', $value);

        return $this;
    }

    /**
     * Adds a less than or equals to "<=" expression.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function lte($field, $value) {
        $this->add($field, '<=', $value);

        return $this;
    }

    /**
     * Generate a new sub-grouped XOR predicate.
     *
     * @param \Closure $callback
     * @return $this
     */
    public function maybe(Closure $callback) {
        $predicate = new Predicate(self::MAYBE);
        $predicate->bindCallback($callback);

        $this->_params[] = $predicate;

        $this->addBinding(null, $predicate);

        return $this;
    }

    /**
     * Generate a new sub-grouped NOR predicate.
     *
     * @param \Closure $callback
     * @return $this
     */
    public function neither(Closure $callback) {
        $predicate = new Predicate(self::NEITHER);
        $predicate->bindCallback($callback);

        $this->_params[] = $predicate;

        $this->addBinding(null, $predicate);

        return $this;
    }

    /**
     * Adds a not between range "NOT BETWEEN" expression.
     *
     * @param string $field
     * @param int $start
     * @param int $end
     * @return $this
     */
    public function notBetween($field, $start, $end) {
        $this->add($field, Expr::NOT_BETWEEN, [$start, $end]);

        return $this;
    }

    /**
     * Adds a not equals "!=" expression.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function notEq($field, $value) {
        if (is_array($value)) {
            $this->notIn($field, $value);

        } else if ($value === null) {
            $this->notNull($field);

        } else {
            $this->add($field, '!=', $value);
        }

        return $this;
    }

    /**
     * Adds a not in array "NOT IN()" expression.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function notIn($field, $value) {
        $this->add($field, Expr::NOT_IN, (array) $value);

        return $this;
    }

    /**
     * Adds a not like wildcard "LIKE" expression.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function notLike($field, $value) {
        $this->add($field, Expr::NOT_LIKE, $value);

        return $this;
    }

    /**
     * Adds a not is null "NOT IS NULL" expression.
     *
     * @param string $field
     * @return $this
     */
    public function notNull($field) {
        $this->add($field, Expr::NOT_NULL, null);

        return $this;
    }

    /**
     * Adds an is null "IS NULL" expression.
     *
     * @param string $field
     * @return $this
     */
    public function null($field) {
        $this->add($field, Expr::NULL, null);

        return $this;
    }

}