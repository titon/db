<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model\Driver\Type;

use DateTime;
use PDO;
use Titon\Model\Driver\Type\DateType;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Driver\Type\DateType.
 *
 * @property \Titon\Model\Driver\Type\DateType $object
 */
class DateTypeTest extends TestCase {

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new DateType(new DriverStub('default', []));
    }

    /**
     * Test from database conversion.
     */
    public function testFrom() {
        $this->assertSame('1988-02-26', $this->object->from('1988-02-26'));
        $this->assertSame('2011-03-11', $this->object->from('2011-03-11'));
        $this->assertSame('1985-06-06', $this->object->from('1985-06-06'));
        $this->assertSame('1995-11-30', $this->object->from('1995-11-30'));
    }

    /**
     * Test to database conversion.
     */
    public function testTo() {
        $this->assertSame('1988-02-26', $this->object->to(mktime(0, 2, 5, 2, 26, 1988)));
        $this->assertSame('2011-03-11', $this->object->to('2011-03-11 21:05:29'));
        $this->assertSame('1985-06-06', $this->object->to('June 6th 1985, 12:33pm'));
        $this->assertSame('1995-11-30', $this->object->to(new DateTime('1995-11-30 02:44:55')));
    }

    /**
     * Test name string.
     */
    public function testGetName() {
        $this->assertEquals('date', $this->object->getName());
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
        $this->assertEquals(['null' => true, 'default' => null], $this->object->getDefaultOptions());
    }

}