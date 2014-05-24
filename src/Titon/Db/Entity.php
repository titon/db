<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Common\Traits\Mutable;
use Titon\Type\Contract\Arrayable;
use Titon\Type\Contract\Jsonable;
use \Serializable;
use \JsonSerializable;
use \IteratorAggregate;
use \ArrayAccess;
use \Countable;
use \Closure;

/**
 * Represents a single entity of data, usually a record from a database.
 *
 *      - Data mapping functionality during creation
 *      - Getter and setter support
 *
 * @link http://en.wikipedia.org/wiki/Entity_class
 *
 * @package Titon\Db
 */
class Entity implements Serializable, JsonSerializable, IteratorAggregate, ArrayAccess, Countable, Arrayable, Jsonable {
    use Mutable;

    /**
     * Initialize the entity and map the data.
     *
     * @param array $data
     */
    public function __construct(array $data = []) {
        $this->mapData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null) {
        $value = $this->has($key) ? $this->_data[$key] : $default;

        // Execute closure to trigger any lazy-loaded values
        if ($value instanceof Closure) {
            $value = call_user_func($value);
            $this->set($key, $value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key) {
        return isset($this->_data[$key]);
    }

    /**
     * Map data by storing the raw value and setting public properties.
     *
     * @param array $data
     * @return $this
     */
    public function mapData(array $data) {
        $this->_data = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key) {
        unset($this->_data[$key]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value = null) {
        $this->_data[$key] = $value;

        return $this;
    }

    /**
     * Serialize the configuration.
     *
     * @return string
     */
    public function serialize() {
        return serialize($this->_data);
    }

    /**
     * Reconstruct the data once unserialized.
     *
     * @param string $data
     */
    public function unserialize($data) {
        $this->mapData(unserialize($data));
    }

    /**
     * Return the values for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize() {
        return $this->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function toArray() {
        $data = [];

        foreach ($this->_data as $key => $value) {
            if ($value instanceof Closure) {
                $value = call_user_func($value);
                $this->set($key, $value);
            }

            if ($value instanceof Arrayable) {
                $data[$key] = $value->toArray();

            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function toJson($options = 0) {
        return json_encode($this, $options);
    }

}