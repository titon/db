<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver;

use Titon\Db\Query;

/**
 * A finder represents a specific type of query fetch, which allows alteration before or after.
 *
 * @package Titon\Db\Driver
 */
interface Finder {

    /**
     * Modify a query before it's executed.
     *
     * @param \Titon\Db\Query $query
     * @param array $options
     * @return mixed
     */
    public function before(Query $query, array $options = []);

    /**
     * Process results after a query has executed.
     *
     * @param \Titon\Db\Entity[] $results
     * @param array $options
     * @return mixed
     */
    public function after(array $results, array $options = []);

}