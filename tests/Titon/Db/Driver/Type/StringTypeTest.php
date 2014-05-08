<?php
namespace Titon\Db\Driver\Type;

use PDO;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Driver\Type\StringType $object
 */
class StringTypeTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new StringType(new DriverStub([]));
    }

    public function testFrom() {
        $this->assertSame('123', $this->object->from(123));
        $this->assertSame('abc', $this->object->from('abc'));
        $this->assertSame('1', $this->object->from(true));
        $this->assertSame('', $this->object->from(false));
        $this->assertSame('', $this->object->from(null));
    }

    public function testTo() {
        $this->assertSame('123', $this->object->to(123));
        $this->assertSame('abc', $this->object->to('abc'));
        $this->assertSame('1', $this->object->to(true));
        $this->assertSame('', $this->object->to(false));
        $this->assertSame('', $this->object->to(null));
    }

    public function testGetName() {
        $this->assertEquals('string', $this->object->getName());
    }

    public function testGetBindingType() {
        $this->assertEquals(PDO::PARAM_STR, $this->object->getBindingType());
    }

    public function testGetDefaultOptions() {
        $this->assertEquals(['length' => 255], $this->object->getDefaultOptions());
    }

}