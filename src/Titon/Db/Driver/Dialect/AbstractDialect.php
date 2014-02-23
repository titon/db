<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Dialect;

use Titon\Common\Base;
use Titon\Db\Driver;
use Titon\Db\Driver\Dialect;
use Titon\Db\Driver\Schema;
use Titon\Db\Driver\Type\AbstractType;
use Titon\Db\Exception\InvalidQueryException;
use Titon\Db\Exception\MissingClauseException;
use Titon\Db\Exception\MissingKeywordException;
use Titon\Db\Exception\MissingStatementException;
use Titon\Db\Exception\UnsupportedQueryStatementException;
use Titon\Db\Query;
use Titon\Db\Query\Expr;
use Titon\Db\Query\Func;
use Titon\Db\Query\Predicate;
use Titon\Db\Query\RawExpr;
use Titon\Db\Query\SubQuery;
use Titon\Db\Traits\DriverAware;
use Titon\Utility\String;
use \Closure;

/**
 * Provides shared dialect functionality and support for attribute, keyword, clause and statement formatting.
 *
 * @package Titon\Db\Driver\Dialect
 */
abstract class AbstractDialect extends Base implements Dialect {
    use DriverAware;

    /**
     * Configuration.
     *
     * @type array {
     *      @type string $quoteCharacter    Character used for quoting keywords
     * }
     */
    protected $_config = [
        'quoteCharacter' => '`'
    ];

    /**
     * List of component clauses.
     *
     * @type array
     */
    protected $_clauses = [
        self::AS_ALIAS          => '%s AS %s',
        self::BETWEEN           => '%s BETWEEN ? AND ?',
        self::CHARACTER_SET     => 'CHARACTER SET %s',
        self::COLLATE           => 'COLLATE %s',
        self::COMMENT           => 'COMMENT %s',
        self::CONSTRAINT        => 'CONSTRAINT %s',
        self::DEFAULT_TO        => 'DEFAULT %s',
        self::EXCEPT            => 'EXCEPT {a.flag} %s',
        self::EXPRESSION        => '%s %s ?',
        self::FOREIGN_KEY       => 'FOREIGN KEY (%s) REFERENCES %s(%s)',
        self::FUNC              => '%s(%s)',
        self::GROUP             => '(%s)',
        self::GROUP_BY          => 'GROUP BY %s',
        self::HAVING            => 'HAVING %s',
        self::IN                => '%s IN (%s)',
        self::INDEX             => 'KEY %s (%s)',
        self::INTERSECT         => 'INTERSECT {a.flag} %s',
        self::IS_NULL           => '%s IS NULL',
        self::IS_NOT_NULL       => '%s IS NOT NULL',
        self::JOIN_INNER        => 'INNER JOIN %s ON %s',
        self::JOIN_LEFT         => 'LEFT JOIN %s ON %s',
        self::JOIN_OUTER        => 'FULL OUTER JOIN %s ON %s',
        self::JOIN_RIGHT        => 'RIGHT JOIN %s ON %s',
        self::JOIN_STRAIGHT     => 'STRAIGHT_JOIN %s ON %s',
        self::LIKE              => '%s LIKE ?',
        self::LIMIT             => 'LIMIT %s',
        self::LIMIT_OFFSET      => 'LIMIT %s OFFSET %s',
        self::NOT_BETWEEN       => '%s NOT BETWEEN ? AND ?',
        self::NOT_IN            => '%s NOT IN (%s)',
        self::NOT_LIKE          => '%s NOT LIKE ?',
        self::NOT_REGEXP        => '%s NOT REGEXP ?',
        self::ON_DELETE         => 'ON DELETE %s',
        self::ON_UPDATE         => 'ON UPDATE %s',
        self::ORDER_BY          => 'ORDER BY %s',
        self::PRIMARY_KEY       => 'PRIMARY KEY (%s)',
        self::REGEXP            => '%s REGEXP ?',
        self::RLIKE             => '%s REGEXP ?',
        self::SUB_QUERY         => '(%s)',
        self::UNION             => 'UNION {a.flag} %s',
        self::WHERE             => 'WHERE %s',
        self::UNIQUE_KEY        => 'UNIQUE KEY %s (%s)',
    ];

