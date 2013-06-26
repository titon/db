<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opendriver.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Traits;

use Titon\Model\Query\Expr;

/**
 * Permits a class to instantiate new expressions.
 *
 * @package Titon\Model\Traits
 */
trait ExprAware {

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

}