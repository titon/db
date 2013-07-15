<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver\Type;

use DateTime;
use Titon\Model\Driver\Type\YearType;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Driver\Type\YearType.
 *
 * @property \Titon\Model\Driver\Type\YearType $object
 */
class YearTypeTest extends TestCase {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new YearType(new DriverStub('default', []));
	}

	/**
	 * Test from database conversion.
	 */
	public function testFrom() {
		$this->assertSame(1988, $this->object->from('1988'));
		$this->assertSame(2011, $this->object->from('2011'));
		$this->assertSame(1985, $this->object->from('1985'));
		$this->assertSame(1995, $this->object->from('1995'));
		$this->assertSame(2013, $this->object->from('2013'));
	}

	/**
	 * Test to database conversion.
	 */
	public function testTo() {
		$this->assertSame('1988', $this->object->to(mktime(0, 2, 5, 2, 26, 1988)));
		$this->assertSame('2011', $this->object->to('2011-03-11 21:05:29'));
		$this->assertSame('1985', $this->object->to('June 6th 1985, 12:33pm'));
		$this->assertSame('1995', $this->object->to(new DateTime('1995-11-30 02:44:55')));
		$this->assertSame('2013', $this->object->to('Dec 24th 13, 02:15am'));
	}

}