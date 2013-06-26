<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opendriver.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Traits;

use Titon\Model\Query\Func;

/**
 * Permits a class to instantiate new function.
 *
 * @package Titon\Model\Traits
 */
trait FuncAware {

	/**
	 * Instantiate a new database function.
	 *
	 * @param string $name
	 * @param string|array $arguments
	 * @param string $separator
	 * @return \Titon\Model\Query\Func
	 */
	public function func($name, $arguments = [], $separator = ', ') {
		return new Func($name, $arguments, $separator);
	}

}