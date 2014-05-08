<?php
namespace Titon\Db\Driver\Type;

use PDO;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Driver\Type\BigintType $object
 */
class BigintTypeTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new BigintType(new DriverStub([]));
    }

    public function testFrom() {
        $this->assertSame('1337', $this->object->from(1337));
        $this->assertSame('1234567890', $this->object->from(1234567890));
        $this->assertSame('12345678901234567890', $this->object->from('12345678901234567890'));
    }

    public function testTo() {
        $this->assertSame(null, $this->object->to(''));
        $this->assertSame(null, $this->object->to(null));
        $this->assertSame('1337', $this->object->to(1337));
        $this->assertSame('1234567890', $this->object->to(1234567890));
        $this->assertSame('12345678901234567890', $this->object->to('12345678901234567890'));
    }

    public function testGetName() {
        $this->assertEquals('bigint', $this->object->getName());
    }

    public function testGetBindingType() {
        $this->assertEquals(PDO::PARAM_STR, $this->object->getBindingType());
    }

    public function testGetDefaultOptions() {
        $this->assertEquals([], $this->object->getDefaultOptions());
    }

}