<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Model\Query;

/**
 * Represents a data source, whether a database, API, or other storage system.
 */
interface Driver {

	/**
	 * Build an SQL statement based off the current query parameters.
	 * Binds any necessary values to the statement.
	 *
	 * @param \Titon\Model\Query $query
	 * @return mixed
	 */
	public function buildStatement(Query $query);

	/**
	 * Connect to the driver.
	 */
	public function connect();

	/**
	 * Disconnect from the driver.
	 */
	public function disconnect();

	/**
	 * Return the encoding for the driver.
	 *
	 * @return string
	 */
	public function getEncoding();

	/**
	 * Return a list of flags used to connect to the driver.
	 *
	 * @return array
	 */
	public function getFlags();

	/**
	 * Return the unique identifier.
	 *
	 * @return string
	 */
	public function getKey();

	/**
	 * Return true if connected to the driver.
	 *
	 * @return bool
	 */
	public function isConnected();

	/**
	 * Return true if the connection is persistent.
	 *
	 * @return bool
	 */
	public function isPersistent();

	/**
	 * Query the driver for data records.
	 *
	 * @param \Titon\Model\Query|string $query
	 * @return \Titon\Model\Result
	 */
	public function query($query);

	/**
	 * Reset the driver for the next query.
	 */
	public function reset();

}