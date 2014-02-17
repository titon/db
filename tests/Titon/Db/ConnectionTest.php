<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for Titon\Db\Connection.
 *
 * @property \Titon\Db\Connection $object
 */
class ConnectionTest extends TestCase {

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new Connection();
        $this->object->addDriver('mysql', new DriverStub([]));
    }

    /**
     * Test getting and setting data drivers.
     */
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

    /**
     * Test that getDrivers() returns all.
     */
    public function testGetDrivers() {
        $this->assertEquals(1, count($this->object->getDrivers()));

        $this->object->addDriver('foobar', new DriverStub([]));
        $this->assertEquals(2, count($this->object->getDrivers()));
    }

}