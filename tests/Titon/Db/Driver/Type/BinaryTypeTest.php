<?php
namespace Titon\Db\Driver\Type;

use PDO;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Driver\Type\BinaryType $object
 */
class BinaryTypeTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new BinaryType(new DriverStub([]));
    }

    public function testFrom() {
        $this->assertSame('456', $this->object->from('1101000011010100110110'));
        $this->assertSame('xyz', $this->object->from('11110000111100101111010'));
    }

    public function testTo() {
        $this->assertSame('1100010011001000110011', $this->object->to('1100010011001000110011'));
        $this->assertSame('1100010011001000110011', $this->object->to(123));
        $this->assertSame('11000010110001001100011', $this->object->to('abc'));
    }

    public function testGetName() {
        $this->assertEquals('binary', $this->object->getName());
    }

    public function testGetBindingType() {
        $this->assertEquals(PDO::PARAM_STR, $this->object->getBindingType());
    }

    public function testGetDefaultOptions() {
        $this->assertEquals([], $this->object->getDefaultOptions());
    }

}