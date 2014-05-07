<?php
namespace Titon\Db\Query;

use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Query\Join $object
 */
class JoinTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new Join(Join::LEFT);
    }

    public function testFrom() {
        $this->object->from('users');
        $this->assertEquals('users', $this->object->getTable());
    }

    public function testAlias() {
        $this->object->asAlias('U');
        $this->assertEquals('U', $this->object->getAlias());
    }

    public function testType() {
        $this->assertEquals(Join::LEFT, $this->object->getType());
    }

    public function testFields() {
        $this->object->fields('id', 'username');
        $this->assertEquals(['id', 'username'], $this->object->getFields());

        $this->object->fields(['id', 'user']);
        $this->assertEquals(['id', 'user'], $this->object->getFields());

        $this->object->fields(['user' => 'foo']);
        $this->assertEquals(['user' => 'foo'], $this->object->getFields());
    }

    public function testOn() {
        $this->object->on('id', 'id');
        $this->assertEquals(['id' => 'id'], $this->object->getOn());

        $this->object->on(['users.id' => 'profiles.id', 'id' => 'new_id']);
        $this->assertEquals(['id' => 'new_id', 'users.id' => 'profiles.id'], $this->object->getOn());
    }

}