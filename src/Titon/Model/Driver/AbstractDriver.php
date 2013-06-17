<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver;

use Psr\Log\LoggerInterface;
use Titon\Common\Base;
use Titon\Cache\Storage;
use Titon\Model\Driver;
use Titon\Model\Query\Log;
use Titon\Utility\Hash;

/**
 * Implements basic driver functionality.
 *
 * @package Titon\Model\Driver
 */
abstract class AbstractDriver extends Base implements Driver {

	/**
	 * Configuration.
	 *
	 * @type array {
	 *		@type bool $persistent	Should we use persistent data connections
	 * 		@type string $encoding	Charset encoding for the driver
	 * 		@type string $timezone	Timezone for the driver
	 * 		@type array $flags		Flags used when connecting
	 * }
	 */
	protected $_config = [
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
	 * @type \Titon\Model\Driver\Dialect
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
	 * @type array
	 */
	protected $_logs = [];

	/**
	 * Unique identifier.
	 *
	 * @type string
	 */
	protected $_key;

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
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getConnection() {
		return $this->_connection;
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
	public function logQuery(Log $log) {
		$this->_logs[] = $log;

		// Cast the SQL to string and log it
		if ($logger = $this->getLogger()) {
			$logger->debug((string) $log);
		}

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