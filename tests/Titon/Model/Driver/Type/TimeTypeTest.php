<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use DateTime;
use Titon\Model\Driver\Type\TimeType;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Driver\Type\TimeType.
 *
 * @property \Titon\Model\Driver\Type\TimeType $object
 */
class TimeTypeTest extends TestCase {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new TimeType();
	}

	/**
	 * Test from database conversion.
	 */
	public function testFrom() {
		$this->assertSame('00:02:05', $this->object->from('00:02:05'));
		$this->assertSame('21:05:29', $this->object->from('21:05:29'));
		$this->assertSame('12:33:00', $this->object->from('12:33:00'));
		$this->assertSame('02:44:55', $this->object->from('02:44:55'));
	}

	/**
	 * Test to database conversion.
	 */
	public function testTo() {
		$this->assertSame('00:02:05', $this->object->to(mktime(0, 2, 5, 2, 26, 1988)));
		$this->assertSame('21:05:29', $this->object->to('21:05:29'));
		$this->assertSame('12:33:00', $this->object->to('June 6th 1985, 12:33pm'));
		$this->assertSame('02:44:55', $this->object->to(new DateTime('02:44:55')));
	}

}