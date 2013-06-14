<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query;

/**
 * Interface for logged queries.
 *
 * @package Titon\Model\Query
 */
interface Log {

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

}