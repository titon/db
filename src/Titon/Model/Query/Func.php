<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query;

use Titon\Model\Driver;
use Titon\Model\Traits\DriverAware;

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
	use DriverAware;

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
		$this->_arguments = (array) $arguments;
	}

	/**
	 * Magic method for toString().
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}

	/**
	 * Add an argument to the list.
	 *
	 * @param string $arg
	 * @param mixed $type
	 * @return \Titon\Model\Query\Func
	 */
	public function addArgument($arg, $type = null) {
		if ($type) {
			$this->_arguments[$arg] = $type;
		} else {
			$this->_arguments[] = $arg;
		}

		return $this;
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
		$func = new Func($name, $arguments, $separator);
		$func->setDriver($this->getDriver());

		return $func;
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
	 * Depending on the argument type, quote or escape the value.
	 *
	 * @return array
	 */
	public function getArguments() {
		$arguments = [];

		foreach ($this->_arguments as $arg => $type) {
			if (is_numeric($arg)) {
				$arg = $type;
				$type = null;
			}

			if ($type === self::FIELD) {
				$arg = $this->getDriver()->getDialect()->quote($arg);

			} else if ($type === self::LITERAL) {
				// Do nothing

			} else {
				$arg = $this->getDriver()->escape($type);
			}

			$arguments[] = $arg;
		}

		return $arguments;
	}

	/**
	 * Return the argument separator.
	 *
	 * @return string
	 */
	public function getSeparator() {
		return $this->_separator;
	}

	/**
	 * Return the function and arguments formatted in the correct SQL structure.
	 *
	 * @return string
	 */
	public function toString() {
		return sprintf($this->getDriver()->getDialect()->getClause('function'),
			$this->getName(),
			implode($this->getSeparator(), $this->getArguments())
		);
	}

}