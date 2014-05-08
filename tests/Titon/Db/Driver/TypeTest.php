<?php
namespace Titon\Db\Driver;

use Titon\Db\Driver\Type\AbstractType;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;
use \Exception;

/**
 * @property \Titon\Db\Driver\Type $object
 */
class TypeTest extends TestCase {

    public function testFactory() {
        $driver = new DriverStub([]);

        $this->assertInstanceOf('Titon\Db\Driver\Type', AbstractType::factory('int', $driver));
        $this->assertInstanceOf('Titon\Db\Driver\Type', AbstractType::factory('varchar', $driver));
        $this->assertInstanceOf('Titon\Db\Driver\Type', AbstractType::factory('longblob', $driver));

        try {
            $this->assertInstanceOf('Titon\Db\Driver\Type', AbstractType::factory('foobar', $driver));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

}