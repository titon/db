<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Data;

use Titon\Db\Query;
use Titon\Test\Stub\Table\Book;
use Titon\Test\Stub\Table\BookGenre;
use Titon\Test\Stub\Table\Series;
use Titon\Test\Stub\Table\User;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for database record deleting.
 */
class AbstractDeleteTest extends TestCase {

    /**
     * Test single record deletion.
     */
    public function testDelete() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertTrue($user->exists(1));
        $this->assertSame(1, $user->delete(1, false));
        $this->assertFalse($user->exists(1));
    }

    /**
     * Test delete with where conditions.
     */
    public function testDeleteConditions() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertSame(5, $user->select()->count());
        $this->assertSame(3, $user->query(Query::DELETE)->where('age', '>', 30)->save());
        $this->assertSame(2, $user->select()->count());
    }

    /**
     * Test delete with ordering.
     */
    public function testDeleteOrdering() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertEquals([
            ['id' => 1, 'username' => 'miles'],
            ['id' => 2, 'username' => 'batman'],
            ['id' => 3, 'username' => 'superman'],
            ['id' => 4, 'username' => 'spiderman'],
            ['id' => 5, 'username' => 'wolverine']
        ], $user->select('id', 'username')->orderBy('id', 'asc')->fetchAll(false));

        $this->assertSame(3, $user->query(Query::DELETE)->orderBy('age', 'asc')->limit(3)->save());

        $this->assertEquals([
            ['id' => 2, 'username' => 'batman'],
            ['id' => 5, 'username' => 'wolverine']
        ], $user->select('id', 'username')->orderBy('id', 'asc')->fetchAll(false));
    }

    /**
     * Test delete with a limit applied.
     */
    public function testDeleteLimit() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertEquals([
            ['id' => 1, 'username' => 'miles'],
            ['id' => 2, 'username' => 'batman'],
            ['id' => 3, 'username' => 'superman'],
            ['id' => 4, 'username' => 'spiderman'],
            ['id' => 5, 'username' => 'wolverine']
        ], $user->select('id', 'username')->orderBy('id', 'asc')->fetchAll(false));

        $this->assertSame(2, $user->query(Query::DELETE)->limit(2)->save());

        $this->assertEquals([
            ['id' => 3, 'username' => 'superman'],
            ['id' => 4, 'username' => 'spiderman'],
            ['id' => 5, 'username' => 'wolverine']
        ], $user->select('id', 'username')->orderBy('id', 'asc')->fetchAll(false));
    }

    /**
     * Test that one-to-one relation dependents are deleted.
     */
    public function testDeleteCascadeOneToOne() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new User();

        $this->assertTrue($user->exists(2));
        $this->assertTrue($user->Profile->exists(5));

        $user->delete(2, true);

        $this->assertFalse($user->exists(2));
        $this->assertFalse($user->Profile->exists(5));
    }

    /**
     * Test that one-to-many relation dependents are deleted.
     */
    public function testDeleteCascadeOneToMany() {
        $this->loadFixtures(['Series', 'Books', 'BookGenres', 'Genres']);

        $series = new Series();

        $this->assertTrue($series->exists(1));
        $this->assertEquals([
            ['id' => 1, 'name' => 'A Game of Thrones'],
            ['id' => 2, 'name' => 'A Clash of Kings'],
            ['id' => 3, 'name' => 'A Storm of Swords'],
            ['id' => 4, 'name' => 'A Feast for Crows'],
            ['id' => 5, 'name' => 'A Dance with Dragons'],
        ], $series->Books->select('id', 'name')->where('series_id', 1)->orderBy('id', 'asc')->fetchAll(false));

        $series->delete(1, true);

        $this->assertFalse($series->exists(1));
        $this->assertEquals([], $series->Books->select('id', 'name')->where('series_id', 1)->fetchAll(false));
    }

    /**
     * Test that many-to-many relation dependents are deleted.
     */
    public function testDeleteCascadeManyToMany() {
        $this->loadFixtures(['Books', 'BookGenres', 'Genres']);

        $book = new Book();
        $bookGenres = new BookGenre();

        $this->assertTrue($book->exists(5));
        $this->assertEquals([
            'id' => 5,
            'name' => 'A Dance with Dragons',
            'Genres' => [
                [
                    'id' => 3,
                    'name' => 'Action-Adventure',
                    'book_count' => 8,
                    'Junction' => [
                        'id' => 14,
                        'book_id' => 5,
                        'genre_id' => 3
                    ]
                ], [
                    'id' => 5,
                    'name' => 'Horror',
                    'book_count' => 5,
                    'Junction' => [
                        'id' => 15,
                        'book_id' => 5,
                        'genre_id' => 5
                    ]
                ], [
                    'id' => 8,
                    'name' => 'Fantasy',
                    'book_count' => 15,
                    'Junction' => [
                        'id' => 13,
                        'book_id' => 5,
                        'genre_id' => 8
                    ]
                ]
            ]
        ], $book->select('id', 'name')->where('id', 5)->with('Genres')->fetch(false));

        $book->delete(5, true);

        $this->assertFalse($book->exists(5));
        $this->assertEquals([], $book->select()->where('id', 5)->with('Genres')->fetch(false));
        $this->assertEquals([], $bookGenres->select()->where('book_id', 5)->fetch(false));

        // The related records don't get deleted
        // Only the junction records should be
        $this->assertEquals([
            ['id' => 3, 'name' => 'Action-Adventure'],
            ['id' => 5, 'name' => 'Horror'],
            ['id' => 8, 'name' => 'Fantasy'],
        ], $book->Genres->select('id', 'name')->where('id', [3, 5, 8])->fetchAll(false));
    }

    /**
     * Test that deep relations are also deleted.
     */
    public function testDeleteCascadeDeepRelations() {
        $this->loadFixtures(['Series', 'Books', 'BookGenres', 'Genres']);

        $series = new Series();
        $bookGenres = new BookGenre();

        $this->assertTrue($series->exists(3));

        $this->assertEquals([
            ['id' => 13, 'name' => 'The Fellowship of the Ring'],
            ['id' => 14, 'name' => 'The Two Towers'],
            ['id' => 15, 'name' => 'The Return of the King'],
        ], $series->Books->select('id', 'name')->where('series_id', 3)->orderBy('id', 'asc')->fetchAll(false));

        $this->assertEquals([
            'id' => 14,
            'name' => 'The Two Towers',
            'Genres' => [
                [
                    'id' => 3,
                    'name' => 'Action-Adventure',
                    'book_count' => 8,
                    'Junction' => [
                        'id' => 41,
                        'book_id' => 14,
                        'genre_id' => 3
                    ]
                ], [
                    'id' => 6,
                    'name' => 'Thriller',
                    'book_count' => 3,
                    'Junction' => [
                        'id' => 42,
                        'book_id' => 14,
                        'genre_id' => 6
                    ]
                ], [
                    'id' => 8,
                    'name' => 'Fantasy',
                    'book_count' => 15,
                    'Junction' => [
                        'id' => 40,
                        'book_id' => 14,
                        'genre_id' => 8
                    ]
                ]
            ]
        ], $series->Books->select('id', 'name')->where('id', 14)->with('Genres')->fetch(false));

        $series->delete(3, true);

        $this->assertFalse($series->exists(3));
        $this->assertEquals([], $series->Books->select('id', 'name')->where('series_id', 3)->fetchAll(false));
        $this->assertEquals([], $bookGenres->select()->where('book_id', 14)->fetch(false));
    }

    /**
     * Test that dependents aren't deleted if cascade is false.
     */
    public function testDeleteNoCascade() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new User();

        $this->assertTrue($user->exists(2));
        $this->assertTrue($user->Profile->exists(5));

        $user->delete(2, false);

        $this->assertFalse($user->exists(2));
        $this->assertTrue($user->Profile->exists(5));
    }

    /**
     * Test multiple deletion through conditions.
     */
    public function testDeleteMany() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new User();

        // Throws exceptions if no conditions applied
        try {
            $user->deleteMany(function() {
                // Nothing
            });
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $this->assertEquals(3, $user->deleteMany(function() {
            $this->where('age', '>', 30);
        }));
    }

}