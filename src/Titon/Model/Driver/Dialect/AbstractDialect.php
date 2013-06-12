<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Dialect;

use Titon\Common\Base;
use Titon\Model\Driver\Dialect;
use Titon\Model\Driver;
use Titon\Model\Exception;
use Titon\Model\Query;
use Titon\Model\Query\Clause;
use Titon\Utility\String;

/**
 * Provides shared dialect functionality as well as MySQL style statement building.
 *
 * @package Titon\Model\Driver\Dialect
 */
abstract class AbstractDialect extends Base implements Dialect {

	/**
	 * Configuration.
	 *
	 * @type array {
	 * 		@type string $quoteCharacter	Character used for quoting fields
	 * }
	 */
	protected $_config = [
		'quoteCharacter' => '`'
	];

	/**
	 * List of query component clauses.
	 *
	 * @type array
	 */
	protected $_clauses = [
		'between'		=> '%s BETWEEN ? AND ?',
		'groupBy'		=> 'GROUP BY %s',
		'having'		=> 'HAVING %s',
		'in'			=> '%s IN (%s)',
		'limit'			=> 'LIMIT %s',
		'limitOffset'	=> 'LIMIT %s,%s',
		'notBetween'	=> '%s NOT BETWEEN ? AND ?',
		'notIn'			=> '%s NOT IN (%s)',
		'notNull'		=> '%s IS NOT NULL',
		'null'			=> '%s IS NULL',
		'orderBy'		=> 'ORDER BY %s',
		'where'			=> 'WHERE %s',
		'valueGroup'	=> '(%s)'
	];

	/**
	 * The parent driver class.
	 *
	 * @type \Titon\Model\Driver
	 */
	protected $_driver;

	/**
	 * List of full SQL statements.
	 *
	 * @type array
	 */
	protected $_statements = [
		Query::INSERT		=> 'INSERT INTO {table} {fields} VALUES {values}',
		Query::SELECT		=> 'SELECT {fields} FROM {table} {where} {groupBy} {having} {orderBy} {limit}',
		Query::UPDATE		=> 'UPDATE {table} SET {fields} {where} {orderBy} {limit}',
		Query::DELETE		=> 'DELETE FROM {table} {where} {orderBy} {limit}',
		Query::TRUNCATE		=> 'TRUNCATE {table}',
		Query::DESCRIBE		=> 'DESCRIBE {table}',
		Query::EXPLAIN		=> 'EXPLAIN EXTENDED SELECT {options}',
		Query::DROP_TABLE	=> 'DROP TABLE {table}',
		Query::CREATE_TABLE	=> 'CREATE TABLE {table} ({fields}{indexes}) {params}',
		Query::ALTER_TABLE	=> 'ALTER TABLE {table}'
	];

	/**
	 * Store the driver.
	 *
	 * @param \Titon\Model\Driver $driver
	 */
	public function __construct(Driver $driver) {
		parent::__construct();

		$this->setDriver($driver);
	}

	/**
	 * Build the INSERT query.
	 *
	 * @param \Titon\Model\Query $query
	 * @return string
	 */
	public function buildInsert(Query $query) {
		return $this->renderStatement($this->getStatement(Query::INSERT), [
			'table' => $this->formatTable($query->getTable()),
			'fields' => $this->formatFields($query->getFields(), $query->getType()),
			'values' => $this->formatValues($query->getFields(), $query->getType()),
		]);
	}

	/**
	 * Build the SELECT query.
	 *
	 * @param \Titon\Model\Query $query
	 * @return string
	 */
	public function buildSelect(Query $query) {
		return $this->renderStatement($this->getStatement(Query::SELECT), [
			'table' => $this->formatTable($query->getTable()),
			'fields' => $this->formatFields($query->getFields(), $query->getType()),
			'where' => $this->formatWhere($query->getWhere()),
			'groupBy' => $this->formatGroupBy($query->getGroupBy()),
			'having' => $this->formatHaving($query->getHaving()),
			'orderBy' => $this->formatOrderBy($query->getOrderBy()),
			'limit' => $this->formatLimit($query->getLimit(), $query->getOffset()),
		]);
	}

	/**
	 * Build the UPDATE query.
	 *
	 * @param \Titon\Model\Query $query
	 * @return string
	 */
	public function buildUpdate(Query $query) {
		return $this->renderStatement($this->getStatement(Query::UPDATE), [
			'table' => $this->formatTable($query->getTable()),
			'fields' => $this->formatFields($query->getFields(), $query->getType()),
			'where' => $this->formatWhere($query->getWhere()),
			'orderBy' => $this->formatOrderBy($query->getOrderBy()),
			'limit' => $this->formatLimit($query->getLimit(), $query->getOffset()),
		]);
	}

	/**
	 * Build the DELETE query.
	 *
	 * @param \Titon\Model\Query $query
	 * @return string
	 */
	public function buildDelete(Query $query) {
		return $this->renderStatement($this->getStatement(Query::DELETE), [
			'table' => $this->formatTable($query->getTable()),
			'where' => $this->formatWhere($query->getWhere()),
			'orderBy' => $this->formatOrderBy($query->getOrderBy()),
			'limit' => $this->formatLimit($query->getLimit(), $query->getOffset()),
		]);
	}

	/**
	 * Build the TRUNCATE query.
	 *
	 * @param \Titon\Model\Query $query
	 * @return string
	 */
	public function buildTruncate(Query $query) {
		return $this->renderStatement($this->getStatement(Query::TRUNCATE), [
			'table' => $this->formatTable($query->getTable())
		]);
	}

