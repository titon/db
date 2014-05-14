<?php
namespace Titon\Db;

use Titon\Cache\Storage\MemoryStorage;
use Titon\Common\Config;
use Titon\Db\Query\ResultSet\SqlResultSet;
use Titon\Debug\Logger;
use Titon\Test\Stub\DialectStub;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Driver $object
 */
class DriverTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new DriverStub(Config::get('db'));
    }

    protected function tearDown() {
        parent::tearDown();

        $this->object->disconnect();
    }

    public function testDisconnect() {
        $this->assertFalse($this->object->isConnected());

        $this->object->connect();

        $this->assertTrue($this->object->isConnected());

        $this->object->disconnect();

        $this->assertFalse($this->object->isConnected());
    }

    public function testDisconnectAll() {
        $this->assertEquals(0, count($this->object->getConnections()));

        $this->object->getConnection(); // read context

        $this->assertEquals(1, count($this->object->getConnections()));

        $this->object->setContext('write');
        $this->object->getConnection(); // write context

        $this->assertEquals(2, count($this->object->getConnections()));

        $this->object->disconnect(true);

        $this->assertEquals(0, count($this->object->getConnections()));
    }

    public function testGetConnection() {
        $read = $this->object->getConnection();

        $this->object->setContext('write');
        $write = $this->object->getConnection();

        $this->assertInstanceOf('PDO', $read);
        $this->assertInstanceOf('PDO', $write);
        $this->assertNotSame($write, $read);
    }

    public function testGetConnections() {
        $read = $this->object->getConnection();

        $this->object->setContext('write');
        $write = $this->object->getConnection();

        $this->assertEquals([
            'read' => $read,
            'write' => $write
        ], $this->object->getConnections());
    }

    public function testGetSetContext() {
        $this->assertEquals('read', $this->object->getContext());

        $this->object->setContext('write');

        $this->assertEquals('write', $this->object->getContext());
    }

    /**
     * @expectedException \Titon\Db\Exception\InvalidArgumentException
     */
    public function testSetContextThrowsError() {
        $this->object->setContext(null);
    }

    public function testGetContextConfig() {
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

    public function testDialects() {
        $this->assertInstanceOf('Titon\Db\Driver\Dialect', $this->object->getDialect());

        $this->object->setDialect(new DialectStub($this->object));

        $this->assertInstanceOf('Titon\Test\Stub\DialectStub', $this->object->getDialect());
    }

    public function testStorage() {
        $this->assertEquals(null, $this->object->getStorage());

        $this->object->setStorage(new MemoryStorage());

        $this->assertInstanceOf('Titon\Cache\Storage', $this->object->getStorage());
    }

    public function testLogger() {
        $this->assertEquals(null, $this->object->getLogger());

        $this->object->setLogger(new Logger(TEST_DIR));

        $this->assertInstanceOf('Psr\Log\LoggerInterface', $this->object->getLogger());
    }

    public function testLogQuery() {
        $this->assertEquals([], $this->object->getLoggedQueries());

        $log1 = new SqlResultSet('SELECT * FROM users');
        $this->object->logQuery($log1);

        $this->assertEquals([$log1], $this->object->getLoggedQueries());

        $log2 = new SqlResultSet('DELETE FROM users WHERE id = 1');
        $this->object->logQuery($log2);

        $this->assertEquals([$log1, $log2], $this->object->getLoggedQueries());
    }

    public function testLogQueryToLogger() {
        $path = TEMP_DIR . '/debug-' . date('Y-m-d') . '.log';

        $this->assertFileNotExists($path);

        $this->object->setLogger(new Logger(TEMP_DIR));
        $this->object->logQuery(new SqlResultSet('SELECT * FROM users'));

        $this->assertFileExists($path);

        @unlink($path);
    }

}