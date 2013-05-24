<?php

namespace Titon\Model\Source;

use Titon\Common\Base;
use Titon\Model\Source;
use Titon\Utility\Hash;

abstract class AbstractSource extends Base implements Source {

	/**
	 * Configuration.
	 *
	 * 	persistent	- (bool) Should we use persistent data connections
	 * 	encoding	- (string) Charset encoding for the remote data source
	 *
	 * @type array
	 */
	protected $_config = [
		'persistent' => true,
		'encoding' => 'UTF-8'
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
	 * Flags used for connecting.
	 *
	 * @type array
	 */
	protected $_flags = [];

	/**
	 * Unique source identifier.
	 *
	 * @type string
	 */
	protected $_key;

	/**
	 * Store the identifier key, configuration and optional flags.
	 *
	 * @param string $key
	 * @param array $config
	 * @param array $flags
	 */
	public function __construct($key, array $config, array $flags = []) {
		parent::__construct($config);

		$this->_key = $key;
		$this->_flags = Hash::merge($this->_flags, $flags);
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
		if ($this->isConnected()) {
			unset($this->_connection);
			$this->_connected = false;
		}
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
	public function getFlags() {
		return $this->_flags;
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
	public function isConnected() {
		return $this->_connected;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isPersistent() {
		return $this->config->persistent;
	}

}