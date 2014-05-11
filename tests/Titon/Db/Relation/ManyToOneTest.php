<?php
namespace Titon\Db\Relation;

use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Relation $object
 */
class ManyToOneTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new ManyToOne('Alias', 'Repo');
    }

    public function testGetType() {
        $this->assertEquals('manyToOne', $this->object->getType());
    }

    public function testIsDependent() {
        $this->assertFalse($this->object->isDependent());

        // Cannot be changed
        $this->object->setDependent(true);

        $this->assertFalse($this->object->isDependent());
    }

}