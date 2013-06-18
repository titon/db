<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use Titon\Model\Driver\Type\CharType;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Driver\Type\CharType.
 *
 * @property \Titon\Model\Driver\Type\CharType $object
 */
class CharTypeTest extends TestCase {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new CharType();
	}

	/**
	 * Test from database conversion.
	 */
	public function testFrom() {
		$this->assertSame('123', $this->object->from(123));
		$this->assertSame('abc', $this->object->from('abc'));
		$this->assertSame('1', $this->object->from(true));
		$this->assertSame('', $this->object->from(false));
		$this->assertSame('', $this->object->from(null));
	}

	/**
	 * Test to database conversion.
	 */
	public function testTo() {
		$this->assertSame('123', $this->object->to(123));
		$this->assertSame('abc', $this->object->to('abc'));
		$this->assertSame('1', $this->object->to(true));
		$this->assertSame('', $this->object->to(false));
		$this->assertSame('', $this->object->to(null));
	}

}