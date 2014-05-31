<?php
namespace Titon\Db\Driver;

use Titon\Cache\Storage\MemoryStorage;
use Titon\Common\Config;
use Titon\Db\Driver;
use Titon\Db\Exception\InvalidQueryException;
use Titon\Db\Query;
use Titon\Db\Driver\ResultSet;
use Titon\Test\Stub\DriverStub;
use Titon\Test\Stub\Repository\Stat;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;
use \PDO;
use \Exception;

/**
 * @property \Titon\Db\Driver\AbstractPdoDriver $object
 */
class PdoDriverTest extends TestCase {

    /** @type \Titon\Db\Repository */
    protected $table;

    protected function setUp() {
        parent::setUp();

        $this->object = new DriverStub(Config::get('db'));
        $this->object->connect();

        $this->table = new User();
    }

    protected function tearDown() {
        parent::tearDown();

        $this->object->disconnect();
    }

    public function testBuildStatement() {
        $this->loadFixtures('Users');

        $statement = $this->object->buildStatement((new Query(Query::SELECT, $this->table))->fields('id')->from('users'));

        $this->assertInstanceOf('PDOStatement', $statement);

        $statement->closeCursor();
    }

    /**
     * @expectedException \Titon\Db\Exception\UnsupportedQueryStatementException
     */
    public function testBuildStatementInvalidQuery() {
        $this->object->buildStatement(new Query('someType', $this->table));
    }

    public function testConnect() {
        $connections = $this->object->getConnections();

        $this->assertArrayNotHasKey('write', $connections);

        $this->object->setContext('write')->connect();

        $connections = $this->object->getConnections();

        $this->assertArrayHasKey('write', $connections);
    }

    public function testDescribeTable() {
        $this->loadFixtures(['Users', 'Stats']);

        $user = new User();
        $this->assertEquals([
            'id' => [
                'field' => 'id',
                'type' => 'int',
                'length' => '11',
                'null' => false,
                'primary' => true,
                'ai' => true
            ],
            'country_id' => [
                'field' => 'country_id',
                'type' => 'int',
                'length' => '11',
                'null' => true,
                'index' => true
            ],
            'username' => [
                'field' => 'username',
                'type' => 'varchar',
                'length' => '255',
                'null' => false,
                'charset' => 'utf8',
                'collate' => 'utf8_unicode_ci',
                'unique' => true
            ],
            'password' => [
                'field' => 'password',
                'type' => 'varchar',
                'length' => '255',
                'null' => true,
                'charset' => 'utf8',
                'collate' => 'utf8_unicode_ci'
            ],
            'email' => [
                'field' => 'email',
                'type' => 'varchar',
                'length' => '255',
                'null' => true,
                'charset' => 'utf8',
                'collate' => 'utf8_unicode_ci'
            ],
            'firstName' => [
                'field' => 'firstName',
                'type' => 'varchar',
                'length' => '255',
                'null' => true,
                'charset' => 'utf8',
                'collate' => 'utf8_unicode_ci'
            ],
            'lastName' => [
                'field' => 'lastName',
                'type' => 'varchar',
                'length' => '255',
                'null' => true,
                'charset' => 'utf8',
                'collate' => 'utf8_unicode_ci'
            ],
            'age' => [
                'field' => 'age',
                'type' => 'smallint',
                'length' => '6',
                'null' => true
            ],
            'created' => [
                'field' => 'created',
                'type' => 'datetime',
                'length' => '',
                'null' => true,
                'default' => null
            ],
            'modified' => [
                'field' => 'modified',
                'type' => 'datetime',
                'length' => '',
                'null' => true,
                'default' => null
            ],
        ], $user->getDriver()->describeTable($user->getTable()));

        $stat = new Stat();
        $this->assertEquals([
            'id' => [
                'field' => 'id',
                'type' => 'int',
                'length' => '11',
                'null' => false,
                'primary' => true,
                'ai' => true
            ],
            'name' => [
                'field' => 'name',
                'type' => 'varchar',
                'length' => '255',
                'null' => true,
                'charset' => 'utf8',
                'collate' => 'utf8_unicode_ci'
            ],
            'health' => [
                'field' => 'health',
                'type' => 'int',
                'length' => '11',
                'null' => true
            ],
            'energy' => [
                'field' => 'energy',
                'type' => 'smallint',
                'length' => '6',
                'null' => true
            ],
            'damage' => [
                'field' => 'damage',
                'type' => 'float',
                'length' => '',
                'null' => true
            ],
            'defense' => [
                'field' => 'defense',
                'type' => 'double',
                'length' => '',
                'null' => true
            ],
            'range' => [
                'field' => 'range',
                'type' => 'decimal',
                'length' => '8,2',
                'null' => true
            ],
            'isMelee' => [
                'field' => 'isMelee',
                'type' => 'tinyint',
                'length' => '1',
                'null' => true
            ],
            'data' => [
                'field' => 'data',
                'type' => 'blob',
                'length' => '',
                'null' => true
            ],
        ], $user->getDriver()->describeTable($stat->getTable()));
    }

