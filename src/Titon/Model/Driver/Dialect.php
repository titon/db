<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver;

use Titon\Model\Driver;

/**
 * A dialect parses and builds SQL statements specific to a driver.
 *
 * @link http://en.wikipedia.org/wiki/SQL
 *
 * @package Titon\Model\Driver
 */
interface Dialect {

	/**
	 * Return a clause by key.
	 *
	 * @param string $key
	 * @return string
	 */
	public function getClause($key);

	/**
	 * Return all clauses.
	 *
	 * @return string[]
	 */
	public function getClauses();

	/**
	 * Return the driver.
	 *
	 * @return \Titon\Model\Driver
	 */
	public function getDriver();

	/**
	 * Return a statement by key.
	 *
	 * @param string $key
	 * @return string
	 */
	public function getStatement($key);

	/**
	 * Return all statements.
	 *
	 * @return string[]
	 */
	public function getStatements();

	/**
	 * Quote an SQL identifier by wrapping with a driver specific character.
	 *
	 * @param string $value
	 * @return string
	 */
	public function quote($value);

	/**
	 * Render the statement by piecing together the parameters.
	 *
	 * @param string $statement
	 * @param array $params
	 * @return string
	 */
	public function renderStatement($statement, array $params);

	/**
	 * Set the driver that this dialect belongs to.
	 *
	 * @param \Titon\Model\Driver $driver
	 * @return \Titon\Model\Driver\Dialect
	 */
	public function setDriver(Driver $driver);

}