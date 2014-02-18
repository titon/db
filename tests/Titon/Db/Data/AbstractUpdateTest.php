<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Data;

use DateTime;
use Titon\Db\Entity;
use Titon\Db\EntityCollection;
use Titon\Db\Query;
use Titon\Test\Stub\Repository\Book;
use Titon\Test\Stub\Repository\Series;
use Titon\Test\Stub\Repository\Stat;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for database updating.
 */
class AbstractUpdateTest extends TestCase {

    /**
     * Test basic database record updating.
     */
    public function testUpdate() {
        $this->loadFixtures('Users');

        $user = new User();
        $data = [
            'country_id' => 3,
            'username' => 'milesj'
        ];

        $this->assertEquals(1, $user->update(1, $data));

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
        ]), $user->select()->where('id', 1)->first());
    }

    /**
     * Test database record updating of a record that doesn't exist.
     */
    public function testUpdateNonExistingRecord() {
        $this->loadFixtures('Users');

        $user = new User();
        $data = [
            'id' => 10,
            'username' => 'foobar'
        ];

        $this->assertEquals(0, $user->update(10, $data));
    }

    /**
     * Test updating with empty data.
     */
    public function testUpdateEmptyData() {
        $this->loadFixtures('Users');

        $user = new User();

        try {
            $this->assertEquals(0, $user->update(1, []));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // Relation without data
        try {
            $user->update(1, [
                'Profile' => [
                    'lastLogin' => time()
                ]
            ]);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test expressions in fields while updating.
     */
    public function testUpdateExpressions() {
        $this->loadFixtures('Stats');

        $stat = new Stat();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'name' => 'Warrior', 'health' => 1500]),
            new Entity(['id' => 2, 'name' => 'Ranger', 'health' => 800]),
            new Entity(['id' => 3, 'name' => 'Mage', 'health' => 600]),
        ]), $stat->select('id', 'name', 'health')->orderBy('id', 'asc')->all());

        $query = $stat->query(Query::UPDATE);
        $query->fields(['health' => $query->expr('health', '+', 75)]);

        $this->assertEquals(3, $query->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'name' => 'Warrior', 'health' => 1575]),
            new Entity(['id' => 2, 'name' => 'Ranger', 'health' => 875]),
            new Entity(['id' => 3, 'name' => 'Mage', 'health' => 675]),
        ]), $stat->select('id', 'name', 'health')->orderBy('id', 'asc')->all());

        // Single record
        $this->assertEquals(1, $stat->update(2, [
            'health' => new Query\Expr('health', '-', 125)
        ]));

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'name' => 'Warrior', 'health' => 1575]),
            new Entity(['id' => 2, 'name' => 'Ranger', 'health' => 750]),
            new Entity(['id' => 3, 'name' => 'Mage', 'health' => 675]),
        ]), $stat->select('id', 'name', 'health')->orderBy('id', 'asc')->all());
    }

    /**
     * Test database record updating against unique columns.
     */
    public function testUpdateUniqueColumn() {
        $this->loadFixtures('Users');

        $user = new User();
        $data = [
            'username' => 'batman' // name already exists
        ];

        try {
            $this->assertEquals(1, $user->update(1, $data));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test database record updating with one to one relations.
     */
    public function testUpdateWithOneToOne() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new User();
        $data = [
            'country_id' => 3,
            'username' => 'milesj',
            'Profile' => [
                'id' => 4,
                'lastLogin' => '2012-06-24 17:30:33'
            ]
        ];

        $this->assertEquals(1, $user->update(1, $data));

        $this->assertEquals([
            'id' => 1,
            'country_id' => 3,
            'username' => 'milesj',
            'password' => '',
            'email' => '',
            'firstName' => '',
            'lastName' => '',
            'age' => '',
            'created' => '',
            'modified' => '',
            'Profile' => [
                'id' => 4,
                'user_id' => 1,
                'lastLogin' => '2012-06-24 17:30:33',
                'currentLogin' => ''
            ]
        ], $user->data);

        // Should throw errors for invalid array structure
        unset($data['id'], $data['Profile']);

        $data['Profile'] = [
            ['lastLogin' => '2012-06-24 17:30:33'] // Nested array
        ];

        try {
            $this->assertEquals(1, $user->update(1, $data));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // Will upsert if no one-to-one ID is present
        $data = [
            'country_id' => 3,
            'username' => 'miles',
            'Profile' => [
                'currentLogin' => '2012-06-24 17:30:33'
            ]
        ];

        $this->assertEquals(1, $user->update(1, $data));

        $this->assertEquals([
            'id' => 1,
            'country_id' => 3,
            'username' => 'miles',
            'password' => '',
            'email' => '',
            'firstName' => '',
            'lastName' => '',
            'age' => '',
            'created' => '',
            'modified' => '',
            'Profile' => [
                'id' => 6,
                'user_id' => 1,
                'lastLogin' => '',
                'currentLogin' => '2012-06-24 17:30:33',
            ]
        ], $user->data);
    }

    /**
     * Test database record updating with one to many relations.
     */
    public function testUpdateWithOneToMany() {
        $this->loadFixtures(['Books', 'Series']);

        $series = new Series();
        $data = [
            'author_id' => 3,
            'name' => 'The Lord of the Rings (Updated)',
            'Books' => [
                ['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring (Updated)'],
                ['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers (Updated)'],
                ['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King (Updated)'],
            ]
        ];

        $this->assertEquals(1, $series->update(3, $data));

        $this->assertEquals([
            'id' => 3,
            'author_id' => 3,
            'name' => 'The Lord of the Rings (Updated)',
            'Books' => [
                ['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring (Updated)', 'isbn' => '', 'released' => ''],
                ['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers (Updated)', 'isbn' => '', 'released' => ''],
                ['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King (Updated)', 'isbn' => '', 'released' => ''],
            ]
        ], $series->data);

        // Should throw errors for invalid array structure
        unset($data['Books']);

        $data['Books'] = [
            'name' => 'The Bad Beginning'
        ]; // Non numeric array

        try {
            $series->update(3, $data);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test database record updating with many to many relations.
     */
    public function testUpdateWithManyToMany() {
        $this->loadFixtures(['Genres', 'Books', 'BookGenres']);

        $book = new Book();
        $data = [
            'series_id' => 1,
            'name' => 'A Dance with Dragons (Updated)',
            'Genres' => [
                ['id' => 3, 'name' => 'Action-Adventure'], // Existing genre
                ['name' => 'Epic-Horror'], // New genre
                ['genre_id' => 8] // Existing genre by ID
            ]
        ];

        $this->assertEquals(1, $book->update(5, $data));

        $this->assertEquals([
            'id' => 5,
            'series_id' => 1,
            'name' => 'A Dance with Dragons (Updated)',
            'isbn' => '',
            'released' => '',
            'Genres' => [
                [
                    'id' => 3,
                    'name' => 'Action-Adventure',
                    'book_count' => '',
                    'Junction' => [
                        'id' => 14,
                        'book_id' => 5,
                        'genre_id' => 3
                    ]
                ], [
                    'id' => 12,
                    'name' => 'Epic-Horror',
                    'book_count' => '',
                    'Junction' => [
                        'book_id' => 5,
                        'genre_id' => 12,
                        'id' => 46
                    ]
                ], [
                    // Data isn't set when using foreign keys
                    'Junction' => [
                        'id' => 13,
                        'book_id' => 5,
                        'genre_id' => 8
                    ]
                ]
            ]
        ], $book->data);

        // Should throw errors for invalid array structure
        unset($data['Genres']);

        $data['Genres'] = [
            'id' => 3,
            'name' => 'Action-Adventure'
        ]; // Non numeric array

        try {
            $this->assertTrue($book->update(5, $data));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // Try again with another structure
        unset($data['Genres']);

        $data['Genres'] = [
            'Fantasy', 'Horror'
        ]; // Non array value

        try {
            $this->assertTrue($book->update(5, $data));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test multiple record updates.
     */
    public function testUpdateMultiple() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertSame(4, $user->query(Query::UPDATE)->fields(['country_id' => 1])->where('country_id', '!=', 1)->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => 1, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => 1, 'username' => 'superman']),
            new Entity(['id' => 4, 'country_id' => 1, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 1, 'username' => 'wolverine']),
        ]), $user->select('id', 'country_id', 'username')->orderBy('id', 'asc')->all());

        // No where clause
        $this->assertSame(5, $user->query(Query::UPDATE)->fields(['country_id' => 2])->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 2, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => 2, 'username' => 'superman']),
            new Entity(['id' => 4, 'country_id' => 2, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 2, 'username' => 'wolverine']),
        ]), $user->select('id', 'country_id', 'username')->orderBy('id', 'asc')->all());
    }

    /**
     * Test multiple record updates with a limit and offset applied.
     */
    public function testUpdateMultipleWithLimit() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertSame(2, $user->query(Query::UPDATE)->fields(['country_id' => 1])->where('country_id', '!=', 1)->limit(2)->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => 1, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => 1, 'username' => 'superman']),
            new Entity(['id' => 4, 'country_id' => 5, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 4, 'username' => 'wolverine']),
        ]), $user->select('id', 'country_id', 'username')->orderBy('id', 'asc')->all());

        // No where clause, offset ignored
        $this->assertSame(2, $user->query(Query::UPDATE)->fields(['country_id' => 5])->limit(2, 2)->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 5, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => 5, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => 1, 'username' => 'superman']),
            new Entity(['id' => 4, 'country_id' => 5, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 4, 'username' => 'wolverine']),
        ]), $user->select('id', 'country_id', 'username')->orderBy('id', 'asc')->all());
    }

    /**
     * Test multiple record updates with an order by applied.
     */
    public function testUpdateMultipleWithOrderBy() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertSame(2, $user->query(Query::UPDATE)
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
        ]), $user->select('id', 'country_id', 'username')->orderBy('id', 'asc')->all());
    }

    /**
     * Test multiple record updates with an order by applied.
     */
    public function testUpdateMultipleWithConditions() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertSame(3, $user->query(Query::UPDATE)
            ->fields(['country_id' => null])
            ->where('username', 'like', '%man%')
            ->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => null, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => null, 'username' => 'superman']),
            new Entity(['id' => 4, 'country_id' => null, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 4, 'username' => 'wolverine']),
        ]), $user->select('id', 'country_id', 'username')->orderBy('id', 'asc')->all());
    }

    /**
     * Test multiple record updates setting empty values.
     */
    public function testUpdateMultipleEmptyValue() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertSame(5, $user->query(Query::UPDATE)
            ->fields(['firstName' => ''])
            ->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles', 'firstName' => '']),
            new Entity(['id' => 2, 'username' => 'batman', 'firstName' => '']),
            new Entity(['id' => 3, 'username' => 'superman', 'firstName' => '']),
            new Entity(['id' => 4, 'username' => 'spiderman', 'firstName' => '']),
            new Entity(['id' => 5, 'username' => 'wolverine', 'firstName' => '']),
        ]), $user->select('id', 'username', 'firstName')->orderBy('id', 'asc')->all());
    }

    /**
     * Test updating and reading casts types.
     */
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

    /**
     * Test updating blob data.
     */
    public function testUpdateBlob() {
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

    /**
     * Test updating nulls.
     */
    public function testUpdateNull() {
        $this->loadFixtures('Users');

        $user = new User();
        $data = [
            'created' => null, // allowed to be null
            'modified' => date('Y-m-d H:i:s') // null to string
        ];

        $this->assertEquals(1, $user->update(1, $data));

        $this->assertSame($data, $user->select('created', 'modified')->where('id', 1)->first()->toArray());
    }

    /**
     * Test dates in different formats.
     */
    public function testUpdateDates() {
        $this->loadFixtures('Users');

        $user = new User();

        // Integer
        $time = time();
        $this->assertEquals(1, $user->update(1, ['created' => $time]));
        $this->assertSame(['created' => date('Y-m-d H:i:s', $time)], $user->select('created')->where('id', 1)->first()->toArray());

        // String
        $time = date('Y-m-d H:i:s', strtotime('+1 week'));
        $this->assertEquals(1, $user->update(1, ['created' => $time]));
        $this->assertSame(['created' => $time], $user->select('created')->where('id', 1)->first()->toArray());

        // Object
        $time = new DateTime();
        $time->modify('+2 days');
        $this->assertEquals(1, $user->update(1, ['created' => $time]));
        $this->assertSame(['created' => $time->format('Y-m-d H:i:s')], $user->select('created')->where('id', 1)->first()->toArray());
    }

    /**
     * Test multiple update with conditions.
     */
    public function testUpdateMany() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new User();
        $data = ['country_id' => null];

        // Throws exceptions if no conditions applied
        try {
            $user->updateMany($data, function() {
                // Nothing
            });
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $this->assertEquals(3, $user->updateMany($data, function(Query $query) {
            $query->where('age', '>', 30);
        }));
    }

}