<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Serializable;
use JsonSerializable;
use Iterator;
use ArrayAccess;
use Countable;

/**
 * Represents a single entity of data, usually a record from a database.
 *
 * 	- Data mapping functionality during creation
 * 	- Getter and setter support
 *
 * @link http://en.wikipedia.org/wiki/Entity_class
 *
 * @package Titon\Model
 */
class Entity implements Serializable, JsonSerializable, Iterator, ArrayAccess, Countable {

	/**
	 * Raw data set.
	 *
	 * @type array
	 */
	protected $_data = [];

	/**
	 * Initialize the entity and map the data.
	 *
	 * @param array $data
	 */
	public function __construct(array $data = []) {
		$this->mapData($data);
	}

	/**
	 * Return a parameter by key.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		return $this->has($key) ? $this->_data[$key] : null;
	}

	/**
	 * Check if a parameter exists.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has($key) {
		return isset($this->_data[$key]);
	}

	/**
	 * Return all the parameter keys.
	 *
	 * @return array
	 */
	public function keys() {
		return array_keys($this->_data);
	}

	/**
	 * Remove a parameter by key.
	 *
	 * @param string $key
	 */
	public function remove($key) {
		unset($this->_data[$key]);
	}

	/**
	 * Set a parameter value by key.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set($key, $value) {
		$this->_data[$key] = $value;
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
	 * @param array $data
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

	/**
	 * Magic method for get().
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key) {
		return $this->get($key);
	}

	/**
	 * Magic method for set().
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value) {
		$this->set($key, $value);
	}

	/**
	 * Magic method for has().
	 *
	 * @param string $key
	 * @return bool
	 */
	public function __isset($key) {
		return $this->has($key);
	}

	/**
	 * Magic method for remove().
	 *
	 * @param string $key
	 */
	public function __unset($key) {
		$this->remove($key);
	}

	/**
	 * Alias method for get().
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function offsetGet($key) {
		return $this->get($key);
	}

	/**
	 * Alias method for set().
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function offsetSet($key, $value) {
		$this->set($key, $value);
	}

	/**
	 * Alias method for has().
	 *
	 * @param string $key
	 * @return bool
	 */
	public function offsetExists($key) {
		return $this->has($key);
	}

	/**
	 * Alias method for remove().
	 *
	 * @param string $key
	 */
	public function offsetUnset($key) {
		$this->remove($key);
	}

	/**
	 * Reset the loop.
	 *
	 */
	public function rewind() {
		reset($this->_data);
	}

	/**
	 * Return the current value in the loop.
	 *
	 * @return mixed
	 */
	public function current() {
		return current($this->_data);
	}

	/**
	 * Reset the current key in the loop.
	 *
	 * @return mixed
	 */
	public function key() {
		return key($this->_data);
	}

	/**
	 * Go to the next index.
	 *
	 * @return mixed
	 */
	public function next() {
		return next($this->_data);
	}

	/**
	 * Check if the current index is valid.
	 *
	 * @return bool
	 */
	public function valid() {
		return ($this->current() !== false);
	}

	/**
	 * Return the count of the array.
	 *
	 * @return int
	 */
	public function count() {
		return count($this->_data);
	}

}