<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Finder;

/**
 * Returns all records wrapped in a collection.
 *
 * @package Titon\Db\Finder
 */
class AllFinder extends AbstractFinder {

    /**
     * {@inheritdoc}
     */
    public function after(array $results, array $options = []) {
        $collection = $options['collection'];

        return new $collection($results);
    }

    /**
     * {@inheritdoc}
     */
    public function noResults(array $options = []) {
        $collection = $options['collection'];

        return new $collection();
    }

}