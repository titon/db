<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Common\Traits\Mutable;
use \Serializable;
use \JsonSerializable;
use \Iterator;
use \ArrayAccess;
use \Countable;

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
class Entity implements Serializable, JsonSerializable, Iterator, ArrayAccess, Countable {
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
        return $this->_data;
    }

}