<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query\ResultSet;

/**
 * The SqlResult is used for debugging purposes where an SQL string statement needs to be logged.
 *
 * @package Titon\Db\Query\ResultSet
 */
class SqlResultSet extends AbstractResultSet {

    /**
     * SQL string.
     *
     * @type string
     */
    protected $_sql;

    /**
     * Store the statement.
     *
     * @param string $sql
     */
    public function __construct($sql) {
        $this->_sql = $sql;
    }

    /**
     * {@inheritdoc}
     */
    public function count() {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function find() {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getStatement() {
        return $this->_sql;
    }

    /**
     * {@inheritdoc}
     */
    public function save() {
        return true;
    }

}