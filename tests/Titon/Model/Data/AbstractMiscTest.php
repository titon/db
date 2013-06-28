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

/**
 * Test class for misc database functionality.
 */
class AbstractMiscTest extends TestCase {

	/**
	 * Unload fixtures.
	 */
	protected function tearDown() {
		parent::tearDown();

		$this->unloadFixtures();
	}

	/**
	 * Test table creation and deletion.
	 */
	public function testCreateDropTable() {
		$user = new User();

		$sql = sprintf("SELECT COUNT(`table_name`) FROM information_schema.tables WHERE table_schema = 'titon_test' AND table_name = '%s';", $user->getTable());

		$this->assertEquals(0, $user->getDriver()->query($sql)->count());

		$user->createTable();

		$this->assertEquals(1, $user->getDriver()->query($sql)->count());

		$user->query(Query::DROP_TABLE)->save();

		$this->assertEquals(0, $user->getDriver()->query($sql)->count());
	}

	/**
	 * Test table truncation.
	 */
	public function testTruncateTable() {
		$this->loadFixtures('Users');

		$user = new User();

		$this->assertEquals(5, $user->select()->count());

		$user->query(Query::TRUNCATE)->save();

		$this->assertEquals(0, $user->select()->count());
	}

	/**
	 * Test table describing.
	 */
	public function testDescribeTable() {
		$this->loadFixtures('Users');

		$user = new User();

		$this->assertEquals(10, count($user->getSchema()->getColumns()));

		$this->assertEquals(10, count($user->query(Query::DESCRIBE)->fetchAll(false)));
	}

}