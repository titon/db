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
use Titon\Db\Traits\RepositoryAware;

/**
 * Provides shared functionality for behaviors.
 *
 * @package Titon\Db\Behavior
 */
abstract class AbstractBehavior extends Base implements Behavior, Listener {
    use RepositoryAware;

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
    public function preFind(Event $event, Query $query, $finder) {
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
    public function postFind(Event $event, array &$results, $finder) {
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
            'db.preFind' => 'preFind',
            'db.postFind' => 'postFind'
        ];
    }

}