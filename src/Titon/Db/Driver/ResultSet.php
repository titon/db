<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver;

/**
 * Handles the result set and profiling of a query.
 *
 * @package Titon\Db\Driver
 */
interface ResultSet {

    /**
     * Close any open connection.
     *
     * @return bool
     */
    public function close();

    /**
     * Return a count for the number of results. This assumes COUNT() was used.
     *
     * @return int
     */
    public function count();

    /**
     * Execute the query and log the affected rows and execution time.
     *
     * @return $this
     */
    public function execute();

    /**
     * Return rows from the database query.
     *
     * @return array
     */
    public function find();

    /**
     * Return the time in milliseconds it took to execute the query.
     *
     * @return int
     */
    public function getExecutionTime();

    /**
     * Return a list of params that were bound to the query.
     *
     * @return array
     */
    public function getParams();

    /**
     * Return the query object for this result.
     *
     * @return \Titon\Db\Query
     */
    public function getQuery();

    /**
     * Return a count of how many affected rows.
     *
     * @return int
     */
    public function getRowCount();

    /**
     * Return the final SQL statement.
     *
     * @return string
     */
    public function getStatement();

    /**
     * Has the query been executed.
     *
     * @return bool
     */
    public function hasExecuted();

    /**
     * Was the query execution successful?
     *
     * @return bool
     */
    public function isSuccessful();

    /**
     * Return the affected row count or a boolean if not successful.
     *
     * @return int
     */
    public function save();

}