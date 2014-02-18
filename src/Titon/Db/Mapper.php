<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

/**
 * A Mapper can be used to modify data before a save, and after a find.
 *
 * @package Titon\Db
 */
interface Mapper {

    /**
     * Modify a list of results after a find.
     *
     * @param array $results
     * @return array
     */
    public function after(array $results);

    /**
     * Modify a record before being save.
     *
     * @param array $data
     * @return array
     */
    public function before(array $data);

    /**
     * Return the current repository.
     *
     * @return \Titon\Db\Repository
     */
    public function getRepository();

    /**
     * Set the repository this data mapper belongs to.
     *
     * @param \Titon\Db\Repository $repo
     * @return $this
     */
    public function setRepository(Repository $repo);

}