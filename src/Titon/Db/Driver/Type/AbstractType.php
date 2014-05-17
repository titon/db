<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

use Titon\Db\Driver;
use Titon\Db\Driver\Type;
use Titon\Db\DriverAware;
use \PDO;

/**
 * Provides default shared functionality for types.
 *
 * @package Titon\Db\Driver\Type
 */
abstract class AbstractType implements Type {
    use DriverAware;

    /**
     * Store the driver.
     *
     * @param \Titon\Db\Driver $driver
     */
    public function __construct(Driver $driver) {
        $this->setDriver($driver);
    }

    /**
     * {@inheritdoc}
     */
    public function from($value) {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getBindingType() {
        return PDO::PARAM_STR;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions() {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function to($value) {
        return $value;
    }

}