    /**
     * List of keywords.
     *
     * @type array
     */
    protected $_keywords = [
        self::ALL               => 'ALL',
        self::ALSO              => 'AND',
        self::ANY               => 'ANY',
        self::ASC               => 'ASC',
        self::AUTO_INCREMENT    => 'AUTO_INCREMENT',
        self::CASCADE           => 'CASCADE',
        self::CHARACTER_SET     => 'CHARACTER SET',
        self::CHECKSUM          => 'CHECKSUM',
        self::COLLATE           => 'COLLATE',
        self::DESC              => 'DESC',
        self::DISTINCT          => 'DISTINCT',
        self::EITHER            => 'OR',
        self::ENGINE            => 'ENGINE',
        self::EXCEPT            => 'EXCEPT',
        self::EXISTS            => 'EXISTS',
        self::IGNORE            => 'IGNORE',
        self::INTERSECT         => 'INTERSECT',
        self::MAYBE             => 'XOR',
        self::NO_ACTION         => 'NO ACTION',
        self::NOT_EXISTS        => 'NOT EXISTS',
        self::NOT_NULL          => 'NOT NULL',
        self::NULL              => 'NULL',
        self::PASSWORD          => 'PASSWORD',
        self::RESTRICT          => 'RESTRICT',
        self::SET_NULL          => 'SET NULL',
        self::SOME              => 'SOME',
        self::TEMPORARY         => 'TEMPORARY',
        self::UNION             => 'UNION',
        self::UNSIGNED          => 'UNSIGNED',
        self::ZEROFILL          => 'ZEROFILL'
    ];

    /**
     * List of full SQL statements.
     *
     * @type array
     */
    protected $_statements = [
        Query::INSERT        => 'INSERT INTO {table} {fields} VALUES {values}',
        Query::SELECT        => 'SELECT {fields} FROM {table} {joins} {where} {groupBy} {having} {compounds} {orderBy} {limit}',
        Query::UPDATE        => 'UPDATE {table} {joins} SET {fields} {where} {orderBy} {limit}',
        Query::DELETE        => 'DELETE FROM {table} {joins} {where} {orderBy} {limit}',
        Query::TRUNCATE      => 'TRUNCATE {table}',
        Query::CREATE_TABLE  => "CREATE TABLE IF NOT EXISTS {table} (\n{columns}{keys}\n) {options}",
        Query::CREATE_INDEX  => 'CREATE INDEX {index} ON {table} ({fields})',
        Query::DROP_TABLE    => 'DROP TABLE IF EXISTS {table}',
        Query::DROP_INDEX    => 'DROP INDEX {index} ON {table}'
    ];

    /**
     * Available attributes for each query type.
     *
     * @type array
     */
    protected $_attributes = [];

    /**
     * Store the driver.
     *
     * @param \Titon\Db\Driver $driver
     */
    public function __construct(Driver $driver) {
        parent::__construct();

        $this->setDriver($driver);
    }

