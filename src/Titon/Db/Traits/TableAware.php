<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Traits;

use Titon\Db\Table;

/**
 * Permits a class to interact with a table.
 *
 * @package Titon\Db\Traits
 */
trait TableAware {

    /**
     * Table object instance.
     *
     * @type \Titon\Db\Table
     */
    protected $_table;

    /**
     * Return the table.
     *
     * @return \Titon\Db\Table
     */
    final public function getTable() {
        return $this->_table;
    }

    /**
     * Set the table.
     *
     * @param \Titon\Db\Table $table
     * @return \Titon\Db\Traits\TableAware
     */
    final public function setTable(Table $table) {
        $this->_table = $table;

        return $this;
    }

}