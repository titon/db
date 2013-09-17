<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model\Driver\Type;

use PDO;
use Titon\Model\Driver\Type\TextType;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Driver\Type\TextType.
 *
 * @property \Titon\Model\Driver\Type\TextType $object
 */
class TextTypeTest extends TestCase {

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new TextType(new DriverStub('default', []));
    }

    /**
     * Test from database conversion.
     */
    public function testFrom() {
        $this->assertSame(123, $this->object->from(123));
        $this->assertSame('abc', $this->object->from('abc'));
        $this->assertSame(null, $this->object->from(null));
    }

    /**
     * Test to database conversion.
     */
    public function testTo() {
        $this->assertSame(123, $this->object->to(123));
        $this->assertSame('abc', $this->object->to('abc'));
        $this->assertSame(null, $this->object->to(null));
    }

    /**
     * Test name string.
     */
    public function testGetName() {
        $this->assertEquals('text', $this->object->getName());
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