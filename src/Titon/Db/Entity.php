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
     * Override the original get method to execute closure values.
     * Will allow for lazy-loaded properties to be fetched.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        $value = isset($this->_data[$key]) ? $this->_data[$key] : null;

        if ($value instanceof Closure) {
            $value = call_user_func($value);
            $this->set($key, $value);
        }

        return $value;
    }

    /**
     * Map data by storing the raw value and setting public properties.
     *
     * @param array $data
     */
    public function mapData(array $data) {
        $this->_data = $data;
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

        // Loop and trigger any closures
        foreach ($this->keys() as $key) {
            $data[$key] = $this->get($key);
        }

        return $this->_toArray($data);
    }

    /**
     * {@inheritdoc}
     */
    public function toJson($options = 0) {
        return json_encode($this, $options);
    }

    /**
     * Apply array casting recursively.
     *
     * @param mixed $data
     * @return array
     */
    protected function _toArray($data) {
        $array = [];

        foreach ($data as $key => $value) {
            if ($value instanceof Arrayable) {
                $array[$key] = $value->toArray();

            } else if (is_array($value)) {
                $array[$key] = $this->_toArray($value);

            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

}