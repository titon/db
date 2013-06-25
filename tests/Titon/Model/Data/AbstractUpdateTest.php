<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Data;

use Titon\Test\Stub\Model\User;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for database updating.
 */
class AbstractUpdateTest extends TestCase {

	/**
	 * Unload fixtures.
	 */
	protected function tearDown() {
		parent::tearDown();

		$this->unloadFixtures();
	}

	/**
	 * Test basic database record updating.
	 */
	public function testUpdate() {
		$this->loadFixtures('Users');

		$user = new User();
		$data = [
			'country_id' => 3,
			'username' => 'milesj'
		];

		$this->assertTrue($user->update(1, $data));
	}

	/**
	 * Test database record updating with one to one relations.
	 */
	public function testUpdateWithOneToOne() {
		$this->loadFixtures('Users');

		$user = new User();
		$data = [
			'country_id' => 3,
			'username' => 'milesj',
			'Profile' => [
				'id' => 4,
				'lastLogin' => '2012-06-24 17:30:33'
			]
		];

		$this->assertTrue($user->update(1, $data));
	}

}