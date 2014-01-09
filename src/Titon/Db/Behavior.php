<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

/**
 * A Behavior can be attached to a table and triggered during callbacks to modify functionality.
 *
 * @package Titon\Db
 */
interface Behavior extends Callback {

    /**
     * Return the behavior alias name.
     *
     * @return string
     */
    public function getAlias();

    /**
     * Return the current table.
     *
     * @return \Titon\Db\Table
     */
    public function getTable();

    /**
     * Set the table this behavior is attached to.
     *
     * @param \Titon\Db\Table $table
     * @return \Titon\Db\Behavior
     */
    public function setTable(Table $table);

}