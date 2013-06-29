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
	const SOME = Dialect::SOME;
	const EXISTS = Dialect::EXISTS;
	const NOT_EXISTS = Dialect::NOT_EXISTS;

	/**
	 * Column alias name.
	 *
	 * @type string
	 */
	protected $_alias;

	/**
	 * Comparison filter.
	 *
	 * @type string
	 */
	protected $_filter;

	/**
	 * Set the alias name.
	 *
	 * @param string $alias
	 * @return \Titon\Model\Query\SubQuery
	 */
	public function asAlias($alias) {
		$this->_alias = $alias;

		return $this;
	}

	/**
	 * Return the alias name.
	 *
	 * @return string
	 */
	public function getAlias() {
		return $this->_alias;
	}

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
		if (!in_array($filter, [self::ALL, self::ANY, self::SOME, self::EXISTS, self::NOT_EXISTS])) {
			throw new InvalidArgumentException('Invalid filter type for sub-query');
		}

		$this->_filter = $filter;

		return $this;
	}

}