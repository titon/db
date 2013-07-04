<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Traits;

use Titon\Model\Driver;

/**
 * Permits a class to interact with a driver.
 *
 * @package Titon\Model\Traits
 */
trait DriverAware {

	/**
	 * Driver object instance.
	 *
	 * @type \Titon\Model\Driver
	 */
	protected $_driver;

	/**
	 * Return the driver.
	 *
	 * @return \Titon\Model\Driver
	 */
	public function getDriver() {
		return $this->_driver;
	}

	/**
	 * Set the driver.
	 *
	 * @param \Titon\Model\Driver $driver
	 * @return \Titon\Model\Traits\DriverAware
	 */
	public function setDriver(Driver $driver) {
		$this->_driver = $driver;

		return $this;
	}

}