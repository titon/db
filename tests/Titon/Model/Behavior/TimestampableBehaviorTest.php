<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opendriver.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Behavior;

use Titon\Test\Stub\Model\User;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Behavior\TimestampableBehavior.
 *
 * @property \Titon\Model\Model $object
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
	 * Unload fixtures.
	 */
	protected function tearDown() {
		parent::tearDown();

		$this->unloadFixtures();
	}

	/**
	 * Test created timestamp is appended.
	 */
	public function testOnCreate() {
		$this->loadFixtures('Users');

		$id = $this->object->create(['username' => 'foo']);

		$this->assertEquals([
			'id' => 6,
			'username' => 'foo',
			'created' => null
		], $this->object->select('id', 'username', 'created')->where('id', $id)->fetch(false));

		// Now with behavior
		$this->object->addBehavior(new TimestampableBehavior());

		$time = time();
		$id = $this->object->create(['username' => 'bar']);

		$this->assertEquals([
			'id' => 7,
			'username' => 'bar',
			'created' => date('Y-m-d H:i:s', $time)
		], $this->object->select('id', 'username', 'created')->where('id', $id)->fetch(false));
	}

	/**
	 * Test updated timestamp is appended.
	 */
	public function testOnUpdated() {
		$this->loadFixtures('Users');

		$this->object->update(1, ['username' => 'foo']);

		$this->assertEquals([
			'id' => 1,
			'username' => 'foo',
			'modified' => null
		], $this->object->select('id', 'username', 'modified')->where('id', 1)->fetch(false));

		// Now with behavior
		$this->object->addBehavior(new TimestampableBehavior([
			'updateField' => 'modified'
		]));

		$time = time();
		$this->object->update(1, ['username' => 'bar']);

		$this->assertEquals([
			'id' => 1,
			'username' => 'bar',
			'modified' => date('Y-m-d H:i:s', $time)
		], $this->object->select('id', 'username', 'modified')->where('id', 1)->fetch(false));
	}

}