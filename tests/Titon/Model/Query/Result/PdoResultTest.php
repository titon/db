<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query\Result;

use Titon\Model\Query;
use Titon\Test\Stub\Model\User;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Query\Result\PdoResult.
 *
 * @property \Titon\Model\Model $object
 */
class PdoResultTest extends TestCase {

	/**
	 * Create a result.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new User();

		$this->loadFixtures('Users');
	}

	/**
	 * Unload fixtures.
	 */
	protected function tearDown() {
		parent::tearDown();

		$this->unloadFixtures();
	}

	/**
	 * Test a count of records is returned.
	 */
	public function testCount() {
		$this->assertEquals(5, $this->object->select()->count());
	}

	/**
	 * Test a single result is returned.
	 */
	public function testFetch() {
		$this->assertSame([
			'id' => 1,
			'country_id' => 1,
			'username' => 'miles',
			'password' => '1Z5895jf72yL77h',
			'email' => 'miles@email.com',
			'firstName' => 'Miles',
			'lastName' => 'Johnson',
			'age' => 25,
			'created' => '1988-02-26 21:22:34',
			'modified' => null
		], $this->object->select()->fetch(false));
	}

	/**
	 * Test all results are returned.
	 */
	public function testFetchAll() {
		$this->assertSame([
			[
				'id' => 1,
				'country_id' => 1,
				'username' => 'miles',
				'password' => '1Z5895jf72yL77h',
				'email' => 'miles@email.com',
				'firstName' => 'Miles',
				'lastName' => 'Johnson',
				'age' => 25,
				'created' => '1988-02-26 21:22:34',
				'modified' => null
			], [
				'id' => 2,
				'country_id' => 3,
				'username' => 'batman',
				'password' => '1Z5895jf72yL77h',
				'email' => 'batman@email.com',
				'firstName' => 'Bruce',
				'lastName' => 'Wayne',
				'age' => 35,
				'created' => '1960-05-11 21:22:34',
				'modified' => null
			], [
				'id' => 3,
				'country_id' => 2,
				'username' => 'superman',
				'password' => '1Z5895jf72yL77h',
				'email' => 'superman@email.com',
				'firstName' => 'Clark',
				'lastName' => 'Kent',
				'age' => 33,
				'created' => '1970-09-18 21:22:34',
				'modified' => null
			], [
				'id' => 4,
				'country_id' => 5,
				'username' => 'spiderman',
				'password' => '1Z5895jf72yL77h',
				'email' => 'spiderman@email.com',
				'firstName' => 'Peter',
				'lastName' => 'Parker',
				'age' => 22,
				'created' => '1990-01-05 21:22:34',
				'modified' => null
			], [
				'id' => 5,
				'country_id' => 4,
				'username' => 'wolverine',
				'password' => '1Z5895jf72yL77h',
				'email' => 'wolverine@email.com',
				'firstName' => 'Logan',
				'lastName' => '',
				'age' => 127,
				'created' => '2000-11-30 21:22:34',
				'modified' => null
			]
		], $this->object->select()->fetchAll(false));
	}

	/**
	 * Test statement params are parsed in.
	 */
	public function testGetStatement() {
		$stmt = $this->object->getDriver()->query($this->object->select('id', 'username')->where('id', 5));

		$this->assertRegExp('/SELECT (`|\")id(`|\"), (`|\")username(`|\") FROM (`|\")users(`|\") WHERE (`|\")id(`|\") = 5;/', $stmt->getStatement());
	}

	/**
	 * Test row count is returned for deletes.
	 */
	public function testSave() {
		$this->assertEquals(5, $this->object->query(Query::DELETE)->save());
	}

}