    /**
     * Format columns for a table schema.
     *
     * @param \Titon\Db\Driver\Schema $schema
     * @return string
     */
    public function formatColumns(Schema $schema) {
        $columns = [];

        foreach ($schema->getColumns() as $column => $options) {
            $dataType = AbstractType::factory($options['type'], $this->getDriver());

            $options = $options + $dataType->getDefaultOptions();
            $type = $options['type'];

            if (!empty($options['length'])) {
                $type .= '(' . $options['length'] . ')';
            }

            $output = [$this->quote($column), strtoupper($type)];

            // Integers
            if (!empty($options['unsigned'])) {
                $output[] = $this->getKeyword(self::UNSIGNED);
            }

            if (!empty($options['zerofill'])) {
                $output[] = $this->getKeyword(self::ZEROFILL);
            }

            // Strings
            if (!empty($options['charset'])) {
                $output[] = sprintf($this->getClause(self::CHARACTER_SET), $options['charset']);
            }

            if (!empty($options['collate'])) {
                $output[] = sprintf($this->getClause(self::COLLATE), $options['collate']);
            }

            // Primary and uniques can't be null
            if (!empty($options['primary']) || !empty($options['unique'])) {
                $output[] = $this->getKeyword(self::NOT_NULL);
            } else {
                $output[] = $this->getKeyword(empty($options['null']) ? self::NOT_NULL : self::NULL);
            }

            if (array_key_exists('default', $options)) {
                $output[] = $this->formatDefault($options['default']);
            }

            if (!empty($options['ai'])) {
                $output[] = $this->getKeyword(self::AUTO_INCREMENT);
            }

            if (!empty($options['comment'])) {
                $output[] = sprintf($this->getClause(self::COMMENT), $this->getDriver()->escape(substr($options['comment'], 0, 255)));
            }

            $columns[] = trim(implode(' ', $output));
        }

        return implode(",\n", $columns);
    }

    /**
     * Format compound queries: union, intersect, exclude.
     *
     * @param \Titon\Db\Query[] $queries
     * @return string
     * @throws \Titon\Db\Exception\UnsupportedQueryStatementException
     */
    public function formatCompounds(array $queries) {
        if (!$queries) {
            return '';
        }

        if (!method_exists($this, 'buildSelect')) {
            throw new UnsupportedQueryStatementException('Unions require a buildSelect() method');
        }

        $output = [];

        foreach ($queries as $query) {
            $attributes = $query->getAttributes();
            $clause = sprintf($this->getClause($attributes['compound']), trim($this->buildSelect($query), ';'));

            unset($attributes['compound']);
            $attributes = $this->renderAttributes($attributes);

            if (empty($attributes['a.flag'])) {
                $attributes['a.flag'] = '';
            }

            $output[] = str_replace('{a.flag}', $attributes['a.flag'], $clause);
        }

        return implode(' ', $output);
    }

    /**
     * Format the default value of a column.
     *
     * @param mixed $value
     * @return string
     */
    public function formatDefault($value) {
        if ($value === '') {
            return '';
        }

        if ($value instanceof Closure) {
            $value = $value($this);

        } else if (is_string($value) || $value === null) {
            $value = $this->getDriver()->escape($value);
        }

        return sprintf($this->getClause(self::DEFAULT_TO), $value);
    }

    /**
     * Format database expressions.
     *
     * @param \Titon\Db\Query\Expr $expr
     * @return string
     */
    public function formatExpression(Expr $expr) {
        $field = $expr->getField();
        $operator = $expr->getOperator();
        $value = $expr->getValue();

        if ($operator === Expr::AS_ALIAS) {
            return sprintf($this->getClause(self::AS_ALIAS), $this->quote($field), $this->quote($value));

        } else if (!$expr->useValue() && !($operator === Expr::NULL || $operator === Expr::NOT_NULL)) {
            return $this->quote($field);
        }

        $isSubQuery = ($value instanceof SubQuery);

        // Function instead of field
        if ($field instanceof Func) {
            $field = $this->formatFunction($field);

        // Regular clause
        } else {
            $field = $this->quote($field);
        }

        // IN has special case
        if ($operator === Expr::IN || $operator === Expr::NOT_IN) {
            if ($isSubQuery) {
                $clause = sprintf($this->getClause($operator), $field, '?');
                $clause = str_replace(['(', ')'], '', $clause);
            } else {
                $clause = sprintf($this->getClause($operator), $field, implode(', ', array_fill(0, count($value), '?')));
            }

        // Operators with clauses
        } else if ($this->hasClause($operator)) {
            $clause = sprintf($this->getClause($operator), $field);

        // Basic operator
        } else {
            $clause = sprintf($this->getClause(self::EXPRESSION), $field, $operator);
        }

        // Replace ? with sub-query statement
        if ($isSubQuery) {

            // EXISTS and NOT EXISTS doesn't have a field or operator
            if (in_array($value->getFilter(), [SubQuery::EXISTS, SubQuery::NOT_EXISTS], true)) {
                $clause = $this->formatSubQuery($value);

            } else {
                $clause = str_replace('?', $this->formatSubQuery($value), $clause);
            }
        }

        return $clause;
    }

