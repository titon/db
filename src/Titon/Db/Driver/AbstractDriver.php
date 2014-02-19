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
use Titon\Db\Exception\UnknownConnectionException;
use Titon\Db\Query\Result;
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
     *      @type array $connections    Mapping of read, write, and custom connections
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
        'connections' => []
    ];

    /**
     * PDO or API object instances.
     *
     * @type array
     */
    protected $_connections = [];

    /**
     * Dialect object instance.
     *
     * @type \Titon\Db\Driver\Dialect
     */
    protected $_dialect;

    /**
     * The current read, write, or custom connection group.
     *
     * @type string
     */
    protected $_group = 'read';

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
     * Disconnect when the object is destroyed.
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
                unset($this->_connections[$this->getConnectionGroup()]);
            }

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection() {
        $group = $this->getConnectionGroup();

        if (isset($this->_connections[$group])) {
            return $this->_connections[$group];
        }

        throw new UnknownConnectionException(sprintf('No connection found for %s group', $group));
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionGroup() {
        return $this->_group;
    }

    /**
     * Return the database name.
     *
     * @return string
     */
    public function getDatabase() {
        return $this->getGroup($this->getConnectionGroup())['database'];
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
     * {@inheritdoc}
     */
    public function getGroup($key) {
        $config = $this->allConfig();

        if (isset($config['connections'][$key])) {
            $config = array_merge($config, $config['connections'][$key]);
        }

        return Hash::reduce($config, ['user', 'pass', 'host', 'port', 'database']);
    }

    /**
     * Return the database host.
     *
     * @return string
     */
    public function getHost() {
        return $this->getGroup($this->getConnectionGroup())['host'];
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
        return $this->getGroup($this->getConnectionGroup())['pass'];
    }

    /**
     * Return the database port.
     *
     * @return int
     */
    public function getPort() {
        return $this->getGroup($this->getConnectionGroup())['port'];
    }

    /**
     * Return the database user.
     *
     * @return string
     */
    public function getUser() {
        return $this->getGroup($this->getConnectionGroup())['user'];
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
        return isset($this->_connections[$this->getConnectionGroup()]);
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
    public function setConnectionGroup($group) {
        if (!$group) {
            throw new InvalidArgumentException('A connection group is required');
        }

        $this->_group = $group;

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