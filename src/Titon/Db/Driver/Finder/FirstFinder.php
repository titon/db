<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Finder;

/**
 * Returns the first record in the results.
 *
 * @package Titon\Db\Driver\Finder
 */
class FirstFinder extends AbstractFinder {

    /**
     * {@inheritdoc}
     */
    public function after(array $results, array $options = []) {
        return $results[0]; // Return the first result
    }

}