    /**
     * Format the fields structure depending on the type of query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     * @throws \Titon\Db\Exception\InvalidQueryException
     */
    public function formatFields(Query $query) {
        $fields = $query->getFields();
        $joins = $query->getJoins();
        $type = $query->getType();

        switch ($type) {
            case Query::INSERT:
            case Query::MULTI_INSERT:
                if (empty($fields)) {
                    throw new InvalidQueryException('Missing field data for insert query');
                }

                if ($type === Query::MULTI_INSERT) {
                    $fields = $fields[0];
                }

                return '(' . $this->quoteList(array_keys($fields)) . ')';
            break;

            case Query::SELECT:
                if ($joins) {
                    $columns = $this->formatSelectFields($fields, $query->getRepository()->getAlias());

                    foreach ($joins as $join) {
                        $columns = array_merge($columns, $this->formatSelectFields($join->getFields(), $join->getAlias()));
                    }
                } else {
                    $columns = $this->formatSelectFields($fields);
                }

                return implode(', ', $columns);
            break;

            case Query::UPDATE:
                if (empty($fields)) {
                    throw new InvalidQueryException('Missing field data for update query');
                }

                if ($joins) {
                    $values = $this->formatUpdateFields($fields, $query->getRepository()->getAlias());

                    foreach ($joins as $join) {
                        $values = array_merge($values, $this->formatUpdateFields($join->getFields(), $join->getAlias()));
                    }
                } else {
                    $values = $this->formatUpdateFields($fields);
                }

                return implode(', ', $values);
            break;

            case Query::CREATE_INDEX:
                $columns = [];

                foreach ($fields as $column => $data) {
                    if (is_numeric($column)) {
                        $column = $data;
                        $data = [];
                    }

                    if (is_numeric($data)) {
                        $data = ['length' => $data, 'order' => '', 'collate' => ''];
                    } else if (is_string($data)) {
                        $data = ['length' => '', 'order' => $data, 'collate' => ''];
                    }

                    $column = $this->quote($column);

                    if (!empty($data['length'])) {
                        $column .= sprintf($this->getClause(self::GROUP), $data['length']);
                    }

                    if (!empty($data['collate'])) {
                        $column .= ' ' . sprintf($this->getClause(self::COLLATE), $data['collate']);
                    }

                    if (!empty($data['order'])) {
                        $column .= ' ' . $this->getKeyword($data['order']);
                    }

                    $columns[] = $column;
                }

                return implode(', ', $columns);
            break;
        }

        return '';
    }

    /**
     * Format a list of fields for a SELECT statement and apply an optional alias.
     *
     * @param array $fields
     * @param string $alias
     * @return array
     */
    public function formatSelectFields(array $fields, $alias = null) {
        $columns = [];

        if ($alias) {
            $alias = $this->quote($alias) . '.';
        }

        if (empty($fields)) {
            $columns[] = $alias . '*';
        } else {
            foreach ($fields as $field) {
                if ($field instanceof Func) {
                    $columns[] = $this->formatFunction($field);

                } else if ($field instanceof Expr) {
                    $columns[] = $this->formatExpression($field);

                } else if ($field instanceof RawExpr) {
                    $columns[] = $field->getValue(); // must come 2nd

                } else if ($field instanceof SubQuery) {
                    $columns[] = $this->formatSubQuery($field);

                } else if (preg_match('/^(.*?)\s+AS\s+(.*?)$/i', $field, $matches)) {
                    $columns[] = sprintf($this->getClause(self::AS_ALIAS), $alias . $this->quote($matches[1]), $this->quote($matches[2]));

                } else {
                    $columns[] = $alias . $this->quote($field);
                }
            }
        }

        return $columns;
    }

