<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query\Log;

use \PDO;
use \PDOStatement;

/**
 * Provides query logging for PDO. The log will accept the PDOStatement as an argument and introspect the values from it.
 *
 * @package Titon\Model\Query\Log
 */
class PdoLog extends AbstractLog {

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
	 * {@inheritdoc}
	 */
	public function getStatement() {
		$statement = preg_replace("/ {2,}/", " ", parent::getStatement()); // Trim spaces

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

}