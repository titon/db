<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

use PDO;
use Titon\Db\Driver\Type\DecimalType;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Driver\Type\DecimalType.
 *
 * @property \Titon\Db\Driver\Type\DecimalType $object
 */
class DecimalTypeTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new DecimalType(new DriverStub([]));
    }

    public function testFrom() {
        $this->assertSame(1234.25, $this->object->from('1234.25'));
        $this->assertSame(5678.9, $this->object->from('5678.900'));
        $this->assertSame(666.1337, $this->object->from('666.1337'));
    }

    public function testTo() {
        $this->assertSame(1234.25, $this->object->to(1234.25));
        $this->assertSame(5678.9, $this->object->to(5678.900));
        $this->assertSame(666.1337, $this->object->to('666.1337'));
    }

    public function testGetName() {
        $this->assertEquals('decimal', $this->object->getName());
    }

    public function testGetBindingType() {
        $this->assertEquals(PDO::PARAM_STR, $this->object->getBindingType());
    }

    public function testGetDefaultOptions() {
        $this->assertEquals(['length' => '8,2'], $this->object->getDefaultOptions());
    }

}