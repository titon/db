<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query;

use Titon\Model\Driver\Dialect;
use Titon\Model\Exception\InvalidArgumentException;
use Titon\Model\Query;

/**
 * The SubQuery class represents a nested query within a query.
 * It provides additional functionality that is not present in root queries.
 *
 * @package Titon\Model\Query
 */
class SubQuery extends Query {

	const ALL = Dialect::ALL;
	const ANY = Dialect::ANY;
	const EXISTS = Dialect::EXISTS;
	const IN = Dialect::IN;
	const NOT_EXISTS = Dialect::NOT_EXISTS;
	const NOT_IN = Dialect::NOT_IN;
	const SOME = Dialect::SOME;

	/**
	 * Comparison filter.
	 *
	 * @type string
	 */
	protected $_filter;

	/**
	 * Return the filter type.
	 *
	 * @return string
	 */
	public function getFilter() {
		return $this->_filter;
	}

	/**
	 * Set the filter type.
	 *
	 * @param string $filter
	 * @return \Titon\Model\Query\SubQuery
	 * @throws \Titon\Model\Exception\InvalidArgumentException
	 */
	public function withFilter($filter) {
		if (!in_array($filter, [self::ALL, self::ANY, self::SOME, self::EXISTS, self::NOT_EXISTS, self::IN, self::NOT_IN])) {
			throw new InvalidArgumentException('Invalid filter type for sub-query');
		}

		$this->_filter = $filter;

		return $this;
	}

}