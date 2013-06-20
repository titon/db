<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query;

/**
 * Handles the result set and profiling of a query.
 *
 * @package Titon\Model\Query
 */
interface Result {

	/**
	 * Return a count for the number of results. This assumes COUNT() was used.
	 *
	 * @return int
	 */
	public function count();

	/**
	 * Return the first row in the results.
	 *
	 * @return array
	 */
	public function fetch();

	/**
	 * Return all rows in the results.
	 *
	 * @return array
	 */
	public function fetchAll();

	/**
	 * Return the time in milliseconds it took to execute the query.
	 *
	 * @return int
	 */
	public function getExecutionTime();

	/**
	 * Return a list of params that were bound to the query.
	 *
	 * @return array
	 */
	public function getParams();

	/**
	 * Return a count of how many affected rows.
	 *
	 * @return int
	 */
	public function getRowCount();

	/**
	 * Return the final SQL statement.
	 *
	 * @return string
	 */
	public function getStatement();

	/**
	 * Has the query been executed.
	 *
	 * @return bool
	 */
	public function hasExecuted();

	/**
	 * Was the query execution successful?
	 *
	 * @return bool
	 */
	public function isSuccessful();

	/**
	 * Return the affected row count or a boolean if successful.
	 *
	 * @return int
	 */
	public function save();

}