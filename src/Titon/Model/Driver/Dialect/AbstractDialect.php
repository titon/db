<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Dialect;

use Titon\Common\Base;
use Titon\Common\Registry;
use Titon\Model\Driver;
use Titon\Model\Driver\Dialect;
use Titon\Model\Driver\Schema;
use Titon\Model\Driver\Type\AbstractType;
use Titon\Model\Exception\InvalidQueryException;
use Titon\Model\Exception\InvalidSchemaException;
use Titon\Model\Exception\MissingClauseException;
use Titon\Model\Exception\MissingStatementException;
use Titon\Model\Query;
use Titon\Model\Query\Expr;
use Titon\Model\Query\Func;
use Titon\Model\Query\Predicate;
use Titon\Model\Traits\DriverAware;
use Titon\Utility\String;

/**
 * Provides shared dialect functionality as well as MySQL style statement building.
 *
 * @package Titon\Model\Driver\Dialect
 */
abstract class AbstractDialect extends Base implements Dialect {
	use DriverAware;

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
		'autoIncrement'	=> 'AUTO_INCREMENT',
		'and'			=> 'AND',
		'as'			=> '%s AS %s',
		'asc'			=> 'ASC',
		'between'		=> '%s BETWEEN ? AND ?',
		'cascade'		=> 'CASCADE',
		'characterSet'	=> 'CHARACTER SET',
		'comment'		=> 'COMMENT %s',
		'constraint'	=> 'CONSTRAINT %s',
		'defaultComment'=> 'DEFAULT COMMENT',
		'defaultValue'	=> 'DEFAULT %s',
		'desc'			=> 'DESC',
		'engine'		=> 'ENGINE',
		'expression'	=> '%s %s ?',
		'foreignKey'	=> 'FOREIGN KEY (%s) REFERENCES %s(%s)',
		'function'		=> '%s(%s)',
		'groupBy'		=> 'GROUP BY %s',
		'having'		=> 'HAVING %s',
		'in'			=> '%s IN (%s)',
		'indexKey'		=> 'KEY %s (%s)',
		'isNull'		=> '%s IS NULL',
		'isNotNull'		=> '%s IS NOT NULL',
		'like'			=> '%s LIKE ?',
		'limit'			=> 'LIMIT %s',
		'limitOffset'	=> 'LIMIT %s,%s',
		'noAction'		=> 'NO ACTION',
		'not'			=> '%s NOT ?',
		'notBetween'	=> '%s NOT BETWEEN ? AND ?',
		'notIn'			=> '%s NOT IN (%s)',
		'notLike'		=> '%s NOT LIKE ?',
		'notNull'		=> 'NOT NULL',
		'null'			=> 'NULL',
		'onDelete'		=> 'ON DELETE %s',
		'onUpdate'		=> 'ON UPDATE %s',
		'or'			=> 'OR',
		'orderBy'		=> 'ORDER BY %s',
		'primaryKey'	=> 'PRIMARY KEY (%s)',
		'restrict'		=> 'RESTRICT',
		'setNull'		=> 'SET NULL',
		'where'			=> 'WHERE %s',
		'uniqueKey'		=> 'UNIQUE KEY %s (%s)',
		'unsigned'		=> 'UNSIGNED',
		'valueGroup'	=> '(%s)',
		'zerofill'		=> 'ZEROFILL'
	];

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
		Query::DROP_TABLE	=> 'DROP TABLE {table}',
		Query::CREATE_TABLE	=> "CREATE TABLE {table} (\n{columns}{keys}\n) {options}"
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
	 * Build the CREATE TABLE query. Requires a table schema object.
	 *
	 * @param \Titon\Model\Query $query
	 * @return string
	 * @throws \Titon\Model\Exception\InvalidSchemaException
	 */
	public function buildCreateTable(Query $query) {
		$schema = $query->getSchema();

		if (!$schema) {
			throw new InvalidSchemaException('Table creation requires a valid schema object');
		}

		return $this->renderStatement($this->getStatement(Query::CREATE_TABLE), [
			'table' => $this->formatTable($schema->getTable()),
			'columns' => $this->formatColumns($schema),
			'keys' => $this->formatTableKeys($schema),
			'options' => $this->formatTableOptions($query->getAttributes())
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
			'limit' => $this->formatLimit($query->getLimit()),
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
	 * Build the DROP TABLE query.
	 *
	 * @param \Titon\Model\Query $query
	 * @return string
	 */
	public function buildDropTable(Query $query) {
		return $this->renderStatement($this->getStatement(Query::DROP_TABLE), [
			'table' => $this->formatTable($query->getTable())
		]);
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
			'limit' => $this->formatLimitOffset($query->getLimit(), $query->getOffset()),
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
			'limit' => $this->formatLimit($query->getLimit()),
		]);
	}

	/**
	 * Format columns for a table schema.
	 *
	 * @param \Titon\Model\Driver\Schema $schema
	 * @return string
	 */
	public function formatColumns(Schema $schema) {
		$columns = [];

		foreach ($schema->getColumns() as $column => $options) {
			$type = $options['type'];
			$dataType = AbstractType::factory($type, $this->getDriver());

			$options = $options + $dataType->getDefaultOptions();

			if (!empty($options['length'])) {
				$type .= '(' . $options['length'] . ')';
			}

			$output = [$this->quote($column), $type];

			if (!empty($options['unsigned'])) {
				$output[] = $this->getClause('unsigned');
			}

			if (!empty($options['zerofill'])) {
				$output[] = $this->getClause('zerofill');
			}

			$output[] = $this->getClause(empty($options['null']) ? 'notNull' : 'null');

			if (array_key_exists('default', $options) && $options['default'] !== '') {
				$output[] = sprintf($this->getClause('defaultValue'), $this->getDriver()->escape($options['default']));
			}

			if (!empty($options['ai'])) {
				$output[] = $this->getClause('autoIncrement');
			}

			if (!empty($options['comment'])) {
				$output[] = sprintf($this->getClause('comment'), $this->getDriver()->escape(substr($options['comment'], 0, 255)));
			}

			$columns[] = implode(' ', $output);
		}

		return implode(",\n", $columns);
	}

	/**
	 * Format database expressions.
	 *
	 * @param \Titon\Model\Query\Expr $expr
	 * @return string
	 */
	public function formatExpression(Expr $expr) {
		$field = $this->quote($expr->getField());

		if ($expr->useValue()) {
			return sprintf($this->getClause('expression'), $field, $expr->getOperator());
		}

		return $field;
	}

	/**
	 * Format the fields structure depending on the type of query.
	 *
	 * @param array $fields
	 * @param int $type
	 * @return string
	 * @throws \Titon\Model\Exception\InvalidQueryException
	 */
	public function formatFields(array $fields, $type) {
		switch ($type) {
			case Query::INSERT:
				if (empty($fields)) {
					throw new InvalidQueryException('Missing field data for insert query');
				}

				return '(' . $this->quoteList(array_keys($fields)) . ')';
			break;

			case Query::SELECT:
				if (empty($fields)) {
					return '*';
				}

				$columns = [];

				foreach ($fields as $field) {
					if ($field instanceof Func) {
						$columns[] = $this->formatFunction($field);
					} else {
						$columns[] = $this->quote($field);
					}
				}

				return implode(', ', $columns);
			break;

			case Query::UPDATE:
				if (empty($fields)) {
					throw new InvalidQueryException('Missing field data for update query');
				}

				$values = [];

				foreach ($fields as $key => $value) {
					if ($value instanceof Expr) {
						$values[] = $this->quote($key) . ' = ' . $this->formatExpression($value);
					} else {
						$values[] = $this->quote($key) . ' = ?';
					}
				}

				return implode(', ', $values);
			break;
		}

		return '';
	}

	/**
	 * Format a database function.
	 *
	 * @param \Titon\Model\Query\Func $func
	 * @return string
	 */
	public function formatFunction(Func $func) {
		$arguments = [];

		foreach ($func->getArguments() as $arg) {
			$type = $arg['type'];
			$value = $arg['value'];

			if ($value instanceof Func) {
				$value = $this->formatFunction($value);

			} else if ($type === Func::FIELD) {
				$value = $this->quote($value);

			} else if ($type === Func::LITERAL) {
				// Do nothing

			} else if (is_string($value) || $value === null) {
				$value = $this->getDriver()->escape($value);
			}

			$arguments[] = $value;
		}

		$output = sprintf($this->getClause('function'),
			$func->getName(),
			implode($func->getSeparator(), $arguments)
		);

		if ($alias = $func->getAlias()) {
			$output = sprintf($this->getClause('as'), $output, $this->quote($alias));
		}

		return $output;
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
	 * @param \Titon\Model\Query\Predicate $having
	 * @return string
	 */
	public function formatHaving(Predicate $having) {
		if ($having->getParams()) {
			return sprintf($this->getClause('having'), $this->formatPredicate($having));
		}

		return '';
	}

	/**
	 * Format the limit.
	 *
	 * @param int $limit
	 * @return string
	 */
	public function formatLimit($limit) {
		if ($limit) {
			return sprintf($this->getClause('limit'), (int) $limit);
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
	public function formatLimitOffset($limit, $offset = 0) {
		if ($limit && $offset) {
			return sprintf($this->getClause('limitOffset'), (int) $offset, (int) $limit);
		}

		return $this->formatLimit($limit);
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
				if ($direction instanceof Func) {
					$output[] = $this->formatFunction($direction);
				} else {
					$output[] = $this->quote($field) . ' ' . $this->getClause($direction);
				}
			}

			return sprintf($this->getClause('orderBy'), implode(', ', $output));
		}

		return '';
	}

	/**
	 * Format the predicate object by grouping nested predicates and parameters.
	 *
	 * @param \Titon\Model\Query\Predicate $predicate
	 * @return string
	 */
	public function formatPredicate(Predicate $predicate) {
		$output = [];

		foreach ($predicate->getParams() as $param) {
			if ($param instanceof Predicate) {
				$output[] = sprintf($this->getClause('valueGroup'), $this->formatPredicate($param));

			} else if ($param instanceof Expr) {
				$field = $param->getField();
				$operator = $param->getOperator();

				// Function instead of field
				if ($field instanceof Func) {
					$value = sprintf($this->getClause('expression'), $this->formatFunction($field), $operator);

				// Regular clause
				} else {
					$field = $this->quote($field);

					switch ($operator) {
						case Expr::IN:
						case Expr::NOT_IN:
							$value = sprintf($this->getClause($operator), $field, implode(', ', array_fill(0, count($param->getValue()), '?')));
						break;
						case Expr::NULL:
						case Expr::NOT_NULL:
						case Expr::BETWEEN:
						case Expr::NOT_BETWEEN:
						case Expr::LIKE:
						case Expr::NOT_LIKE:
							$value = sprintf($this->getClause($operator), $field);
						break;
						default:
							$value = sprintf($this->getClause('expression'), $field, $operator);
						break;
					}
				}

				$output[] = $value;
			}
		}

		return implode(' ' . $this->getClause($predicate->getType()) . ' ', $output);
	}

	/**
	 * Format the table name.
	 *
	 * @param string $table
	 * @return string
	 * @throws \Titon\Model\Exception\InvalidQueryException
	 */
	public function formatTable($table) {
		if (!$table) {
			throw new InvalidQueryException('Missing table for query');
		}

		return $this->quote($table);
	}

	/**
	 * Format table keys (primary, unique and foreign) and indexes.
	 *
	 * @param \Titon\Model\Driver\Schema $schema
	 * @return string
	 */
	public function formatTableKeys(Schema $schema) {
		$keys = [];
		$constraint = $this->getClause('constraint');

		if ($primary = $schema->getPrimaryKey()) {
			$key = sprintf($this->getClause('primaryKey'), $this->quoteList($primary['columns']));

			if ($primary['constraint']) {
				$key = sprintf($constraint, $this->quote($primary['constraint'])) . ' ' . $key;
			}

			$keys[] = $key;
		}

		foreach ($schema->getUniqueKeys() as $index => $unique) {
			$key = sprintf($this->getClause('uniqueKey'), $this->quote($index), $this->quoteList($unique['columns']));

			if ($unique['constraint']) {
				$key = sprintf($constraint, $this->quote($unique['constraint'])) . ' ' . $key;
			}

			$keys[] = $key;
		}

		foreach ($schema->getForeignKeys() as $column => $foreign) {
			$ref = explode('.', $foreign['references']);
			$key = sprintf($this->getClause('foreignKey'), $this->quote($column), $this->quote($ref[0]), $this->quote($ref[1]));

			if ($foreign['constraint']) {
				$key = sprintf($constraint, $this->quote($foreign['constraint'])) . ' ' . $key;
			}

			foreach (['onDelete', 'onUpdate'] as $action) {
				if ($foreign[$action]) {
					$key .= ' ' . sprintf($this->getClause($action), $this->getClause($foreign[$action]));
				}
			}

			$keys[] = $key;
		}

		foreach ($schema->getIndexes() as $index => $columns) {
			$keys[] = sprintf($this->getClause('indexKey'), $this->quote($index), $this->quoteList($columns));
		}

		if ($keys) {
			return ",\n" . implode(",\n", $keys);
		}

		return '';
	}

	/**
	 * Format the table options for a create table statement.
	 *
	 * @param array $attributes
	 * @return string
	 */
	public function formatTableOptions(array $attributes) {
		$output = [];

		foreach ($attributes as $key => $value) {
			if ($key === 'comment' || $key === 'defaultComment') {
				$key = 'defaultComment';
				$value = $this->getDriver()->getConnection()->quote($value);
			}

			$output[] = $this->getClause($key) . '=' . $value;
		}

		return implode(' ', $output);
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
	 * @param \Titon\Model\Query\Predicate $where
	 * @return string
	 */
	public function formatWhere(Predicate $where) {
		if ($where->getParams()) {
			return sprintf($this->getClause('where'), $this->formatPredicate($where));
		}

		return '';
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \Titon\Model\Exception\MissingClauseException
	 */
	public function getClause($key) {
		if (isset($this->_clauses[$key])) {
			return $this->_clauses[$key];
		}

		throw new MissingClauseException(sprintf('Invalid clause %s', $key));
	}

	/**
	 * {@inheritdoc}
	 */
	public function getClauses() {
		return $this->_clauses;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \Titon\Model\Exception\MissingStatementException
	 */
	public function getStatement($key) {
		if (isset($this->_statements[$key])) {
			return $this->_statements[$key];
		}

		throw new MissingStatementException(sprintf('Invalid statement %s', $key));
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
	 * Quote an array of identifiers and return as a string.
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
		$statement = trim(String::insert($statement, $params, ['escape' => false])) . ';';

		return $statement;
	}

}