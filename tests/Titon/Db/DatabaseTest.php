<?php
namespace Titon\Db;

use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;
use \Exception;

/**
 * @property \Titon\Db\Database $object
 */
class DatabaseTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new Database();
        $this->object->addDriver('mysql', new DriverStub([]));
    }

    public function testAddGetDriver() {
        $this->assertInstanceOf('Titon\Db\Driver', $this->object->getDriver('mysql'));

        try {
            $this->object->getDriver('foobar');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $this->object->addDriver('foobar', new DriverStub([]));
        $this->assertInstanceOf('Titon\Db\Driver', $this->object->getDriver('foobar'));
    }

    public function testGetDrivers() {
        $this->assertEquals(1, count($this->object->getDrivers()));
        $this->object->addDriver('foobar', new DriverStub([]));
        $this->assertEquals(2, count($this->object->getDrivers()));
    }

}