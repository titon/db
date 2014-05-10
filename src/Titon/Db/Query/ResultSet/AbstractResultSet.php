<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query\ResultSet;

use Titon\Common\Traits\Cacheable;
use Titon\Db\Query\ResultSet;
use Titon\Db\Query;

/**
 * Provides shared functionality for results.
 *
 * @package Titon\Db\Query\ResultSet
 * @codeCoverageIgnore
 */
abstract class AbstractResultSet implements ResultSet {
    use Cacheable;

    /**
     * Affected row count.
     *
     * @type int
     */
    protected $_count = 0;

    /**
     * Has query been executed.
     *
     * @type bool
     */
    protected $_executed = false;

    /**
     * Bound parameters.
     *
     * @type array
     */
    protected $_params = [];

    /**
     * Query object.
     *
     * @type \Titon\Db\Query
     */
    protected $_query;

    /**
     * Was the query execution successful.
     *
     * @type bool
     */
    protected $_success = false;

    /**
     * Execution time in milliseconds.
     *
     * @type int
     */
    protected $_time = 0;

    /**
     * Store the query object.
     *
     * @param \Titon\Db\Query $query
     */
    public function __construct(Query $query = null) {
        $this->_query = $query;
    }

    /**
     * Return all logged values.
     *
     * @return string
     */
    public function __toString() {
        return sprintf('%s %s %s %s',
            '[SQL] ' . $this->getStatement(),
            '[TIME] ' . $this->getExecutionTime(),
            '[COUNT] ' . $this->getRowCount(),
            '[STATE] ' . ($this->hasExecuted() ? 'Executed' : 'Prepared')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function close() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function execute() {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutionTime() {
        return $this->_time;
    }

    /**
     * {@inheritdoc}
     */
    public function getParams() {
        return $this->_params;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery() {
        return $this->_query;
    }

    /**
     * {@inheritdoc}
     */
    public function getRowCount() {
        return $this->_count;
    }

    /**
     * {@inheritdoc}
     */
    public function hasExecuted() {
        return $this->_executed;
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessful() {
        return $this->_success;
    }

}