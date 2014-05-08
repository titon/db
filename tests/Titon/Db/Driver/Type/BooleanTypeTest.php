<?php
namespace Titon\Db\Driver\Type;

use PDO;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Driver\Type\BooleanType $object
 */
class BooleanTypeTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new BooleanType(new DriverStub([]));
    }

    public function testFrom() {
        $this->assertSame(true, $this->object->from(true));
        $this->assertSame(false, $this->object->from(false));
        $this->assertSame(true, $this->object->from(1));
        $this->assertSame(false, $this->object->from(0));
        $this->assertSame(true, $this->object->from('1'));
        $this->assertSame(false, $this->object->from('0'));
    }

    public function testTo() {
        $this->assertSame(true, $this->object->to(true));
        $this->assertSame(false, $this->object->to(false));
        $this->assertSame(true, $this->object->to(1));
        $this->assertSame(false, $this->object->to(0));
        $this->assertSame(true, $this->object->to('abc'));
        $this->assertSame(false, $this->object->to(null));
    }

    public function testGetName() {
        $this->assertEquals('boolean', $this->object->getName());
    }

    public function testGetBindingType() {
        $this->assertEquals(PDO::PARAM_BOOL, $this->object->getBindingType());
    }

    public function testGetDefaultOptions() {
        $this->assertEquals([], $this->object->getDefaultOptions());
    }

}