    /**
     * Format a list of fields for a SELECT statement and apply an optional alias.
     *
     * @param array $fields
     * @param string $alias
     * @return array
     */
    public function formatUpdateFields(array $fields, $alias = null) {
        $columns = [];

        if ($alias) {
            $alias = $this->quote($alias) . '.';
        }

        foreach ($fields as $key => $value) {
            if ($value instanceof Expr) {
                $columns[] = $alias . $this->quote($key) . ' = ' . $this->formatExpression($value);
            } else {
                $columns[] = $alias . $this->quote($key) . ' = ?';
            }
        }

        return $columns;
    }

    /**
     * Format a database function.
     *
     * @param \Titon\Db\Query\Func $func
     * @return string
     */
    public function formatFunction(Func $func) {
        $arguments = [];

        foreach ($func->getArguments() as $arg) {
            $type = $arg['type'];
            $value = $arg['value'];

            if ($value instanceof Func) {
                $value = $this->formatFunction($value);

            } else if ($value instanceof SubQuery) {
                $value = $this->formatSubQuery($value);

            } else if ($type === Func::FIELD) {
                $value = $this->quote($value);

            } else if ($type === Func::LITERAL) {
                // Do nothing

            } else if (is_string($value) || $value === null) {
                $value = $this->getDriver()->escape($value);
            }

            $arguments[] = $value;
        }

        $output = sprintf($this->getClause(self::FUNC),
            $func->getName(),
            implode($func->getSeparator(), $arguments)
        );

        if ($alias = $func->getAlias()) {
            $output = sprintf($this->getClause(self::AS_ALIAS), $output, $this->quote($alias));
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
            return sprintf($this->getClause(self::GROUP_BY), $this->quoteList($groupBy));
        }

        return '';
    }

    /**
     * Format the having clause.
     *
     * @param \Titon\Db\Query\Predicate $having
     * @return string
     */
    public function formatHaving(Predicate $having) {
        if ($having->getParams()) {
            return sprintf($this->getClause(self::HAVING), $this->formatPredicate($having));
        }

        return '';
    }

    /**
     * Format the list of joins.
     *
     * @param \Titon\Db\Query\Join[] $joins
     * @return string
     */
    public function formatJoins(array $joins) {
        if ($joins) {
            $output = [];

            foreach ($joins as $join) {
                $conditions = [];

                foreach ($join->getOn() as $pfk => $rfk) {
                    $conditions[] = $this->quote($pfk) . ' = ' . $this->quote($rfk);
                }

                $output[] = sprintf($this->getClause($join->getType()),
                    $this->formatTable($join->getTable(), $join->getAlias()),
                    implode(' ' . $this->getKeyword(self::ALSO) . ' ', $conditions));
            }

            return implode(' ', $output);
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
            return sprintf($this->getClause(self::LIMIT), (int) $limit);
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
            return sprintf($this->getClause(self::LIMIT_OFFSET), (int) $limit, (int) $offset);
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
                    $output[] = $this->quote($field) . ' ' . $this->getKeyword($direction);
                }
            }

