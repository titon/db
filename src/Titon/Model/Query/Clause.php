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
 * Functionality for handling advanced query clauses: where, having
 */
class Clause implements Serializable, JsonSerializable {

	// Types
	const ALSO = 'AND';
	const EITHER = 'OR';

	// Special operators
	const NULL = 'IS NULL';
	const NOT_NULL = 'IS NOT NULL';
	const IN = 'IN';
	const NOT_IN = 'NOT IN';
	const BETWEEN = 'BETWEEN';
	const NOT_BETWEEN = 'NOT BETWEEN';
	const LIKE = 'LIKE';
	const NOT_LIKE = 'NOT LIKE';

	/**
	 * Type of clause, either AND or OR.
	 *
	 * @type string
	 */
	protected $_type;

	/**
	 * List of parameters for this clause group.
	 *
	 * @type array
	 */
	protected $_params = [];

	/**
	 * Add a parameter to the AND clause.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @param string $op
	 * @return \Titon\Model\Query\Clause
	 */
	public function also($field, $value, $op = '=') {
		return $this->_process(self::ALSO, $field, $value, $op);
	}

	/**
	 * Add a parameter to the OR clause.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @param string $op
	 * @return \Titon\Model\Query\Clause
	 */
	public function either($field, $value, $op = '=') {
		return $this->_process(self::EITHER, $field, $value, $op);
	}

	/**
	 * Return the clause parameters.
	 *
	 * @return array
	 */
	public function getParams() {
		return $this->_params;
	}

	/**
	 * Return the type of clause.
	 *
	 * @return string
	 */
	public function getType() {
		return $this->_type ?: self::ALSO;
	}

	/**
	 * Generate a new sub-grouped clause.
	 *
	 * @param \Closure $callback
	 * @return \Titon\Model\Query\Clause
	 */
	public function group(Closure $callback) {
		$clause = new Clause();

		$callback = $callback->bindTo($clause, 'Titon\Model\Query\Clause');
		$callback();

		$this->_params[] = $clause;

		return $this;
	}

	/**
	 * Process a parameter before adding it to the list.
	 * Set the primary clause type if it has not been set.
	 *
	 * @param string $type
	 * @param string $field
	 * @param mixed $value
	 * @param string $op
	 * @return \Titon\Model\Query\Clause
	 * @throws \Titon\Model\Exception
	 */
	protected function _process($type, $field, $value = null, $op = '=') {
		if (!$this->_type) {
			$this->_type = $type;
		}

		$op = strtoupper($op);

		if (is_array($value)) {
			if ($op === '=') {
				$op = self::IN;
			} else if ($op === '!=' || $op === '<>') {
				$op = self::NOT_IN;
			}

		} else if ($value === null) {
			if ($op === '=') {
				$op = self::NULL;
			} else if ($op === '!=' || $op === '<>') {
				$op = self::NOT_NULL;
			}
		}

		if (($op === self::BETWEEN || $op === self::NOT_BETWEEN) && (!is_array($value) || count($value) !== 2)) {
			throw new Exception(sprintf('%s clause must have an array of 2 values', $op));
		}

		$castValue = is_array($value) ? implode('', $value) : $value;
		$key = $field . $op . $castValue;

		$this->_params[$key] = [
			'field' => $field,
			'value' => $value,
			'op' => $op
		];

		return $this;
	}

	/**
	 * Serialize the query clause.
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize($this->jsonSerialize());
	}

	/**
	 * Reconstruct the query clause once unserialized.
	 *
	 * @param array $data
	 */
	public function unserialize($data) {
		$data = unserialize($data);

		foreach ($data['params'] as $param) {
			$this->_process($data['type'], $param['field'], $param['value'], $param['op']);
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