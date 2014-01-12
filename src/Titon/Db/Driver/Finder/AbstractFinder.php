<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Finder;

use Titon\Db\Driver\Finder;
use Titon\Db\Query;

/**
 * Implement basic finder functionality.
 *
 * @package Titon\Db\Driver\Finder
 */
abstract class AbstractFinder implements Finder {

    /**
     * {@inheritdoc}
     */
    public function before(Query $query, array $options = []) {
        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function after(array $results, array $options = []) {
        return $results;
    }

}