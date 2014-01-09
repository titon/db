<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver;

use Psr\Log\LoggerInterface;
use Titon\Common\Base;
use Titon\Common\Traits\Cacheable;
use Titon\Cache\Storage;
use Titon\Db\Driver;
use Titon\Db\Query\Result;

/**
 * Implements basic driver functionality.
 *
 * @package Titon\Db\Driver
 */
abstract class AbstractDriver extends Base implements Driver {
    use Cacheable;

    /**
     * Configuration.
     *
     * @type array {
     *      @type string $database  The database name
     *      @type string $host      The hostname or IP to connect to
     *      @type int $port         The port to connect with
     *      @type string $user      Login user name
     *      @type string $pass      Login user password
     *      @type string $socket    Path to unix socket to connect with
     *      @type bool $persistent  Should we use persistent data connections
     *      @type string $encoding  Charset encoding for the driver
     *      @type string $timezone  Timezone for the driver
     *      @type array $flags      Flags used when connecting
     * }
     */
    protected $_config = [
        'database' => '',
        'host' => '127.0.0.1',
        'port' => 0,
        'user' => '',
        'pass' => '',
        'socket' => '',
        'persistent' => true,
        'encoding' => 'utf8',
        'timezone' => 'UTC',
        'flags' => []
    ];

    /**
     * Is the connection established.
     *
     * @type bool
     */
    protected $_connected = false;

    /**
     * PDO or API object instance.
     *
     * @type object
     */
    protected $_connection;

    /**
     * Dialect object instance.
     *
     * @type \Titon\Db\Driver\Dialect
     */
    protected $_dialect;

    /**
     * Logger object instance.
     *
     * @type \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Logged query statements and bound parameters.
     *
     * @type \Titon\Db\Query\Result[]
     */
    protected $_logs = [];

    /**
     * Unique identifier.
     *
     * @type string
     */
    protected $_key;

    /**
     * The last query result.
     *
     * @type \Titon\Db\Query\Result
     */
    protected $_result;

    /**
     * Storage engine instance.
     *
     * @type \Titon\Cache\Storage
     */
    protected $_storage;

    /**
     * Store the identifier key and configuration.
     *
     * @param string $key
     * @param array $config
     */
    public function __construct($key, array $config) {
        $this->_key = $key;

        parent::__construct($config);
    }

    /**
     * Disconnect when the object is destroyed.
     */
    public function __destruct() {
        $this->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect() {
        $this->reset();

        if ($this->isConnected()) {
            $this->_connection = null;
            $this->_connected = false;

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection() {
        return $this->_connection;
    }

    /**
     * Return the database name.
     *
     * @return string
     */
    public function getDatabase() {
        return $this->config->database;
    }

    /**
     * {@inheritdoc}
     */
    public function getDialect() {
        return $this->_dialect;
    }

    /**
     * {@inheritdoc}
     */
    public function getEncoding() {
        return $this->config->encoding;
    }

    /**
     * Return the database host.
     *
     * @return string
     */
    public function getHost() {
        return $this->config->host;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey() {
        return $this->_key;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoggedQueries() {
        return $this->_logs;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogger() {
        return $this->_logger;
    }

    /**
     * Return the database password.
     *
     * @return string
     */
    public function getPassword() {
        return $this->config->pass;
    }

    /**
     * Return the database port.
     *
     * @return int
     */
    public function getPort() {
        return $this->config->port;
    }

    /**
     * Return the database user.
     *
     * @return string
     */
    public function getUser() {
        return $this->config->user;
    }

    /**
     * Return the unix socket path to connect with.
     *
     * @return string
     */
    public function getSocket() {
        return $this->config->socket;
    }

    /**
     * {@inheritdoc}
     */
    public function getStorage() {
        return $this->_storage;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected() {
        return $this->_connected;
    }

    /**
     * {@inheritdoc}
     */
    public function isPersistent() {
        return $this->config->persistent;
    }

    /**
     * {@inheritdoc}
     */
    public function logQuery(Result $result) {
        $this->_logs[] = $result;

        // Cast the SQL to string and log it
        if ($logger = $this->getLogger()) {
            $logger->debug((string) $result);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function reset() {
        if ($this->_result) {
            $this->_result->close();
            $this->_result = null;
        }

        // Clear the cache
        $this->flushCache();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDialect(Dialect $dialect) {
        $this->_dialect = $dialect;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger) {
        $this->_logger = $logger;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setStorage(Storage $storage) {
        $this->_storage = $storage;

        return $this;
    }

}