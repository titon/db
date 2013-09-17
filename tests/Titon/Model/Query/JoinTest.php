<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model\Query;

use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Query\Join.
 *
 * @property \Titon\Model\Query\Join $object
 */
class JoinTest extends TestCase {

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new Join(Join::LEFT);
    }

    /**
     * Test table name is set.
     */
    public function testFrom() {
        $this->object->from('users');
        $this->assertEquals('users', $this->object->getTable());
    }

    /**
     * Test alias name is set.
     */
    public function testAlias() {
        $this->object->asAlias('U');
        $this->assertEquals('U', $this->object->getAlias());
    }

    /**
     * Test type is set.
     */
    public function testType() {
        $this->assertEquals(Join::LEFT, $this->object->getType());
    }

    /**
     * Test fields are set.
     */
    public function testFields() {
        $this->object->fields('id', 'username');
        $this->assertEquals(['id', 'username'], $this->object->getFields());

        $this->object->fields(['id', 'user']);
        $this->assertEquals(['id', 'user'], $this->object->getFields());

        $this->object->fields(['user' => 'foo']);
        $this->assertEquals(['user' => 'foo'], $this->object->getFields());
    }

    /**
     * Test on conditions.
     */
    public function testOn() {
        $this->object->on('id', 'id');
        $this->assertEquals(['id' => 'id'], $this->object->getOn());

        $this->object->on(['users.id' => 'profiles.id', 'id' => 'new_id']);
        $this->assertEquals(['id' => 'new_id', 'users.id' => 'profiles.id'], $this->object->getOn());
    }

}