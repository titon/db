<?php
namespace Titon\Db\Driver\Type;

use PDO;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Driver\Type\FloatType $object
 */
class FloatTypeTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new FloatType(new DriverStub([]));
    }

    public function testFrom() {
        $this->assertSame(123.5, $this->object->from('123.5'));
        $this->assertSame(456.0, $this->object->from('456.00'));
        $this->assertSame(666.1337, $this->object->from('666.1337'));
    }

    public function testTo() {
        $this->assertSame(123.5, $this->object->to(123.5));
        $this->assertSame(456.0, $this->object->to(456));
        $this->assertSame(666.1337, $this->object->to('666.1337'));
    }

    public function testGetName() {
        $this->assertEquals('float', $this->object->getName());
    }

    public function testGetBindingType() {
        $this->assertEquals(PDO::PARAM_STR, $this->object->getBindingType());
    }

    public function testGetDefaultOptions() {
        $this->assertEquals([], $this->object->getDefaultOptions());
    }

}