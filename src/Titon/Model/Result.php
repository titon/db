<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

/**
 * Handles the results of a query.
 */
interface Result {

	/**
	 * Count the number of results. This assumes COUNT() was used.
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
	 * Return the affected row count.
	 *
	 * @return int
	 */
	public function save();

}