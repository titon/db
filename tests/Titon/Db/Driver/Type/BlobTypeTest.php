<?php
namespace Titon\Db\Driver\Type;

use PDO;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Driver\Type\BlobType $object
 */
class BlobTypeTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new BlobType(new DriverStub([]));
    }

    public function testFrom() {
        $this->assertSame(null, $this->object->from(null));
        $this->assertInternalType('resource', $this->object->from('This is loading from a file handle'));
    }

    public function testFromNonResource() {
        try {
            $this->object->from(123456);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testTo() {
        $this->assertInternalType('resource', $this->object->to(fopen(TEMP_DIR . '/blob.txt', 'r')));
    }

    public function testToNonResource() {
        try {
            $this->object->to(123456);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testGetName() {
        $this->assertEquals('blob', $this->object->getName());
    }

    public function testGetBindingType() {
        $this->assertEquals(PDO::PARAM_LOB, $this->object->getBindingType());
    }

    public function testGetDefaultOptions() {
        $this->assertEquals([], $this->object->getDefaultOptions());
    }

}