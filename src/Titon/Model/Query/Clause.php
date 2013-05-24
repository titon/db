<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query;

use \Closure;

/**
 * Functionality for handling advanced query clauses: where, having
 */
class Clause {

	// Types
	const ALSO = 'AND';
	const EITHER = 'OR';

	// Operators
	const EQ = '=';
	const NEQ = '!=';
	const GT = '>';
	const GTE = '>=';
	const LT = '<';
	const LTE = '<=';
	const IN = 'IN';
	const NIN = 'NOT IN';
	const NOT = 'NOT';
	const NULL = 'IS NULL';
	const BETWEEN = 'BETWEEN';
	const LIKE = 'LIKE';
	const NLIKE = 'NOT LIKE';

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
	public function also($field, $value = null, $op = self::EQ) {
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
	public function either($field, $value = null, $op = self::EQ) {
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
		return $this->_type;
	}

	/**
	 * Process a parameter before adding it to the list.
	 * If the field is a closure, generate a new sub-clause.
	 *
	 * @param string $type
	 * @param string $field
	 * @param mixed $value
	 * @param string $op
	 * @return \Titon\Model\Query\Clause
	 */
	protected function _process($type, $field, $value = null, $op = self::EQ) {
		if (!$this->_type) {
			$this->_type = $type;
		}

		// Build a new sub-clause if a Closure is passed
		if ($field instanceof Closure) {
			$clause = new Clause();

			$field = $field->bindTo($clause, 'Titon\Model\Query\Clause');
			$field();

			$this->_params[] = $clause;

		// Else append more parameters
		} else {
			$this->_params[] = [
				'field' => $field,
				'value' => $value,
				'op' => $op
			];
		}

		return $this;
	}

}