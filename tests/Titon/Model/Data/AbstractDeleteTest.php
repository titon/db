<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Data;

use Titon\Model\Query;
use Titon\Test\Stub\Model\User;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for database record deleting.
 */
class AbstractDeleteTest extends TestCase {

	/**
	 * Unload fixtures.
	 */
	protected function tearDown() {
		parent::tearDown();

		$this->unloadFixtures();
	}

	/**
	 * Test single record deletion.
	 */
	public function testDelete() {
		$this->loadFixtures('Users');

		$user = new User();

		$this->assertTrue($user->exists(1));

		$user->delete(1, false);

		$this->assertFalse($user->exists(1));
	}

	/**
	 * Test delete with where conditions.
	 */
	public function testDeleteConditions() {
		$this->loadFixtures('Users');

		$user = new User();

		$this->assertSame(5, $user->select()->count());
		$this->assertSame(3, $user->query(Query::DELETE)->where('age', '>', 30)->save());
		$this->assertSame(2, $user->select()->count());
	}

	/**
	 * Test delete with ordering.
	 */
	public function testDeleteOrdering() {
		$this->loadFixtures('Users');

		$user = new User();

		$this->assertEquals([
			['id' => 1, 'username' => 'miles'],
			['id' => 2, 'username' => 'batman'],
			['id' => 3, 'username' => 'superman'],
			['id' => 4, 'username' => 'spiderman'],
			['id' => 5, 'username' => 'wolverine']
		], $user->select('id', 'username')->orderBy('id', 'asc')->fetchAll(false));

		$this->assertSame(3, $user->query(Query::DELETE)->orderBy('age', 'asc')->limit(3)->save());

		$this->assertEquals([
			['id' => 2, 'username' => 'batman'],
			['id' => 5, 'username' => 'wolverine']
		], $user->select('id', 'username')->orderBy('id', 'asc')->fetchAll(false));
	}

	/**
	 * Test delete with a limit applied.
	 */
	public function testDeleteLimit() {
		$this->loadFixtures('Users');

		$user = new User();

		$this->assertEquals([
			['id' => 2, 'username' => 'batman'],
			['id' => 1, 'username' => 'miles'],
			['id' => 4, 'username' => 'spiderman'],
			['id' => 3, 'username' => 'superman'],
			['id' => 5, 'username' => 'wolverine']
		], $user->select('id', 'username')->fetchAll(false));

		$this->assertSame(2, $user->query(Query::DELETE)->limit(2)->save());

		$this->assertEquals([
			['id' => 4, 'username' => 'spiderman'],
			['id' => 3, 'username' => 'superman'],
			['id' => 5, 'username' => 'wolverine']
		], $user->select('id', 'username')->fetchAll(false));
	}

}