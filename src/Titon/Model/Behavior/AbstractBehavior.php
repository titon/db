<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model\Behavior;

use Titon\Common\Base;
use Titon\Event\Event;
use Titon\Event\Listener;
use Titon\Model\Behavior;
use Titon\Model\Model;
use Titon\Model\Query;
use Titon\Model\Traits\ModelAware;

/**
 * Provides shared functionality for behaviors.
 *
 * @package Titon\Model\Behavior
 */
abstract class AbstractBehavior extends Base implements Behavior, Listener {
    use ModelAware;

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
            'model.preSave' => 'preSave',
            'model.postSave' => 'postSave',
            'model.preDelete' => 'preDelete',
            'model.postDelete' => 'postDelete',
            'model.preFetch' => 'preFetch',
            'model.postFetch' => 'postFetch'
        ];
    }

}