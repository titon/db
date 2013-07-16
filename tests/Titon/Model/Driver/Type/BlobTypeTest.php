<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use PDO;
use Titon\Model\Driver\Type\BlobType;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Driver\Type\BlobType.
 *
 * @property \Titon\Model\Driver\Type\BlobType $object
 */
class BlobTypeTest extends TestCase {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new BlobType(new DriverStub('default', []));
	}

	/**
	 * Test from database conversion.
	 */
	public function testFrom() {
		$this->assertSame(null, $this->object->from(null));
		$this->assertInternalType('resource', $this->object->from('This is loading from a file handle'));
	}

	/**
	 * Test to database conversion.
	 */
	public function testTo() {
		$this->assertInternalType('resource', $this->object->to(fopen(TEMP_DIR . '/blob.txt', 'r')));
	}

	/**
	 * Test name string.
	 */
	public function testGetName() {
		$this->assertEquals('blob', $this->object->getName());
	}

	/**
	 * Test PDO type.
	 */
	public function testGetBindingType() {
		$this->assertEquals(PDO::PARAM_LOB, $this->object->getBindingType());
	}

	/**
	 * Test schema options.
	 */
	public function testGetDefaultOptions() {
		$this->assertEquals([], $this->object->getDefaultOptions());
	}

}