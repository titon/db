<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Db\Query\Expr;
use Titon\Db\Relation\ManyToMany;
use Titon\Db\Relation\OneToOne;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Relation.
 *
 * @property \Titon\Db\Relation\ManyToMany $object
 */
class RelationTest extends TestCase {

    /**
     * Use a ManyToMany for testing as it provides more functionality.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new ManyToMany('User', 'Titon\Test\Stub\Repository\User');
    }

    /**
     * Test alias names.
     */
    public function testAlias() {
        $this->assertEquals('User', $this->object->getAlias());

        $this->object->setAlias('Foobar');
        $this->assertEquals('Foobar', $this->object->getAlias());
    }

    /**
     * Test foreign keys.
     */
    public function testForeignKey() {
        $this->assertEquals(null, $this->object->getForeignKey());

        $this->object->setForeignKey('user_id');
        $this->assertEquals('user_id', $this->object->getForeignKey());
    }

    /**
     * Test table class names.
     */
    public function testClass() {
        $this->assertEquals('Titon\Test\Stub\Repository\User', $this->object->getClass());

        $this->object->setClass('Titon\Test\Stub\Repository\Profile');
        $this->assertEquals('Titon\Test\Stub\Repository\Profile', $this->object->getClass());
    }

    /**
     * Test related foreign keys.
     */
    public function testRelatedForeignKey() {
        $this->assertEquals(null, $this->object->getRelatedForeignKey());

        $this->object->setRelatedForeignKey('profile_id');
        $this->assertEquals('profile_id', $this->object->getRelatedForeignKey());
    }

    /**
     * Test dependencies.
     */
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

    /**
     * Test that conditions modify queries.
     */
    public function testConditions() {
        $query = new Query(Query::SELECT, new User());

        $this->object->setConditions(function() {
            $this->where('status', 1);
        });

        $this->assertEquals([], $query->getWhere()->getParams());

        $query->bindCallback($this->object->getConditions(), $this->object);

        $this->assertEquals([
            'status=1' => new Expr('status', '=', 1)
        ], $query->getWhere()->getParams());
    }

    /**
     * Test junction table class names.
     */
    public function testJunctionClass() {
        $this->assertEquals(null, $this->object->getJunctionClass());

        $this->object->setJunctionClass('Titon\Test\Stub\Repository\Profile');
        $this->assertEquals('Titon\Test\Stub\Repository\Profile', $this->object->getJunctionClass());
    }

}