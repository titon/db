<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Model\Relation\ManyToMany;
use Titon\Test\Stub\Model\User;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Relation.
 *
 * @property \Titon\Model\Relation\ManyToMany $object
 */
class RelationTest extends TestCase {

	/**
	 * Use a ManyToMany for testing as it provides more functionality.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new ManyToMany('User', 'Titon\Test\Stub\Model\User');
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
	 * Test model class names.
	 */
	public function testModel() {
		$this->assertEquals('Titon\Test\Stub\Model\User', $this->object->getModel());

		$this->object->setModel('Titon\Test\Stub\Model\Profile');
		$this->assertEquals('Titon\Test\Stub\Model\Profile', $this->object->getModel());
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

		$this->object->setDependent(false);
		$this->assertFalse($this->object->isDependent());
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
			'status=1' => ['field' => 'status', 'value' => 1, 'op' => '=']
		], $query->getWhere()->getParams());
	}

	/**
	 * Test junction model class names.
	 */
	public function testJunctionModel() {
		$this->assertEquals(null, $this->object->getJunctionModel());

		$this->object->setJunctionModel('Titon\Test\Stub\Model\Profile');
		$this->assertEquals('Titon\Test\Stub\Model\Profile', $this->object->getJunctionModel());
	}

}