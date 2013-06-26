<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query;

use Titon\Model\Driver;

/**
 * The Expr class represents a mathematical or logical expression that can be calculated depending on context.
 *
 * @package Titon\Model\Query
 */
class Expr {

	const NOT = 'not';
	const NULL = 'isNull';
	const NOT_NULL = 'isNotNull';
	const IN = 'in';
	const NOT_IN = 'notIn';
	const BETWEEN = 'between';
	const NOT_BETWEEN = 'notBetween';
	const LIKE = 'like';
	const NOT_LIKE = 'notLike';

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
	 * Instantiate a new database expression.
	 *
	 * @param string $field
	 * @param string $operator
	 * @param mixed $value
	 * @return \Titon\Model\Query\Expr
	 */
	public function expr($field, $operator = null, $value = null) {
		return new Expr($field, $operator, $value);
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
		return ($this->getOperator() && $this->getValue());
	}

}