    public function testDescribeTableMissingTable() {
        $this->assertEquals([], $this->object->describeTable('foobar'));
    }

    public function testEscape() {
        $this->assertSame('NULL', $this->object->escape(null));
        $this->assertSame("'12345'", $this->object->escape(12345));
        $this->assertSame("'67890'", $this->object->escape('67890'));
        $this->assertSame("'666.25'", $this->object->escape(666.25));
        $this->assertSame("'abc'", $this->object->escape('abc'));
        $this->assertSame("'1'", $this->object->escape(true));
    }

    public function testExecuteQuery() {
        $this->loadFixtures('Users');

        $query = (new Query(Query::SELECT, $this->table))->from('users');
        $result = $this->object->executeQuery($query);

        $this->assertInstanceOf('Titon\Db\Driver\ResultSet', $result);

        // Test before and after execute
        $this->assertEquals(0, $result->getExecutionTime());
        $this->assertEquals(0, $result->getRowCount());
        $this->assertEquals(false, $result->hasExecuted());
        $this->assertEquals(false, $result->isSuccessful());

        $results = $result->find();

        $this->assertEquals(5, count($results));
        $this->assertNotEquals(0, $result->getExecutionTime());
        $this->assertEquals(1, $result->getRowCount());
        $this->assertEquals(true, $result->hasExecuted());
        $this->assertEquals(true, $result->isSuccessful());
    }

    public function testExecuteQueryString() {
        $this->loadFixtures('Users');

        $result = $this->object->executeQuery('DELETE FROM users WHERE country_id = ?', [1]);

        $this->assertInstanceOf('Titon\Db\Driver\ResultSet', $result);

        $this->assertEquals(0, $result->getRowCount());
        $this->assertEquals(1, $result->save());
    }

    public function testExecuteQueryStringNoParams() {
        $this->loadFixtures('Users');

        $result = $this->object->executeQuery('DELETE FROM users');

        $this->assertInstanceOf('Titon\Db\Driver\ResultSet', $result);

        $this->assertEquals(0, $result->getRowCount());
        $this->assertEquals(5, $result->save());
    }

    /**
     * @expectedException \Titon\Db\Exception\InvalidQueryException
     */
    public function testExecuteQueryInvalidQuery() {
        $this->object->executeQuery(123456);
    }

    public function testExecuteQueryNoCache() {
        $this->loadFixtures('Users');

        $this->assertEquals([], $this->object->getLoggedQueries());
        $this->assertFalse($this->object->hasCache(__METHOD__));

        $query = new Query(Query::SELECT, $this->table);
        $query->from('users');

        $result = $this->object->executeQuery($query);

        $this->assertEquals([$result], $this->object->getLoggedQueries());
        $this->assertFalse($this->object->hasCache(__METHOD__));

        // Execute it again
        $result = $this->object->executeQuery($query);

        $this->assertEquals([$result, $result], $this->object->getLoggedQueries());
        $this->assertFalse($this->object->hasCache(__METHOD__));
    }

    public function testExecuteQueryLocalCache() {
        $this->loadFixtures('Users');

        $this->assertEquals([], $this->object->getLoggedQueries());
        $this->assertFalse($this->object->hasCache(__METHOD__));

        $query = new Query(Query::SELECT, $this->table);
        $query->from('users')->cache(__METHOD__, '+5 minutes');

        $result = $this->object->executeQuery($query);

        $this->assertEquals([$result], $this->object->getLoggedQueries());
        $this->assertTrue($this->object->hasCache(__METHOD__));

        // Execute it again
        $result = $this->object->executeQuery($query);

        $this->assertEquals([$result], $this->object->getLoggedQueries());
        $this->assertTrue($this->object->hasCache(__METHOD__));
    }

