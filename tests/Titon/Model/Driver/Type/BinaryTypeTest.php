<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use PDO;
use Titon\Model\Driver\Type\BinaryType;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Driver\Type\BinaryType.
 *
 * @property \Titon\Model\Driver\Type\BinaryType $object
 */
class BinaryTypeTest extends TestCase {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new BinaryType(new DriverStub('default', []));
	}

	/**
	 * Test from database conversion.
	 */
	public function testFrom() {
		$this->assertSame('456', $this->object->from('1101000011010100110110'));
		$this->assertSame('xyz', $this->object->from('11110000111100101111010'));
	}

	/**
	 * Test to database conversion.
	 */
	public function testTo() {
		$this->assertSame('1100010011001000110011', $this->object->to('1100010011001000110011'));
		$this->assertSame('1100010011001000110011', $this->object->to(123));
		$this->assertSame('11000010110001001100011', $this->object->to('abc'));
	}

	/**
	 * Test name string.
	 */
	public function testGetName() {
		$this->assertEquals('binary', $this->object->getName());
	}

	/**
	 * Test PDO type.
	 */
	public function testGetBindingType() {
		$this->assertEquals(PDO::PARAM_STR, $this->object->getBindingType());
	}

	/**
	 * Test schema options.
	 */
	public function testGetDefaultOptions() {
		$this->assertEquals([], $this->object->getDefaultOptions());
	}

}