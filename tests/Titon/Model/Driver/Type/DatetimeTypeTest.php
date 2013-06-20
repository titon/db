<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use DateTime;
use Titon\Model\Driver\Type\DatetimeType;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Driver\Type\DatetimeType.
 *
 * @property \Titon\Model\Driver\Type\DatetimeType $object
 */
class DatetimeTypeTest extends TestCase {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new DatetimeType();
	}

	/**
	 * Test from database conversion.
	 */
	public function testFrom() {
		$this->assertSame('1988-02-26 00:02:05', $this->object->from('1988-02-26 00:02:05'));
		$this->assertSame('2011-03-11 21:05:29', $this->object->from('2011-03-11 21:05:29'));
		$this->assertSame('1985-06-06 12:33:00', $this->object->from('1985-06-06 12:33:00'));
		$this->assertSame('1995-11-30 02:44:55', $this->object->from('1995-11-30 02:44:55'));
	}

	/**
	 * Test to database conversion.
	 */
	public function testTo() {
		$this->assertSame('1988-02-26 00:02:05', $this->object->to(mktime(0, 2, 5, 2, 26, 1988)));
		$this->assertSame('2011-03-11 21:05:29', $this->object->to('2011-03-11 21:05:29'));
		$this->assertSame('1985-06-06 12:33:00', $this->object->to('June 6th 1985, 12:33pm'));
		$this->assertSame('1995-11-30 02:44:55', $this->object->to(new DateTime('1995-11-30 02:44:55')));
	}

}