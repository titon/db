<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opendriver.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Model\Exception\MissingDriverException;
use Titon\Model\Driver;

/**
 * Manages drivers, connections and login credentials.
 *
 * @package Titon\Model
 */
class Connection {

	/**
	 * Driver mappings.
	 *
	 * @type \Titon\Model\Driver[]
	 */
	protected $_drivers = [];

	/**
	 * Add a driver that houses login credentials.
	 *
	 * @param \Titon\Model\Driver $driver
	 * @return \Titon\Model\Driver
	 */
	public function addDriver(Driver $driver) {
		$this->_drivers[$driver->getKey()] = $driver;

		return $driver;
	}

	/**
	 * Return a driver by key.
	 *
	 * @param string $key
	 * @return \Titon\Model\Driver
	 * @throws \Titon\Model\Exception\MissingDriverException
	 */
	public function getDriver($key) {
		if (isset($this->_drivers[$key])) {
			return $this->_drivers[$key];
		}

		throw new MissingDriverException(sprintf('Invalid driver %s', $key));
	}

	/**
	 * Returns the list of drivers.
	 *
	 * @return \Titon\Model\Driver[]
	 */
	public function getDrivers() {
		return $this->_drivers;
	}

}