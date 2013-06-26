<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query;

use Titon\Model\Driver;

/**
 * The Func class represents an SQL function with optional arguments.
 * Provides support for argument type casting, literal arguments, and database column arguments.
 *
 * Using scalar arguments will be type casted and will produce `SUBSTRING('Titon', -2, 2)`.
 *
 *		new Func('SUBSTRING', ['Titon', -2, 2]);
 *
 * Using literal arguments will produce `DATE_ADD('1988-02-26', INTERVAL 31 DAY)`.
 *
 * 		new Func('DATE_ADD', [
 * 			'1988-02-26',
 * 			'INTERVAL 31 DAY' => Func::LITERAL
 * 		]);
 *
 * Using database columns/fields will quote and produce `COUNT(`id`)`.
 *
 * 		new Func('COUNT', ['id' => Func::FIELD]);
 *
 * Using no arguments will simply return the function name and an empty argument group.
 *
 * @package Titon\Model\Query
 */
class Func {

	const FIELD = 'field';
	const LITERAL = 'literal';

	/**
	 * Name of the function.
	 *
	 * @type string
	 */
	protected $_name;

	/**
	 * List of function arguments.
	 *
	 * @type array
	 */
	protected $_arguments = [];

	/**
	 * Separator between arguments.
	 *
	 * @type string
	 */
	protected $_separator;

	/**
	 * Store the function name, list of arguments, and argument separator.
	 *
	 * @param string $name
	 * @param string|array $arguments
	 * @param string $separator
	 */
	public function __construct($name, $arguments = [], $separator = ', ') {
		$this->_name = strtoupper($name);
		$this->_separator = $separator;

		if (!is_array($arguments)) {
			$arguments = [$arguments];
		}

		foreach ($arguments as $arg => $type) {
			if (is_numeric($arg)) {
				$arg = $type;
				$type = null;
			}

			$this->_arguments[] = [
				'type' => $type,
				'value' => $arg
			];
		}
	}

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

	/**
	 * Return the function name.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->_name;
	}

	/**
	 * Return the list of arguments.
	 *
	 * @return array
	 */
	public function getArguments() {
		return $this->_arguments;
	}

	/**
	 * Return the argument separator.
	 *
	 * @return string
	 */
	public function getSeparator() {
		return $this->_separator;
	}

}