            return sprintf($this->getClause(self::ORDER_BY), implode(', ', $output));
        }

        return '';
    }

    /**
     * Format the predicate object by grouping nested predicates and parameters.
     *
     * @param \Titon\Db\Query\Predicate $predicate
     * @return string
     */
    public function formatPredicate(Predicate $predicate) {
        $output = [];

        foreach ($predicate->getParams() as $param) {
            if ($param instanceof Predicate) {
                $output[] = sprintf($this->getClause(self::GROUP), $this->formatPredicate($param));

            } else if ($param instanceof Expr) {
                $output[] = $this->formatExpression($param);
            }
        }

        return implode(' ' . $this->getKeyword($predicate->getType()) . ' ', $output);
    }

    /**
     * Format a sub-query.
     *
     * @param \Titon\Db\Query\SubQuery $query
     * @return string
     * @throws \Titon\Db\Exception\UnsupportedQueryStatementException
     */
    public function formatSubQuery(SubQuery $query) {

        // Reset the alias since statement would have double aliasing
        $alias = $query->getAlias();
        $query->asAlias(null);

        if (method_exists($this, 'buildSelect')) {
            $output = sprintf($this->getClause(self::SUB_QUERY), trim($this->buildSelect($query), ';'));
        } else {
            throw new UnsupportedQueryStatementException('Sub-query building requires a buildSelect() method');
        }

        if ($alias) {
            $output = sprintf($this->getClause(self::AS_ALIAS), $output, $this->quote($alias));
        }

        if ($filter = $query->getFilter()) {
            $output = $this->getKeyword($filter) . ' ' . $output;
        }

        return $output;
    }

    /**
     * Format the table name and alias name.
     *
     * @param string $repo
     * @param string $alias
     * @return string
     * @throws \Titon\Db\Exception\InvalidQueryException
     */
    public function formatTable($repo, $alias = null) {
        if (!$repo) {
            throw new InvalidQueryException('Missing table for query');
        }

        $output = $this->quote($repo);

        if ($alias && $repo !== $alias) {
            $output = sprintf($this->getClause(self::AS_ALIAS), $output, $this->quote($alias));
        }

        return $output;
    }

    /**
     * Format a table foreign key.
     *
     * @param array $data
     * @return string
     */
    public function formatTableForeign(array $data) {
        $ref = explode('.', $data['references']);
        $key = sprintf($this->getClause(self::FOREIGN_KEY), $this->quote($data['column']), $this->quote($ref[0]), $this->quote($ref[1]));

        if ($data['constraint']) {
            $key = sprintf($this->getClause(self::CONSTRAINT), $this->quote($data['constraint'])) . ' ' . $key;
        }

        $actions = $data;
        unset($actions['references'], $actions['constraint'], $actions['column']);

        foreach ($actions as $clause => $action) {
            if (!$action) {
                continue;
            }

            $value = '';

            if ($this->hasKeyword($action)) {
                $value = $this->getKeyword($action);

            } else if (is_string($action)) {
                $value = $this->quote($action);
            }

            $key .= ' ' . sprintf($this->getClause($clause), $value);
        }

        return $key;
    }

    /**
     * Format a table index key.
     *
     * @param string $index
     * @param array $columns
     * @return string
     */
    public function formatTableIndex($index, array $columns) {
        return sprintf($this->getClause(self::INDEX), $this->quote($index), $this->quoteList($columns));
    }

    /**
     * Format table keys (primary, unique and foreign) and indexes.
     *
     * @param \Titon\Db\Driver\Schema $schema
     * @return string
     */
    public function formatTableKeys(Schema $schema) {
        $keys = [];

        if ($primary = $schema->getPrimaryKey()) {
            $keys[] = $this->formatTablePrimary($primary);
        }

        foreach ($schema->getUniqueKeys() as $unique) {
            $keys[] = $this->formatTableUnique($unique);
        }

        foreach ($schema->getForeignKeys() as $foreign) {
            $keys[] = $this->formatTableForeign($foreign);
        }

        $keys = array_filter($keys);

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
            if ($this->hasKeyword($value)) {
                $value = $this->getKeyword($value);
            }

            if ($this->hasClause($key)) {
                $option = sprintf($this->getClause($key), $value);

            } else {
                $option = $this->getKeyword($key);

                if ($value !== true) {
                    $option .= ' ' . $value;
                }
            }

            $output[] = $option;
        }

        return implode(' ', $output);
    }

    /**
     * Format a table primary key.
     *
     * @param array $data
     * @return string
     */
    public function formatTablePrimary(array $data) {
        $key = sprintf($this->getClause(self::PRIMARY_KEY), $this->quoteList($data['columns']));

        if ($data['constraint']) {
            $key = sprintf($this->getClause(self::CONSTRAINT), $this->quote($data['constraint'])) . ' ' . $key;
        }

        return $key;
    }

    /**
     * Format a table unique key.
     *
     * @param array $data
     * @return string
     */
    public function formatTableUnique(array $data) {
        $key = sprintf($this->getClause(self::UNIQUE_KEY), $this->quote($data['index']), $this->quoteList($data['columns']));

        if ($data['constraint']) {
            $key = sprintf($this->getClause(self::CONSTRAINT), $this->quote($data['constraint'])) . ' ' . $key;
        }

        return $key;
    }

    /**
     * Format the fields values structure depending on the type of query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function formatValues(Query $query) {
        $fields = $query->getFields();

        switch ($query->getType()) {
            case Query::INSERT:
                return sprintf($this->getClause(self::GROUP), implode(', ', array_fill(0, count($fields), '?')));
            break;
            case Query::MULTI_INSERT:
                $value = sprintf($this->getClause(self::GROUP), implode(', ', array_fill(0, count($fields[0]), '?')));

                return implode(', ', array_fill(0, count($fields), $value));
            break;
        }

        return '';
    }

    /**
     * Format the where clause.
     *
     * @param \Titon\Db\Query\Predicate $where
     * @return string
     */
    public function formatWhere(Predicate $where) {
        if ($where->getParams()) {
            return sprintf($this->getClause(self::WHERE), $this->formatPredicate($where));
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes($type) {
        return $this->hasAttribute($type) ? $this->_attributes[$type] : [];
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Titon\Db\Exception\MissingClauseException
     */
    public function getClause($key) {
        if ($this->hasClause($key)) {
            return $this->_clauses[$key];
        }

        throw new MissingClauseException(sprintf('Missing clause %s', $key));
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
     * @throws \Titon\Db\Exception\MissingKeywordException
     */
    public function getKeyword($key) {
        if ($this->hasKeyword($key)) {
            return $this->_keywords[$key];
        }

        throw new MissingKeywordException(sprintf('Missing keyword %s', $key));
    }

    /**
     * {@inheritdoc}
     */
    public function getKeywords() {
        return $this->_keywords;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Titon\Db\Exception\MissingStatementException
     */
    public function getStatement($key) {
        if ($this->hasStatement($key)) {
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
    public function hasAttribute($type) {
        return isset($this->_attributes[$type]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasClause($key) {
        return isset($this->_clauses[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasKeyword($key) {
        return isset($this->_keywords[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasStatement($key) {
        return isset($this->_statements[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function quote($value) {
        if (strpos($value, '.') !== false) {
            list($repo, $field) = explode('.', $value);

            if ($field !== '*') {
                $field = $this->quote($field);
            }

            return $this->quote($repo) . '.' . $field;
        }

        $char = $this->getConfig('quoteCharacter');

        if (!$char) {
            return $value;
        }

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
                if ($clause instanceof Closure) {
                    $value = $clause($this);

                } else if (is_string($clause)) {
                    $value = $this->getKeyword($clause);

                } else if ($clause === true) {
                    $value = $this->getKeyword($key);
                }
            }

            $output['a.' . $key] = $value;
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     *
     * @uses Titon\Utility\String
     */
    public function renderStatement($statement, array $params) {
        $statement = trim(String::insert($statement, $params, ['escape' => false])) . ';';

        return $statement;
    }

}