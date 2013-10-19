<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model\Driver\Type;

use PDO;
use Titon\Model\Driver\Type\BigintType;
use Titon\Test\Stub\DriverStub;
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

        $this->object = new BigintType(new DriverStub('default', []));
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
        $this->assertSame(null, $this->object->to(''));
        $this->assertSame(null, $this->object->to(null));
        $this->assertSame('1337', $this->object->to(1337));
        $this->assertSame('1234567890', $this->object->to(1234567890));
        $this->assertSame('12345678901234567890', $this->object->to('12345678901234567890'));
    }

    /**
     * Test name string.
     */
    public function testGetName() {
        $this->assertEquals('bigint', $this->object->getName());
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