<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query;

/**
 * Permits an object to set an alias name.
 *
 * @package Titon\Db\Query
 */
trait AliasAware {

    /**
     * Alias name.
     *
     * @type string
     */
    protected $_alias;

    /**
     * Set the alias name.
     *
     * @param string $alias
     * @return $this
     */
    public function asAlias($alias) {
        $this->_alias = $alias;

        return $this;
    }

    /**
     * Return the alias name.
     *
     * @return string
     */
    public function getAlias() {
        return $this->_alias;
    }

}