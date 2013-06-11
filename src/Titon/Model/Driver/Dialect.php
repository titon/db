<?php

namespace Titon\Model\Driver;


use Titon\Model\Driver;

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
	 * Quote an array of identifiers.
	 *
	 * @param array $values
	 * @return string
	 */
	public function quoteList(array $values);

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