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
 * Provides a set of callbacks that tables and behaviors should implement.
 *
 * @package Titon\Db\Table
 */
interface Callback {

    /**
     * Callback called before a delete query.
     * Modify cascading by overwriting the value.
     * Return a falsey value to stop the process.
     *
     * @param \Titon\Event\Event $event
     * @param int|int[] $id
     * @param bool $cascade
     * @return bool
     */
    public function preDelete(Event $event, $id, &$cascade);

    /**
     * Callback called before a select query.
     * Return an array of data to use instead of the fetch results.
     *
     * @param \Titon\Event\Event $event
     * @param \Titon\Db\Query $query
     * @param string $finder
     * @return bool
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