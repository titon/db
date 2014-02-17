<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Test\Stub\BehaviorStub;
use Titon\Test\Stub\MapperStub;
use Titon\Test\Stub\Repository\Author;
use Titon\Test\Stub\Repository\Book;
use Titon\Test\Stub\Repository\Series;
use Titon\Test\Stub\Repository\Topic;
use Titon\Test\Stub\Repository\User;
use Titon\Test\Stub\RepositoryStub;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for Titon\Db\Repository.
 *
 * @property \Titon\Db\Repository $object
 */
class RepositoryTest extends TestCase {

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new User();
    }

    /**
     * Test table behavior management.
     */
    public function testAddHasBehaviors() {
        $stub = new RepositoryStub();

        $this->assertFalse($stub->hasBehavior('Stub'));

        $stub->addBehavior(new BehaviorStub());
        $this->assertTrue($stub->hasBehavior('Stub'));
    }

    /**
     * Test data mapper management.
     */
    public function testAddHasMappers() {
        $stub = new RepositoryStub();

        $this->assertFalse($stub->hasMapper('Stub'));

        $stub->addMapper(new MapperStub());
        $this->assertTrue($stub->hasMapper('Stub'));
    }

    /**
     * Test table relation management.
     */
    public function testAddHasRelations() {
        $stub = new RepositoryStub();

        $this->assertFalse($stub->hasRelation('User'));

        $stub->hasOne('User', 'Titon\Test\Stub\Repository\User', 'user_id');
        $this->assertTrue($stub->hasRelation('User'));

        $this->assertInstanceOf('Titon\Db\Repository', $stub->User);
        $this->assertInstanceOf('Titon\Db\Repository', $stub->getObject('User'));
    }

    /**
     * Test that a record count is returned.
     */
    public function testCount() {
        $this->loadFixtures('Users');

        $this->assertEquals(5, $this->object->query(Query::SELECT)->count());

        $this->object->query(Query::DELETE)->where('id', 1)->save();

        $this->assertEquals(4, $this->object->query(Query::SELECT)->count());
    }

    /**
     * Test decrementing a field for a record.
     */
    public function testDecrement() {
        $this->loadFixtures('Topics');

        $topic = new Topic();

        $this->assertEquals(new Entity(['post_count' => 4]), $topic->select('post_count')->where('id', 1)->first());

        $topic->decrement(1, ['post_count' => 1]);

        $this->assertEquals(new Entity(['post_count' => 3]), $topic->select('post_count')->where('id', 1)->first());

        // with step
        $topic->decrement(1, ['post_count' => 3]);

        $this->assertEquals(new Entity(['post_count' => 0]), $topic->select('post_count')->where('id', 1)->first());
    }

    /**
     * Test decrementing a field for multiple record.
     */
    public function testDecrementMany() {
        $this->loadFixtures('Topics');

        $topic = new Topic();

        $this->assertEquals([
            new Entity(['post_count' => 4]),
            new Entity(['post_count' => 1])
        ], $topic->select('post_count')->all());

        $topic->decrement(null, ['post_count' => 1]);

        $this->assertEquals([
            new Entity(['post_count' => 3]),
            new Entity(['post_count' => 0])
        ], $topic->select('post_count')->all());
    }

    /**
     * Test record existence.
     */
    public function testExists() {
        $this->loadFixtures('Users');

        $this->assertTrue($this->object->exists(1));
        $this->assertFalse($this->object->exists(10));
    }

    /**
     * Test single record fetching.
     */
    public function testFirst() {
        $this->loadFixtures('Users');

        // Return first from a list of many
        $result = [
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
        ];
        $this->assertEquals(new Entity($result), $this->object->query(Query::SELECT)->first());

        // Return by ID
        $result = [
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
        ];
        $this->assertEquals(new Entity($result), $this->object->query(Query::SELECT)->where('id', 3)->first());

        // No results
        $this->assertEquals([], $this->object->query(Query::SELECT)->where('id', 15)->first());
    }

    /**
     * Test multiple record fetching.
     */
    public function testAll() {
        $this->loadFixtures('Users');

        // Return all items
        $results = [
            [
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
            ], [
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
            ], [
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
            ], [
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
            ], [
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
            ]
        ];

        $this->assertEquals(array_map(function($value) {
            return new Entity($value);
        }, $results), $this->object->query(Query::SELECT)->all());

        // With conditions
        unset($results[0], $results[1], $results[2]);
        $results = array_values($results);

        $this->assertEquals(array_map(function($value) {
            return new Entity($value);
        }, $results), $this->object->query(Query::SELECT)->where(function() {
            $this->gte('id', 4);
        })->all());

        // No results
        $this->assertEquals([], $this->object->query(Query::SELECT)->where('country_id', 15)->all());
    }

    /**
     * Test multiple records are returned as a key value list.
     */
    public function testList() {
        $this->loadFixtures('Users');

        $this->assertEquals([
            1 => 'miles',
            2 => 'batman',
            3 => 'superman',
            4 => 'spiderman',
            5 => 'wolverine'
        ], $this->object->query(Query::SELECT)->lists('username'));

        $this->assertEquals([
            'miles' => 'Miles',
            'batman' => 'Bruce',
            'superman' => 'Clark',
            'spiderman' => 'Peter',
            'wolverine' => 'Logan'
        ], $this->object->query(Query::SELECT)->lists('firstName', 'username'));

        // Falls back to ID and display field (ID)
        $this->assertEquals([
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5
        ], $this->object->query(Query::SELECT)->lists());

        // No results
        $this->assertEquals([], $this->object->query(Query::SELECT)->where('country_id', 15)->lists());
    }

    /**
     * Test behavior fetching.
     */
    public function testGetBehaviors() {
        try {
            $this->object->getBehavior('Stub');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $this->object->addBehavior(new BehaviorStub());
        $this->assertInstanceOf('Titon\Db\Behavior', $this->object->getBehavior('Stub'));

        $this->assertEquals([
            'Stub' => $this->object->getBehavior('Stub')
        ], $this->object->getBehaviors());
    }

    /**
     * Test connection config.
     */
    public function testGetConnectionKey() {
        $this->assertEquals('default', $this->object->getConnectionKey());
    }

    /**
     * Test display field config.
     */
    public function testGetDisplayField() {
        $user1 = new User();
        $user1->setConfig('displayField', 'username');

        $this->assertEquals('username', $user1->getDisplayField());

        // Unknown field, falls back to ID
        $user2 = new User();
        $user2->setConfig('displayField', 'foobar');

        $this->assertEquals('id', $user2->getDisplayField());

        // Loops through till it finds one
        $user3 = new User();
        $user3->setConfig('displayField', ['foo', 'bar', 'firstName', 'baz']);

        $this->assertEquals('firstName', $user3->getDisplayField());
    }

    /**
     * Test connection drivers.
     */
    public function testGetDriver() {
        $this->assertInstanceOf('Titon\Db\Driver', $this->object->getDriver());

        // Connection doesnt exist
        $this->object->setConfig('connection', 'foobar');

        try {
            $this->assertInstanceOf('Titon\Db\Driver', $this->object->getDriver());
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test entity config.
     */
    public function testGetEntity() {
        $this->assertEquals('Titon\Db\Entity', $this->object->getEntity());
    }

    /**
     * Test primary key config.
     */
    public function testGetPrimaryKey() {
        $user1 = new User();
        $user1->setConfig('primaryKey', 'username');

        $this->assertEquals('username', $user1->getPrimaryKey());

        // Unknown field, falls back to ID
        $user2 = new User();
        $user2->setConfig('primaryKey', 'foobar');

        $this->assertEquals('id', $user2->getPrimaryKey());
    }

    /**
     * Test relation fetching.
     */
    public function testGetRelations() {
        try {
            $this->object->getRelation('Foobar');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $this->assertInstanceOf('Titon\Db\Relation', $this->object->getRelation('Profile'));

        $expected = [
            'Profile' => $this->object->getRelation('Profile'),
            'Country' => $this->object->getRelation('Country')
        ];

        $this->assertEquals($expected, $this->object->getRelations());

        unset($expected['Profile']);

        $this->assertEquals($expected, $this->object->getRelations(Relation::MANY_TO_ONE));
    }

    /**
     * Test that a schema object is returned.
     */
    public function testGetSchema() {
        $this->assertInstanceOf('Titon\Db\Driver\Schema', $this->object->getSchema());
    }

    /**
     * Test table config.
     */
    public function testGetTable() {
        $this->assertEquals('users', $this->object->getTable());

        $this->object->setConfig('prefix', 'test_');
        $this->assertEquals('test_users', $this->object->getTable());
    }

    /**
     * Test table prefix config.
     */
    public function testGetPrefix() {
        $this->assertEquals('', $this->object->getTablePrefix());

        $this->object->setConfig('prefix', 'test_');
        $this->assertEquals('test_', $this->object->getTablePrefix());
    }

    /**
     * Test incrementing a field for a record.
     */
    public function testIncrement() {
        $this->loadFixtures('Topics');

        $topic = new Topic();

        $this->assertEquals(new Entity(['post_count' => 4]), $topic->select('post_count')->where('id', 1)->first());

        $topic->increment(1, ['post_count' => 1]);

        $this->assertEquals(new Entity(['post_count' => 5]), $topic->select('post_count')->where('id', 1)->first());

        // with step
        $topic->increment(1, ['post_count' => 3]);

        $this->assertEquals(new Entity(['post_count' => 8]), $topic->select('post_count')->where('id', 1)->first());
    }

    /**
     * Test incrementing a field for multiple record.
     */
    public function testIncrementMany() {
        $this->loadFixtures('Topics');

        $topic = new Topic();

        $this->assertEquals([
            new Entity(['post_count' => 4]),
            new Entity(['post_count' => 1])
        ], $topic->select('post_count')->all());

        $topic->increment(null, ['post_count' => 3]);

        $this->assertEquals([
            new Entity(['post_count' => 7]),
            new Entity(['post_count' => 4])
        ], $topic->select('post_count')->all());
    }

    /**
     * Test that a query object is returned.
     */
    public function testQuery() {
        $this->assertInstanceOf('Titon\Db\Query', $this->object->query(Query::SELECT));
    }

    /**
     * Test that read returns a record by ID.
     */
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

        // No results
        $this->assertEquals([], $this->object->read(25));
    }

    /**
     * Test that a select query is returned.
     */
    public function testSelect() {
        $query = new Query(Query::SELECT, $this->object);
        $query->from($this->object->getTable(), 'User')->fields('id', 'username');

        $this->assertEquals($query, $this->object->select('id', 'username'));
    }

}