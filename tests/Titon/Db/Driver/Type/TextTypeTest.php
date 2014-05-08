<?php
namespace Titon\Db\Driver\Type;

use PDO;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Driver\Type\TextType $object
 */
class TextTypeTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new TextType(new DriverStub([]));
    }

    public function testFrom() {
        $this->assertSame(123, $this->object->from(123));
        $this->assertSame('abc', $this->object->from('abc'));
        $this->assertSame(null, $this->object->from(null));
    }

    public function testTo() {
        $this->assertSame(123, $this->object->to(123));
        $this->assertSame('abc', $this->object->to('abc'));
        $this->assertSame(null, $this->object->to(null));
    }

    public function testGetName() {
        $this->assertEquals('text', $this->object->getName());
    }

    public function testGetBindingType() {
        $this->assertEquals(PDO::PARAM_STR, $this->object->getBindingType());
    }

    public function testGetDefaultOptions() {
        $this->assertEquals([], $this->object->getDefaultOptions());
    }

}