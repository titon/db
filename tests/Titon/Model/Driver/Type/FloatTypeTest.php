<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use Titon\Model\Driver\Type\FloatType;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Driver\Type\FloatType.
 *
 * @property \Titon\Model\Driver\Type\FloatType $object
 */
class FloatTypeTest extends TestCase {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new FloatType();
	}

	/**
	 * Test from database conversion.
	 */
	public function testFrom() {
		$this->assertSame(123.5, $this->object->from('123.5'));
		$this->assertSame(456.0, $this->object->from('456.00'));
		$this->assertSame(666.1337, $this->object->from('666.1337'));
	}

	/**
	 * Test to database conversion.
	 */
	public function testTo() {
		$this->assertSame(123.5, $this->object->to(123.5));
		$this->assertSame(456.0, $this->object->to(456));
		$this->assertSame(666.1337, $this->object->to('666.1337'));
	}

}