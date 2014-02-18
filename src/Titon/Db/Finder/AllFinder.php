<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Finder;

use Titon\Db\EntityCollection;

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
        return new EntityCollection($results);
    }

    /**
     * {@inheritdoc}
     */
    public function noResults() {
        return new EntityCollection();
    }

}