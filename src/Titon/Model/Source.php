<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

/**
 * Represents a data source, whether a database, API, or other storage system.
 */
interface Source {

	/**
	 * Connect to the data source, whether a database or remote API.
	 */
	public function connect();

	/**
	 * Disconnect from the data source.
	 */
	public function disconnect();

	/**
	 * Return the encoding for the data source.
	 *
	 * @return string
	 */
	public function getEncoding();

	/**
	 * Return a list of flags used to connect to the data source.
	 *
	 * @return array
	 */
	public function getFlags();

	/**
	 * Return the unique source identifier.
	 *
	 * @return string
	 */
	public function getKey();

	/**
	 * Return true if connected to the data source.
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
	 * Query the data source for data records.
	 *
	 * @return mixed
	 */
	public function query();

}