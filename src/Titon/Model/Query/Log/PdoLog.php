<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query\Log;

use Titon\Model\Query\Log;
use \PDOStatement;

/**
 * Provides query logging for PDO.
 *
 * @package Titon\Model\Query\Log
 */
class PdoLog implements Log {

	/**
	 * Affected row count.
	 *
	 * @type int
	 */
	protected $_count = 0;

	/**
	 * Execution time in milliseconds.
	 *
	 * @type int
	 */
	protected $_time = 0;

	/**
	 * Bound parameters.
	 *
	 * @type array
	 */
	protected $_params = [];

	/**
	 * The SQL statement.
	 *
	 * @type string
	 */
	protected $_statement;

	/**
	 * Introspect all query logging values from the PDOStatement.
	 *
	 * @param \PDOStatement $statement
	 */
	public function __construct(PDOStatement $statement) {
		$this->_statement = $statement->queryString;
		$this->_count = $statement->rowCount();

		if (isset($statement->startTime)) {
			$this->_time = number_format(microtime() - $statement->startTime, 5);
		}

		if (isset($statement->params)) {
			$this->_params = $statement->params;
		}
	}

	/**
	 * Return all logged values.
	 *
	 * @return string
	 */
	public function __toString() {
		return sprintf('%s %s %s',
			'[SQL] ' . $this->getStatement(),
			'[TIME] ' . $this->getExecutionTime(),
			'[COUNT] ' . $this->getRowCount()
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getExecutionTime() {
		return $this->_time;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getParams() {
		return $this->_params;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRowCount() {
		return $this->_count;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getStatement() {
		$statement = preg_replace("/ {2,}/", " ", $this->_statement); // Trim spaces

		foreach ($this->getParams() as $param) {
			if (!$param) {
				$param = 0;
			}

			$statement = preg_replace('/\?/', (string) $param, $statement, 1);
		}

		return $statement;
	}

}