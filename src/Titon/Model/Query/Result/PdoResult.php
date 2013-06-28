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
		$this->execute();

		$count = (int) $this->_statement->fetchColumn();

		$this->close();

		return $count;
	}

	/**
	 * {@inheritdoc}
	 */
	public function execute() {
		if ($this->hasExecuted()) {
			return $this;
		}

		$startTime = microtime();

		if ($this->_statement->execute()) {
			if (preg_match('/^(update|insert|delete)/i', $this->_statement->queryString)) {
				$this->_count = $this->_statement->rowCount();
			} else {
				$this->_count = 1;
			}

			$this->_time = number_format(microtime() - $startTime, 5);
			$this->_success = true;
		}

		$this->_executed = true;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function fetch() {
		$this->execute();

		$result = (array) $this->_statement->fetch(PDO::FETCH_ASSOC);

		$this->close();

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function fetchAll() {
		$this->execute();

		$results = (array) $this->_statement->fetchAll(PDO::FETCH_ASSOC);

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
				case PDO::PARAM_NULL:	$value = 'NULL'; break;
				case PDO::PARAM_INT:
				case PDO::PARAM_BOOL:	$value = (int) $param[0]; break;
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
		$this->execute()->close();

		if ($this->isSuccessful()) {
			return $this->_count;
		}

		return false;
	}

}