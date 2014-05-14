<?php
namespace Titon\Db;

use Titon\Db\Driver\AbstractPdoDriver;
use Titon\Db\Finder\ListFinder;
use Titon\Db\Query\Func;
use Titon\Db\Query\Predicate;
use Titon\Db\Query\SubQuery;
use Titon\Test\Stub\BehaviorStub;
use Titon\Test\Stub\Repository\Book;
use Titon\Test\Stub\Repository\Category;
use Titon\Test\Stub\Repository\Genre;
use Titon\Test\Stub\Repository\Order;
use Titon\Test\Stub\Repository\Stat;
use Titon\Test\Stub\Repository\Topic;
use Titon\Test\Stub\Repository\User;
use Titon\Test\Stub\RepositoryStub;
use Titon\Test\TestCase;
use \DateTime;

/**
 * @property \Titon\Db\Repository $object
 */
class RepositoryTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new User();
    }

    public function testAddBehavior() {
        $stub = new RepositoryStub();

        $this->assertFalse($stub->hasBehaviors());
        $this->assertFalse($stub->hasBehavior('Stub'));

        $stub->addBehavior(new BehaviorStub());

        $this->assertTrue($stub->hasBehaviors());
        $this->assertTrue($stub->hasBehavior('Stub'));
    }

    public function testAddFinder() {
        $stub = new RepositoryStub();

        $this->assertTrue($stub->hasFinders());
        $this->assertFalse($stub->hasFinder('stub'));

        $stub->addFinder('stub', new ListFinder());

        $this->assertTrue($stub->hasFinders());
        $this->assertTrue($stub->hasFinder('stub'));
    }

    public function testCount() {
        $this->loadFixtures('Users');

        $this->assertEquals(5, $this->object->select()->count());

        $this->object->delete(1);

        $this->assertEquals(4, $this->object->select()->count());
    }

    public function testCreate() {
        $this->loadFixtures('Users');

        $data = [
            'country_id' => 1,
            'username' => 'ironman',
            'firstName' => 'Tony',
            'lastName' => 'Stark',
            'password' => '7NAks9193KAkjs1',
            'email' => 'ironman@email.com',
            'age' => 38
        ];

        $last_id = $this->object->create($data);

        $this->assertEquals(6, $last_id);
        $this->assertEquals(new Entity([
            'id' => 6,
            'country_id' => 1,
            'username' => 'ironman',
            'firstName' => 'Tony',
            'lastName' => 'Stark',
            'password' => '7NAks9193KAkjs1',
            'email' => 'ironman@email.com',
            'age' => 38,
            'created' => '',
            'modified' => ''
        ]), $this->object->read($last_id));
    }

    /**
     * @expectedException \PDOException
     */
    public function testCreateFailsOnUnique() {
        $this->object->create(['username' => 'ironman']);
        $this->object->create(['username' => 'ironman']); // Fails
    }

    /**
     * @expectedException \Titon\Db\Exception\InvalidQueryException
     */
    public function testCreateFailsOnEmptyData() {
        $this->object->create([]);
    }

    public function testCreateMany() {
        $this->loadFixtures('Users');

        $this->object->truncate(); // Empty first

        $this->assertEquals(0, $this->object->select()->count());

        $this->assertEquals(5, $this->object->createMany([
            ['country_id' => 1, 'username' => 'miles', 'firstName' => 'Miles', 'lastName' => 'Johnson', 'password' => '1Z5895jf72yL77h', 'email' => 'miles@email.com', 'age' => 25, 'created' => '1988-02-26 21:22:34'],
            ['country_id' => 3, 'username' => 'batman', 'firstName' => 'Bruce', 'lastName' => 'Wayne', 'created' => '1960-05-11 21:22:34'],
            ['country_id' => 2, 'username' => 'superman', 'email' => 'superman@email.com', 'age' => 33, 'created' => '1970-09-18 21:22:34'],
            ['country_id' => 5, 'username' => 'spiderman', 'firstName' => 'Peter', 'lastName' => 'Parker', 'password' => '1Z5895jf72yL77h', 'email' => 'spiderman@email.com', 'age' => 22, 'created' => '1990-01-05 21:22:34'],
            ['country_id' => 4, 'username' => 'wolverine', 'password' => '1Z5895jf72yL77h', 'email' => 'wolverine@email.com'],
        ]));

        $this->assertEquals(5, $this->object->select()->count());
    }

    public function testCreateTypeCastingStatements() {
        $this->loadFixtures('Stats');

        $stat = new Stat();
        $time = time();
        $date = date('Y-m-d H:i:s', $time);
        $driver = $stat->getDriver();

        // int
        $query = $driver->executeQuery($stat->query(Query::INSERT)->fields(['health' => '100', 'energy' => 200]));
        $this->assertRegExp("/^INSERT INTO `stats` \(`health`, `energy`\) VALUES \(100, 200\);$/i", $query->getStatement());

        // string
        $query = $driver->executeQuery($stat->query(Query::INSERT)->fields(['name' => 12345]));
        $this->assertRegExp("/^INSERT INTO `stats` \(`name`\) VALUES \('12345'\);$/i", $query->getStatement());

        // float, double, decimal (they are strings in PDO)
        $query = $driver->executeQuery($stat->query(Query::INSERT)->fields(['damage' => '123.45', 'defense' => 456.78, 'range' => 999.00]));
        $this->assertRegExp("/^INSERT INTO `stats` \(`damage`, `defense`, `range`\) VALUES \('123.45', '456.78', '999'\);$/i", $query->getStatement());

        // bool
        $query = $driver->executeQuery($stat->query(Query::INSERT)->fields(['isMelee' => 'true']));
        $this->assertRegExp("/^INSERT INTO `stats` \(`isMelee`\) VALUES \(1\);$/i", $query->getStatement());

        $query = $driver->executeQuery($stat->query(Query::INSERT)->fields(['isMelee' => false]));
        $this->assertRegExp("/^INSERT INTO `stats` \(`isMelee`\) VALUES \(0\);$/i", $query->getStatement());

        // datetime
        $query = $driver->executeQuery($this->object->query(Query::INSERT)->fields(['created' => $time]));
        $this->assertRegExp("/^INSERT INTO `users` \(`created`\) VALUES \('" . $date . "'\);$/i", $query->getStatement());

        $query = $driver->executeQuery($this->object->query(Query::INSERT)->fields(['created' => new DateTime($date)]));
        $this->assertRegExp("/^INSERT INTO `users` \(`created`\) VALUES \('" . $date . "'\);$/i", $query->getStatement());

        $query = $driver->executeQuery($this->object->query(Query::INSERT)->fields(['created' => $date]));
        $this->assertRegExp("/^INSERT INTO `users` \(`created`\) VALUES \('" . $date . "'\);$/i", $query->getStatement());

        // null
        $query = $driver->executeQuery($this->object->query(Query::INSERT)->fields(['created' => null]));
        $this->assertRegExp("/^INSERT INTO `users` \(`created`\) VALUES \(NULL\);$/i", $query->getStatement());
    }

    public function testCreateTable() {
        $sql = sprintf("SELECT COUNT(table_name) FROM information_schema.tables WHERE table_schema = 'titon_test' AND table_name = '%s';", $this->object->getTable());

        $this->assertEquals(0, $this->object->getDriver()->executeQuery($sql)->count());

        $this->object->createTable();

        $this->assertEquals(1, $this->object->getDriver()->executeQuery($sql)->count());

        $this->object->query(Query::DROP_TABLE)->save();

        $this->assertEquals(0, $this->object->getDriver()->executeQuery($sql)->count());
    }

    public function testDecrement() {
        $this->loadFixtures('Topics');

        $topic = new Topic();

        $this->assertEquals(new Entity(['post_count' => 4]), $topic->select('post_count')->where('id', 1)->first());

        $topic->decrement(1, ['post_count' => 1]);

        $this->assertEquals(new Entity(['post_count' => 3]), $topic->select('post_count')->where('id', 1)->first());
    }

    public function testDecrementStep() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->decrement(1, ['post_count' => 3]);

        $this->assertEquals(new Entity(['post_count' => 1]), $topic->select('post_count')->where('id', 1)->first());
    }

    public function testDecrementMany() {
        $this->loadFixtures('Topics');

        $topic = new Topic();

        $this->assertEquals(new EntityCollection([
            new Entity(['post_count' => 4]),
            new Entity(['post_count' => 1])
        ]), $topic->select('post_count')->all());

        $topic->decrement(null, ['post_count' => 1]);

        $this->assertEquals(new EntityCollection([
            new Entity(['post_count' => 3]),
            new Entity(['post_count' => 0])
        ]), $topic->select('post_count')->all());
    }

    public function testDelete() {
        $this->loadFixtures('Users');

        $this->assertTrue($this->object->exists(1));

        $this->assertSame(1, $this->object->delete(1));

        $this->assertFalse($this->object->exists(1));
    }

    public function testDeleteWithConditions() {
        $this->loadFixtures('Users');

        $this->assertSame(5, $this->object->select()->count());

        $this->assertSame(3, $this->object->query(Query::DELETE)->where('age', '>', 30)->save());

        $this->assertSame(2, $this->object->select()->count());
    }

    public function testDeleteWithOrdering() {
        $this->loadFixtures('Users');

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'username' => 'superman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $this->object->select('id', 'username')->orderBy('id', 'asc')->all());

        $this->assertSame(3, $this->object->query(Query::DELETE)->orderBy('age', 'asc')->limit(3)->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $this->object->select('id', 'username')->orderBy('id', 'asc')->all());
    }

    public function testDeleteWithLimit() {
        $this->loadFixtures('Users');

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'username' => 'superman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $this->object->select('id', 'username')->orderBy('id', 'asc')->all());

        $this->assertSame(2, $this->object->query(Query::DELETE)->limit(2)->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 3, 'username' => 'superman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $this->object->select('id', 'username')->orderBy('id', 'asc')->all());
    }

    public function testDeleteMany() {
        $this->loadFixtures('Users');

        $this->assertEquals(3, $this->object->deleteMany(function(Query $query) {
            $query->where('age', '>', 30);
        }));
    }

    /**
     * @expectedException \Titon\Db\Exception\InvalidQueryException
     */
    public function testDeleteManyFailsOnNoConditions() {
        $this->loadFixtures('Users');

        $this->object->deleteMany(function() {});
    }

    public function testExists() {
        $this->loadFixtures('Users');

        $this->assertTrue($this->object->exists(1));
        $this->assertFalse($this->object->exists(10));
    }

    public function testFindFirst() {
        $this->loadFixtures('Users');

        $this->assertEquals(new Entity([
            'id' => 1,
            'country_id' => 1,
            'username' => 'miles',
            'password' => '1Z5895jf72yL77h',
            'email' => 'miles@email.com',
            'firstName' => 'Miles',
            'lastName' => 'Johnson',
            'age' => 25,
            'created' => '1988-02-26 21:22:34',
            'modified' => null
        ]), $this->object->select()->first());
    }

    public function testFindFirstByID() {
        $this->loadFixtures('Users');

        $this->assertEquals(new Entity([
            'id' => 3,
            'country_id' => 2,
            'username' => 'superman',
            'password' => '1Z5895jf72yL77h',
            'email' => 'superman@email.com',
            'firstName' => 'Clark',
            'lastName' => 'Kent',
            'age' => 33,
            'created' => '1970-09-18 21:22:34',
            'modified' => null
        ]), $this->object->select()->where('id', 3)->first());
    }

    public function testFindFirstNoRecords() {
        $this->loadFixtures('Users');

        $this->assertEquals(null, $this->object->select()->where('id', 15)->first());
    }

    public function testFindAll() {
        $this->loadFixtures('Users');

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'country_id' => 1,
                'username' => 'miles',
                'password' => '1Z5895jf72yL77h',
                'email' => 'miles@email.com',
                'firstName' => 'Miles',
                'lastName' => 'Johnson',
                'age' => 25,
                'created' => '1988-02-26 21:22:34',
                'modified' => null
            ]),
            new Entity([
                'id' => 2,
                'country_id' => 3,
                'username' => 'batman',
                'password' => '1Z5895jf72yL77h',
                'email' => 'batman@email.com',
                'firstName' => 'Bruce',
                'lastName' => 'Wayne',
                'age' => 35,
                'created' => '1960-05-11 21:22:34',
                'modified' => null
            ]),
            new Entity([
                'id' => 3,
                'country_id' => 2,
                'username' => 'superman',
                'password' => '1Z5895jf72yL77h',
                'email' => 'superman@email.com',
                'firstName' => 'Clark',
                'lastName' => 'Kent',
                'age' => 33,
                'created' => '1970-09-18 21:22:34',
                'modified' => null
            ]),
            new Entity([
                'id' => 4,
                'country_id' => 5,
                'username' => 'spiderman',
                'password' => '1Z5895jf72yL77h',
                'email' => 'spiderman@email.com',
                'firstName' => 'Peter',
                'lastName' => 'Parker',
                'age' => 22,
                'created' => '1990-01-05 21:22:34',
                'modified' => null
            ]),
            new Entity([
                'id' => 5,
                'country_id' => 4,
                'username' => 'wolverine',
                'password' => '1Z5895jf72yL77h',
                'email' => 'wolverine@email.com',
                'firstName' => 'Logan',
                'lastName' => '',
                'age' => 355,
                'created' => '2000-11-30 21:22:34',
                'modified' => null
            ])
        ]), $this->object->select()->all());
    }

    public function testFindAllWithConditions() {
        $this->loadFixtures('Users');

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 4,
                'country_id' => 5,
                'username' => 'spiderman',
                'password' => '1Z5895jf72yL77h',
                'email' => 'spiderman@email.com',
                'firstName' => 'Peter',
                'lastName' => 'Parker',
                'age' => 22,
                'created' => '1990-01-05 21:22:34',
                'modified' => null
            ]),
            new Entity([
                'id' => 5,
                'country_id' => 4,
                'username' => 'wolverine',
                'password' => '1Z5895jf72yL77h',
                'email' => 'wolverine@email.com',
                'firstName' => 'Logan',
                'lastName' => '',
                'age' => 355,
                'created' => '2000-11-30 21:22:34',
                'modified' => null
            ])
        ]), $this->object->select()->where(function(Predicate $where) {
            $where->gte('id', 4);
        })->all());
    }

    public function testFindAllNoRecords() {
        $this->loadFixtures('Users');

        $this->assertEquals(new EntityCollection(), $this->object->select()->where('country_id', 15)->all());
    }

    public function testFindList() {
        $this->loadFixtures('Users');

        $this->assertEquals([
            1 => 'miles',
            2 => 'batman',
            3 => 'superman',
            4 => 'spiderman',
            5 => 'wolverine'
        ], $this->object->select()->lists('username'));

        $this->assertEquals([
            'miles' => 'Miles',
            'batman' => 'Bruce',
            'superman' => 'Clark',
            'spiderman' => 'Peter',
            'wolverine' => 'Logan'
        ], $this->object->select()->lists('firstName', 'username'));
    }

    public function testFindListFallbackKeys() {
        $this->loadFixtures('Users');

        $this->assertEquals([
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5
        ], $this->object->select()->lists());
    }

    public function testFindListNoRecords() {
        $this->loadFixtures('Users');

        $this->assertEquals([], $this->object->select()->where('country_id', 15)->lists());
    }

    public function testGetAlias() {
        $this->assertEquals('User', $this->object->getAlias());
    }

    public function testGetBehavior() {
        $this->assertFalse($this->object->hasBehavior('Stub'));

        $this->object->addBehavior(new BehaviorStub());

        $this->assertInstanceOf('Titon\Db\Behavior', $this->object->getBehavior('Stub'));

        $this->assertTrue($this->object->hasBehavior('Stub'));
    }

    /**
     * @expectedException \Titon\Db\Exception\MissingBehaviorException
     */
    public function testGetBehaviorMissingKey() {
        $this->object->getBehavior('foobar');
    }

    public function testGetBehaviors() {
        $stub = new BehaviorStub();

        $this->assertEquals([], $this->object->getBehaviors());

        $this->object->addBehavior($stub);

        $this->assertEquals([
            'Stub' => $stub
        ], $this->object->getBehaviors());
    }

    public function testGetConnectionKey() {
        $this->assertEquals('default', $this->object->getConnectionKey());
    }

    public function testGetDisplayField() {
        $this->object->setConfig('displayField', 'username');

        $this->assertEquals('username', $this->object->getDisplayField());
    }

    public function testGetDisplayFieldUnknownField() {
        $this->object->setConfig('displayField', 'foobar'); // Not in schema

        $this->assertEquals('id', $this->object->getDisplayField());
    }

    public function testGetDisplayFieldLoopResolver() {
        $this->object->setConfig('displayField', ['foo', 'bar', 'firstName', 'baz']);

        $this->assertEquals('firstName', $this->object->getDisplayField());
    }

    public function testGetDriver() {
        $this->assertInstanceOf('Titon\Db\Driver', $this->object->getDriver());
    }

    /**
     * @expectedException \Titon\Db\Exception\MissingDriverException
     */
    public function testGetDriverMissingConnection() {
        $object = new RepositoryStub();
        $object->setConfig('connection', 'foobar');
        $object->getDriver();
    }

    public function testGetEntity() {
        $this->assertEquals('Titon\Db\Entity', $this->object->getEntity());
    }

    public function testGetFinder() {
        $this->assertInstanceOf('Titon\Db\Finder', $this->object->getFinder('first'));
    }

    /**
     * @expectedException \Titon\Db\Exception\MissingFinderException
     */
    public function testGetFinderMissingKey() {
        $this->object->getFinder('foobar');
    }

    public function testGetFinders() {
        $this->assertEquals(['first', 'all', 'list'], array_keys($this->object->getFinders()));
    }

    public function testGetPrimaryKey() {
        $this->object->setConfig('primaryKey', 'username');

        $this->assertEquals('username', $this->object->getPrimaryKey());
    }

    public function testGetPrimaryKeyUnknownField() {
        $this->object->setConfig('primaryKey', 'foobar');

        $this->assertEquals('id', $this->object->getPrimaryKey());
    }

    public function testGetSchema() {
        $this->assertInstanceOf('Titon\Db\Driver\Schema', $this->object->getSchema());
    }

    public function testGetTable() {
        $this->assertEquals('users', $this->object->getTable());
    }

    public function testGetTableWithPrefix() {
        $this->object->setConfig('prefix', 'test_');

        $this->assertEquals('test_users', $this->object->getTable());
    }

    public function testGetPrefix() {
        $this->assertEquals('', $this->object->getTablePrefix());

        $this->object->setConfig('prefix', 'test_');

        $this->assertEquals('test_', $this->object->getTablePrefix());
    }

    public function testIncrement() {
        $this->loadFixtures('Topics');

        $topic = new Topic();

        $this->assertEquals(new Entity(['post_count' => 4]), $topic->select('post_count')->where('id', 1)->first());

        $topic->increment(1, ['post_count' => 1]);

        $this->assertEquals(new Entity(['post_count' => 5]), $topic->select('post_count')->where('id', 1)->first());
    }

    public function testIncrementStep() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->increment(1, ['post_count' => 3]);

        $this->assertEquals(new Entity(['post_count' => 7]), $topic->select('post_count')->where('id', 1)->first());
    }

    public function testIncrementMany() {
        $this->loadFixtures('Topics');

        $topic = new Topic();

        $this->assertEquals(new EntityCollection([
            new Entity(['post_count' => 4]),
            new Entity(['post_count' => 1])
        ]), $topic->select('post_count')->all());

        $topic->increment(null, ['post_count' => 3]);

        $this->assertEquals(new EntityCollection([
            new Entity(['post_count' => 7]),
            new Entity(['post_count' => 4])
        ]), $topic->select('post_count')->all());
    }

    public function testQuery() {
        $query = $this->object->query(Query::SELECT);

        $this->assertInstanceOf('Titon\Db\Query', $query);
        $this->assertEquals(Query::SELECT, $query->getType());
        $this->assertEquals('users', $query->getTable());
    }

    public function testRead() {
        $this->loadFixtures('Users');

        $this->assertEquals(new Entity([
            'id' => 3,
            'country_id' => 2,
            'username' => 'superman',
            'password' => '1Z5895jf72yL77h',
            'email' => 'superman@email.com',
            'firstName' => 'Clark',
            'lastName' => 'Kent',
            'age' => 33,
            'created' => '1970-09-18 21:22:34',
            'modified' => null
        ]), $this->object->read(3));
    }

    public function testReadNoRecord() {
        $this->loadFixtures('Users');

        $this->assertEquals(null, $this->object->read(25));
    }

    public function testSelect() {
        $query = new Query(Query::SELECT, $this->object);
        $query->from($this->object->getTable(), 'User')->fields('id', 'username');

        $this->assertEquals($query, $this->object->select('id', 'username'));
    }

    public function testSelectFinders() {
        $this->loadFixtures('Books');

        $book = new Book();

        // Single
        $this->assertEquals(new Entity([
            'id' => 5,
            'series_id' => 1,
            'name' => 'A Dance with Dragons',
            'isbn' => '0-553-80147-3',
            'released' => '2011-07-19'
        ]), $book->select()->where('id', 5)->first());

        // Multiple
        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 13,
                'series_id' => 3,
                'name' => 'The Fellowship of the Ring',
                'isbn' => '',
                'released' => '1954-07-24'
            ]),
            new Entity([
                'id' => 14,
                'series_id' => 3,
                'name' => 'The Two Towers',
                'isbn' => '',
                'released' => '1954-11-11'
            ]),
            new Entity([
                'id' => 15,
                'series_id' => 3,
                'name' => 'The Return of the King',
                'isbn' => '',
                'released' => '1955-10-25'
            ]),
        ]), $book->select()->where('series_id', 3)->orderBy('id', 'asc')->all());
    }

    public function testSelectExpressions() {
        $this->loadFixtures('Stats');

        $stat = new Stat();

        $query = $stat->select();
        $query->fields([
            'name as  role',
            Query::expr('name', 'as', 'class')
        ]);

        $this->assertEquals(new EntityCollection([
            new Entity(['role' => 'Warrior', 'class' => 'Warrior']),
            new Entity(['role' => 'Ranger', 'class' => 'Ranger']),
            new Entity(['role' => 'Mage', 'class' => 'Mage']),
        ]), $query->all());
    }

    public function testSelectRawExpressions() {
        $this->loadFixtures('Stats');

        $stat = new Stat();

        // In place of expr()
        $query = $stat->select();
        $query->fields([
            'name AS role',
            Query::raw('`name` AS `class`')
        ]);

        $this->assertEquals(new EntityCollection([
            new Entity(['role' => 'Warrior', 'class' => 'Warrior']),
            new Entity(['role' => 'Ranger', 'class' => 'Ranger']),
            new Entity(['role' => 'Mage', 'class' => 'Mage']),
        ]), $query->all());

        // In place of func()
        $query = $stat->select();
        $query->fields([
            Query::raw('SUBSTR(`name`, 1, 3) as `shortName`')
        ]);

        $this->assertEquals(new EntityCollection([
            new Entity(['shortName' => 'War']),
            new Entity(['shortName' => 'Ran']),
            new Entity(['shortName' => 'Mag']),
        ]), $query->all());
    }

    public function testSelectFunctions() {
        $this->loadFixtures('Stats');

        $stat = new Stat();

        // SUM
        $query = $stat->select();
        $query->fields([
            Query::func('SUM', ['health' => Func::FIELD])->asAlias('sum')
        ]);

        $this->assertEquals(new Entity(['sum' => 2900]), $query->first());

        // SUBSTRING
        $query = $stat->select();
        $query->fields([
            Query::func('SUBSTR', ['name' => Func::FIELD, 1, 3])->asAlias('shortName')
        ]);

        $this->assertEquals(new EntityCollection([
            new Entity(['shortName' => 'War']),
            new Entity(['shortName' => 'Ran']),
            new Entity(['shortName' => 'Mag']),
        ]), $query->all());

        // SUBSTRING as field in where
        $query = $stat->select('id', 'name');
        $query->where(
            Query::func('SUBSTR', ['name' => Func::FIELD, -3]),
            'ior'
        );

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'name' => 'Warrior'])
        ]), $query->all());
    }

    public function testSelectCount() {
        $this->loadFixtures('Books');

        $book = new Book();

        $query = $book->select();
        $this->assertEquals(15, $query->count());

        $query->where('series_id', 2);
        $this->assertEquals(7, $query->count());

        $query->where('name', 'like', '%prince%');
        $this->assertEquals(1, $query->count());
    }

    public function testSelectLike() {
        $this->loadFixtures('Users');

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'username' => 'superman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
        ]), $this->object->select('id', 'username')->where('username', 'like', '%man%')->orderBy('id', 'asc')->all());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $this->object->select('id', 'username')->where('username', 'notLike', '%man%')->orderBy('id', 'asc')->all());
    }

    public function testSelectRegexp() {
        $this->loadFixtures('Users');

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'username' => 'superman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
        ]), $this->object->select('id', 'username')->where('username', 'regexp', 'man$')->orderBy('id', 'asc')->all());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $this->object->select('id', 'username')->where('username', 'notRegexp', 'man$')->all());
    }

    public function testSelectIn() {
        $this->loadFixtures('Users');

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles']),
            new Entity(['id' => 3, 'username' => 'superman']),
        ]), $this->object->select('id', 'username')->where('id', 'in', [1, 3, 10])->all()); // use fake 10

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $this->object->select('id', 'username')->where('id', 'notIn', [1, 3, 10])->all());
    }

    public function testSelectBetween() {
        $this->loadFixtures('Users');

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'username' => 'superman']),
        ]), $this->object->select('id', 'username')->where('age', 'between', [30, 45])->all());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $this->object->select('id', 'username')->where('age', 'notBetween', [30, 45])->all());
    }

    public function testSelectNull() {
        $this->loadFixtures('Users');

        $this->object->query(Query::UPDATE)->fields(['created' => null])->where('country_id', 1)->save();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles'])
        ]), $this->object->select('id', 'username')->where('created', 'isNull')->all());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'username' => 'superman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $this->object->select('id', 'username')->where('created', 'isNotNull')->orderBy('id', 'asc')->all());
    }

    public function testSelectFieldFiltering() {
        $this->loadFixtures(['Books', 'Series']);

        $book = new Book();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'name' => 'A Game of Thrones']),
            new Entity(['id' => 2, 'name' => 'A Clash of Kings']),
            new Entity(['id' => 3, 'name' => 'A Storm of Swords']),
            new Entity(['id' => 4, 'name' => 'A Feast for Crows']),
            new Entity(['id' => 5, 'name' => 'A Dance with Dragons']),
        ]), $book->select('id', 'name')->where('series_id', 1)->orderBy('id', 'asc')->all());

        $actual = $book->select('id', 'name', 'series_id')
            ->where('series_id', 3)
            ->leftJoin(['series', 'Series'], ['name'], ['Book.series_id' => 'Series.id'])
            ->orderBy('id', 'asc')
            ->all(['eager' => true]);

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 13,
                'name' => 'The Fellowship of the Ring',
                'series_id' => 3,
                'Series' => new Entity([
                    'name' => 'The Lord of the Rings'
                ])
            ]),
            new Entity([
                'id' => 14,
                'name' => 'The Two Towers',
                'series_id' => 3,
                'Series' => new Entity([
                    'name' => 'The Lord of the Rings'
                ])
            ]),
            new Entity([
                'id' => 15,
                'name' => 'The Return of the King',
                'series_id' => 3,
                'Series' => new Entity([
                    'name' => 'The Lord of the Rings'
                ])
            ]),
        ]), $actual);
    }

    /**
     * @expectedException \PDOException
     */
    public function testSelectFieldInvalidColumn() {
        $this->loadFixtures(['Books', 'Series']);

        $book = new Book();
        $book->select('id', 'name', 'author')->all();
    }

    public function testSelectGrouping() {
        $this->loadFixtures('Books');

        $book = new Book();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'name' => 'A Game of Thrones']),
            new Entity(['id' => 6, 'name' => 'Harry Potter and the Philosopher\'s Stone']),
            new Entity(['id' => 13, 'name' => 'The Fellowship of the Ring'])
        ]), $book->select('id', 'name')->groupBy('series_id')->orderBy('id', 'asc')->all());
    }

    public function testSelectLimiting() {
        $this->loadFixtures('Genres');

        $genre = new Genre();

        // Limit only
        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 5, 'name' => 'Horror']),
            new Entity(['id' => 6, 'name' => 'Thriller']),
            new Entity(['id' => 7, 'name' => 'Mystery'])
        ]), $genre->select('id', 'name')->where('id', '>=', 5)->limit(3)->all());

        // Limit and offset
        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 10, 'name' => 'Sci-fi']),
            new Entity(['id' => 11, 'name' => 'Fiction'])
        ]), $genre->select('id', 'name')->where('id', '>=', 7)->limit(3, 3)->all());
    }

    public function testSelectOrdering() {
        $this->loadFixtures('Books');

        $book = new Book();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King']),
            new Entity(['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers']),
            new Entity(['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring']),
            new Entity(['id' => 12, 'series_id' => 2, 'name' => 'Harry Potter and the Deathly Hallows']),
            new Entity(['id' => 11, 'series_id' => 2, 'name' => 'Harry Potter and the Half-blood Prince']),
            new Entity(['id' => 10, 'series_id' => 2, 'name' => 'Harry Potter and the Order of the Phoenix']),
            new Entity(['id' => 9, 'series_id' => 2, 'name' => 'Harry Potter and the Goblet of Fire']),
            new Entity(['id' => 8, 'series_id' => 2, 'name' => 'Harry Potter and the Prisoner of Azkaban']),
            new Entity(['id' => 7, 'series_id' => 2, 'name' => 'Harry Potter and the Chamber of Secrets']),
            new Entity(['id' => 6, 'series_id' => 2, 'name' => 'Harry Potter and the Philosopher\'s Stone']),
            new Entity(['id' => 5, 'series_id' => 1, 'name' => 'A Dance with Dragons']),
            new Entity(['id' => 4, 'series_id' => 1, 'name' => 'A Feast for Crows']),
            new Entity(['id' => 3, 'series_id' => 1, 'name' => 'A Storm of Swords']),
            new Entity(['id' => 2, 'series_id' => 1, 'name' => 'A Clash of Kings']),
            new Entity(['id' => 1, 'series_id' => 1, 'name' => 'A Game of Thrones']),
        ]), $book->select('id', 'series_id', 'name')->orderBy([
            'series_id' => 'desc',
            'id' => 'desc'
        ])->all());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring']),
            new Entity(['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King']),
            new Entity(['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers']),
            new Entity(['id' => 7, 'series_id' => 2, 'name' => 'Harry Potter and the Chamber of Secrets']),
            new Entity(['id' => 12, 'series_id' => 2, 'name' => 'Harry Potter and the Deathly Hallows']),
            new Entity(['id' => 9, 'series_id' => 2, 'name' => 'Harry Potter and the Goblet of Fire']),
            new Entity(['id' => 11, 'series_id' => 2, 'name' => 'Harry Potter and the Half-blood Prince']),
            new Entity(['id' => 10, 'series_id' => 2, 'name' => 'Harry Potter and the Order of the Phoenix']),
            new Entity(['id' => 6, 'series_id' => 2, 'name' => 'Harry Potter and the Philosopher\'s Stone']),
            new Entity(['id' => 8, 'series_id' => 2, 'name' => 'Harry Potter and the Prisoner of Azkaban']),
            new Entity(['id' => 2, 'series_id' => 1, 'name' => 'A Clash of Kings']),
            new Entity(['id' => 5, 'series_id' => 1, 'name' => 'A Dance with Dragons']),
            new Entity(['id' => 4, 'series_id' => 1, 'name' => 'A Feast for Crows']),
            new Entity(['id' => 1, 'series_id' => 1, 'name' => 'A Game of Thrones']),
            new Entity(['id' => 3, 'series_id' => 1, 'name' => 'A Storm of Swords']),
        ]), $book->select('id', 'series_id', 'name')->orderBy([
            'series_id' => 'desc',
            'name' => 'asc'
        ])->all());

        // Randomizing
        $this->assertNotEquals(new EntityCollection([
            new Entity(['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King']),
            new Entity(['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers']),
            new Entity(['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring']),
            new Entity(['id' => 12, 'series_id' => 2, 'name' => 'Harry Potter and the Deathly Hallows']),
            new Entity(['id' => 11, 'series_id' => 2, 'name' => 'Harry Potter and the Half-blood Prince']),
            new Entity(['id' => 10, 'series_id' => 2, 'name' => 'Harry Potter and the Order of the Phoenix']),
            new Entity(['id' => 9, 'series_id' => 2, 'name' => 'Harry Potter and the Goblet of Fire']),
            new Entity(['id' => 8, 'series_id' => 2, 'name' => 'Harry Potter and the Prisoner of Azkaban']),
            new Entity(['id' => 7, 'series_id' => 2, 'name' => 'Harry Potter and the Chamber of Secrets']),
            new Entity(['id' => 6, 'series_id' => 2, 'name' => 'Harry Potter and the Philosopher\'s Stone']),
            new Entity(['id' => 5, 'series_id' => 1, 'name' => 'A Dance with Dragons']),
            new Entity(['id' => 4, 'series_id' => 1, 'name' => 'A Feast for Crows']),
            new Entity(['id' => 3, 'series_id' => 1, 'name' => 'A Storm of Swords']),
            new Entity(['id' => 2, 'series_id' => 1, 'name' => 'A Clash of Kings']),
            new Entity(['id' => 1, 'series_id' => 1, 'name' => 'A Game of Thrones']),
        ]), $book->select('id', 'series_id', 'name')->orderBy('RAND')->all());
    }

    public function testSelectWhereAnd() {
        $this->loadFixtures('Stats');

        $stat = new Stat();

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 2,
                'name' => 'Ranger',
                'health' => 800,
                'isMelee' => false
            ])
        ]), $stat->select('id', 'name', 'health', 'isMelee')
            ->where('isMelee', false)
            ->where('health', '>=', 700)
            ->all());

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 2,
                'name' => 'Ranger',
                'health' => 800,
                'energy' => 335,
                'range' => 6.75
            ]),
            new Entity([
                'id' => 3,
                'name' => 'Mage',
                'health' => 600,
                'energy' => 600,
                'range' => 8.33
            ])
        ]), $stat->select('id', 'name', 'health', 'energy', 'range')
            ->where('health', '<', 1000)
            ->where('range', '>=', 5)
            ->where('energy', '!=', 0)
            ->all());

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'name' => 'Warrior',
                'health' => 1500,
                'isMelee' => true,
                'range' => 1
            ])
        ]), $stat->select('id', 'name', 'health', 'isMelee', 'range')
            ->where(function(Predicate $where) {
                $where->gte('health', 500)->lte('range', 7)->eq('isMelee', true);
            })->all());
    }

    public function testSelectWhereOr() {
        $this->loadFixtures('Stats');

        $stat = new Stat();

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'name' => 'Warrior',
                'health' => 1500,
                'range' => 1
            ]),
            new Entity([
                'id' => 3,
                'name' => 'Mage',
                'health' => 600,
                'range' => 8.33
            ])
        ]), $stat->select('id', 'name', 'health', 'range')
            ->orWhere('health', '>', 1000)
            ->orWhere('range', '>', 7)
            ->all());

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'name' => 'Warrior',
                'damage' => 125.25,
                'defense' => 55.75,
                'range' => 1
            ]),
            new Entity([
                'id' => 2,
                'name' => 'Ranger',
                'damage' => 90.45,
                'defense' => 30.5,
                'range' => 6.75
            ]),
            new Entity([
                'id' => 3,
                'name' => 'Mage',
                'damage' => 55.84,
                'defense' => 40.15,
                'range' => 8.33
            ])
        ]), $stat->select('id', 'name', 'damage', 'defense', 'range')
            ->orWhere(function(Predicate $where) {
                $where->gt('damage', 100)->gt('range', 5)->gt('defense', 50);
            })
            ->all());
    }

    public function testSelectWhereNested() {
        $this->loadFixtures('Stats');

        $stat = new Stat();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 3, 'name' => 'Mage'])
        ]), $stat->select('id', 'name')
            ->where(function(Predicate $where) {
                $where->eq('isMelee', false);
                $where->either(function(Predicate $where2) {
                    $where2->lte('health', 600)->lte('damage', 60);
                });
            })->all());
    }

    public function testSelectHavingAnd() {
        $this->loadFixtures('Orders');

        $order = new Order();
        $query = $order->select();
        $query
            ->fields([
                'id', 'user_id', 'quantity', 'status', 'shipped',
                Query::func('SUM', ['quantity' => 'field'])->asAlias('qty'),
                Query::func('COUNT', ['user_id' => 'field'])->asAlias('count')
            ])
            ->groupBy('user_id');

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'user_id' => 1, 'quantity' => 15, 'status' => 'pending', 'shipped' => null, 'qty' => 97, 'count' => 5]),
            new Entity(['id' => 2, 'user_id' => 2, 'quantity' => 33, 'status' => 'pending', 'shipped' => null, 'qty' => 77, 'count' => 5]),
            new Entity(['id' => 3, 'user_id' => 3, 'quantity' => 4, 'status' => 'pending', 'shipped' => null, 'qty' => 90, 'count' => 7]),
            new Entity(['id' => 4, 'user_id' => 4, 'quantity' => 24, 'status' => 'pending', 'shipped' => null, 'qty' => 114, 'count' => 7]),
            new Entity(['id' => 5, 'user_id' => 5, 'quantity' => 29, 'status' => 'pending', 'shipped' => null, 'qty' => 112, 'count' => 6]),
        ]), $query->all());

        $query->having('qty', '>', 100);

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 4, 'user_id' => 4, 'quantity' => 24, 'status' => 'pending', 'shipped' => null, 'qty' => 114, 'count' => 7]),
            new Entity(['id' => 5, 'user_id' => 5, 'quantity' => 29, 'status' => 'pending', 'shipped' => null, 'qty' => 112, 'count' => 6]),
        ]), $query->all());

        $query->having('count', '>', 6);

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 4, 'user_id' => 4, 'quantity' => 24, 'status' => 'pending', 'shipped' => null, 'qty' => 114, 'count' => 7])
        ]), $query->all());
    }

    public function testSelectHavingOr() {
        $this->loadFixtures('Orders');

        $order = new Order();
        $query = $order->select();
        $query
            ->fields([
                'id', 'user_id', 'quantity', 'status', 'shipped',
                Query::func('SUM', ['quantity' => 'field'])->asAlias('qty'),
                Query::func('COUNT', ['user_id' => 'field'])->asAlias('count')
            ])
            ->groupBy('user_id');

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'user_id' => 1, 'quantity' => 15, 'status' => 'pending', 'shipped' => null, 'qty' => 97, 'count' => 5]),
            new Entity(['id' => 2, 'user_id' => 2, 'quantity' => 33, 'status' => 'pending', 'shipped' => null, 'qty' => 77, 'count' => 5]),
            new Entity(['id' => 3, 'user_id' => 3, 'quantity' => 4, 'status' => 'pending', 'shipped' => null, 'qty' => 90, 'count' => 7]),
            new Entity(['id' => 4, 'user_id' => 4, 'quantity' => 24, 'status' => 'pending', 'shipped' => null, 'qty' => 114, 'count' => 7]),
            new Entity(['id' => 5, 'user_id' => 5, 'quantity' => 29, 'status' => 'pending', 'shipped' => null, 'qty' => 112, 'count' => 6]),
        ]), $query->all());

        $query->orHaving('qty', '<=', 90);

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'user_id' => 2, 'quantity' => 33, 'status' => 'pending', 'shipped' => null, 'qty' => 77, 'count' => 5]),
            new Entity(['id' => 3, 'user_id' => 3, 'quantity' => 4, 'status' => 'pending', 'shipped' => null, 'qty' => 90, 'count' => 7]),
        ]), $query->all());

        $query->orHaving('count', '>=', 6);

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'user_id' => 2, 'quantity' => 33, 'status' => 'pending', 'shipped' => null, 'qty' => 77, 'count' => 5]),
            new Entity(['id' => 3, 'user_id' => 3, 'quantity' => 4, 'status' => 'pending', 'shipped' => null, 'qty' => 90, 'count' => 7]),
            new Entity(['id' => 4, 'user_id' => 4, 'quantity' => 24, 'status' => 'pending', 'shipped' => null, 'qty' => 114, 'count' => 7]),
            new Entity(['id' => 5, 'user_id' => 5, 'quantity' => 29, 'status' => 'pending', 'shipped' => null, 'qty' => 112, 'count' => 6]),
        ]), $query->all());
    }

    public function testSelectHavingNested() {
        $this->loadFixtures('Orders');

        $order = new Order();
        $query = $order->select();
        $query
            ->fields([
                'id', 'user_id', 'quantity', 'status', 'shipped',
                Query::func('SUM', ['quantity' => 'field'])->asAlias('qty'),
                Query::func('COUNT', ['user_id' => 'field'])->asAlias('count')
            ])
            ->where('status', '!=', 'pending')
            ->groupBy('user_id')
            ->having(function(Predicate $having) {
                $having->between('qty', 40, 50);
                $having->either(function(Predicate $having2) {
                    $having2->eq('status', 'shipped');
                    $having2->eq('status', 'delivered');
                });
            });

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 21, 'user_id' => 1, 'quantity' => 17, 'status' => 'delivered', 'shipped' => '2013-05-27 12:33:02', 'qty' => 49, 'count' => 3]),
            new Entity(['id' => 17, 'user_id' => 2, 'quantity' => 26, 'status' => 'shipped', 'shipped' => '2013-06-28 12:33:02', 'qty' => 41, 'count' => 2]),
            new Entity(['id' => 19, 'user_id' => 4, 'quantity' => 20, 'status' => 'delivered', 'shipped' => '2013-06-30 12:33:02', 'qty' => 40, 'count' => 3]),
        ]), $query->all());
    }

    public function testSelectInnerJoin() {
        $this->loadFixtures(['Users', 'Countries']);

        $this->object->update([2, 5], ['country_id' => null]); // Reset some records

        $query = $this->object->select('id', 'username')
            ->innerJoin(['countries', 'Country'], ['id', 'name', 'iso'], ['User.country_id' => 'Country.id'])
            ->orderBy('User.id', 'asc');

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'username' => 'miles',
                'Country' => new Entity([
                    'id' => 1,
                    'name' => 'United States of America',
                    'iso' => 'USA'
                ])
            ]),
            new Entity([
                'id' => 3,
                'username' => 'superman',
                'Country' => new Entity([
                    'id' => 2,
                    'name' => 'Canada',
                    'iso' => 'CAN'
                ])
            ]),
            new Entity([
                'id' => 4,
                'username' => 'spiderman',
                'Country' => new Entity([
                    'id' => 5,
                    'name' => 'Mexico',
                    'iso' => 'MEX'
                ])
            ])
        ]), $query->all());
    }

    public function testSelectOuterJoin() {
        $this->loadFixtures(['Users', 'Countries']);

        $this->object->update([2, 5], ['country_id' => null]); // Reset some records

        if ($this->object->getDriver() instanceof AbstractPdoDriver) {
            if ($this->object->getDriver()->getDriver() === 'mysql') {
                $this->markTestSkipped('MySQL does not support outer joins');
            }
        }

        $query = $this->object->select()
            ->fields('id', 'username')
            ->outerJoin(['countries', 'Country'], ['id', 'name', 'iso'], ['country_id' => 'Country.id'])
            ->orderBy('User.id', 'asc');

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'username' => 'miles',
                'Country' => new Entity([
                    'id' => 1,
                    'name' => 'United States of America',
                    'iso' => 'USA'
                ])
            ]),
            new Entity([
                'id' => 2,
                'username' => 'batman',
                'Country' => new Entity([
                    'id' => null,
                    'name' => null,
                    'iso' => null
                ])
            ]),
            new Entity([
                'id' => 3,
                'username' => 'superman',
                'Country' => new Entity([
                    'id' => 2,
                    'name' => 'Canada',
                    'iso' => 'CAN'
                ])
            ]),
            new Entity([
                'id' => 4,
                'username' => 'spiderman',
                'Country' => new Entity([
                    'id' => 5,
                    'name' => 'Mexico',
                    'iso' => 'MEX'
                ])
            ]),
            new Entity([
                'id' => 5,
                'username' => 'wolverine',
                'Country' => new Entity([
                    'id' => null,
                    'name' => null,
                    'iso' => null
                ])
            ]),
            new Entity([
                'id' => null,
                'username' => null,
                'Country' => new Entity([
                    'id' => 4,
                    'name' => 'Australia',
                    'iso' => 'AUS'
                ])
            ]),
            new Entity([
                'id' => null,
                'username' => null,
                'Country' => new Entity([
                    'id' => 3,
                    'name' => 'England',
                    'iso' => 'ENG'
                ])
            ]),
        ]), $query->all());
    }

    public function testSelectLeftJoin() {
        $this->loadFixtures(['Users', 'Countries']);

        $this->object->update([2, 5], ['country_id' => null]); // Reset some records

        $query = $this->object->select('id', 'username')
            ->leftJoin(['countries', 'Country'], ['id', 'name', 'iso'], ['country_id' => 'Country.id'])
            ->orderBy('User.id', 'asc');

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'username' => 'miles',
                'Country' => new Entity([
                    'id' => 1,
                    'name' => 'United States of America',
                    'iso' => 'USA'
                ])
            ]),
            // Empty country
            new Entity([
                'id' => 2,
                'username' => 'batman',
                'Country' => new Entity([
                    'id' => null,
                    'name' => null,
                    'iso' => null
                ])
            ]),
            new Entity([
                'id' => 3,
                'username' => 'superman',
                'Country' => new Entity([
                    'id' => 2,
                    'name' => 'Canada',
                    'iso' => 'CAN'
                ])
            ]),
            new Entity([
                'id' => 4,
                'username' => 'spiderman',
                'Country' => new Entity([
                    'id' => 5,
                    'name' => 'Mexico',
                    'iso' => 'MEX'
                ])
            ]),
            // Empty country
            new Entity([
                'id' => 5,
                'username' => 'wolverine',
                'Country' => new Entity([
                    'id' => null,
                    'name' => null,
                    'iso' => null
                ])
            ]),
        ]), $query->all());
    }

    public function testSelectRightJoin() {
        $this->loadFixtures(['Users', 'Countries']);

        $this->object->update([2, 5], ['country_id' => null]); // Reset some records

        $query = $this->object->select('id', 'username')
            ->rightJoin(['countries', 'Country'], ['id', 'name', 'iso'], ['country_id' => 'Country.id'])
            ->orderBy('User.id', 'asc');

        $this->assertEquals(new EntityCollection([
            // Empty user
            new Entity([
                'id' => null,
                'username' => null,
                'Country' => new Entity([
                    'id' => 3,
                    'name' => 'England',
                    'iso' => 'ENG'
                ])
            ]),
            // Empty user
            new Entity([
                'id' => null,
                'username' => null,
                'Country' => new Entity([
                    'id' => 4,
                    'name' => 'Australia',
                    'iso' => 'AUS'
                ])
            ]),
            new Entity([
                'id' => 1,
                'username' => 'miles',
                'Country' => new Entity([
                    'id' => 1,
                    'name' => 'United States of America',
                    'iso' => 'USA'
                ])
            ]),
            new Entity([
                'id' => 3,
                'username' => 'superman',
                'Country' => new Entity([
                    'id' => 2,
                    'name' => 'Canada',
                    'iso' => 'CAN'
                ])
            ]),
            new Entity([
                'id' => 4,
                'username' => 'spiderman',
                'Country' => new Entity([
                    'id' => 5,
                    'name' => 'Mexico',
                    'iso' => 'MEX'
                ])
            ])
        ]), $query->all());
    }

    public function testSelectStraightJoin() {
        $this->loadFixtures(['Users', 'Countries']);

        $this->object->update([2, 5], ['country_id' => null]); // Reset some records

        $query = $this->object->select('id', 'username')
            ->straightJoin(['countries', 'Country'], ['id', 'name', 'iso'], ['country_id' => 'Country.id'])
            ->orderBy('User.id', 'asc');

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'username' => 'miles',
                'Country' => new Entity([
                    'id' => 1,
                    'name' => 'United States of America',
                    'iso' => 'USA'
                ])
            ]),
            new Entity([
                'id' => 3,
                'username' => 'superman',
                'Country' => new Entity([
                    'id' => 2,
                    'name' => 'Canada',
                    'iso' => 'CAN'
                ])
            ]),
            new Entity([
                'id' => 4,
                'username' => 'spiderman',
                'Country' => new Entity([
                    'id' => 5,
                    'name' => 'Mexico',
                    'iso' => 'MEX'
                ])
            ])
        ]), $query->all());
    }

    public function testSelectJoinsWithFunction() {
        $this->loadFixtures(['Users', 'Countries']);

        $query = $this->object->select()->where('User.id', 1);
        $query->fields([
            'id', 'username',
            Query::func('SUBSTR', ['username' => Func::FIELD, 1, 3])->asAlias('shortName')
        ]);
        $query->leftJoin(['countries', 'Country'], [
            'id', 'name', 'iso',
            Query::func('SUBSTR', ['Country.name' => Func::FIELD, 1, 6])->asAlias('countryName')
        ], ['country_id' => 'Country.id']);

        $this->assertEquals(new Entity([
            'id' => 1,
            'username' => 'miles',
            'shortName' => 'mil',
            'countryName' => 'United',
            'Country' => new Entity([
                'id' => 1,
                'name' => 'United States of America',
                'iso' => 'USA'
            ])
        ]), $query->first());
    }

    public function testSelectJoinToSelf() {
        $this->loadFixtures('Categories');

        $category = new Category();

        $query = $category->select()
            ->leftJoin(['categories', 'Parent'], ['id', 'parent_id', 'name', 'left', 'right'], ['Category.parent_id' => 'Parent.id'])
            ->where('Category.id', 2);

        $this->assertEquals(new Entity([
            'id' => 2,
            'parent_id' => 1,
            'name' => 'Banana',
            'left' => 2,
            'right' => 3,
            'Parent' => new Entity([
                'id' => 1,
                'parent_id' => null,
                'name' => 'Fruit',
                'left' => 1,
                'right' => 20,
            ])
        ]), $query->first());
    }

    public function testSelectUnions() {
        $this->loadFixtures(['Users', 'Books', 'Authors']);

        $query = $this->object->select('username AS name');
        $query->union($query->subQuery('name')->from('books')->where('series_id', 1));
        $query->union($query->subQuery('name')->from('authors'));

        $this->assertEquals(new EntityCollection([
            new Entity(['name' => 'batman']),
            new Entity(['name' => 'miles']),
            new Entity(['name' => 'spiderman']),
            new Entity(['name' => 'superman']),
            new Entity(['name' => 'wolverine']),
            new Entity(['name' => 'A Game of Thrones']),
            new Entity(['name' => 'A Clash of Kings']),
            new Entity(['name' => 'A Storm of Swords']),
            new Entity(['name' => 'A Feast for Crows']),
            new Entity(['name' => 'A Dance with Dragons']),
            new Entity(['name' => 'George R. R. Martin']),
            new Entity(['name' => 'J. K. Rowling']),
            new Entity(['name' => 'J. R. R. Tolkien']),
        ]), $query->all());

        $query->orderBy('name', 'desc')->limit(10);

        $this->assertEquals(new EntityCollection([
            new Entity(['name' => 'wolverine']),
            new Entity(['name' => 'superman']),
            new Entity(['name' => 'spiderman']),
            new Entity(['name' => 'miles']),
            new Entity(['name' => 'J. R. R. Tolkien']),
            new Entity(['name' => 'J. K. Rowling']),
            new Entity(['name' => 'George R. R. Martin']),
            new Entity(['name' => 'batman']),
            new Entity(['name' => 'A Storm of Swords']),
            new Entity(['name' => 'A Game of Thrones']),
        ]), $query->all());
    }

    public function testSelectSubQueries() {
        $this->loadFixtures(['Users', 'Profiles', 'Countries']);

        // ANY filter
        $query = $this->object->select('id', 'country_id', 'username');
        $query->where('country_id', '=', $query->subQuery('id')->from('countries')->withFilter(SubQuery::ANY))->orderBy('id', 'asc');

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => 3, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => 2, 'username' => 'superman']),
            new Entity(['id' => 4, 'country_id' => 5, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 4, 'username' => 'wolverine']),
        ]), $query->all());

        // Single record
        $query = $this->object->select('id', 'country_id', 'username');
        $query->where('country_id', '=', $query->subQuery('id')->from('countries')->where('iso', 'USA'));

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles'])
        ]), $query->all());
    }

    public function testSelectTypeCastingStatements() {
        $this->loadFixtures('Stats');

        $stat = new Stat();
        $time = time();
        $date = date('Y-m-d H:i:s', $time);
        $driver = $stat->getDriver();

        // int
        $query = $driver->executeQuery($stat->select()->where('health', '>', '100'));
        $this->assertRegExp("/^SELECT \* FROM `stats` WHERE `health` > 100;$/i", $query->getStatement());

        $query = $driver->executeQuery($stat->select()->where('id', [1, '2', 3]));
        $this->assertRegExp("/^SELECT \* FROM `stats` WHERE `id` IN \(1, 2, 3\);$/i", $query->getStatement());

        // string
        $query = $driver->executeQuery($stat->select()->where('name', '!=', 123.45));
        $this->assertRegExp("/^SELECT \* FROM `stats` WHERE `name` != '123.45';$/i", $query->getStatement());

        // float (they are strings in PDO)
        $query = $driver->executeQuery($stat->select()->where('damage', '<', 55.25));
        $this->assertRegExp("/^SELECT \* FROM `stats` WHERE `damage` < '55.25';$/i", $query->getStatement());

        // bool
        $query = $driver->executeQuery($stat->select()->where('isMelee', true));
        $this->assertRegExp("/^SELECT \* FROM `stats` WHERE `isMelee` = 1;$/i", $query->getStatement());

        $query = $driver->executeQuery($stat->select()->where('isMelee', '0'));
        $this->assertRegExp("/^SELECT \* FROM `stats` WHERE `isMelee` = 0;$/i", $query->getStatement());

        // datetime
        $query = $driver->executeQuery($this->object->select()->where('created', '>', $time));
        $this->assertRegExp("/^SELECT \* FROM `users` WHERE `created` > '" . $date . "';$/i", $query->getStatement());

        $query = $driver->executeQuery($this->object->select()->where('created', '<=', new DateTime($date)));
        $this->assertRegExp("/^SELECT \* FROM `users` WHERE `created` <= '" . $date . "';$/i", $query->getStatement());

        $query = $driver->executeQuery($this->object->select()->where('created', '!=', $date));
        $this->assertRegExp("/^SELECT \* FROM `users` WHERE `created` != '" . $date . "';$/i", $query->getStatement());

        // null
        $query = $driver->executeQuery($this->object->select()->where('created', null));
        $this->assertRegExp("/^SELECT \* FROM `users` WHERE `created` IS NULL;$/i", $query->getStatement());

        $query = $driver->executeQuery($this->object->select()->where('created', '!=', null));
        $this->assertRegExp("/^SELECT \* FROM `users` WHERE `created` IS NOT NULL;$/i", $query->getStatement());
    }

    public function testTruncate() {
        $this->loadFixtures('Users');

        $this->assertEquals(5, $this->object->select()->count());

        $this->object->truncate();

        $this->assertEquals(0, $this->object->select()->count());
    }

    public function testUpdate() {
        $this->loadFixtures('Users');

        $this->assertEquals(1, $this->object->update(1, [
            'country_id' => 3,
            'username' => 'milesj'
        ]));

        $this->assertEquals(new Entity([
            'id' => 1,
            'country_id' => 3,
            'username' => 'milesj',
            'password' => '1Z5895jf72yL77h',
            'email' => 'miles@email.com',
            'firstName' => 'Miles',
            'lastName' => 'Johnson',
            'age' => 25,
            'created' => '1988-02-26 21:22:34',
            'modified' => null
        ]), $this->object->read(1));
    }

    public function testUpdateNonExistingRecord() {
        $this->loadFixtures('Users');

        $this->assertEquals(0, $this->object->update(10, [
            'id' => 10,
            'username' => 'foobar'
        ]));
    }

    /**
     * @expectedException \PDOException
     */
    public function testUpdateFailsOnUnique() {
        $this->object->update(1, ['username' => 'batman']); // Fails
    }

    /**
     * @expectedException \Titon\Db\Exception\InvalidQueryException
     */
    public function testUpdateFailsOnEmptyData() {
        $this->loadFixtures('Users');

        $this->object->update(1, []);
    }

    public function testUpdateWithExpressions() {
        $this->loadFixtures('Stats');

        $stat = new Stat();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'name' => 'Warrior', 'health' => 1500]),
            new Entity(['id' => 2, 'name' => 'Ranger', 'health' => 800]),
            new Entity(['id' => 3, 'name' => 'Mage', 'health' => 600]),
        ]), $stat->select('id', 'name', 'health')->orderBy('id', 'asc')->all());

        $query = $stat->query(Query::UPDATE);
        $query->fields(['health' => Query::expr('health', '+', 75)]);

        $this->assertEquals(3, $query->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'name' => 'Warrior', 'health' => 1575]),
            new Entity(['id' => 2, 'name' => 'Ranger', 'health' => 875]),
            new Entity(['id' => 3, 'name' => 'Mage', 'health' => 675]),
        ]), $stat->select('id', 'name', 'health')->orderBy('id', 'asc')->all());

        $this->assertEquals(1, $stat->update(2, [
            'health' => Query::expr('health', '-', 125)
        ]));

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'name' => 'Warrior', 'health' => 1575]),
            new Entity(['id' => 2, 'name' => 'Ranger', 'health' => 750]),
            new Entity(['id' => 3, 'name' => 'Mage', 'health' => 675]),
        ]), $stat->select('id', 'name', 'health')->orderBy('id', 'asc')->all());
    }

    public function testUpdateMultiple() {
        $this->loadFixtures('Users');

        $this->assertSame(4, $this->object->query(Query::UPDATE)->fields(['country_id' => 1])->where('country_id', '!=', 1)->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => 1, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => 1, 'username' => 'superman']),
            new Entity(['id' => 4, 'country_id' => 1, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 1, 'username' => 'wolverine']),
        ]), $this->object->select('id', 'country_id', 'username')->orderBy('id', 'asc')->all());

        // No where clause
        $this->assertSame(5, $this->object->query(Query::UPDATE)->fields(['country_id' => 2])->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 2, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => 2, 'username' => 'superman']),
            new Entity(['id' => 4, 'country_id' => 2, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 2, 'username' => 'wolverine']),
        ]), $this->object->select('id', 'country_id', 'username')->orderBy('id', 'asc')->all());
    }

    public function testUpdateMultipleWithLimit() {
        $this->loadFixtures('Users');

        $this->assertSame(2, $this->object->query(Query::UPDATE)->fields(['country_id' => 1])->where('country_id', '!=', 1)->limit(2)->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => 1, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => 1, 'username' => 'superman']),
            new Entity(['id' => 4, 'country_id' => 5, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 4, 'username' => 'wolverine']),
        ]), $this->object->select('id', 'country_id', 'username')->orderBy('id', 'asc')->all());

        // No where clause, offset ignored
        $this->assertSame(2, $this->object->query(Query::UPDATE)->fields(['country_id' => 5])->limit(2, 2)->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 5, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => 5, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => 1, 'username' => 'superman']),
            new Entity(['id' => 4, 'country_id' => 5, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 4, 'username' => 'wolverine']),
        ]), $this->object->select('id', 'country_id', 'username')->orderBy('id', 'asc')->all());
    }

    public function testUpdateMultipleWithOrderBy() {
        $this->loadFixtures('Users');

        $this->assertSame(2, $this->object->query(Query::UPDATE)
            ->fields(['country_id' => 6])
            ->orderBy('username', 'desc')
            ->limit(2)
            ->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => 3, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => 6, 'username' => 'superman']), // changed
            new Entity(['id' => 4, 'country_id' => 5, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 6, 'username' => 'wolverine']), // changed
        ]), $this->object->select('id', 'country_id', 'username')->orderBy('id', 'asc')->all());
    }

    public function testUpdateMultipleWithConditions() {
        $this->loadFixtures('Users');

        $this->assertSame(3, $this->object->query(Query::UPDATE)
            ->fields(['country_id' => null])
            ->where('username', 'like', '%man%')
            ->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => null, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => null, 'username' => 'superman']),
            new Entity(['id' => 4, 'country_id' => null, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 4, 'username' => 'wolverine']),
        ]), $this->object->select('id', 'country_id', 'username')->orderBy('id', 'asc')->all());
    }

    public function testUpdateMultipleEmptyValue() {
        $this->loadFixtures('Users');

        $this->assertSame(5, $this->object->query(Query::UPDATE)
            ->fields(['firstName' => ''])
            ->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles', 'firstName' => '']),
            new Entity(['id' => 2, 'username' => 'batman', 'firstName' => '']),
            new Entity(['id' => 3, 'username' => 'superman', 'firstName' => '']),
            new Entity(['id' => 4, 'username' => 'spiderman', 'firstName' => '']),
            new Entity(['id' => 5, 'username' => 'wolverine', 'firstName' => '']),
        ]), $this->object->select('id', 'username', 'firstName')->orderBy('id', 'asc')->all());
    }

    public function testUpdateTypeCasting() {
        $this->loadFixtures('Stats');

        $stat = new Stat();
        $data = [
            'health' => '2000', // to int
            'energy' => '300', // to int
            'damage' => 145, // to float
            'defense' => 60.25, // to double
            'range' => '2', // to decimal
            'isMelee' => false, // to boolean
        ];

        $this->assertEquals(1, $stat->update(1, $data));

        $expected = $stat->select()->where('id', 1)->first()->toArray();
        unset($expected['data'], $expected['id']);

        $this->assertSame([
            'name' => 'Warrior',
            'health' => 2000,
            'energy' => 300,
            'damage' => 145.0,
            'defense' => 60.25,
            'range' => 2.0,
            'isMelee' => false
        ], $expected);
    }

    public function testUpdateTypeCastingStatements() {
        $this->loadFixtures('Stats');

        $stat = new Stat();
        $time = time();
        $date = date('Y-m-d H:i:s', $time);
        $driver = $stat->getDriver();

        // int
        $query = $driver->executeQuery($stat->query(Query::UPDATE)->fields(['health' => '100', 'energy' => 200]));
        $this->assertRegExp("/^UPDATE `stats` SET `health` = 100, `energy` = 200;$/i", $query->getStatement());

        // string
        $query = $driver->executeQuery($stat->query(Query::UPDATE)->fields(['name' => 12345]));
        $this->assertRegExp("/^UPDATE `stats` SET `name` = '12345';$/i", $query->getStatement());

        // float, double, decimal (they are strings in PDO)
        $query = $driver->executeQuery($stat->query(Query::UPDATE)->fields(['damage' => '123.45', 'defense' => 456.78, 'range' => 999.00]));
        $this->assertRegExp("/^UPDATE `stats` SET `damage` = '123.45', `defense` = '456.78', `range` = '999';$/i", $query->getStatement());

        // bool
        $query = $driver->executeQuery($stat->query(Query::UPDATE)->fields(['isMelee' => 'true']));
        $this->assertRegExp("/^UPDATE `stats` SET `isMelee` = 1;$/i", $query->getStatement());

        $query = $driver->executeQuery($stat->query(Query::UPDATE)->fields(['isMelee' => false]));
        $this->assertRegExp("/^UPDATE `stats` SET `isMelee` = 0;$/i", $query->getStatement());

        // datetime
        $query = $driver->executeQuery($this->object->query(Query::UPDATE)->fields(['created' => $time]));
        $this->assertRegExp("/^UPDATE `users` SET `created` = '" . $date . "';$/i", $query->getStatement());

        $query = $driver->executeQuery($this->object->query(Query::UPDATE)->fields(['created' => new DateTime($date)]));
        $this->assertRegExp("/^UPDATE `users` SET `created` = '" . $date . "';$/i", $query->getStatement());

        $query = $driver->executeQuery($this->object->query(Query::UPDATE)->fields(['created' => $date]));
        $this->assertRegExp("/^UPDATE `users` SET `created` = '" . $date . "';$/i", $query->getStatement());

        // null
        $query = $driver->executeQuery($this->object->query(Query::UPDATE)->fields(['created' => null]));
        $this->assertRegExp("/^UPDATE `users` SET `created` = NULL;$/i", $query->getStatement());
    }

    public function testUpdateTypeBlob() {
        $this->loadFixtures('Stats');

        $handle = fopen(TEMP_DIR . '/blob.txt', 'rb');

        $stat = new Stat();

        $this->assertEquals(1, $stat->update(1, [
            'data' => $handle
        ]));

        // Match row
        $expected = $stat->select()->where('id', 1)->first()->toArray();
        $handle = $expected['data'];
        $expected['data'] = stream_get_contents($handle, -1, 0);
        fclose($handle);

        $this->assertEquals([
            'id' => 1,
            'name' => 'Warrior',
            'health' => 1500,
            'energy' => 150,
            'damage' => 125.25,
            'defense' => 55.75,
            'range' => 1.0,
            'isMelee' => true,
            'data' => 'This is loading from a file handle'
        ], $expected);
    }

    public function testUpdateTypeNull() {
        $this->loadFixtures('Users');

        $data = [
            'created' => null, // allowed to be null
            'modified' => date('Y-m-d H:i:s') // null to string
        ];

        $this->assertEquals(1, $this->object->update(1, $data));

        $this->assertSame($data, $this->object->select('created', 'modified')->where('id', 1)->first()->toArray());
    }

    public function testUpdateWithDates() {
        $this->loadFixtures('Users');

        // Integer
        $time = time();
        $this->assertEquals(1, $this->object->update(1, ['created' => $time]));
        $this->assertSame(['created' => date('Y-m-d H:i:s', $time)], $this->object->select('created')->where('id', 1)->first()->toArray());

        // String
        $time = date('Y-m-d H:i:s', strtotime('+1 week'));
        $this->assertEquals(1, $this->object->update(1, ['created' => $time]));
        $this->assertSame(['created' => $time], $this->object->select('created')->where('id', 1)->first()->toArray());

        // Object
        $time = new DateTime();
        $time->modify('+2 days');
        $this->assertEquals(1, $this->object->update(1, ['created' => $time]));
        $this->assertSame(['created' => $time->format('Y-m-d H:i:s')], $this->object->select('created')->where('id', 1)->first()->toArray());
    }

    public function testUpdateMany() {
        $this->loadFixtures('Users');

        $this->assertEquals(3, $this->object->updateMany(['country_id' => null], function(Query $query) {
            $query->where('age', '>', 30);
        }));
    }

    /**
     * @expectedException \Titon\Db\Exception\InvalidQueryException
     */
    public function testUpdateManyFailsOnNoConditions() {
        $this->loadFixtures('Users');

        $this->object->updateMany(['country_id' => null], function() {});
    }

    public function testUpsertInserts() {
        $this->loadFixtures('Users');

        $this->assertFalse($this->object->exists(6));

        $this->assertEquals(6, $this->object->upsert([
            'username' => 'ironman'
        ]));

        $this->assertTrue($this->object->exists(6));
    }

    public function testUpsertUpdates() {
        $this->loadFixtures('Users');

        $this->assertFalse($this->object->exists(6));

        $this->assertEquals(1, $this->object->upsert([
            'id' => 1,
            'username' => 'ironman'
        ]));

        $this->assertFalse($this->object->exists(6));
    }

    public function testUpsertUpdatesViaArg() {
        $this->loadFixtures('Users');

        $this->assertFalse($this->object->exists(6));

        $this->assertEquals(1, $this->object->upsert([
            'username' => 'ironman'
        ], 1));

        $this->assertFalse($this->object->exists(6));
    }

    public function testUpsertDoesInsertWhenFakeIDUsed() {
        $this->loadFixtures('Users');

        $this->assertFalse($this->object->exists(6));

        $this->assertEquals(6, $this->object->upsert([
            'id' => 10,
            'username' => 'ironman'
        ]));

        $this->assertTrue($this->object->exists(6));
    }

    public function testUpsertDoesInsertWhenFakeIDUsedViaArg() {
        $this->loadFixtures('Users');

        $this->assertFalse($this->object->exists(6));

        $this->assertEquals(6, $this->object->upsert([
            'username' => 'ironman'
        ], 10));

        $this->assertTrue($this->object->exists(6));
    }

    public function testWrapEntities() {
        $this->assertEquals([
            new Entity(['foo' => 'bar']),
            new Entity(['foo' => 'baz'])
        ], $this->object->wrapEntities([
            ['foo' => 'bar'],
            ['foo' => 'baz']
        ]));
    }

    public function testWrapEntitiesNested() {
        $this->assertEquals([
            new Entity([
                'foo' => 'bar',
                'join' => new Entity([
                    'key' => 'value'
                ])
            ])
        ], $this->object->wrapEntities([
            ['foo' => 'bar', 'join' => ['key' => 'value']]
        ]));
    }

}