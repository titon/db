<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Cache\Storage\MemoryStorage;
use Titon\Common\Config;
use Titon\Debug\Logger;
use Titon\Test\Stub\DialectStub;
use Titon\Test\Stub\DriverStub;
use Titon\Test\Stub\QueryResultStub;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for Titon\Db\Driver.
 *
 * @property \Titon\Db\Driver $object
 */
class DriverTest extends TestCase {

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new DriverStub('default', Config::get('db'));
    }

    /**
     * Disconnect just in case.
     */
    protected function tearDown() {
        parent::tearDown();

        $this->object->disconnect();
    }

    /**
     * Test that the driver can connect and disconnect.
     */
    public function testConnection() {
        $this->assertFalse($this->object->isConnected());
        $this->assertEquals(null, $this->object->getConnection());

        $this->object->connect();

        $this->assertTrue($this->object->isConnected());
        $this->assertInstanceOf('PDO', $this->object->getConnection());

        $this->object->disconnect();

        $this->assertFalse($this->object->isConnected());
        $this->assertEquals(null, $this->object->getConnection());
    }

    /**
     * Test dialect management.
     */
    public function testDialects() {
        $this->assertInstanceOf('Titon\Db\Driver\Dialect', $this->object->getDialect());

        $this->object->setDialect(new DialectStub($this->object));
        $this->assertInstanceOf('Titon\Test\Stub\DialectStub', $this->object->getDialect());
    }

    /**
     * Test storage management.
     */
    public function testStorage() {
        $this->assertEquals(null, $this->object->getStorage());

        $this->object->setStorage(new MemoryStorage('memory'));
        $this->assertInstanceOf('Titon\Cache\Storage', $this->object->getStorage());
    }

    /**
     * Test log management.
     */
    public function testLogger() {
        $this->assertEquals(null, $this->object->getLogger());

        $this->object->setLogger(new Logger(TEST_DIR));
        $this->assertInstanceOf('Psr\Log\LoggerInterface', $this->object->getLogger());
    }

    /**
     * Test query logging.
     */
    public function testLogging() {
        $this->assertEquals([], $this->object->getLoggedQueries());

        $log1 = new QueryResultStub('SELECT * FROM users');
        $this->object->logQuery($log1);

        $this->assertEquals([$log1], $this->object->getLoggedQueries());

        $log2 = new QueryResultStub('DELETE FROM users WHERE id = 1');
        $this->object->logQuery($log2);

        $this->assertEquals([$log1, $log2], $this->object->getLoggedQueries());
    }

}