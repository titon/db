<?php
namespace Titon\Db\Relation;

use Titon\Db\Repository;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Relation\ManyToMany $object
 */
class ManyToManyTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new ManyToMany('Alias', 'Repo');
        $this->object->setRepository(new Repository());
    }

    public function testGetType() {
        $this->assertEquals('manyToMany', $this->object->getType());
    }

    public function testIsDependent() {
        $this->assertTrue($this->object->isDependent());

        // Cannot be changed
        $this->object->setDependent(false);

        $this->assertTrue($this->object->isDependent());
    }

    public function testJunctionAlias() {
        $this->assertEquals('', $this->object->getJunctionAlias());

        $this->object->setJunctionAlias('Junction');

        $this->assertEquals('Junction', $this->object->getJunctionAlias());
    }

    public function testJunctionClass() {
        $this->assertEquals('', $this->object->getJunctionClass());

        $this->object->setJunctionClass('Junction\Class');

        $this->assertEquals('Junction\Class', $this->object->getJunctionClass());
        $this->assertEquals('Class', $this->object->getJunctionAlias());
    }

    public function testJunctionRepository() {
        $repo = new Repository();

        $this->object->setJunctionAlias('Junction');
        $this->object->setJunctionRepository($repo);

        $this->assertSame($repo, $this->object->getJunctionRepository());
    }

    public function testSetJunctionClassRepository() {
        $this->object->setJunctionClass(new User());

        $this->assertEquals('Titon\Test\Stub\Repository\User', $this->object->getJunctionClass());
        $this->assertEquals('User', $this->object->getJunctionAlias());
    }

    /**
     * @expectedException \Titon\Db\Exception\InvalidRelationStructureException
     */
    public function testSetJunctionClassThrowsError() {
        $this->object->setJunctionClass(123456);
    }

}