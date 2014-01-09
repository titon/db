<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Closure;

/**
 * Represents a relation of one table to another.
 *
 * @package Titon\Db
 */
interface Relation {

    const ONE_TO_ONE = 'oneToOne'; // Has One
    const ONE_TO_MANY = 'oneToMany'; // Has Many
    const MANY_TO_ONE = 'manyToOne'; // Belongs To
    const MANY_TO_MANY = 'manyToMany'; // Has And Belongs To Many

    /**
     * Return the relation alias name.
     *
     * @return string
     */
    public function getAlias();

    /**
     * Return the class name of the related table.
     *
     * @return string
     */
    public function getClass();

    /**
     * Return the query conditions.
     *
     * @return \Closure
     */
    public function getConditions();

    /**
     * Return the name of the foreign key.
     *
     * @return string
     */
    public function getForeignKey();

    /**
     * Return the primary table instance.
     *
     * @return \Titon\Db\Table
     */
    public function getTable();

    /**
     * Return the name of the related foreign key.
     *
     * @return string
     */
    public function getRelatedForeignKey();

    /**
     * Return the related table instance.
     *
     * @return \Titon\Db\Table
     */
    public function getRelatedTable();

    /**
     * Return the type of relation.
     *
     * @return string
     */
    public function getType();

    /**
     * Return true if the relation is dependent to the parent.
     *
     * @return bool
     */
    public function isDependent();

    /**
     * Set the alias name.
     *
     * @param string $alias
     * @return \Titon\Db\Relation
     */
    public function setAlias($alias);

    /**
     * Set the related class name.
     *
     * @param string $class
     * @return \Titon\Db\Relation
     */
    public function setClass($class);

    /**
     * Set the query conditions for this relation.
     *
     * @param \Closure $callback
     * @return \Titon\Db\Relation
     */
    public function setConditions(Closure $callback);

    /**
     * Set relation dependency.
     *
     * @param bool $state
     * @return \Titon\Db\Relation
     */
    public function setDependent($state);

    /**
     * Set the foreign key for the current table.
     *
     * @param string $key
     * @return \Titon\Db\Relation
     */
    public function setForeignKey($key);

    /**
     * Set the primary table instance.
     *
     * @param \Titon\Db\Table $table
     * @return \Titon\Db\Relation
     */
    public function setTable(Table $table);

    /**
     * Set the foreign key for the related table.
     *
     * @param string $key
     * @return \Titon\Db\Relation
     */
    public function setRelatedForeignKey($key);

}