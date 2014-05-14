<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Psr\Log\LoggerInterface;
use Titon\Cache\Storage;
use Titon\Db\Driver\Dialect;
use Titon\Db\Finder;
use Titon\Db\Query;
use Titon\Db\Query\ResultSet;
use \Closure;

/**
 * Represents a data source, whether a database, API, or other storage system.
 *
 * @package Titon\Db
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
     * @param string $repo
     * @return array
     */
    public function describeTable($repo);

    /**
     * Disconnect from the driver.
     *
     * @param bool $flush
     * @return bool
     */
    public function disconnect($flush = false);

    /**
     * Escape a value to be SQL valid.
     *
     * @param mixed $value
     * @return mixed
     */
    public function escape($value);

    /**
     * Query the driver for database records.
     *
     * @param \Titon\Db\Query|string $query
     * @param array $params
     * @return \Titon\Db\Query\ResultSet
     */
    public function executeQuery($query, array $params = []);

    /**
     * Return the current connection object.
     *
     * @return object
     */
    public function getConnection();

    /**
     * Return all the active connections indexed by context.
     *
     * @return object[]
     */
    public function getConnections();

    /**
     * The current read, write, or custom connection config.
     *
     * @return string
     */
    public function getContext();

    /**
     * Return connection configuration for a context.
     *
     * @param string $key
     * @return array
     */
    public function getContextConfig($key);

    /**
     * Return the dialect.
     *
     * @return \Titon\Db\Driver\Dialect
     */
    public function getDialect();

    /**
     * Return the ID of the last inserted record.
     *
     * @param \Titon\Db\Repository
     * @return int
     */
    public function getLastInsertID(Repository $repo);

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
     * @param \Titon\Db\Query\ResultSet $result
     * @return $this
     */
    public function logQuery(ResultSet $result);

    /**
     * Create a new query object.
     *
     * @param string $type
     * @return \Titon\Db\Query
     */
    public function newQuery($type);

    /**
     * Reset the driver for the next query.
     *
     * @return $this
     */
    public function reset();

    /**
     * Rollback the last transaction that failed.
     *
     * @return bool
     */
    public function rollbackTransaction();

    /**
     * Set the connection context.
     *
     * @param string $group
     * @return $this
     */
    public function setContext($group);

    /**
     * Set the driver specific dialect.
     *
     * @param \Titon\Db\Driver\Dialect $dialect
     * @return $this
     */
    public function setDialect(Dialect $dialect);

    /**
     * Set the logger for query logging.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger);

    /**
     * Set the storage engine for query caching.
     *
     * @param \Titon\Cache\Storage $storage
     * @return $this
     */
    public function setStorage(Storage $storage);

    /**
     * Start the query transaction process.
     *
     * @return bool
     */
    public function startTransaction();

    /**
     * Perform a transaction by wrapping all the relevant queries in a closure.
     * Will automatically start, commit and rollback a transaction.
     *
     * @param \Closure $bulk
     * @return bool
     */
    public function transaction(Closure $bulk);

}