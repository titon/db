<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query\Result;

use Titon\Model\Query\Result;
use \PDO;
use \PDOStatement;

/**
 * Accepts a PDOStatement instance which is used for result fetching and query profiling.
 *
 * @package Titon\Model\Query\Result
 */
class PdoResult extends AbstractResult implements Result {

	/**
	 * PDOStatement instance.
	 *
	 * @type \PDOStatement
	 */
	protected $_statement;

	/**
	 * Store the statement.
	 *
	 * @param \PDOStatement $statement
	 */
	public function __construct(PDOStatement $statement) {
		$this->_statement = $statement;

		if (isset($statement->params)) {
			$this->_params = $statement->params;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function close() {
		return $this->_statement->closeCursor();
	}

	/**
	 * {@inheritdoc}
	 */
	public function count() {
		$count = (int) $this->_execute()->fetchColumn();

		$this->close();

		return $count;
	}

	/**
	 * {@inheritdoc}
	 */
	public function fetch() {
		$result = (array) $this->_execute()->fetch(PDO::FETCH_ASSOC);

		$this->close();

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function fetchAll() {
		$results = (array) $this->_execute()->fetchAll(PDO::FETCH_ASSOC);

		$this->close();

		return $results;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getStatement() {
		$statement = preg_replace("/ {2,}/", " ", $this->_statement->queryString); // Trim spaces

		foreach ($this->getParams() as $param) {
			switch ($param[1]) {
				case PDO::PARAM_NULL:	$value = 'null'; break;
				case PDO::PARAM_INT:	$value = (int) $param[0]; break;
				case PDO::PARAM_BOOL:	$value = (bool) $param[0]; break;
				default: 				$value = "'" . (string) $param[0] . "'"; break;
			}

			$statement = preg_replace('/\?/', $value, $statement, 1);
		}

		return $statement;
	}

	/**
	 * {@inheritdoc}
	 */
	public function save() {
		$this->_execute();
		$this->close();

		return $this->_count;
	}

	/**
	 * Execute the PDOStatement and log the affected rows and execution time.
	 *
	 * @return \PDOStatement
	 */
	protected function _execute() {
		if ($this->hasExecuted()) {
			return $this->_statement;
		}

		$startTime = microtime();

		if ($this->_statement->execute()) {
			if (preg_match('/^(update|insert|delete)/i', $this->_statement->queryString, $matches)) {
				$count = $this->_statement->rowCount();

				// If an update succeeds but no data changes, the row count will be 0, force it to 1
				if (strtolower($matches[1]) === 'update') {
					$this->_count = ($count == 0) ? 1 : $count;
				} else {
					$this->_count = $count;
				}
			} else {
				$this->_count = 1;
			}

			$this->_time = number_format(microtime() - $startTime, 5);
		}

		$this->_executed = true;

		return $this->_statement;
	}

}