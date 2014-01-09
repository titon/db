<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

use Titon\Common\Registry;
use Titon\Db\Driver;
use Titon\Db\Driver\Type;
use Titon\Db\Exception\UnsupportedTypeException;
use Titon\Db\Traits\DriverAware;
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
     *
     * @uses \Titon\Common\Registry
     *
     * @throws \Titon\Db\Exception\UnsupportedTypeException
     */
    public static function factory($type, Driver $driver) {
        $types = $driver->getSupportedTypes();

        if (isset($types[$type])) {
            $class = $types[$type];

            if (Registry::has($class)) {
                return Registry::get($class);
            }

            $object = new $class($driver);

            return Registry::set($object, $class);
        }

        throw new UnsupportedTypeException(sprintf('Unsupported data type %s', $type));
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