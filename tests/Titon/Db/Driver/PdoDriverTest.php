<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver;

use Titon\Common\Config;
use Titon\Db\Query;
use Titon\Test\Stub\DriverStub;
use Titon\Test\Stub\Table\Stat;
use Titon\Test\Stub\Table\User;
use Titon\Test\TestCase;
use \Exception;
use \PDO;

/**
 * Test class for Titon\Db\Driver\AbstractPdoDriver.
 *
 * @property \Titon\Db\Driver\AbstractPdoDriver $object
 */
class PdoDriverTest extends TestCase {

    /**
     * Stub table.
     *
     * @type \Titon\Db\Table
     */
    protected $table;

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new DriverStub('default', Config::get('db'));
        $this->object->connect();

        $this->table = new User();
    }

    /**
     * Disconnect just in case.
     */
    protected function tearDown() {
        parent::tearDown();

        $this->object->disconnect();
    }

    /**
     * Test statement building.
     */
    public function testBuildStatement() {
        $this->loadFixtures('Users');

        // Unsupported query
        try {
            $this->object->buildStatement(new Query('someType', $this->table));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $statement = $this->object->buildStatement((new Query(Query::SELECT, $this->table))->fields('id')->from('users'));
        $this->assertInstanceOf('PDOStatement', $statement);
        $statement->closeCursor();
    }

    /**
     * Test value escaping and quoting.
     */
    public function testEscape() {
        $this->assertSame('NULL', $this->object->escape(null));
        $this->assertSame("'12345'", $this->object->escape(12345));
        $this->assertSame("'67890'", $this->object->escape('67890'));
        $this->assertSame("'666.25'", $this->object->escape(666.25));
        $this->assertSame("'abc'", $this->object->escape('abc'));
        $this->assertSame("'1'", $this->object->escape(true));
    }

    /**
     * Test table inspecting.
     */
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
        ], $user->getDriver()->describeTable($user->getTableName()));

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
        ], $user->getDriver()->describeTable($stat->getTableName()));
    }

    /**
     * Test DSN building.
     */
    public function testGetDsn() {
        $this->assertEquals('mysql:dbname=titon_test;host=127.0.0.1;port=3306;charset=utf8', $this->object->getDsn());

        $this->object->config->encoding = '';
        $this->object->config->port = 1337;
        $this->assertEquals('mysql:dbname=titon_test;host=127.0.0.1;port=1337', $this->object->getDsn());

        $this->object->config->dsn = 'custom:dsn';
        $this->assertEquals('custom:dsn', $this->object->getDsn());
    }

    /**
     * Test database inspecting.
     */
    public function testListTables() {
        $this->loadFixtures(['Authors', 'Books', 'Genres', 'BookGenres', 'Series']);

        // Check the keys since the values constantly change
        $this->assertArraysEqual(['authors', 'books', 'books_genres', 'genres', 'series'], $this->object->listTables());
    }

    /**
     * Test query execution.
     */
    public function testQuery() {
        $this->loadFixtures('Users');

        $query1 = (new Query(Query::SELECT, $this->table))->from('users');
        $result = $this->object->query($query1);

        $this->assertInstanceOf('Titon\Db\Query\Result', $result);

        // Test before and after execute
        $this->assertEquals(0, $result->getExecutionTime());
        $this->assertEquals(0, $result->getRowCount());
        $this->assertEquals(false, $result->hasExecuted());
        $this->assertEquals(false, $result->isSuccessful());

        $results = $result->fetchAll();

        $this->assertEquals(5, count($results));
        $this->assertNotEquals(0, $result->getExecutionTime());
        $this->assertEquals(1, $result->getRowCount());
        $this->assertEquals(true, $result->hasExecuted());
        $this->assertEquals(true, $result->isSuccessful());

        // Test with params
        $result = $this->object->query('DELETE FROM users WHERE country_id = ?', [1]);

        $this->assertInstanceOf('Titon\Db\Query\Result', $result);

        $this->assertEquals(0, $result->getRowCount());
        $this->assertEquals(1, $result->save());

        // Test with a string
        $result = $this->object->query('DELETE FROM users');

        $this->assertInstanceOf('Titon\Db\Query\Result', $result);

        $this->assertEquals(0, $result->getRowCount());
        $this->assertEquals(4, $result->save());
    }

    /**
     * Test that query params are resolved for binds.
     * Should be in correct order.
     */
    public function testResolveParams() {
        $query1 = new Query(Query::SELECT, $this->table);
        $query1->where('id', 1)->where(function() {
            $this->like('name', 'Titon')->in('size', [1, 2, 3]);
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
        $query2->fields([
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
        $query3->fields([
            'username' => 'miles',
            'age' => 26
        ])->orWhere(function() {
            $this
                ->in('id', [4, 5, 6])
                ->also(function() {
                    $this->eq('status', true)->notEq('email', 'email@domain.com');
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

    /**
     * Test type introspecting.
     */
    public function testResolveType() {
        $this->assertSame(PDO::PARAM_NULL, $this->object->resolveType(null));
        $this->assertSame(PDO::PARAM_INT, $this->object->resolveType(12345));
        $this->assertSame(PDO::PARAM_INT, $this->object->resolveType('67890'));
        $this->assertSame(PDO::PARAM_STR, $this->object->resolveType(666.25));
        $this->assertSame(PDO::PARAM_STR, $this->object->resolveType('abc'));
        $this->assertSame(PDO::PARAM_BOOL, $this->object->resolveType(true));
    }

}