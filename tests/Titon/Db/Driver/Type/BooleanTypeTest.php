<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

use PDO;
use Titon\Db\Driver\Type\BooleanType;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Driver\Type\BooleanType.
 *
 * @property \Titon\Db\Driver\Type\BooleanType $object
 */
class BooleanTypeTest extends TestCase {

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new BooleanType(new DriverStub('default', []));
    }

    /**
     * Test from database conversion.
     */
    public function testFrom() {
        $this->assertSame(true, $this->object->from(true));
        $this->assertSame(false, $this->object->from(false));
        $this->assertSame(true, $this->object->from(1));
        $this->assertSame(false, $this->object->from(0));
        $this->assertSame(true, $this->object->from('1'));
        $this->assertSame(false, $this->object->from('0'));
    }

    /**
     * Test to database conversion.
     */
    public function testTo() {
        $this->assertSame(true, $this->object->to(true));
        $this->assertSame(false, $this->object->to(false));
        $this->assertSame(true, $this->object->to(1));
        $this->assertSame(false, $this->object->to(0));
        $this->assertSame(true, $this->object->to('abc'));
        $this->assertSame(false, $this->object->to(null));
    }

    /**
     * Test name string.
     */
    public function testGetName() {
        $this->assertEquals('boolean', $this->object->getName());
    }

    /**
     * Test PDO type.
     */
    public function testGetBindingType() {
        $this->assertEquals(PDO::PARAM_BOOL, $this->object->getBindingType());
    }

    /**
     * Test schema options.
     */
    public function testGetDefaultOptions() {
        $this->assertEquals([], $this->object->getDefaultOptions());
    }

}