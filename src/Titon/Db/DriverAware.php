<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Db\Driver;

/**
 * Permits a class to interact with a driver.
 *
 * @package Titon\Db
 */
trait DriverAware {

    /**
     * Driver object instance.
     *
     * @type \Titon\Db\Driver
     */
    protected $_driver;

    /**
     * Return the driver.
     *
     * @return \Titon\Db\Driver
     */
    public function getDriver() {
        return $this->_driver;
    }

    /**
     * Set the driver.
     *
     * @param \Titon\Db\Driver $driver
     * @return $this
     */
    public function setDriver(Driver $driver) {
        $this->_driver = $driver;

        return $this;
    }

}