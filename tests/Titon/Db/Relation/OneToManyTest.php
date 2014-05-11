<?php
namespace Titon\Db\Relation;

use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Relation $object
 */
class OneToManyTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new OneToMany('Alias', 'Repo');
    }

    public function testGetType() {
        $this->assertEquals('oneToMany', $this->object->getType());
    }

    public function testIsDependent() {
        $this->assertTrue($this->object->isDependent());

        $this->object->setDependent(false);

        $this->assertFalse($this->object->isDependent());
    }

}