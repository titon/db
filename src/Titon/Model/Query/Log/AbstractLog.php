<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query\Log;

use Titon\Model\Query\Log;

/**
 * Provides shared functionality from query logging.
 *
 * @package Titon\Model\Query\Log
 */
abstract class AbstractLog implements Log {

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
		return $this->_statement;
	}

}