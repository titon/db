<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query\Result;

use Titon\Model\Query\Result;

/**
 * Provides shared functionality for results.
 *
 * @package Titon\Model\Query\Result
 */
abstract class AbstractResult implements Result {

	/**
	 * Affected row count.
	 *
	 * @type int
	 */
	protected $_count = 0;

	/**
	 * Has query been executed.
	 *
	 * @type bool
	 */
	protected $_executed = false;

	/**
	 * Bound parameters.
	 *
	 * @type array
	 */
	protected $_params = [];

	/**
	 * Was the query execution successful.
	 *
	 * @type bool
	 */
	protected $_success = false;

	/**
	 * Execution time in milliseconds.
	 *
	 * @type int
	 */
	protected $_time = 0;

	/**
	 * Return all logged values.
	 *
	 * @return string
	 */
	public function __toString() {
		return sprintf('%s %s %s %s',
			'[SQL] ' . $this->getStatement(),
			'[TIME] ' . $this->getExecutionTime(),
			'[COUNT] ' . $this->getRowCount(),
			'[STATE] ' . ($this->hasExecuted() ? 'Executed' : 'Prepared')
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
	public function hasExecuted() {
		return $this->_executed;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isSuccessful() {
		return $this->_success;
	}

}