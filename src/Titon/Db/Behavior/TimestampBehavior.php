<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Event\Event;

/**
 * The TimestampBehavior will update a field with a timestamp anytime a record is created or updated.
 *
 * @package Titon\Db\Behavior
 */
class TimestampBehavior extends AbstractBehavior {

    /**
     * Configuration.
     *
     * @type array {
     *      @type string $createField    Field to update when a record is created
     *      @type string $updateField    Field to update when a record is updated
     * }
     */
    protected $_config = [
        'createField' => 'created_at',
        'updateField' => 'updated_at'
    ];

    /**
     * Append the current timestamp to the data.
     *
     * @param \Titon\Event\Event $event
     * @param int|int[] $id
     * @param array $data
     * @return bool
     */
    public function preSave(Event $event, $id, array &$data) {
        $data[$this->getConfig($id ? 'updateField' : 'createField')] = time();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function registerEvents() {
        return [
            'db.preSave' => 'preSave'
        ];
    }

}