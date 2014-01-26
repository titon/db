<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Event\Event;
use Titon\Db\Query;

/**
 * Provides a set of callbacks that repositories and behaviors should implement.
 *
 * @package Titon\Db\Repository
 */
interface Callback {

    /**
     * Callback called before a delete query.
     * Modify cascading by overwriting the value.
     *
     * @param \Titon\Event\Event $event
     * @param int|int[] $id
     * @param bool $cascade
     * @return bool|int
     *      If true is returned, continue with the delete
     *      If false is returned, exit the delete
     *      if int is returned, exit the delete with the count
     */
    public function preDelete(Event $event, $id, &$cascade);

    /**
     * Callback called before a select query.
     * Return an array of data to use instead of the fetch results.
     *
     * @param \Titon\Event\Event $event
     * @param \Titon\Db\Query $query
     * @param string $finder
     * @return bool|array
     *      If false is returned, return empty results
     *      If an array is returned, use the array as the results
     */
    public function preFind(Event $event, Query $query, $finder);

    /**
     * Callback called before an insert or update query.
     * Return a falsey value to stop the process.
     *
     * @param \Titon\Event\Event $event
     * @param int|int[] $id
     * @param array $data
     * @return bool
     *      If false is returned, return a 0 (no affected records) and exit the save
     */
    public function preSave(Event $event, $id, array &$data);

    /**
     * Callback called after a delete query.
     *
     * @param \Titon\Event\Event $event
     * @param int|int[] $id
     */
    public function postDelete(Event $event, $id);

    /**
     * Callback called after a select query.
     *
     * @param \Titon\Event\Event $event
     * @param array $results
     * @param string $finder
     */
    public function postFind(Event $event, array &$results, $finder);

    /**
     * Callback called after an insert or update query.
     *
     * @param \Titon\Event\Event $event
     * @param int|int[] $id
     * @param bool $created If the record was created
     */
    public function postSave(Event $event, $id, $created = false);

}