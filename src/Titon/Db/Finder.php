<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

/**
 * A finder represents a specific type of query fetch, which allows alteration before or after.
 *
 * @package Titon\Db
 */
interface Finder {

    /**
     * Process results after a query has executed.
     *
     * @param \Titon\Db\Entity[] $results
     * @param array $options
     * @return mixed
     */
    public function after(array $results, array $options = []);

    /**
     * Modify a query before it's executed.
     *
     * @param \Titon\Db\Query $query
     * @param array $options
     * @return mixed
     */
    public function before(Query $query, array $options = []);

    /**
     * Return an empty value when no results are found.
     *
     * @return mixed
     */
    public function noResults();

}