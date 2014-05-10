<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Finder;

use Titon\Db\Entity;
use Titon\Db\Exception\InvalidArgumentException;
use Titon\Utility\Hash;

/**
 * Returns a list of records indexed by a certain field, with the value of another field.
 *
 * @package Titon\Db\Finder
 */
class ListFinder extends AbstractFinder {

    /**
     * {@inheritdoc}
     */
    public function after(array $results, array $options = []) {
        $key = isset($options['key']) ? $options['key'] : null;
        $value = isset($options['value']) ? $options['value'] : null;
        $list = [];

        if (!$key || !$value) {
            throw new InvalidArgumentException('Missing key or value option for ListFinder');
        }

        foreach ($results as $result) {
            if ($result instanceof Entity) {
                $result = $result->toArray();
            }

            $list[Hash::extract($result, $key)] = Hash::extract($result, $value);
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function noResults() {
        return [];
    }

}