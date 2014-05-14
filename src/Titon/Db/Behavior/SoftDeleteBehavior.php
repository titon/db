<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Db\Query;
use Titon\Event\Event;
use Titon\Utility\Time;

/**
 * The SoftDeleteBehavior provides a mechanism for soft deleting a record,
 * by marking a record as deleted instead of actually deleting the record.
 *
 * @package Titon\Db\Behavior
 */
class SoftDeleteBehavior extends AbstractBehavior {

    /**
     * Configuration.
     *
     * @type array {
     *      @type string $flagField     The boolean field used to flag a record as deleted
     *      @type string $deleteField   The datetime field used to flag when a record was deleted
     *      @type bool $useFlag         Whether the flag field is required (timestamp field is)
     *      @type bool $filterDeleted   Whether to filter records from find queries
     * }
     */
    protected $_config = [
        'flagField' => 'deleted',
        'deleteField' => 'deleted_at',
        'useFlag' => true,
        'filterDeleted' => true
    ];

    /**
     * Perform a hard delete and delete the actual database record.
     *
     * @param int|int[] $id
     * @return int
     */
    public function hardDelete($id) {
        $repo = $this->getRepository();

        return $repo->query(Query::DELETE)
            ->where($repo->getPrimaryKey(), $id)
            ->save();
    }

    /**
     * Filter out all soft deleted records from a select query if $filterDeleted is true.
     * Only apply the filter if the field being filtered on is not part of the original query.
     *
     * @param \Titon\Event\Event $event
     * @param \Titon\Db\Query $query
     * @param string $finder
     * @return bool
     */
    public function preFind(Event $event, Query $query, $finder) {
        $config = $this->allConfig();

        if ($config['filterDeleted']) {
            $where = $query->getWhere();

            if ($config['useFlag']) {
                if (!$where->hasParam($config['flagField'])) {
                    $query->where($config['flagField'], false);
                }
            } else if (!$where->hasParam($config['deleteField'])) {
                $query->where($config['deleteField'], null);
            }
        }

        return true;
    }

    /**
     * Perform a soft delete before a record is deleted.
     * Return a count of affected row count and exit actual deletion process.
     *
     * @param \Titon\Event\Event $event
     * @param int|\int[] $id
     * @param bool $cascade
     * @return int
     */
    public function preDelete(Event $event, $id, &$cascade) {
        return $this->softDelete($id);
    }

    /**
     * Purge all soft deleted records from the database.
     * If a time frame is provided, delete all records within that time frame.
     * If no time frame is provided, delete all records based on flag or timestamp being not null.
     *
     * @uses \Titon\Utility\Time
     *
     * @param int|string $timeFrame
     * @return int
     */
    public function purgeDeleted($timeFrame) {
        $query = $this->getRepository()->query(Query::DELETE);
        $config = $this->allConfig();

        if ($timeFrame) {
            $query->where($config['deleteField'], '>=', Time::toUnix($timeFrame));

        } else if ($config['useFlag']) {
            $query->where($config['flagField'], true);

        } else {
            $query->where($config['deleteField'], '!=', null);
        }

        return $query->save();
    }

    /**
     * {@inheritdoc}
     */
    public function registerEvents() {
        return [
            'db.preDelete' => 'preDelete',
            'db.preFind' => 'preFind'
        ];
    }

    /**
     * Perform a soft delete by marking a record as deleted and updating a timestamp.
     * Do not delete the actual record.
     *
     * @param int|int[] $id
     * @return int
     */
    public function softDelete($id) {
        $repo = $this->getRepository();

        return $repo->query(Query::UPDATE)
            ->fields([
                $this->getConfig('flagField') => true,
                $this->getConfig('deleteField') => time()
            ])
            ->where($repo->getPrimaryKey(), $id)
            ->save();
    }

}