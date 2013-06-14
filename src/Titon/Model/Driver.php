<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Cache\Storage;
use Titon\Model\Driver\Dialect;
use Titon\Model\Query;
use Titon\Model\Query\Log;

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
	 * Return the connection object.
	 *
	 * @return object
	 */
	public function getConnection();

	/**
	 * Return the dialect.
	 *
	 * @return \Titon\Model\Driver\Dialect
	 */
	public function getDialect();

	/**
	 * Return the encoding for the driver.
	 *
	 * @return string
	 */
	public function getEncoding();

	/**
	 * Return the unique identifier.
	 *
	 * @return string
	 */
	public function getKey();

	/**
	 * Return the ID of the last inserted record.
	 *
	 * @return int
	 */
	public function getLastInsertID();

	/**
	 * Return a list of logged query statements.
	 *
	 * @return array
	 */
	public function getLoggedQueries();

	/**
	 * Return the storage engine.
	 *
	 * @return \Titon\Cache\Storage
	 */
	public function getStorage();

	/**
	 * Return true if connected to the driver.
	 *
	 * @return bool
	 */
	public function isConnected();

	/**
	 * Return true if the driver is ready for use.
	 *
	 * @return bool
	 */
	public function isEnabled();

	/**
	 * Return true if the connection is persistent.
	 *
	 * @return bool
	 */
	public function isPersistent();

	/**
	 * Logs a query statement.
	 *
	 * @param \Titon\Model\Query\Log $log
	 * @return \Titon\Model\Driver
	 */
	public function logQuery(Log $log);

	/**
	 * Query the driver for data records.
	 *
	 * @param \Titon\Model\Query|string $query
	 * @return \Titon\Model\Query\Result
	 */
	public function query($query);

	/**
	 * Reset the driver for the next query.
	 */
	public function reset();

	/**
	 * Set the driver specific dialect.
	 *
	 * @param \Titon\Model\Driver\Dialect $dialect
	 * @return \Titon\Model\Driver
	 */
	public function setDialect(Dialect $dialect);

	/**
	 * Set the storage engine for query caching.
	 *
	 * @param \Titon\Cache\Storage $storage
	 * @return \Titon\Model\Driver
	 */
	public function setStorage(Storage $storage);

}