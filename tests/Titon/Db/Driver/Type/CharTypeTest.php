<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

use PDO;
use Titon\Db\Driver\Type\CharType;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Driver\Type\CharType.
 *
 * @property \Titon\Db\Driver\Type\CharType $object
 */
class CharTypeTest extends TestCase {

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new CharType(new DriverStub([]));
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

    /**
     * Test name string.
     */
    public function testGetName() {
        $this->assertEquals('char', $this->object->getName());
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