	/**
	 * Build the DESCRIBE query.
	 *
	 * @param \Titon\Model\Query $query
	 * @return string
	 */
	public function buildDescribe(Query $query) {
		return $this->renderStatement($this->getStatement(Query::DESCRIBE), [
			'table' => $this->formatTable($query->getTable())
		]);
	}

	/**
	 * Format the table name.
	 *
	 * @param string $table
	 * @return string
	 */
	public function formatTable($table) {
		return $this->quote($table);
	}

	/**
	 * Format the fields structure depending on the type of query.
	 *
	 * @param array $fields
	 * @param int $type
	 * @return string
	 */
	public function formatFields(array $fields, $type) {
		switch ($type) {
			case Query::INSERT:
				return '(' . $this->quoteList(array_keys($fields)) . ')';
			break;

			case Query::SELECT:
				return empty($fields) ? '*' : $this->quoteList($fields);
			break;

			case Query::UPDATE:
				$values = [];

				foreach ($fields as $key => $value) {
					$values[] = $this->quote($key) . ' = ?';
				}

				return implode(', ', $values);
			break;
		}

		return '';
	}

	/**
	 * Format the fields values structure depending on the type of query.
	 *
	 * @param array $fields
	 * @param int $type
	 * @return string
	 */
	public function formatValues(array $fields, $type) {
		switch ($type) {
			case Query::INSERT:
				return sprintf($this->getClause('valueGroup'), implode(', ', array_fill(0, count($fields), '?')));
			break;
		}

		return '';
	}

	/**
	 * Format the where clause.
	 *
	 * @param \Titon\Model\Query\Clause $where
	 * @return string
	 */
	public function formatWhere(Clause $where) {
		if ($where->getParams()) {
			return sprintf($this->getClause('where'), $this->formatClause($where));
		}

		return '';
	}

	/**
	 * Format the group by.
	 *
	 * @param array $groupBy
	 * @return string
	 */
	public function formatGroupBy(array $groupBy) {
		if ($groupBy) {
			return sprintf($this->getClause('groupBy'), $this->quoteList($groupBy));
		}

		return '';
	}

	/**
	 * Format the having clause.
	 *
	 * @param \Titon\Model\Query\Clause $having
	 * @return string
	 */
	public function formatHaving(Clause $having) {
		if ($having->getParams()) {
			return sprintf($this->getClause('having'), $this->formatClause($having));
		}

		return '';
	}

	/**
	 * Format the order by.
	 *
	 * @param array $orderBy
	 * @return string
	 */
	public function formatOrderBy(array $orderBy) {
		if ($orderBy) {
			$output = [];

			foreach ($orderBy as $field => $direction) {
				$output[] = $this->quote($field) . ' ' . strtoupper($direction);
			}

			return sprintf($this->getClause('orderBy'), implode(', ', $output));
		}

		return '';
	}

	/**
	 * Format the limit and offset.
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return string
	 */
	public function formatLimit($limit, $offset = 0) {
		if ($limit) {
			if ($offset) {
				return sprintf($this->getClause('limitOffset'), (int) $offset, (int) $limit);
			}

			return sprintf($this->getClause('limit'), (int) $limit);
		}

		return '';
	}

	/**
	 * Format the clause object by grouping nested clauses and parameters.
	 *
	 * @param \Titon\Model\Query\Clause $clause
	 * @return string
	 */
	public function formatClause(Clause $clause) {
		$output = [];

		foreach ($clause->getParams() as $param) {
			if ($param instanceof Clause) {
				$output[] = sprintf($this->getClause('valueGroup'), $this->formatClause($param));

			} else {
				$field = $this->quote($param['field']);

				switch ($param['op']) {
					case Clause::IN:
					case Clause::NOT_IN:
						$clause = $this->getClause('in');

						if ($param['op'] === Clause::NOT_IN) {
							$clause = $this->getClause('notIn');
						}

						$value = sprintf($clause, $field, $param['op'], implode(', ', array_fill(0, count($param['value']), '?')));
					break;
					case Clause::NULL:
						$value = sprintf($this->getClause('null'), $field);
					break;
					case Clause::NOT_NULL:
						$value = sprintf($this->getClause('notNull'), $field);
					break;
					case Clause::BETWEEN:
						$value = sprintf($this->getClause('between'), $field);
					break;
					case Clause::NOT_BETWEEN:
						$value = sprintf($this->getClause('notBetween'), $field);
					break;
					default:
						$value = sprintf('%s %s ?', $field, $param['op']);
					break;
				}

				$output[] = $value;
			}
		}

		return implode(' ' . $clause->getType() . ' ', $output);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getClause($key) {
		if (isset($this->_clauses[$key])) {
			return $this->_clauses[$key];
		}

		throw new Exception(sprintf('Invalid clause %s', $key));
	}

	/**
	 * {@inheritdoc}
	 */
	public function getClauses() {
		return $this->_clauses;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDriver() {
		return $this->_driver;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getStatement($key) {
		if (isset($this->_statements[$key])) {
			return $this->_statements[$key];
		}

		throw new Exception(sprintf('Invalid statement %s', $key));
	}

	/**
	 * {@inheritdoc}
	 */
	public function getStatements() {
		return $this->_statements;
	}

	/**
	 * {@inheritdoc}
	 */
	public function quote($value) {
		$char = $this->config->quoteCharacter;

		return $char . trim($value, $char) . $char;
	}

	/**
	 * Quote an array of identifiers.
	 *
	 * @param array $values
	 * @return string
	 */
	public function quoteList(array $values) {
		return implode(', ', array_map([$this, 'quote'], $values));
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderStatement($statement, array $params) {
		return trim(String::insert($statement, $params));
	}

	/**
	 * {@inheritdoc}
	 */
	public function setDriver(Driver $driver) {
		$this->_driver = $driver;

		return $this;
	}

}