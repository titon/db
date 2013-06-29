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
use Titon\Model\Exception\InvalidArgumentException;
use Titon\Model\Exception\InvalidQueryException;
use Titon\Model\Exception\InvalidSchemaException;
use Titon\Model\Exception\MissingClauseException;
use Titon\Model\Exception\MissingStatementException;
use Titon\Model\Query;
use Titon\Model\Query\Expr;
use Titon\Model\Query\Func;
use Titon\Model\Query\Predicate;
use Titon\Model\Query\SubQuery;
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
		'autoIncrement'		=> 'AUTO_INCREMENT',
		'all'				=> 'ALL',
		'and'				=> 'AND',
		'any'				=> 'ANY',
		'as'				=> '%s AS %s',
		'asc'				=> 'ASC',
		'avgRowLength'		=> 'AVG_ROW_LENGTH',
		'between'			=> '%s BETWEEN ? AND ?',
		'cascade'			=> 'CASCADE',
		'charset'			=> 'CHARACTER SET %s',
		'characterSet'		=> 'CHARACTER SET',
		'checksum'			=> 'CHECKSUM',
		'collate'			=> 'COLLATE',
		'collation'			=> 'COLLATE %s',
		'comment'			=> 'COMMENT %s',
		'connection'		=> 'CONNECTION',
		'constraint'		=> 'CONSTRAINT %s',
		'dataDirectory'		=> 'DATA DIRECTORY',
		'defaultCharacterSet' => 'DEFAULT CHARACTER SET',
		'defaultComment'	=> 'DEFAULT COMMENT',
		'defaultValue'		=> 'DEFAULT %s',
		'delayed'			=> 'DELAYED',
		'delayKeyWrite'		=> 'DELAY_KEY_WRITE',
		'desc'				=> 'DESC',
		'distinct'			=> 'DISTINCT',
		'distinctRow'		=> 'DISTINCTROW',
		'engine'			=> 'ENGINE',
		'exists'			=> 'EXISTS',
		'expression'		=> '%s %s ?',
		'foreignKey'		=> 'FOREIGN KEY (%s) REFERENCES %s(%s)',
		'function'			=> '%s(%s)',
		'groupBy'			=> 'GROUP BY %s',
		'having'			=> 'HAVING %s',
		'highPriority'		=> 'HIGH_PRIORITY',
		'ignore'			=> 'IGNORE',
		'in'				=> '%s IN (%s)',
		'indexDirectory'	=> 'INDEX DIRECTORY',
		'indexKey'			=> 'KEY %s (%s)',
		'insertMethod'		=> 'INSERT_METHOD',
		'isNull'			=> '%s IS NULL',
		'isNotNull'			=> '%s IS NOT NULL',
		'keyBlockSize'		=> 'KEY_BLOCK_SIZE',
		'like'				=> '%s LIKE ?',
		'limit'				=> 'LIMIT %s',
		'limitOffset'		=> 'LIMIT %s OFFSET %s',
		'lowPriority'		=> 'LOW_PRIORITY',
		'maxRows'			=> 'MAX_ROWS',
		'minRows'			=> 'MIN_ROWS',
		'noAction'			=> 'NO ACTION',
		'not'				=> '%s NOT ?',
		'notBetween'		=> '%s NOT BETWEEN ? AND ?',
		'notExists'			=> 'NOT EXISTS',
		'notIn'				=> '%s NOT IN (%s)',
		'notLike'			=> '%s NOT LIKE ?',
		'notNull'			=> 'NOT NULL',
		'notRegexp'			=> '%s NOT REGEXP ?',
		'null'				=> 'NULL',
		'onDelete'			=> 'ON DELETE %s',
		'onUpdate'			=> 'ON UPDATE %s',
		'or'				=> 'OR',
		'orderBy'			=> 'ORDER BY %s',
		'packKeys'			=> 'PACK_KEYS',
		'password'			=> 'PASSWORD',
		'primaryKey'		=> 'PRIMARY KEY (%s)',
		'quick'				=> 'QUICK',
		'regexp'			=> '%s REGEXP ?',
		'restrict'			=> 'RESTRICT',
		'rlike'				=> '%s REGEXP ?',
		'rowFormat'			=> 'ROW_FORMAT',
		'setNull'			=> 'SET NULL',
		'some'				=> 'SOME',
		'sqlBigResult'		=> 'SQL_BIG_RESULT',
		'sqlBufferResult'	=> 'SQL_BUFFER_RESULT',
		'sqlCache'			=> 'SQL_CACHE',
		'sqlNoCache'		=> 'SQL_NO_CACHE',
		'sqlSmallResult'	=> 'SQL_SMALL_RESULT',
		'statsAutoRecalc'	=> 'STATS_AUTO_RECALC',
		'statsPersistent'	=> 'STATS_PERSISTENT',
		'subQuery'			=> '(%s)',
		'temporary'			=> 'TEMPORARY',
		'where'				=> 'WHERE %s',
		'xor'				=> 'XOR',
		'uniqueKey'			=> 'UNIQUE KEY %s (%s)',
		'union'				=> 'UNION',
		'unsigned'			=> 'UNSIGNED',
		'valueGroup'		=> '(%s)',
		'zerofill'			=> 'ZEROFILL'
	];

	/**
	 * List of full SQL statements.
	 *
	 * @type array
	 */
	protected $_statements = [
		Query::INSERT		=> 'INSERT {a.priority} {a.ignore} INTO {table} {fields} VALUES {values}',
		Query::SELECT		=> 'SELECT {a.distinct} {a.priority} {a.optimize} {a.cache} {fields} FROM {table} {where} {groupBy} {having} {orderBy} {limit}',
		Query::UPDATE		=> 'UPDATE {a.priority} {a.ignore} {table} SET {fields} {where} {orderBy} {limit}',
		Query::DELETE		=> 'DELETE {a.priority} {a.quick} {a.ignore} FROM {table} {where} {orderBy} {limit}',
		Query::TRUNCATE		=> 'TRUNCATE {table}',
		Query::DESCRIBE		=> 'DESCRIBE {table}',
		Query::DROP_TABLE	=> 'DROP {a.temporary} TABLE IF EXISTS {table}',
		Query::CREATE_TABLE	=> "CREATE {a.temporary} TABLE IF NOT EXISTS {table} (\n{columns}{keys}\n) {options}"
	];

	/**
	 * Available attributes for each query type.
	 *
	 * @type array
	 */
	protected $_attributes = [
		Query::INSERT => [
			'priority' => '',
			'ignore' => false
		],
		Query::SELECT => [
			'distinct' => false,
			'priority' => '',
			'optimize' => '',
			'cache' => ''
		],
		Query::UPDATE => [
			'priority' => '',
			'ignore' => false
		],
		Query::DELETE => [
			'priority' => '',
			'quick' => false,
			'ignore' => false
		],
		Query::DROP_TABLE => [
			'temporary' => false
		],
		Query::CREATE_TABLE => [
			'temporary' => false
		],
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

		$params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::CREATE_TABLE));
		$params = $params + [
			'table' => $this->formatTable($schema->getTable()),
			'columns' => $this->formatColumns($schema),
			'keys' => $this->formatTableKeys($schema),
			'options' => $this->formatTableOptions($schema->getOptions())
		];

		return $this->renderStatement($this->getStatement(Query::CREATE_TABLE), $params);
	}

	/**
	 * Build the DELETE query.
	 *
	 * @param \Titon\Model\Query $query
	 * @return string
	 */
	public function buildDelete(Query $query) {
		$params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::DELETE));
		$params = $params + [
			'table' => $this->formatTable($query->getTable()),
			'where' => $this->formatWhere($query->getWhere()),
			'orderBy' => $this->formatOrderBy($query->getOrderBy()),
			'limit' => $this->formatLimit($query->getLimit()),
		];

		return $this->renderStatement($this->getStatement(Query::DELETE), $params);
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
		$params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::DROP_TABLE));
		$params = $params + [
			'table' => $this->formatTable($query->getTable())
		];

		return $this->renderStatement($this->getStatement(Query::DROP_TABLE), $params);
	}

	/**
	 * Build the INSERT query.
	 *
	 * @param \Titon\Model\Query $query
	 * @return string
	 */
	public function buildInsert(Query $query) {
		$params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::INSERT));
		$params = $params + [
			'table' => $this->formatTable($query->getTable()),
			'fields' => $this->formatFields($query->getFields(), $query->getType()),
			'values' => $this->formatValues($query->getFields(), $query->getType()),
		];

		return $this->renderStatement($this->getStatement(Query::INSERT), $params);
	}

	/**
	 * Build the INSERT query with multiple record support.
	 *
	 * @param \Titon\Model\Query $query
	 * @return string
	 */
	public function buildMultiInsert(Query $query) {
		$values = [];
		$fields = [];
		$type = $query->getType();

		foreach ($query->getFields() as $record) {
			if (!$fields) {
				$fields = $this->formatFields($record, $type);
			}

			$values[] = $this->formatValues($record, $type);
		}

		$params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::INSERT));
		$params = $params + [
			'table' => $this->formatTable($query->getTable()),
			'fields' => $fields,
			'values' => implode(', ', $values),
		];

		return $this->renderStatement($this->getStatement(Query::INSERT), $params);
	}

	/**
	 * Build the SELECT query.
	 *
	 * @param \Titon\Model\Query $query
	 * @return string
	 */
	public function buildSelect(Query $query) {
		$params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::SELECT));
		$params = $params + [
			'table' => $this->formatTable($query->getTable()),
			'fields' => $this->formatFields($query->getFields(), $query->getType()),
			'where' => $this->formatWhere($query->getWhere()),
			'groupBy' => $this->formatGroupBy($query->getGroupBy()),
			'having' => $this->formatHaving($query->getHaving()),
			'orderBy' => $this->formatOrderBy($query->getOrderBy()),
			'limit' => $this->formatLimitOffset($query->getLimit(), $query->getOffset()),
		];

		return $this->renderStatement($this->getStatement(Query::SELECT), $params);
	}

	/**
	 * Build a sub-query.
	 *
	 * @param \Titon\Model\Query\SubQuery $query
	 * @return string
	 */
	public function buildSubQuery(SubQuery $query) {
		$output = sprintf($this->getClause('subQuery'), trim($this->buildSelect($query), ';'));

		if ($alias = $query->getAlias()) {
			$output = sprintf($this->getClause('as'), $output, $this->quote($alias));
		}

		if ($filter = $query->getFilter()) {
			$output = $this->getClause($filter) . ' ' . $output;
		}

		return $output;
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
		$params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::UPDATE));
		$params = $params + [
			'table' => $this->formatTable($query->getTable()),
			'fields' => $this->formatFields($query->getFields(), $query->getType()),
			'where' => $this->formatWhere($query->getWhere()),
			'orderBy' => $this->formatOrderBy($query->getOrderBy()),
			'limit' => $this->formatLimit($query->getLimit()),
		];

		return $this->renderStatement($this->getStatement(Query::UPDATE), $params);
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

			$output = [$this->quote($column), strtoupper($type)];

			if (!empty($options['unsigned'])) {
				$output[] = $this->getClause('unsigned');
			}

			if (!empty($options['zerofill'])) {
				$output[] = $this->getClause('zerofill');
			}

			if (!empty($options['charset'])) {
				$output[] = sprintf($this->getClause('charset'), $options['charset']);
			}

			if (!empty($options['collation'])) {
				$output[] = sprintf($this->getClause('collation'), $options['collation']);
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
			case Query::MULTI_INSERT:
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

					} else if ($field instanceof Query) {
						$columns[] = $this->buildSubQuery($field);

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

			} else if ($value instanceof Query) {
				$value = $this->buildSubQuery($value);

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
			return sprintf($this->getClause('limitOffset'), (int) $limit, (int) $offset);
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
				$value = $param->getValue();
				$isSubQuery = ($value instanceof Query);

				// Function instead of field
				if ($field instanceof Func) {
					$clause = sprintf($this->getClause('expression'), $this->formatFunction($field), $operator);

				// Regular clause
				} else {
					$field = $this->quote($field);

					switch ($operator) {
						case Expr::IN:
						case Expr::NOT_IN:
							if ($isSubQuery) {
								$clause = sprintf($this->getClause($operator), $field, '?');
								$clause = str_replace(['(', ')'], '', $clause);
							} else {
								$clause = sprintf($this->getClause($operator), $field, implode(', ', array_fill(0, count($value), '?')));
							}
						break;
						case Expr::NULL:
						case Expr::NOT_NULL:
						case Expr::BETWEEN:
						case Expr::NOT_BETWEEN:
						case Expr::LIKE:
						case Expr::NOT_LIKE:
						case Expr::REGEXP:
						case Expr::NOT_REGEXP:
						case Expr::RLIKE;
							$clause = sprintf($this->getClause($operator), $field);
						break;
						default:
							$clause = sprintf($this->getClause('expression'), $field, $operator);
						break;
					}

					// Replace ? with sub-query statement
					if ($isSubQuery) {

						// EXISTS and NOT EXISTS doesn't have a field or operator
						if (in_array($value->getFilter(), [SubQuery::EXISTS, SubQuery::NOT_EXISTS])) {
							$clause = $this->buildSubQuery($value);

						} else {
							$clause = str_replace('?', $this->buildSubQuery($value), $clause);
						}
					}
				}

				$output[] = $clause;
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
	 * @param array $options
	 * @return string
	 */
	public function formatTableOptions(array $options) {
		$output = [];

		foreach ($options as $key => $value) {
			if (in_array($key, ['comment', 'defaultComment', 'connection', 'dataDirectory', 'indexDirectory', 'password'])) {
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
			case Query::MULTI_INSERT:
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
	 * @throws \Titon\Model\Exception\InvalidArgumentException
	 */
	public function getAttributes($type) {
		if (isset($this->_attributes[$type])) {
			return $this->_attributes[$type];
		}

		throw new InvalidArgumentException(sprintf('Invalid query type %s', $type));
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
	public function renderAttributes(array $attributes) {
		$output = [];

		foreach ($attributes as $key => $clause) {
			$value = '';

			if ($clause) {
				if (is_string($clause)) {
					$value = $this->getClause($clause);

				} else if ($clause === true) {
					$value = $this->getClause($key);
				}
			}

			$output['a.' . $key] = $value;
		}

		return $output;
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderStatement($statement, array $params) {
		$statement = trim(String::insert($statement, $params, ['escape' => false])) . ';';

		return $statement;
	}

}