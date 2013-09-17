<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model;

use Psr\Log\LoggerInterface;
use Titon\Cache\Storage;
use Titon\Model\Driver\Dialect;
use Titon\Model\Query;
use Titon\Model\Query\Result;

/**
 * Represents a data source, whether a database, API, or other storage system.
 *
 * @package Titon\Model
 */
interface Driver {

    /**
     * Connect to the driver.
     *
     * @return bool
     */
    public function connect();

    /**
     * Commit the buffered queries within the transaction.
     *
     * @return bool
     */
    public function commitTransaction();

    /**
     * Inspect a table and describe all the columns within it.
     * Return an array of values that are usable in a schema.
     *
     * @param string $table
     * @return array
     */
    public function describeTable($table);

    /**
     * Disconnect from the driver.
     *
     * @return bool
     */
    public function disconnect();

    /**
     * Escape a value to be SQL valid.
     *
     * @param mixed $value
     * @return mixed
     */
    public function escape($value);

    /**
     * Return the connection object.
     *
     * @return object
     */
    public function getConnection();

    /**
     * Return the dialect.
     *
     * @return \Titon\Model\Driver\Dialect
     */
    public function getDialect();

    /**
     * Return the encoding for the driver.
     *
     * @return string
     */
    public function getEncoding();

    /**
     * Return the unique identifier.
     *
     * @return string
     */
    public function getKey();

    /**
     * Return the ID of the last inserted record.
     *
     * @param \Titon\Model\Model
     * @return int
     */
    public function getLastInsertID(Model $model);

    /**
     * Return a list of logged query statements.
     *
     * @return array
     */
    public function getLoggedQueries();

    /**
     * Return the logger.
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger();

    /**
     * Return the storage engine.
     *
     * @return \Titon\Cache\Storage
     */
    public function getStorage();

    /**
     * Return an array of supported types and the class name that represents it.
     *
     * @return array
     */
    public function getSupportedTypes();

    /**
     * Return true if connected to the driver.
     *
     * @return bool
     */
    public function isConnected();

    /**
     * Return true if the driver is ready for use.
     *
     * @return bool
     */
    public function isEnabled();

    /**
     * Return true if the connection is persistent.
     *
     * @return bool
     */
    public function isPersistent();

    /**
     * List out all the tables within a database.
     *
     * @param string $database
     * @return array
     */
    public function listTables($database = null);

    /**
     * Logs a query result.
     *
     * @param \Titon\Model\Query\Result $result
     * @return \Titon\Model\Driver
     */
    public function logQuery(Result $result);

    /**
     * Query the driver for data records.
     *
     * @param \Titon\Model\Query|string $query
     * @param array $params
     * @return \Titon\Model\Query\Result
     */
    public function query($query, array $params = []);

    /**
     * Reset the driver for the next query.
     *
     * @return \Titon\Model\Driver
     */
    public function reset();

    /**
     * Rollback the last transaction that failed.
     *
     * @return bool
     */
    public function rollbackTransaction();

    /**
     * Set the driver specific dialect.
     *
     * @param \Titon\Model\Driver\Dialect $dialect
     * @return \Titon\Model\Driver
     */
    public function setDialect(Dialect $dialect);

    /**
     * Set the logger for query logging.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @return \Titon\Model\Driver
     */
    public function setLogger(LoggerInterface $logger);

    /**
     * Set the storage engine for query caching.
     *
     * @param \Titon\Cache\Storage $storage
     * @return \Titon\Model\Driver
     */
    public function setStorage(Storage $storage);

    /**
     * Start the query transaction process.
     *
     * @return bool
     */
    public function startTransaction();

}