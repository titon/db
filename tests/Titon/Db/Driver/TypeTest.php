<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver;

use Titon\Db\Driver\Type\AbstractType;
use Titon\Db\Driver\Type\BlobType;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for Titon\Db\Driver\Type.
 *
 * @property \Titon\Db\Driver\Type $object
 */
class TypeTest extends TestCase {

    /**
     * Test factory and exceptions are throwing for missing types.
     */
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