    public function testExecuteQueryStorageCache() {
        $this->loadFixtures('Users');

        $storage = new MemoryStorage();
        $this->object->setStorage($storage);

        $this->assertEquals([], $this->object->getLoggedQueries());
        $this->assertFalse($storage->has(__METHOD__));

        $query = new Query(Query::SELECT, $this->table);
        $query->from('users')->cache(__METHOD__, '+5 minutes');

        $result = $this->object->executeQuery($query);

        $this->assertEquals([$result], $this->object->getLoggedQueries());
        $this->assertTrue($storage->has(__METHOD__));

        // Execute it again
        $result = $this->object->executeQuery($query);

        $this->assertEquals([$result], $this->object->getLoggedQueries());
        $this->assertTrue($storage->has(__METHOD__));
    }

    public function testGetDsn() {
        $this->assertEquals('mysql:dbname=titon_test;host=127.0.0.1;port=3306;charset=utf8', $this->object->getDsn());

        $this->object->setConfig('encoding', '');
        $this->object->setConfig('port', 1337);
        $this->assertEquals('mysql:dbname=titon_test;host=127.0.0.1;port=1337', $this->object->getDsn());

        $this->object->setConfig('dsn', 'custom:dsn');
        $this->assertEquals('custom:dsn', $this->object->getDsn());
    }

    public function testListTables() {
        $this->loadFixtures(['Authors', 'Books', 'Genres', 'BookGenres', 'Series']);

        // Check the keys since the values constantly change
        $this->assertArraysEqual(['authors', 'books', 'books_genres', 'genres', 'series'], $this->object->listTables());
    }

    public function testListTablesMissingDatabase() {
        $this->assertEquals([], $this->object->listTables('foobar'));
    }

    public function testResolveBind() {
        $this->assertEquals([null, PDO::PARAM_NULL], $this->object->resolveBind('foo', null));
        $this->assertEquals(['abc', PDO::PARAM_STR], $this->object->resolveBind('foo', 'abc'));
        $this->assertEquals(['123', PDO::PARAM_INT], $this->object->resolveBind('foo', '123'));
        $this->assertEquals([123, PDO::PARAM_INT], $this->object->resolveBind('foo', 123));
        $this->assertEquals([123.45, PDO::PARAM_STR], $this->object->resolveBind('foo', 123.45));
    }

    public function testResolveBindObjects() {
        $this->assertEquals(['abc', PDO::PARAM_STR], $this->object->resolveBind(Query::func('SUBSTRING()'), 'abc'));
        $this->assertEquals([123, PDO::PARAM_INT], $this->object->resolveBind('foo', Query::expr('foo', '+', 123)));
    }

    public function testResolveBindSchema() {
        $this->loadFixtures('Users');

        $schema = $this->table->getSchema();
        $time = time();

        $this->assertEquals([date('Y-m-d H:i:s', $time), PDO::PARAM_STR], $this->object->resolveBind('created', new \DateTime(), $schema->getColumns()));
    }

    public function testResolveParams() {
        $query1 = new Query(Query::SELECT, $this->table);
        $query1->where('id', 1)->where(function(Query\Predicate $where) {
            $where->like('name', 'Titon')->in('size', [1, 2, 3]);
        });

        $this->assertEquals([
            [1, PDO::PARAM_INT],
            ['Titon', PDO::PARAM_STR],
            [1, PDO::PARAM_INT],
            [2, PDO::PARAM_INT],
            [3, PDO::PARAM_INT],
        ], $this->object->resolveParams($query1));

        // Include fields
        $query2 = new Query(Query::UPDATE, $this->table);
        $query2->data([
            'username' => 'miles',
            'age' => 26
        ])->where('id', 666);

        $this->assertEquals([
            ['miles', PDO::PARAM_STR],
            [26, PDO::PARAM_INT],
            [666, PDO::PARAM_INT],
        ], $this->object->resolveParams($query2));

        // All at once!
        $query3 = new Query(Query::UPDATE, $this->table);
        $query3->data([
            'username' => 'miles',
            'age' => 26
        ])->orWhere(function(Query\Predicate $where) {
            $where
                ->in('id', [4, 5, 6])
                ->also(function(Query\Predicate $where2) {
                    $where2->eq('status', true)->notEq('email', 'email@domain.com');
                })
                ->between('age', 30, 50);
        });

        $this->assertEquals([
            ['miles', PDO::PARAM_STR],
            [26, PDO::PARAM_INT],
            [4, PDO::PARAM_INT],
            [5, PDO::PARAM_INT],
            [6, PDO::PARAM_INT],
            [true, PDO::PARAM_BOOL],
            ['email@domain.com', PDO::PARAM_STR],
            [30, PDO::PARAM_INT],
            [50, PDO::PARAM_INT],
        ], $this->object->resolveParams($query3));
    }

