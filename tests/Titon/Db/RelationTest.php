<?php
namespace Titon\Db;

use Titon\Db\Query\Expr;
use Titon\Db\Relation\ManyToMany;
use Titon\Db\Relation\OneToOne;
use Titon\Test\Stub\Repository\Profile;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Relation\ManyToMany $object
 */
class RelationTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new ManyToMany('User', 'Titon\Test\Stub\Repository\User');
    }

    public function testAlias() {
        $this->assertEquals('User', $this->object->getAlias());

        $this->object->setAlias('Foobar');
        $this->assertEquals('Foobar', $this->object->getAlias());
    }

    public function testForeignKey() {
        $this->assertEquals(null, $this->object->getForeignKey());

        $this->object->setForeignKey('user_id');
        $this->assertEquals('user_id', $this->object->getForeignKey());
    }

    public function testRelatedForeignKey() {
        $this->assertEquals(null, $this->object->getRelatedForeignKey());

        $this->object->setRelatedForeignKey('profile_id');
        $this->assertEquals('profile_id', $this->object->getRelatedForeignKey());
    }

    public function testDependent() {
        $this->assertTrue($this->object->isDependent());

        // Always true for ManyToMany
        $this->object->setDependent(false);
        $this->assertTrue($this->object->isDependent());

        $o2o = new OneToOne('User', 'Titon\Test\Stub\Repository\User');

        $this->assertTrue($o2o->isDependent());
        $o2o->setDependent(false);
        $this->assertFalse($o2o->isDependent());
    }

    public function testConditions() {
        $query = new Query(Query::SELECT, new User());

        $this->object->setConditions(function(Query $query) {
            $query->where('status', 1);
        });

        $this->assertEquals([], $query->getWhere()->getParams());

        $query->bindCallback($this->object->getConditions(), $this->object);

        $this->assertEquals([
            'status=1' => new Expr('status', '=', 1)
        ], $query->getWhere()->getParams());
    }

    public function testJunctionClass() {
        $this->assertEquals(null, $this->object->getJunctionClass());

        $this->object->setJunctionClass('Titon\Test\Stub\Repository\Profile');
        $this->assertEquals('Titon\Test\Stub\Repository\Profile', $this->object->getJunctionClass());
    }

    public function testSetClass() {
        $this->assertEquals('Titon\Test\Stub\Repository\User', $this->object->getClass());

        $this->object->setClass('Titon\Test\Stub\Repository\Profile');

        $this->assertEquals('Titon\Test\Stub\Repository\Profile', $this->object->getClass());
    }

    public function testSetClassRepository() {
        $this->assertEquals('Titon\Test\Stub\Repository\User', $this->object->getClass());

        $this->object->setClass(new Profile());

        $this->assertEquals('Titon\Test\Stub\Repository\Profile', $this->object->getClass());
    }

    /**
     * @expectedException \Titon\Db\Exception\InvalidRelationStructureException
     */
    public function testSetClassThrowsError() {
        $this->object->setClass(123456);
    }

}