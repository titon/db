<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Common\Traits\FactoryAware;
use Titon\Db\Exception\MissingDriverException;
use Titon\Db\Driver;

/**
 * Manages drivers and login credentials.
 *
 * @package Titon\Db
 */
class Database {
    use FactoryAware;

    /**
     * Driver mappings.
     *
     * @type \Titon\Db\Driver[]
     */
    protected $_drivers = [];

    /**
     * Add a driver that houses login credentials.
     *
     * @param string $key
     * @param \Titon\Db\Driver $driver
     * @return \Titon\Db\Driver
     */
    public function addDriver($key, Driver $driver) {
        $this->_drivers[$key] = $driver;

        return $driver;
    }

    /**
     * Return a driver by key.
     *
     * @param string $key
     * @return \Titon\Db\Driver
     * @throws \Titon\Db\Exception\MissingDriverException
     */
    public function getDriver($key) {
        if (isset($this->_drivers[$key])) {
            return $this->_drivers[$key];
        }

        throw new MissingDriverException(sprintf('Driver %s does not exist', $key));
    }

    /**
     * Returns the list of drivers.
     *
     * @return \Titon\Db\Driver[]
     */
    public function getDrivers() {
        return $this->_drivers;
    }

}