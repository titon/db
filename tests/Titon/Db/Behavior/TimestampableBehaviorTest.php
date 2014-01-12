<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Db\Entity;
use Titon\Test\Stub\Table\User;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Behavior\TimestampableBehavior.
 *
 * @property \Titon\Db\Table $object
 */
class TimestampableBehaviorTest extends TestCase {

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new User();
    }

    /**
     * Test created timestamp is appended.
     */
    public function testOnCreate() {
        $this->loadFixtures('Users');

        $id = $this->object->create(['username' => 'foo']);

        $this->assertEquals(new Entity([
            'id' => 6,
            'username' => 'foo',
            'created' => null
        ]), $this->object->select('id', 'username', 'created')->where('id', $id)->fetch());

        // Now with behavior
        $this->object->addBehavior(new TimestampableBehavior([
            'createField' => 'created'
        ]));

        $time = time();
        $id = $this->object->create(['username' => 'bar']);

        $this->assertEquals(new Entity([
            'id' => 7,
            'username' => 'bar',
            'created' => date('Y-m-d H:i:s', $time)
        ]), $this->object->select('id', 'username', 'created')->where('id', $id)->fetch());
    }

    /**
     * Test updated timestamp is appended.
     */
    public function testOnUpdated() {
        $this->loadFixtures('Users');

        $this->object->update(1, ['username' => 'foo']);

        $this->assertEquals(new Entity([
            'id' => 1,
            'username' => 'foo',
            'modified' => null
        ]), $this->object->select('id', 'username', 'modified')->where('id', 1)->fetch());

        // Now with behavior
        $this->object->addBehavior(new TimestampableBehavior([
            'updateField' => 'modified'
        ]));

        $time = time();
        $this->object->update(1, ['username' => 'bar']);

        $this->assertEquals(new Entity([
            'id' => 1,
            'username' => 'bar',
            'modified' => date('Y-m-d H:i:s', $time)
        ]), $this->object->select('id', 'username', 'modified')->where('id', 1)->fetch());
    }

}