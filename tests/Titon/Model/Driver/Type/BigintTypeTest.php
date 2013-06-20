<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use Titon\Model\Driver\Type\BigintType;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Driver\Type\BigintType.
 *
 * @property \Titon\Model\Driver\Type\BigintType $object
 */
class BigintTypeTest extends TestCase {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new BigintType();
	}

	/**
	 * Test from database conversion.
	 */
	public function testFrom() {
		$this->assertSame('1337', $this->object->from(1337));
		$this->assertSame('1234567890', $this->object->from(1234567890));
		$this->assertSame('12345678901234567890', $this->object->from('12345678901234567890'));
	}

	/**
	 * Test to database conversion.
	 */
	public function testTo() {
		$this->assertSame('1337', $this->object->to(1337));
		$this->assertSame('1234567890', $this->object->to(1234567890));
		$this->assertSame('12345678901234567890', $this->object->to('12345678901234567890'));
	}

}