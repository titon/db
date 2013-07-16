<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver;

use Titon\Model\Driver\Type\AbstractType;
use Titon\Model\Driver\Type\BlobType;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for Titon\Model\Driver\Type.
 *
 * @property \Titon\Model\Driver\Type $object
 */
class TypeTest extends TestCase {

	/**
	 * Test factory and exceptions are throwing for missing types.
	 */
	public function testFactory() {
		$driver = new DriverStub('default', []);

		$this->assertInstanceOf('Titon\Model\Driver\Type', AbstractType::factory('int', $driver));
		$this->assertInstanceOf('Titon\Model\Driver\Type', AbstractType::factory('varchar', $driver));
		$this->assertInstanceOf('Titon\Model\Driver\Type', AbstractType::factory('longblob', $driver));

		try {
			$this->assertInstanceOf('Titon\Model\Driver\Type', AbstractType::factory('foobar', $driver));
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}

}