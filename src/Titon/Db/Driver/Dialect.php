<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver;

use Titon\Db\Driver;
use Titon\Db\Driver\Dialect\Statement;

/**
 * A dialect parses and builds SQL statements specific to a driver.
 *
 * @link http://en.wikipedia.org/wiki/SQL
 *
 * @package Titon\Db\Driver
 * @codeCoverageIgnore
 */
interface Dialect {

    const AUTO_INCREMENT = 'autoIncrement';
    const ALL = 'all';
    const ALSO = 'and';
    const ANY = 'any';
    const AS_ALIAS = 'as';
    const ASC = 'asc';
    const BETWEEN = 'between';
    const CASCADE = 'cascade';
    const CHARACTER_SET = 'characterSet';
    const CHECKSUM = 'checksum';
    const COLLATE = 'collate';
    const COMMENT = 'comment';
    const CONSTRAINT = 'constraint';
    const DEFAULT_TO = 'default';
    const DESC = 'desc';
    const DISTINCT = 'distinct';
    const EITHER = 'or';
    const ENGINE = 'engine';
    const EXCEPT = 'except';
    const EXISTS = 'exists';
    const EXPRESSION = 'expression';
    const FOREIGN_KEY = 'foreignKey';
    const FUNC = 'function';
    const GROUP = 'group';
    const GROUP_BY = 'groupBy';
    const HAVING = 'having';
    const IGNORE = 'ignore';
    const IN = 'in';
    const INDEX = 'index';
    const INTERSECT = 'intersect';
    const IS_NULL = 'isNull';
    const IS_NOT_NULL = 'isNotNull';
    const JOIN = 'join';
    const JOIN_INNER = 'innerJoin';
    const JOIN_LEFT = 'leftJoin';
    const JOIN_OUTER = 'outerJoin';
    const JOIN_RIGHT = 'rightJoin';
    const JOIN_STRAIGHT = 'straightJoin';
    const LIKE = 'like';
    const LIMIT = 'limit';
    const LIMIT_OFFSET = 'limitOffset';
    const MAYBE = 'xor';
    const NEITHER = 'nor';
    const NO_ACTION = 'noAction';
    const NOT = 'not';
    const NOT_BETWEEN = 'notBetween';
    const NOT_EXISTS = 'notExists';
    const NOT_IN = 'notIn';
    const NOT_LIKE = 'notLike';
    const NOT_NULL = 'notNull';
    const NOT_REGEXP = 'notRegexp';
    const NULL = 'null';
    const ON_DELETE = 'onDelete';
    const ON_UPDATE = 'onUpdate';
    const ORDER_BY = 'orderBy';
    const PASSWORD = 'password';
    const PRIMARY_KEY = 'primaryKey';
    const REGEXP = 'regexp';
    const RESTRICT = 'restrict';
    const RLIKE = 'rlike';
    const SET_NULL = 'setNull';
    const SOME = 'some';
    const SUB_QUERY = 'subQuery';
    const TEMPORARY = 'temporary';
    const UNION = 'union';
    const UNIQUE_KEY = 'uniqueKey';
    const UNSIGNED = 'unsigned';
    const WHERE = 'where';
    const ZEROFILL = 'zerofill';

    /**
     * Add a clause.
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function addClause($key, $value);

    /**
     * Add multiple clauses.
     *
     * @param array $values
     * @return $this
     */
    public function addClauses(array $values);

    /**
     * Return a clause by key.
     *
     * @param string $key
     * @return string
     */
    public function getClause($key);

    /**
     * Return all clauses.
     *
     * @return string[]
     */
    public function getClauses();

    /**
     * Return the driver.
     *
     * @return \Titon\Db\Driver
     */
    public function getDriver();

    /**
     * Add a keyword.
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function addKeyword($key, $value);

    /**
     * Add multiple keywords.
     *
     * @param array $values
     * @return $this
     */
    public function addKeywords(array $values);

    /**
     * Return a keyword by key.
     *
     * @param string $key
     * @return string
     */
    public function getKeyword($key);

    /**
     * Return all keywords.
     *
     * @return string[]
     */
    public function getKeywords();

    /**
     * Add a statement.
     *
     * @param string $key
     * @param \Titon\Db\Driver\Dialect\Statement $statement
     * @return $this
     */
    public function addStatement($key, Statement $statement);

    /**
     * Add multiple statements.
     *
     * @param array $statements
     * @return $this
     */
    public function addStatements(array $statements);

    /**
     * Return a statement by key.
     *
     * @param string $key
     * @return \Titon\Db\Driver\Dialect\Statement
     */
    public function getStatement($key);

    /**
     * Return all statements.
     *
     * @return \Titon\Db\Driver\Dialect\Statement[]
     */
    public function getStatements();

    /**
     * Return true if the clause by key exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasClause($key);

    /**
     * Return true if the keyword by key exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasKeyword($key);

    /**
     * Return true if the statement by key exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasStatement($key);

    /**
     * Quote an SQL identifier by wrapping with a driver specific character.
     *
     * @param string $value
     * @return string
     */
    public function quote($value);

    /**
     * Render the statement by piecing together the parameters.
     *
     * @param string $key
     * @param array $params
     * @return string
     */
    public function renderStatement($key, array $params);

    /**
     * Set the driver that this dialect belongs to.
     *
     * @param \Titon\Db\Driver $driver
     * @return $this
     */
    public function setDriver(Driver $driver);

}