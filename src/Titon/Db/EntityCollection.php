<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Type\Collection;
use Titon\Type\Contract\Arrayable;
use \Closure;

/**
 * Houses a collection of Entity objects.
 *
 * @package Titon\Db
 * @method \Titon\Db\Entity[] value()
 */
class EntityCollection extends Collection {

    /**
     * Attempt to find an entity whose field matches a specific value.
     *
     * @param mixed $value
     * @param string $key
     * @return \Titon\Db\Entity
     */
    public function find($value, $key = 'id') {
        foreach ($this->value() as $entity) {
            if ($entity->get($key) == $value) {
                return $entity;
            }
        }

        return null;
    }

    /**
     * Pluck the value of a specific field from each entity.
     *
     * @param string $key
     * @return array
     */
    public function pluck($key = 'id') {
        return array_map(function($entity) use ($key) {
            return $entity->get($key);
        }, $this->value());
    }

    /**
     * Sort an array of entities by a field within each entity.
     *
     * @param string|\Closure$key
     * @param int $flags
     * @param bool $reverse
     * @return $this
     */
    public function sortBy($key, $flags = SORT_REGULAR, $reverse = false) {
        if ($key instanceof Closure) {
            $callback = $key;
        } else {
            $callback = function($a, $b) use ($key) {
                return strcmp($a->get($key), $b->get($key));
            };
        }

        return $this->sort([
            'reverse' => $reverse,
            'preserve' => false,
            'flags' => $flags,
            'callback' => $callback
        ]);
    }

    /**
     * Recursively cast items to arrays.
     *
     * @return array
     */
    public function toArray() {
        return array_map(function($item) {
            return ($item instanceof Arrayable) ? $item->toArray() : (array) $item;
        }, $this->value());
    }

}