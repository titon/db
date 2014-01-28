<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Traits;

use Titon\Db\Repository;

/**
 * Permits a class to interact with a table.
 *
 * @package Titon\Db\Traits
 */
trait RepositoryAware {

    /**
     * Repository object instance.
     *
     * @type \Titon\Db\Repository
     */
    protected $_repository;

    /**
     * Return the table.
     *
     * @return \Titon\Db\Repository
     */
    public function getRepository() {
        return $this->_repository;
    }

    /**
     * Set the table.
     *
     * @param \Titon\Db\Repository $repository
     * @return \Titon\Db\Traits\RepositoryAware
     */
    public function setRepository(Repository $repository) {
        $this->_repository = $repository;

        return $this;
    }

}