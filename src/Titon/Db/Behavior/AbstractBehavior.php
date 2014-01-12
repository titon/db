<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Common\Base;
use Titon\Event\Event;
use Titon\Event\Listener;
use Titon\Db\Behavior;
use Titon\Db\Query;
use Titon\Db\Traits\TableAware;

/**
 * Provides shared functionality for behaviors.
 *
 * @package Titon\Db\Behavior
 */
abstract class AbstractBehavior extends Base implements Behavior, Listener {
    use TableAware;

    /**
     * {@inheritdoc}
     */
    public function getAlias() {
        return str_replace('Behavior', '', $this->info->shortClassName);
    }

    /**
     * {@inheritdoc}
     */
    public function preDelete(Event $event, $id, &$cascade) {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function preFetch(Event $event, Query $query, $fetchType) {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function preSave(Event $event, $id, array &$data) {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function postDelete(Event $event, $id) {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function postFetch(Event $event, array &$results, $fetchType) {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function postSave(Event $event, $id, $created = false) {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function registerEvents() {
        return [
            'db.preSave' => 'preSave',
            'db.postSave' => 'postSave',
            'db.preDelete' => 'preDelete',
            'db.postDelete' => 'postDelete',
            'db.preFetch' => 'preFetch',
            'db.postFetch' => 'postFetch'
        ];
    }

}