<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver;

use Psr\Log\LoggerInterface;
use Titon\Common\Base;
use Titon\Common\Traits\Cacheable;
use Titon\Cache\Storage;
use Titon\Db\Driver;
use Titon\Db\Exception\InvalidArgumentException;
use Titon\Db\Query\ResultSet;
use Titon\Db\Query;
use Titon\Utility\Hash;

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
     *      @type string $database      The database name
     *      @type string $host          The hostname or IP to connect to
     *      @type int $port             The port to connect with
     *      @type string $user          Login user name
     *      @type string $pass          Login user password
     *      @type string $socket        Path to unix socket to connect with
     *      @type bool $persistent      Should we use persistent data connections
     *      @type string $encoding      Charset encoding for the driver
     *      @type string $timezone      Timezone for the driver
     *      @type array $flags          Flags used when connecting
     *      @type array $contexts       Mapping of read, write, and custom config contexts
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
        'flags' => [],
        'contexts' => []
    ];

    /**
     * PDO or API object instances.
     *
     * @type array
     */
    protected $_connections = [];

    /**
     * The current connection config.
     *
     * @type string
     */
    protected $_context = 'read';

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
     * @type \Titon\Db\Query\ResultSet[]
     */
    protected $_logs = [];

    /**
     * The last query result.
     *
     * @type \Titon\Db\Query\ResultSet
     */
    protected $_result;

    /**
     * Storage engine instance.
     *
     * @type \Titon\Cache\Storage
     */
    protected $_storage;

    /**
     * Disconnect when the object is destroyed.
     *
     * @codeCoverageIgnore
     */
    public function __destruct() {
        $this->disconnect(true);
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect($flush = false) {
        $this->reset();

        if ($this->isConnected()) {
            if ($flush) {
                $this->_connections = [];
            } else {
                unset($this->_connections[$this->getContext()]);
            }

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection() {
        $context = $this->getContext();

        if (empty($this->_connections[$context])) {
            $this->connect();
        }

        return $this->_connections[$context];
    }

    /**
     * {@inheritdoc}
     */
    public function getConnections() {
        return $this->_connections;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext() {
        return $this->_context;
    }

    /**
     * {@inheritdoc}
     */
    public function getContextConfig($key) {
        $config = $this->allConfig();

        if (isset($config['contexts'][$key])) {
            $config = array_merge($config, $config['contexts'][$key]);
        }

        return Hash::reduce($config, ['user', 'pass', 'host', 'port', 'database']);
    }

    /**
     * Return the database name.
     *
     * @return string
     */
    public function getDatabase() {
        return $this->getContextConfig($this->getContext())['database'];
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
        return $this->getConfig('encoding');
    }

    /**
     * Return the database host.
     *
     * @return string
     */
    public function getHost() {
        return $this->getContextConfig($this->getContext())['host'];
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
        return $this->getContextConfig($this->getContext())['pass'];
    }

    /**
     * Return the database port.
     *
     * @return int
     */
    public function getPort() {
        return $this->getContextConfig($this->getContext())['port'];
    }

    /**
     * Return the database user.
     *
     * @return string
     */
    public function getUser() {
        return $this->getContextConfig($this->getContext())['user'];
    }

    /**
     * Return the unix socket path to connect with.
     *
     * @return string
     */
    public function getSocket() {
        return $this->getConfig('socket');
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
        return isset($this->_connections[$this->getContext()]);
    }

    /**
     * {@inheritdoc}
     */
    public function isPersistent() {
        return $this->getConfig('persistent');
    }

    /**
     * {@inheritdoc}
     */
    public function logQuery(ResultSet $result) {
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
    public function newQuery($type) {
        return new Query($type);
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
    public function setContext($context) {
        if (!$context) {
            throw new InvalidArgumentException('A connection context is required');
        }

        $this->_context = $context;

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