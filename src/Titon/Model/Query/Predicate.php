<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query;

use Titon\Model\Exception;
use \Closure;
use \Serializable;
use \JsonSerializable;

/**
 * Functionality for handling advanced query predicates: where, having
 *
 * @package Titon\Model\Query
 */
class Predicate implements Serializable, JsonSerializable {

	// Types
	const ALSO = 'and';
	const EITHER = 'or';

	// Special operators
	const NULL = 'isNull';
	const NOT_NULL = 'isNotNull';
	const IN = 'in';
	const NOT_IN = 'notIn';
	const BETWEEN = 'between';
	const NOT_BETWEEN = 'notBetween';
	const LIKE = 'like';
	const NOT_LIKE = 'notLike';
	const NOT = 'not';

	/**
	 * Type of predicate, either AND or OR.
	 *
	 * @type string
	 */
	protected $_type;

	/**
	 * List of parameters for this predicate.
	 *
	 * @type array
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
	 * @param mixed $value
	 * @param string $op
	 * @return \Titon\Model\Query\Predicate
	 * @throws \Titon\Model\Exception
	 */
	public function add($field, $value, $op) {
		$key = $field . $op . (is_array($value) ? implode('', $value) : $value);

		$this->_params[$key] = [
			'field' => $field,
			'value' => $value,
			'op' => $op
		];

		return $this;
	}

	/**
	 * Generate a new sub-grouped AND predicate.
	 *
	 * @param \Closure $callback
	 * @return \Titon\Model\Query\Predicate
	 */
	public function also(Closure $callback) {
		$predicate = new Predicate(self::ALSO);
		$predicate->bindCallback($callback);

		$this->_params[] = $predicate;

		return $this;
	}

	/**
	 * Adds a between range "BETWEEN" expression.
	 *
	 * @param string $field
	 * @param int $start
	 * @param int $end
	 * @return \Titon\Model\Query\Predicate
	 */
	public function between($field, $start, $end) {
		$this->add($field, [$start, $end], self::BETWEEN);

		return $this;
	}

	/**
	 * Bind a Closure callback to this predicate and execute it.
	 *
	 * @param \Closure $callback
	 */
	public function bindCallback(Closure $callback) {
		$callback = $callback->bindTo($this, 'Titon\Model\Query\Predicate');
		$callback();
	}

	/**
	 * Generate a new sub-grouped OR predicate.
	 *
	 * @param \Closure $callback
	 * @return \Titon\Model\Query\Predicate
	 */
	public function either(Closure $callback) {
		$predicate = new Predicate(self::EITHER);
		$predicate->bindCallback($callback);

		$this->_params[] = $predicate;

		return $this;
	}

	/**
	 * Adds an equals "=" expression.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return \Titon\Model\Query\Predicate
	 */
	public function eq($field, $value) {
		if (is_array($value)) {
			$this->in($field, $value);

		} else if ($value === null) {
			$this->null($field);

		} else {
			$this->add($field, $value, '=');
		}

		return $this;
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
	 * @return \Titon\Model\Query\Predicate
	 */
	public function gt($field, $value) {
		$this->add($field, $value, '>');

		return $this;
	}

	/**
	 * Adds a greater than or equals to ">=" expression.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return \Titon\Model\Query\Predicate
	 */
	public function gte($field, $value) {
		$this->add($field, $value, '>=');

		return $this;
	}

	/**
	 * Adds an in array "IN()" expression.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return \Titon\Model\Query\Predicate
	 */
	public function in($field, $value) {
		$this->add($field, (array) $value, self::IN);

		return $this;
	}

	/**
	 * Adds a like wildcard "LIKE" expression.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return \Titon\Model\Query\Predicate
	 */
	public function like($field, $value) {
		$this->add($field, $value, self::LIKE);

		return $this;
	}

	/**
	 * Adds a less than "<" expression.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return \Titon\Model\Query\Predicate
	 */
	public function lt($field, $value) {
		$this->add($field, $value, '<');

		return $this;
	}

	/**
	 * Adds a less than or equals to "<=" expression.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return \Titon\Model\Query\Predicate
	 */
	public function lte($field, $value) {
		$this->add($field, $value, '<=');

		return $this;
	}

	/**
	 * Adds a not "NOT" expression.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return \Titon\Model\Query\Predicate
	 */
	public function not($field, $value) {
		$this->add($field, $value, self::NOT);

		return $this;
	}

	/**
	 * Adds a not between range "NOT BETWEEN" expression.
	 *
	 * @param string $field
	 * @param int $start
	 * @param int $end
	 * @return \Titon\Model\Query\Predicate
	 */
	public function notBetween($field, $start, $end) {
		$this->add($field, [$start, $end], self::NOT_BETWEEN);

		return $this;
	}

	/**
	 * Adds a not equals "!=" expression.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return \Titon\Model\Query\Predicate
	 */
	public function notEq($field, $value) {
		if (is_array($value)) {
			$this->notIn($field, $value);

		} else if ($value === null) {
			$this->notNull($field);

		} else {
			$this->add($field, $value, '!=');
		}

		return $this;
	}

	/**
	 * Adds a not in array "NOT IN()" expression.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return \Titon\Model\Query\Predicate
	 */
	public function notIn($field, $value) {
		$this->add($field, (array) $value, self::NOT_IN);

		return $this;
	}

	/**
	 * Adds a not like wildcard "LIKE" expression.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return \Titon\Model\Query\Predicate
	 */
	public function notLike($field, $value) {
		$this->add($field, $value, self::NOT_LIKE);

		return $this;
	}

	/**
	 * Adds a not is null "NOT IS NULL" expression.
	 *
	 * @param string $field
	 * @return \Titon\Model\Query\Predicate
	 */
	public function notNull($field) {
		$this->add($field, null, self::NOT_NULL);

		return $this;
	}

	/**
	 * Adds an is null "IS NULL" expression.
	 *
	 * @param string $field
	 * @return \Titon\Model\Query\Predicate
	 */
	public function null($field) {
		$this->add($field, null, self::NULL);

		return $this;
	}

	/**
	 * Serialize the query predicate.
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize($this->jsonSerialize());
	}

	/**
	 * Reconstruct the query predicate once unserialized.
	 *
	 * @param array $data
	 */
	public function unserialize($data) {
		$data = unserialize($data);

		$this->_type = $data['type'];

		foreach ($data['params'] as $param) {
			$this->add($param['field'], $param['value'], $param['op']);
		}
	}

	/**
	 * Return all data for serialization.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'type' => $this->getType(),
			'params' => $this->getParams()
		];
	}

}