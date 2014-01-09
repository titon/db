<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Dialect;

use Titon\Db\Exception\InvalidSchemaException;
use Titon\Db\Query;

/**
 * Provides statement building for PDO/SQL based drivers.
 *
 * @package Titon\Db\Driver\Dialect
 */
class AbstractPdoDialect extends AbstractDialect {

    /**
     * Build the CREATE INDEX query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildCreateIndex(Query $query) {
        $params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::CREATE_INDEX));
        $params = $params + [
            'index' => $this->formatTable($query->getAlias()),
            'table' => $this->formatTable($query->getTable()),
            'fields' => $this->formatFields($query)
        ];

        return $this->renderStatement($this->getStatement(Query::CREATE_INDEX), $params);
    }

    /**
     * Build the CREATE TABLE query. Requires a table schema object.
     *
     * @param \Titon\Db\Query $query
     * @return string
     * @throws \Titon\Db\Exception\InvalidSchemaException
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
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildDelete(Query $query) {
        $params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::DELETE));
        $params = $params + [
            'table' => $this->formatTable($query->getTable(), $query->getAlias()),
            'joins' => $this->formatJoins($query->getJoins()),
            'where' => $this->formatWhere($query->getWhere()),
            'orderBy' => $this->formatOrderBy($query->getOrderBy()),
            'limit' => $this->formatLimit($query->getLimit()),
        ];

        return $this->renderStatement($this->getStatement(Query::DELETE), $params);
    }

    /**
     * Build the DROP INDEX query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildDropIndex(Query $query) {
        $params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::DROP_INDEX));
        $params = $params + [
            'index' => $this->formatTable($query->getAlias()),
            'table' => $this->formatTable($query->getTable())
        ];

        return $this->renderStatement($this->getStatement(Query::DROP_INDEX), $params);
    }

    /**
     * Build the DROP TABLE query.
     *
     * @param \Titon\Db\Query $query
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
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildInsert(Query $query) {
        $params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::INSERT));
        $params = $params + [
            'table' => $this->formatTable($query->getTable()),
            'fields' => $this->formatFields($query),
            'values' => $this->formatValues($query)
        ];

        return $this->renderStatement($this->getStatement(Query::INSERT), $params);
    }

    /**
     * Build the INSERT query with multiple record support.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildMultiInsert(Query $query) {
        $params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::INSERT));
        $params = $params + [
            'table' => $this->formatTable($query->getTable()),
            'fields' => $this->formatFields($query),
            'values' => $this->formatValues($query)
        ];

        return $this->renderStatement($this->getStatement(Query::INSERT), $params);
    }

    /**
     * Build the SELECT query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildSelect(Query $query) {
        $params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::SELECT));
        $params = $params + [
            'fields' => $this->formatFields($query),
            'table' => $this->formatTable($query->getTable(), $query->getAlias()),
            'joins' => $this->formatJoins($query->getJoins()),
            'where' => $this->formatWhere($query->getWhere()),
            'groupBy' => $this->formatGroupBy($query->getGroupBy()),
            'having' => $this->formatHaving($query->getHaving()),
            'orderBy' => $this->formatOrderBy($query->getOrderBy()),
            'limit' => $this->formatLimitOffset($query->getLimit(), $query->getOffset()),
        ];

        return $this->renderStatement($this->getStatement(Query::SELECT), $params);
    }

    /**
     * Build the TRUNCATE query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildTruncate(Query $query) {
        $params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::TRUNCATE));
        $params = $params + [
            'table' => $this->formatTable($query->getTable())
        ];

        return $this->renderStatement($this->getStatement(Query::TRUNCATE), $params);
    }

    /**
     * Build the UPDATE query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildUpdate(Query $query) {
        $params = $this->renderAttributes($query->getAttributes() + $this->getAttributes(Query::UPDATE));
        $params = $params + [
            'fields' => $this->formatFields($query),
            'table' => $this->formatTable($query->getTable(), $query->getAlias()),
            'joins' => $this->formatJoins($query->getJoins()),
            'where' => $this->formatWhere($query->getWhere()),
            'orderBy' => $this->formatOrderBy($query->getOrderBy()),
            'limit' => $this->formatLimit($query->getLimit()),
        ];

        return $this->renderStatement($this->getStatement(Query::UPDATE), $params);
    }

}