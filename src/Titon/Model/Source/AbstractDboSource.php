<?php

namespace Titon\Model\Source;

use \PDO;

/**
 * http://php.net/manual/en/pdo.drivers.php
 */
abstract class AbstractDboSource extends AbstractSource {

	/**
	 * Configuration.
	 *
	 * @type array
	 */
	protected $_config = [
		'database' => '',
		'host' => 'localhost',
		'port' => '',
		'user' => '',
		'pass' => '',
		'dsn' => '',
		'socket' => ''
	];

	/**
	 * Connect to the database using PDO.
	 */
	public function connect() {
		$this->disconnect();

		$this->_connection = new PDO($this->getDsn(), $this->getUser(), $this->getPassword(), $this->getFlags() + [
			PDO::ATTR_PERSISTENT => $this->isPersistent(),
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		]);

		$this->_connected = true;
	}

	/**
	 * Escape a value before interacting with the database.
	 *
	 * @param mixed $value
	 * @param int $type
	 * @return mixed
	 */
	public function escape($value, $type = PDO::PARAM_STR) {
		return $this->_connection->quote($value, $type);
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
	 * Format and build the DSN based on the current configuration.
	 *
	 * @return string
	 */
	public function getDsn() {
		$dsn = $this->config->dsn;

		if (!$dsn) {
			$params = ['dbname=' . $this->getDatabase()];

			if ($socket = $this->getSocket()) {
				$params[] = 'unix_socket=' . $socket;
			} else {
				$params[] = 'host=' . $this->getHost();
				$params[] = 'port=' . $this->getPort();
				$params[] = 'charset=' . $this->getEncoding();
			}

			$dsn = $this->getDriver() . ':' . implode(';', $params);
		}

		return $dsn;
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
	public function query() {

	}

}