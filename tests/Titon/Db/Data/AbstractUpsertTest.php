<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Data;

use Titon\Db\Entity;
use Titon\Db\EntityCollection;
use Titon\Db\Query;
use Titon\Test\Stub\Repository\Book;
use Titon\Test\Stub\Repository\Series;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;

/**
 * Test class for database upserting.
 */
class AbstractUpsertTest extends TestCase {

    /**
     * Test that the record is created.
     */
    public function testUpsertNoId() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertFalse($user->exists(6));

        $this->assertEquals(6, $user->upsert([
            'username' => 'ironman'
        ]));

        $this->assertTrue($user->exists(6));
    }

    /**
     * Test that the record is updated if the ID exists in the data.
     */
    public function testUpsertWithId() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertFalse($user->exists(6));

        $this->assertEquals(1, $user->upsert([
            'id' => 1,
            'username' => 'ironman'
        ]));

        $this->assertFalse($user->exists(6));
    }

    /**
     * Test that the record is updated if the ID is passed as an argument.
     */
    public function testUpsertWithIdArg() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertFalse($user->exists(6));

        $this->assertEquals(1, $user->upsert([
            'username' => 'ironman'
        ], 1));

        $this->assertFalse($user->exists(6));
    }

    /**
     * Test that the record is created if the ID doesn't exist.
     */
    public function testUpsertWithFakeId() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertFalse($user->exists(6));

        $this->assertEquals(6, $user->upsert([
            'id' => 10,
            'username' => 'ironman'
        ]));

        $this->assertTrue($user->exists(6));
    }

    /**
     * Test that the record is created if the ID argument doesn't exist.
     */
    public function testUpsertWithFakeIdArg() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertFalse($user->exists(6));

        $this->assertEquals(6, $user->upsert([
            'username' => 'ironman'
        ], 10));

        $this->assertTrue($user->exists(6));
    }

    /**
     * Test upserting for one-to-one relations.
     */
    public function testUpsertOneToOne() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new User();
        $time = time();

        // Update
        $this->assertEquals(new Entity([
            'id' => 4,
            'user_id' => 1,
            'lastLogin' => '2012-02-15 21:22:34',
            'currentLogin' => '2013-06-06 19:11:03'
        ]), $user->Profile->select()->where('id', 4)->first());

        $this->assertEquals(1, $user->upsertRelations(1, [
            'Profile' => [
                'id' => 4,
                'lastLogin' => $time
            ]
        ]));

        $this->assertEquals(new Entity([
            'id' => 4,
            'user_id' => 1,
            'lastLogin' => date('Y-m-d H:i:s', $time),
            'currentLogin' => '2013-06-06 19:11:03'
        ]), $user->Profile->select()->where('id', 4)->first());

        // Create
        $this->assertFalse($user->Profile->exists(6));

        $this->assertEquals(1, $user->upsertRelations(1, [
            'Profile' => [
                'lastLogin' => $time
            ]
        ]));

        $this->assertEquals(new Entity([
            'id' => 6,
            'user_id' => 1,
            'lastLogin' => date('Y-m-d H:i:s', $time),
            'currentLogin' => null
        ]), $user->Profile->select()->where('id', 6)->first());
    }

    /**
     * Test upserting for one-to-many relations.
     */
    public function testUpsertOneToMany() {
        $this->loadFixtures(['Series', 'Books']);

        $series = new Series();

        // Trigger lazy-loaded queries
        $results = $series->select()->where('id', 1)->with('Books')->first();
        $results->Books;

        $this->assertEquals(new Entity([
            'id' => 1,
            'author_id' => 1,
            'name' => 'A Song of Ice and Fire',
            'Books' => new EntityCollection([
                new Entity(['id' => 1, 'series_id' => 1, 'name' => 'A Game of Thrones', 'isbn' => '0-553-10354-7', 'released' => '1996-08-02']),
                new Entity(['id' => 2, 'series_id' => 1, 'name' => 'A Clash of Kings', 'isbn' => '0-553-10803-4', 'released' => '1999-02-25']),
                new Entity(['id' => 3, 'series_id' => 1, 'name' => 'A Storm of Swords', 'isbn' => '0-553-10663-5', 'released' => '2000-11-11']),
                new Entity(['id' => 4, 'series_id' => 1, 'name' => 'A Feast for Crows', 'isbn' => '0-553-80150-3', 'released' => '2005-11-02']),
                new Entity(['id' => 5, 'series_id' => 1, 'name' => 'A Dance with Dragons', 'isbn' => '0-553-80147-3', 'released' => '2011-07-19'])
            ])
        ]), $results);

        $this->assertEquals(3, $series->upsertRelations(1, [
            'Books' => [
                ['id' => 1, 'name' => 'A Game of Thrones (Updated)'], // Updated
                ['name' => 'The Winds of Winter'], // Created
                ['id' => 125, 'name' => 'A Dream of Spring'] // Created because of invalid ID
            ]
        ]));

        // Trigger lazy-loaded queries
        $results = $series->select()->where('id', 1)->with('Books')->first();
        $results->Books;

        $this->assertEquals(new Entity([
            'id' => 1,
            'author_id' => 1,
            'name' => 'A Song of Ice and Fire',
            'Books' => new EntityCollection([
                new Entity(['id' => 1, 'series_id' => 1, 'name' => 'A Game of Thrones (Updated)', 'isbn' => '0-553-10354-7', 'released' => '1996-08-02']),
                new Entity(['id' => 2, 'series_id' => 1, 'name' => 'A Clash of Kings', 'isbn' => '0-553-10803-4', 'released' => '1999-02-25']),
                new Entity(['id' => 3, 'series_id' => 1, 'name' => 'A Storm of Swords', 'isbn' => '0-553-10663-5', 'released' => '2000-11-11']),
                new Entity(['id' => 4, 'series_id' => 1, 'name' => 'A Feast for Crows', 'isbn' => '0-553-80150-3', 'released' => '2005-11-02']),
                new Entity(['id' => 5, 'series_id' => 1, 'name' => 'A Dance with Dragons', 'isbn' => '0-553-80147-3', 'released' => '2011-07-19']),
                new Entity(['id' => 16, 'series_id' => 1, 'name' => 'The Winds of Winter', 'isbn' => '', 'released' => '']),
                new Entity(['id' => 17, 'series_id' => 1, 'name' => 'A Dream of Spring', 'isbn' => '', 'released' => ''])
            ])
        ]), $results);
    }

    /**
     * Test upserting for many-to-many relations.
     */
    public function testUpsertWithManyToMany() {
        $this->loadFixtures(['Genres', 'Books', 'BookGenres']);

        $book = new Book();

        // Trigger lazy-loaded queries
        $results = $book->select()->where('id', 10)->with('Genres')->first();
        $results->Genres;

        $this->assertEquals(new Entity([
            'id' => 10,
            'series_id' => 2,
            'name' => 'Harry Potter and the Order of the Phoenix',
            'isbn' => '0-7475-5100-6',
            'released' => '2003-06-21',
            'Genres' => new EntityCollection([
                new Entity([
                    'id' => 2,
                    'name' => 'Adventure',
                    'book_count' => 7,
                    'Junction' => new Entity([
                        'id' => 29,
                        'book_id' => 10,
                        'genre_id' => 2
                    ])
                ]),
                new Entity([
                    'id' => 7,
                    'name' => 'Mystery',
                    'book_count' => 7,
                    'Junction' => new Entity([
                        'id' => 30,
                        'book_id' => 10,
                        'genre_id' => 7
                    ])
                ]),
                new Entity([
                    'id' => 8,
                    'name' => 'Fantasy',
                    'book_count' => 15,
                    'Junction' => new Entity([
                        'id' => 28,
                        'book_id' => 10,
                        'genre_id' => 8
                    ])
                ])
            ])
        ]), $results);

        $this->assertEquals(4, $book->upsertRelations(10, [
            'Genres' => [
                ['id' => 2, 'name' => 'Adventure (Updated)'], // Updated
                ['name' => 'Magic'], // Created
                ['id' => 125, 'name' => 'Wizardry'], // Created because of invalid ID
                ['genre_id' => 8, 'name' => 'Fantasy (Updated)'] // Updated because of direct foreign key
            ]
        ]));

        // Trigger lazy-loaded queries
        $results = $book->select()->where('id', 10)->with('Genres', function(Query $query) {
            $query->orderBy('id', 'asc');
        })->first();
        $results->Genres;

        $this->assertEquals([
            'id' => 10,
            'series_id' => 2,
            'name' => 'Harry Potter and the Order of the Phoenix',
            'isbn' => '0-7475-5100-6',
            'released' => '2003-06-21',
            'Genres' => [
                [
                    'id' => 2,
                    'name' => 'Adventure (Updated)',
                    'book_count' => 7,
                    'Junction' => [
                        'id' => 29,
                        'book_id' => 10,
                        'genre_id' => 2
                    ]
                ], [
                    'id' => 7,
                    'name' => 'Mystery',
                    'book_count' => 7,
                    'Junction' => [
                        'id' => 30,
                        'book_id' => 10,
                        'genre_id' => 7
                    ]
                ], [
                    'id' => 8,
                    'name' => 'Fantasy (Updated)',
                    'book_count' => 15,
                    'Junction' => [
                        'id' => 28,
                        'book_id' => 10,
                        'genre_id' => 8
                    ]
                ], [
                    'id' => 12,
                    'name' => 'Magic',
                    'book_count' => 0,
                    'Junction' => [
                        'id' => 46,
                        'book_id' => 10,
                        'genre_id' => 12
                    ]
                ], [
                    'id' => 13,
                    'name' => 'Wizardry',
                    'book_count' => 0,
                    'Junction' => [
                        'id' => 47,
                        'book_id' => 10,
                        'genre_id' => 13
                    ]
                ]
            ]
        ], $results->toArray());
    }

}