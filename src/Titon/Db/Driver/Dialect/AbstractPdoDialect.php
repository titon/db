<?php
/**
 * @copyright   2010-2014, The Titon Project
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
abstract class AbstractPdoDialect extends AbstractDialect {

    /**
     * Build the CREATE INDEX query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildCreateIndex(Query $query) {
        return $this->renderStatement(Query::CREATE_INDEX, [
            'index' => $this->formatTable($query->getAlias()),
            'table' => $this->formatTable($query->getTable()),
            'fields' => $this->formatFields($query)
        ] + $this->formatAttributes($query->getAttributes()));
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

        return $this->renderStatement(Query::CREATE_TABLE, [
            'table' => $this->formatTable($schema->getTable()),
            'columns' => $this->formatColumns($schema),
            'keys' => $this->formatTableKeys($schema),
            'options' => $this->formatTableOptions($schema->getOptions())
        ] + $this->formatAttributes($query->getAttributes()));
    }

    /**
     * Build the DELETE query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildDelete(Query $query) {
        return $this->renderStatement(Query::DELETE, [
            'table' => $this->formatTable($query->getTable(), $query->getAlias()),
            'joins' => $this->formatJoins($query->getJoins()),
            'where' => $this->formatWhere($query->getWhere()),
            'orderBy' => $this->formatOrderBy($query->getOrderBy()),
            'limit' => $this->formatLimit($query->getLimit()),
        ] + $this->formatAttributes($query->getAttributes()));
    }

    /**
     * Build the DROP INDEX query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildDropIndex(Query $query) {
        return $this->renderStatement(Query::DROP_INDEX, [
            'index' => $this->formatTable($query->getAlias()),
            'table' => $this->formatTable($query->getTable())
        ] + $this->formatAttributes($query->getAttributes()));
    }

    /**
     * Build the DROP TABLE query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildDropTable(Query $query) {
        return $this->renderStatement(Query::DROP_TABLE, [
            'table' => $this->formatTable($query->getTable())
        ] + $this->formatAttributes($query->getAttributes()));
    }

    /**
     * Build the INSERT query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildInsert(Query $query) {
        return $this->renderStatement(Query::INSERT, [
            'table' => $this->formatTable($query->getTable()),
            'fields' => $this->formatFields($query),
            'values' => $this->formatValues($query)
        ] + $this->formatAttributes($query->getAttributes()));
    }

    /**
     * Build the INSERT query with multiple record support.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildMultiInsert(Query $query) {
        return $this->renderStatement(Query::INSERT, [
            'table' => $this->formatTable($query->getTable()),
            'fields' => $this->formatFields($query),
            'values' => $this->formatValues($query)
        ] + $this->formatAttributes($query->getAttributes()));
    }

    /**
     * Build the SELECT query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildSelect(Query $query) {
        return $this->renderStatement(Query::SELECT, [
            'fields' => $this->formatFields($query),
            'table' => $this->formatTable($query->getTable(), $query->getAlias()),
            'joins' => $this->formatJoins($query->getJoins()),
            'where' => $this->formatWhere($query->getWhere()),
            'groupBy' => $this->formatGroupBy($query->getGroupBy()),
            'having' => $this->formatHaving($query->getHaving()),
            'compounds' => $this->formatCompounds($query->getCompounds()),
            'orderBy' => $this->formatOrderBy($query->getOrderBy()),
            'limit' => $this->formatLimitOffset($query->getLimit(), $query->getOffset()),
        ] + $this->formatAttributes($query->getAttributes()));
    }

    /**
     * Build the TRUNCATE query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildTruncate(Query $query) {
        return $this->renderStatement(Query::TRUNCATE, [
            'table' => $this->formatTable($query->getTable())
        ] + $this->formatAttributes($query->getAttributes()));
    }

    /**
     * Build the UPDATE query.
     *
     * @param \Titon\Db\Query $query
     * @return string
     */
    public function buildUpdate(Query $query) {
        return $this->renderStatement(Query::UPDATE, [
            'fields' => $this->formatFields($query),
            'table' => $this->formatTable($query->getTable(), $query->getAlias()),
            'joins' => $this->formatJoins($query->getJoins()),
            'where' => $this->formatWhere($query->getWhere()),
            'orderBy' => $this->formatOrderBy($query->getOrderBy()),
            'limit' => $this->formatLimit($query->getLimit()),
        ] + $this->formatAttributes($query->getAttributes()));
    }

}