<?php
namespace Titon\Db\Driver\Type;

use PDO;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Driver\Type\IntType $object
 */
class IntTypeTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new IntType(new DriverStub([]));
    }

    public function testFrom() {
        $this->assertSame(123, $this->object->from('123.5'));
        $this->assertSame(456, $this->object->from('456'));
        $this->assertSame(666, $this->object->from('666'));
        $this->assertSame(0, $this->object->from('abc'));
        $this->assertSame(0, $this->object->from('true'));
    }

    public function testTo() {
        $this->assertSame(null, $this->object->to(''));
        $this->assertSame(null, $this->object->to(null));
        $this->assertSame(123, $this->object->to(123.5));
        $this->assertSame(456, $this->object->to(456));
        $this->assertSame(666, $this->object->to('666'));
        $this->assertSame(0, $this->object->to('abc'));
    }

    public function testGetName() {
        $this->assertEquals('int', $this->object->getName());
    }

    public function testGetBindingType() {
        $this->assertEquals(PDO::PARAM_INT, $this->object->getBindingType());
    }

    public function testGetDefaultOptions() {
        $this->assertEquals([], $this->object->getDefaultOptions());
    }

}