<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model\Driver\Type;

use Titon\Common\Registry;
use Titon\Model\Driver;
use Titon\Model\Driver\Type;
use Titon\Model\Exception\UnsupportedTypeException;
use Titon\Model\Traits\DriverAware;
use \PDO;

/**
 * Provides default shared functionality for types.
 *
 * @package Titon\Model\Driver\Type
 */
abstract class AbstractType implements Type {
    use DriverAware;

    /**
     * Store the driver.
     *
     * @param \Titon\Model\Driver $driver
     */
    public function __construct(Driver $driver) {
        $this->setDriver($driver);
    }

    /**
     * {@inheritdoc}
     *
     * @uses \Titon\Common\Registry
     *
     * @throws \Titon\Model\Exception\UnsupportedTypeException
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