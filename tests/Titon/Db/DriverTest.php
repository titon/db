<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Cache\Storage\MemoryStorage;
use Titon\Common\Config;
use Titon\Debug\Logger;
use Titon\Test\Stub\DialectStub;
use Titon\Test\Stub\DriverStub;
use Titon\Test\Stub\QueryResultSetStub;
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

        $this->object = new DriverStub(Config::get('db'));
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

        try {
            $this->object->getConnection();
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $this->object->connect();

        $this->assertTrue($this->object->isConnected());
        $this->assertInstanceOf('PDO', $this->object->getConnection());

        $this->object->disconnect();

        $this->assertFalse($this->object->isConnected());

        try {
            $this->object->getConnection();
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test that group settings are inherited.
     */
    public function testContextConnections() {
        $driver = new DriverStub([
            'database' => 'titon_test',
            'host' => '127.0.0.1',
            'user' => 'root',
            'pass' => '',
            'contexts' => [
                'read' => [
                    'host' => 'prod1.com'
                ],
                'write' => [
                    'host' => 'prod2.com',
                    'user' => 'writer'
                ]
            ]
        ]);

        // No changes
        $this->assertEquals([
            'database' => 'titon_test',
            'host' => '127.0.0.1',
            'port' => 3306,
            'user' => 'root',
            'pass' => '',
        ], $driver->getContextConfig('delete'));

        $this->assertEquals([
            'database' => 'titon_test',
            'host' => 'prod1.com',
            'port' => 3306,
            'user' => 'root',
            'pass' => '',
        ], $driver->getContextConfig('read'));

        $this->assertEquals([
            'database' => 'titon_test',
            'host' => 'prod2.com',
            'port' => 3306,
            'user' => 'writer',
            'pass' => '',
        ], $driver->getContextConfig('write'));
    }

    /**
     * Test that the correct values are returned from getters.
     */
    public function testContextGetters() {
        $driver = new DriverStub([
            'database' => 'titon_test',
            'host' => '127.0.0.1',
            'user' => 'root',
            'pass' => '',
            'contexts' => [
                'read' => [
                    'host' => 'prod1.com'
                ],
                'write' => [
                    'host' => 'prod2.com',
                    'user' => 'writer'
                ]
            ]
        ]);

        // Read by default
        $this->assertEquals('root', $driver->getUser());
        $this->assertEquals('', $driver->getPassword());
        $this->assertEquals('prod1.com', $driver->getHost());
        $this->assertEquals('titon_test', $driver->getDatabase());
        $this->assertEquals(3306, $driver->getPort());

        $driver->setContext('write');
        $this->assertEquals('writer', $driver->getUser());
        $this->assertEquals('', $driver->getPassword());
        $this->assertEquals('prod2.com', $driver->getHost());
        $this->assertEquals('titon_test', $driver->getDatabase());
        $this->assertEquals(3306, $driver->getPort());

        $driver->setContext('delete');
        $this->assertEquals('root', $driver->getUser());
        $this->assertEquals('', $driver->getPassword());
        $this->assertEquals('127.0.0.1', $driver->getHost());
        $this->assertEquals('titon_test', $driver->getDatabase());
        $this->assertEquals(3306, $driver->getPort());
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

        $this->object->setStorage(new MemoryStorage());
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

        $log1 = new QueryResultSetStub('SELECT * FROM users');
        $this->object->logQuery($log1);

        $this->assertEquals([$log1], $this->object->getLoggedQueries());

        $log2 = new QueryResultSetStub('DELETE FROM users WHERE id = 1');
        $this->object->logQuery($log2);

        $this->assertEquals([$log1, $log2], $this->object->getLoggedQueries());
    }

}