<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query;

/**
 * Provides an easy interface for managing parameters that should be bound to
 * PDO prepared statements, or for use in driver specific querying.
 *
 * @package Titon\Db\Query
 */
trait BindingAware {

    /**
     * Raw values to use for parameter binding.
     *
     * @type array
     */
    protected $_bindings = [];

    /**
     * Add a value to use in SQL parameter binding.
     *
     * @param string $field
     * @param mixed $values
     * @return $this
     */
    public function addBinding($field, $values) {
        if (!is_array($values)) {
            $values = [$values];
        }

        foreach ($values as $value) {
            if ($value instanceof Predicate) {
                $this->resolvePredicate($value);

            } else if ($value instanceof SubQuery) {
                $this->resolveSubQuery($value);

            } else {
                $this->_bindings[] = [
                    'field' => $field,
                    'value' => $value
                ];
            }
        }

        return $this;
    }

    /**
     * Return a list of parameters in order they were bound.
     *
     * @return array
     */
    public function getBindings() {
        return $this->_bindings;
    }

    /**
     * Merge an external predicates bindings with the current bindings.
     *
     * @param \Titon\Db\Query\Predicate $predicate
     * @return $this
     */
    public function resolvePredicate(Predicate $predicate) {
        $this->_bindings = array_merge($this->_bindings, $predicate->getBindings());

        return $this;
    }

    /**
     * Merge an external sub-queries bindings with the current bindings.
     *
     * @param \Titon\Db\Query\SubQuery $query
     * @return $this
     */
    public function resolveSubQuery(SubQuery $query) {
        foreach ($query->getGroupedBindings() as $bindings) {
            $this->_bindings = array_merge($this->_bindings, $bindings);
        }

        return $this;
    }

    /**
     * Set a mapping of parameters directly.
     *
     * @param array $values
     * @return $this
     */
    public function setBindings(array $values) {
        $this->_bindings = $values;

        return $this;
    }

}