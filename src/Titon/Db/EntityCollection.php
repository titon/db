<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Type\Collection;
use Titon\Type\Contract\Arrayable;

/**
 * Houses a collection of Entity objects.
 *
 * @package Titon\Db
 */
class EntityCollection extends Collection {

    /**
     * Recursively cast items to arrays.
     *
     * @return array
     */
    public function toArray() {
        return array_map(function($item) {
            return ($item instanceof Arrayable) ? $item->toArray() : $item;
        }, $this->_value);
    }

    public function unserialize($value) {
        $this->__construct(unserialize($value));
    }

}