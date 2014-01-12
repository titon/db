<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query\Result;

use Titon\Db\Query;
use Titon\Db\Query\Result;
use \PDO;
use \PDOStatement;
use \PDOException;

/**
 * Accepts a PDOStatement instance which is used for result fetching and query profiling.
 *
 * @package Titon\Db\Query\Result
 */
class PdoResult extends AbstractResult implements Result {

    /**
     * PDOStatement instance.
     *
     * @type \PDOStatement
     */
    protected $_statement;

    /**
     * Store the statement.
     *
     * @param \PDOStatement $statement
     * @param \Titon\Db\Query $query
     */
    public function __construct(PDOStatement $statement, Query $query = null) {
        $this->_statement = $statement;

        if (isset($statement->params)) {
            $this->_params = $statement->params;
        }

        parent::__construct($query);
    }

    /**
     * {@inheritdoc}
     */
    public function close() {
        return $this->_statement->closeCursor();
    }

    /**
     * {@inheritdoc}
     */
    public function count() {
        $this->execute();

        $count = (int) $this->_statement->fetchColumn();

        $this->close();

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function execute() {
        if ($this->hasExecuted()) {
            return $this;
        }

        $startTime = microtime();

        if ($this->_statement->execute()) {
            if (preg_match('/^(update|insert|delete)/i', $this->_statement->queryString)) {
                $this->_count = $this->_statement->rowCount();
            } else {
                $this->_count = 1;
            }

            $this->_time = number_format(microtime() - $startTime, 5);
            $this->_success = true;
        }

        $this->_executed = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch() {
        $results = $this->fetchAll();

        if (isset($results[0])) {
            return $results[0];
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll() {
        $this->execute();

        $statement = $this->_statement;
        $columnMeta = [];
        $columnCount = $statement->columnCount();

        if ($columnCount) {
            foreach (range(0, $columnCount - 1) as $index) {
                try {
                    $columnMeta[] = $statement->getColumnMeta($index);
                } catch (PDOException $e) {
                    $columnMeta = [];
                    break;
                }
            }
        }

        // Fetch associated records if no meta data
        // This solves issues in SQLite where meta fails on complex records
        if (empty($columnMeta)) {
            $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Else use the meta data for building
        } else {
            $aliasMap = $this->_mapAliases();
            $results = [];

            while ($row = $statement->fetch(PDO::FETCH_NUM)) {
                $joins = [];
                $result = [];

                foreach ($row as $index => $value) {
                    $column = $columnMeta[$index];
                    $name = $column['name'];
                    $alias = '';

                    // For drivers that alias fields as Alias__column
                    if (strpos($name, '__') !== false) {
                        list($alias, $name) = explode('__', $name, 2);

                        if (empty($column['table'])) {
                            $column['table'] = $alias;
                        }
                    }

                    if (isset($column['table'])) {
                        // For drivers that only return the table
                        if (isset($aliasMap[$column['table']])) {
                            $alias = $aliasMap[$column['table']];

                        // For drivers that return the actual alias
                        } else {
                            $alias = $column['table'];
                        }
                    }

                    $joins[$alias][$name] = $value;
                }

                foreach ($joins as $join => $data) {
                    if (empty($result)) {
                        $result = $data;

                    // Aliased/count sometimes fields don't have a table
                    } else if (empty($join)) {
                        $result = array_merge($result, $data);

                    } else {
                        $result[$join] = $data;
                    }
                }

                $results[] = $result;
            }
        }

        $this->close();

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatement() {
        return $this->cache(__METHOD__, function() {
            $statement = preg_replace("/ {2,}/", " ", $this->_statement->queryString); // Trim spaces

            foreach ($this->getParams() as $param) {
                switch ($param[1]) {
                    case PDO::PARAM_NULL:    $value = 'NULL'; break;
                    case PDO::PARAM_INT:
                    case PDO::PARAM_BOOL:    $value = (int) $param[0]; break;
                    default:                 $value = "'" . (string) $param[0] . "'"; break;
                }

                $statement = preg_replace('/\?/', $value, $statement, 1);
            }

            return $statement;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function save() {
        $this->execute()->close();

        if ($this->isSuccessful()) {
            return $this->_count;
        }

        return false;
    }

    /**
     * Return a mapping of table to alias for the primary table and joins.
     *
     * @return array
     */
    protected function _mapAliases() {
        return $this->cache(__METHOD__, function() {
            $query = $this->getQuery();

            if (!$query) {
                return [];
            }

            $alias = $query->getAlias();
            $map = [
                $query->getTableName() => $alias,
                strtolower($alias) => $alias
            ];

            foreach ($query->getJoins() as $join) {
                $joinAlias = $join->getAlias();

                $map[$join->getTableName()] = $joinAlias;
                $map[strtolower($joinAlias)] = $joinAlias;
            }

            return $map;
        });
    }

}