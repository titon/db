<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

/**
 * A Behavior can be attached to a repository and triggered during callbacks to modify functionality.
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
     * Return the current repository.
     *
     * @return \Titon\Db\Repository
     */
    public function getRepository();

    /**
     * Set the repository this behavior is attached to.
     *
     * @param \Titon\Db\Repository $repo
     * @return $this
     */
    public function setRepository(Repository $repo);

}