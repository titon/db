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
        return $this->createCollection($results, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function noResults(array $options = []) {
        return $this->createCollection([], $options);
    }

    /**
     * Create a collection object and fill it with the results.
     * The collection class can be customized through the options.
     *
     * @param array $results
     * @param array $options
     * @return \Titon\Db\EntityCollection
     */
    public function createCollection(array $results, array $options) {
        $collection = isset($options['collection']) ? $options['collection'] : 'Titon\Db\EntityCollection';

        return new $collection($results);
    }

}