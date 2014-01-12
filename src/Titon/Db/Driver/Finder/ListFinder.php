<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Finder;

use Titon\Db\Entity;
use Titon\Utility\Hash;

/**
 * Returns a list of records indexed by a certain field, with the value of another field.
 *
 * @package Titon\Db\Driver\Finder
 */
class ListFinder extends AbstractFinder {

    /**
     * {@inheritdoc}
     */
    public function after(array $results, array $options = []) {
        $key = $options['key'];
        $value = $options['value'];
        $list = [];

        foreach ($results as $result) {
            if ($result instanceof Entity) {
                $result = $result->toArray();
            }

            $list[Hash::extract($result, $key)] = Hash::extract($result, $value);
        }

        return $list;
    }

}