    public function testResolveParamsMultiInsert() {
        $time = time();

        $query = new Query(Query::MULTI_INSERT, $this->table);
        $query->data([
            ['username' => 'foo', 'age' => 16, 'created' => null],
            ['username' => 'bar', 'age' => 33, 'created' => new \DateTime()]
        ]);

        $this->assertEquals([
            ['foo', PDO::PARAM_STR],
            [16, PDO::PARAM_INT],
            [null, PDO::PARAM_NULL],
            ['bar', PDO::PARAM_STR],
            [33, PDO::PARAM_INT],
            [date('Y-m-d H:i:s', $time), PDO::PARAM_STR],
        ], $this->object->resolveParams($query));
    }

    public function testResolveParamsCompoundQueries() {
        $query1 = new Query(Query::SELECT, $this->table);
        $query1->where('username', 'like', '%foo%');

        $query2 = new Query(Query::SELECT, $this->table);
        $query2->where('username', 'like', '%bar%');

        $query1->union($query2);

        $this->assertEquals([
            ['%foo%', PDO::PARAM_STR],
            ['%bar%', PDO::PARAM_STR],
        ], $this->object->resolveParams($query1));
    }

    public function testResolveType() {
        $this->assertSame(PDO::PARAM_NULL, $this->object->resolveType(null));
        $this->assertSame(PDO::PARAM_INT, $this->object->resolveType(12345));
        $this->assertSame(PDO::PARAM_INT, $this->object->resolveType('67890'));
        $this->assertSame(PDO::PARAM_STR, $this->object->resolveType(666.25));
        $this->assertSame(PDO::PARAM_STR, $this->object->resolveType('abc'));
        $this->assertSame(PDO::PARAM_BOOL, $this->object->resolveType(true));

        $f = fopen('php://input', 'r');
        $this->assertSame(PDO::PARAM_LOB, $this->object->resolveType($f));
        fclose($f);
    }

