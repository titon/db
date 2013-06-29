<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver;

use Titon\Model\Driver;

/**
 * A dialect parses and builds SQL statements specific to a driver.
 *
 * @link http://en.wikipedia.org/wiki/SQL
 *
 * @package Titon\Model\Driver
 */
interface Dialect {

	const AUTO_INCREMENT = 'autoIncrement';
	const ALL = 'all';
	const ALSO = 'and';
	const ANY = 'any';
	const AS_ALIAS = 'as';
	const ASC = 'asc';
	const AVG_ROW_LENGTH = 'avgRowLength';
	const BETWEEN = 'between';
	const BIG_RESULT = 'sqlBigResult';
	const BUFFER_RESULT = 'sqlBufferResult';
	const CACHE = 'sqlCache';
	const CASCADE = 'cascade';
	const CHARACTER_SET = 'characterSet';
	const CHECKSUM = 'checksum';
	const COLLATE = 'collate';
	const COMMENT = 'comment';
	const CONNECTION = 'connection';
	const CONSTRAINT = 'constraint';
	const DATA_DIRECTORY = 'dataDirectory';
	const DEFAULT_CHARACTER_SET = 'defaultCharacterSet';
	const DEFAULT_COMMENT = 'defaultComment';
	const DEFAULT_TO = 'default';
	const DELAYED = 'delayed';
	const DELAY_KEY_WRITE = 'delayKeyWrite';
	const DESC = 'desc';
	const DISTINCT = 'distinct';
	const DISTINCT_ROW = 'distinctRow';
	const EITHER = 'or';
	const ENGINE = 'engine';
	const EXISTS = 'exists';
	const EXPRESSION = 'expression';
	const FOREIGN_KEY = 'foreignKey';
	const FUNC = 'function';
	const GROUP = 'group';
	const GROUP_BY = 'groupBy';
	const HAVING = 'having';
	const HIGH_PRIORITY = 'highPriority';
	const IGNORE = 'ignore';
	const IN = 'in';
	const INDEX = 'index';
	const INDEX_DIRECTORY = 'indexDirectory';
	const INSERT_METHOD = 'insertMethod';
	const IS_NULL = 'isNull';
	const IS_NOT_NULL = 'isNotNull';
	const KEY_BLOCK_SIZE = 'keyBlockSize';
	const LIKE = 'like';
	const LIMIT = 'limit';
	const LIMIT_OFFSET = 'limitOffset';
	const LOW_PRIORITY = 'lowPriority';
	const MAX_ROWS = 'maxRows';
	const MAYBE = 'xor';
	const MIN_ROWS = 'minRows';
	const NO_ACTION = 'noAction';
	const NO_CACHE = 'sqlNoCache';
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
	const PACK_KEYS = 'packKeys';
	const PASSWORD = 'password';
	const PRIMARY_KEY = 'primaryKey';
	const QUICK = 'quick';
	const REGEXP = 'regexp';
	const RESTRICT = 'restrict';
	const RLIKE = 'rlike';
	const ROW_FORMAT = 'rowFormat';
	const SET_NULL = 'setNull';
	const SMALL_RESULT = 'sqlSmallResult';
	const SOME = 'some';
	const STATS_AUTO_RECALC = 'statsAutoRecalc';
	const STATS_PERSISTENT = 'statsPersistent';
	const SUB_QUERY = 'subQuery';
	const TEMPORARY = 'temporary';
	const UNION = 'union';
	const UNIQUE_KEY = 'uniqueKey';
	const UNSIGNED = 'unsigned';
	const WHERE = 'where';
	const ZEROFILL = 'zerofill';

	/**
	 * Return attributes for a query type.
	 *
	 * @param string $type
	 * @return array
	 */
	public function getAttributes($type);

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
	 * @return \Titon\Model\Driver
	 */
	public function getDriver();

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
	 * Return a statement by key.
	 *
	 * @param string $key
	 * @return string
	 */
	public function getStatement($key);

	/**
	 * Return all statements.
	 *
	 * @return string[]
	 */
	public function getStatements();

	/**
	 * Quote an SQL identifier by wrapping with a driver specific character.
	 *
	 * @param string $value
	 * @return string
	 */
	public function quote($value);

	/**
	 * Prepare the list of attributes for rendering.
	 * If an attribute value exists, fetch a matching clause for it.
	 *
	 * @param array $attributes
	 * @return array
	 */
	public function renderAttributes(array $attributes);

	/**
	 * Render the statement by piecing together the parameters.
	 *
	 * @param string $statement
	 * @param array $params
	 * @return string
	 */
	public function renderStatement($statement, array $params);

	/**
	 * Set the driver that this dialect belongs to.
	 *
	 * @param \Titon\Model\Driver $driver
	 * @return \Titon\Model\Driver\Dialect
	 */
	public function setDriver(Driver $driver);

}