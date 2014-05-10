<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Contract;

/**
 * Dictates an object returns a repository for ORM,
 * instead of acting as the repository.
 *
 * @package Titon\Db\Contract
 * @codeCoverageIgnore
 */
interface Relational {

    /**
     * Return a repository object to use for relational mapping.
     *
     * @return \Titon\Db\Repository
     */
    public function getRepository();

}