    public function testTransactions() {
        $this->loadFixtures(['Users', 'Profiles']);

        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        $this->object->transaction(function(Driver $driver) {
            $driver->executeQuery('DELETE FROM profiles WHERE user_id = 1')->execute();
            $driver->executeQuery('DELETE FROM users WHERE id = 1')->execute();
        });

        $this->assertEquals(0, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(0, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        $this->assertEquals([
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1',
            'BEGIN',
            'DELETE FROM profiles WHERE user_id = 1',
            'DELETE FROM users WHERE id = 1',
            'COMMIT',
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1'
        ], array_map(function(ResultSet $value) {
            return $value->getStatement();
        }, $this->object->getLoggedQueries()));
    }

    public function testCustomTransactions() {
        $this->loadFixtures(['Users', 'Profiles']);

        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        $this->assertTrue($this->object->startTransaction());

        try {
            $this->object->executeQuery('DELETE FROM profiles WHERE user_id = 1')->execute();
            $this->object->executeQuery('DELETE FROM users WHERE id = 1')->execute();

            $this->assertTrue($this->object->commitTransaction());
        } catch (Exception $e) {
            $this->object->rollbackTransaction();
        }

        $this->assertEquals(0, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(0, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        $this->assertEquals([
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1',
            'BEGIN',
            'DELETE FROM profiles WHERE user_id = 1',
            'DELETE FROM users WHERE id = 1',
            'COMMIT',
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1'
        ], array_map(function(ResultSet $value) {
            return $value->getStatement();
        }, $this->object->getLoggedQueries()));
    }

    public function testRollbackTransactions() {
        $this->loadFixtures(['Users', 'Profiles']);

        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        try {
            $this->object->transaction(function(Driver $driver) {
                $driver->executeQuery('DELETE FROM profiles WHERE user_id = 1')->execute();
                $driver->executeQuery('DELETE FROM users WHERE id = 1')->execute();

                throw new Exception('Fake error to trigger rollback!');
            });
        } catch (Exception $e) {}

        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        $this->assertEquals([
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1',
            'BEGIN',
            'DELETE FROM profiles WHERE user_id = 1',
            'DELETE FROM users WHERE id = 1',
            'ROLLBACK',
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1'
        ], array_map(function(ResultSet $value) {
            return $value->getStatement();
        }, $this->object->getLoggedQueries()));
    }

    public function testCustomRollbackTransactions() {
        $this->loadFixtures(['Users', 'Profiles']);

        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        $this->assertTrue($this->object->startTransaction());

        try {
            $this->object->executeQuery('DELETE FROM profiles WHERE user_id = 1')->execute();
            $this->object->executeQuery('DELETE FROM users WHERE id = 1')->execute();

            throw new Exception('Fake error to trigger rollback!');
        } catch (Exception $e) {
            $this->object->rollbackTransaction();
        }

        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        $this->assertEquals([
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1',
            'BEGIN',
            'DELETE FROM profiles WHERE user_id = 1',
            'DELETE FROM users WHERE id = 1',
            'ROLLBACK',
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1'
        ], array_map(function(ResultSet $value) {
            return $value->getStatement();
        }, $this->object->getLoggedQueries()));
    }

    public function testNestedTransactions() {
        $this->loadFixtures(['Users', 'Profiles']);

        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        $this->object->transaction(function(Driver $driver1) {
            $driver1->transaction(function(Driver $driver2) {
                $driver2->executeQuery('DELETE FROM profiles WHERE user_id = 1')->execute();
                $driver2->executeQuery('DELETE FROM users WHERE id = 1')->execute();
            });
        });

        $this->assertEquals(0, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(0, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        $this->assertEquals([
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1',
            'BEGIN',
            'DELETE FROM profiles WHERE user_id = 1',
            'DELETE FROM users WHERE id = 1',
            'COMMIT',
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1'
        ], array_map(function(ResultSet $value) {
            return $value->getStatement();
        }, $this->object->getLoggedQueries()));
    }

    public function testCustomNestedTransactions() {
        $this->loadFixtures(['Users', 'Profiles']);

        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        $this->assertTrue($this->object->startTransaction());

        try {
            $this->assertTrue($this->object->startTransaction());

            try {
                $this->object->executeQuery('DELETE FROM profiles WHERE user_id = 1')->execute();
                $this->object->executeQuery('DELETE FROM users WHERE id = 1')->execute();

                $this->assertTrue($this->object->commitTransaction());
            } catch (InvalidQueryException $e) {
                $this->object->rollbackTransaction();
            }

            $this->assertTrue($this->object->commitTransaction());

        } catch (Exception $e) {
            $this->object->rollbackTransaction();
        }

        $this->assertEquals(0, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(0, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        $this->assertEquals([
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1',
            'BEGIN',
            'DELETE FROM profiles WHERE user_id = 1',
            'DELETE FROM users WHERE id = 1',
            'COMMIT',
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1'
        ], array_map(function(ResultSet $value) {
            return $value->getStatement();
        }, $this->object->getLoggedQueries()));
    }

    public function testNestedRollbackTransactions() {
        $this->loadFixtures(['Users', 'Profiles']);

        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        try {
            $this->object->transaction(function(Driver $driver1) {
                $driver1->transaction(function(Driver $driver2) {
                    $driver2->executeQuery('DELETE FROM profiles WHERE user_id = 1')->execute();
                    $driver2->executeQuery('DELETE FROM users WHERE id = 1')->execute();

                    throw new Exception('Fake error to trigger rollback!');
                });
            });
        } catch (Exception $e) {}

        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        $this->assertEquals([
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1',
            'BEGIN',
            'DELETE FROM profiles WHERE user_id = 1',
            'DELETE FROM users WHERE id = 1',
            'ROLLBACK',
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1'
        ], array_map(function(ResultSet $value) {
            return $value->getStatement();
        }, $this->object->getLoggedQueries()));
    }

    public function testCustomNestedRollbackTransactions() {
        $this->loadFixtures(['Users', 'Profiles']);

        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        $this->assertTrue($this->object->startTransaction());

        try {
            $this->assertTrue($this->object->startTransaction());

            try {
                $this->object->executeQuery('DELETE FROM profiles WHERE user_id = 1')->execute();
                $this->object->executeQuery('DELETE FROM users WHERE id = 1')->execute();

                throw new Exception('Fake error to trigger rollback!');
            } catch (Exception $e) {
                $this->object->rollbackTransaction();

                // Must throw again if nested catch blocks
                throw $e;
            }
        } catch (Exception $e) {
            $this->object->rollbackTransaction();
        }

        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM users WHERE id = 1')->count());
        $this->assertEquals(1, $this->object->executeQuery('SELECT COUNT(id) FROM profiles WHERE user_id = 1')->count());

        $this->assertEquals([
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1',
            'BEGIN',
            'DELETE FROM profiles WHERE user_id = 1',
            'DELETE FROM users WHERE id = 1',
            'ROLLBACK',
            'SELECT COUNT(id) FROM users WHERE id = 1',
            'SELECT COUNT(id) FROM profiles WHERE user_id = 1'
        ], array_map(function(ResultSet $value) {
            return $value->getStatement();
        }, $this->object->getLoggedQueries()));
    }

}