<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use PDO;
use Titon\Model\Driver\Type\IntType;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Driver\Type\IntType.
 *
 * @property \Titon\Model\Driver\Type\IntType $object
 */
class IntTypeTest extends TestCase {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new IntType(new DriverStub('default', []));
	}

	/**
	 * Test from database conversion.
	 */
	public function testFrom() {
		$this->assertSame(123, $this->object->from('123.5'));
		$this->assertSame(456, $this->object->from('456'));
		$this->assertSame(666, $this->object->from('666'));
		$this->assertSame(0, $this->object->from('abc'));
		$this->assertSame(0, $this->object->from('true'));
	}

	/**
	 * Test to database conversion.
	 */
	public function testTo() {
		$this->assertSame(123, $this->object->to(123.5));
		$this->assertSame(456, $this->object->to(456));
		$this->assertSame(666, $this->object->to('666'));
		$this->assertSame(0, $this->object->to('abc'));
	}

	/**
	 * Test name string.
	 */
	public function testGetName() {
		$this->assertEquals('int', $this->object->getName());
	}

	/**
	 * Test PDO type.
	 */
	public function testGetBindingType() {
		$this->assertEquals(PDO::PARAM_INT, $this->object->getBindingType());
	}

	/**
	 * Test schema options.
	 */
	public function testGetDefaultOptions() {
		$this->assertEquals([], $this->object->getDefaultOptions());
	}

}