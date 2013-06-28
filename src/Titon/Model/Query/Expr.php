<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query;

use Titon\Model\Traits\ExprAware;

/**
 * The Expr class represents a mathematical or logical expression that can be calculated depending on context.
 *
 * @package Titon\Model\Query
 */
class Expr {
	use ExprAware;

	const NULL = 'isNull';
	const NOT_NULL = 'isNotNull';
	const IN = 'in';
	const NOT_IN = 'notIn';
	const BETWEEN = 'between';
	const NOT_BETWEEN = 'notBetween';
	const LIKE = 'like';
	const NOT_LIKE = 'notLike';
	const REGEXP = 'regexp';
	const NOT_REGEXP = 'notRegexp';
	const RLIKE = 'rlike';

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
	 * Data value.
	 *
	 * @type mixed
	 */
	protected $_value;

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
	 * Return the value.
	 *
	 * @return mixed
	 */
	public function getValue() {
		return $this->_value;
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