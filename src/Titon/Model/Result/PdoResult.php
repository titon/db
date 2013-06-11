<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Result;

use Titon\Model\Result;
use \PDO;
use \PDOStatement;

/**
 * The PdoResult handles the processing of a PDOStatement to return the correct results.
 */
class PdoResult implements Result {

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
	}

	/**
	 * {@inheritdoc}
	 */
	public function count() {
		$count = (int) $this->_statement->fetchColumn();

		$this->_statement->closeCursor();

		return $count;
	}

	/**
	 * {@inheritdoc}
	 */
	public function fetch() {
		$result = (array) $this->_statement->fetch(PDO::FETCH_ASSOC);

		$this->_statement->closeCursor();

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function fetchAll() {
		$results = (array) $this->_statement->fetchAll(PDO::FETCH_ASSOC);

		$this->_statement->closeCursor();

		return $results;
	}

	/**
	 * {@inheritdoc}
	 */
	public function save() {
		$count = (int) $this->_statement->rowCount();

		$this->_statement->closeCursor();

		return $count;
	}

}