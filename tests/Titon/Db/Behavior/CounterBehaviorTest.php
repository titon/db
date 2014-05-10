<?php
namespace Titon\Db\Behavior;

use Titon\Db\Query;
use Titon\Test\Stub\Repository\Book;
use Titon\Test\Stub\Repository\Genre;
use Titon\Test\Stub\Repository\Post;
use Titon\Test\Stub\Repository\Topic;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Behavior\CounterBehavior $object
 */
class CounterBehaviorTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new CounterBehavior();
    }

    public function testTrack() {
        $scope = function() {};

        $book = new Book();
        $book->addBehavior($this->object);

        $this->object->track('Genres', 'book_count', $scope);

        $this->assertEquals([
            'Genres' => [
                'field' => 'book_count',
                'scope' => $scope,
            ]
        ], $this->object->getCounters());
    }

    public function testOnUpsertManyToMany() {
        $this->loadFixtures(['Books', 'Genres', 'BookGenres']);

        $genre = new Genre();

        $book = new Book();
        $book->addBehavior($this->object->track('Genres', 'book_count'));

        $record = $genre->read(3);
        $this->assertEquals(8, $record->book_count);

        // Create new record and increase to 9
        $book->create([
            'series_id' => 1,
            'name' => 'The Winds of Winter',
            'Genres' => [
                ['genre_id' => 3]
            ]
        ]);

        $record = $genre->read(3);
        $this->assertEquals(9, $record->book_count);

        // Update a record and add to increase to 10
        $book->update(12, [
            'released' => time(),
            'Genres' => [
                ['genre_id' => 3]
            ]
        ]);

        $record = $genre->read(3);
        $this->assertEquals(10, $record->book_count);
    }

    public function testOnDeleteManyToMany() {
        $this->loadFixtures(['Books', 'Genres', 'BookGenres']);

        $genre = new Genre();

        $book = new Book();
        $book->addBehavior($this->object->track('Genres', 'book_count'));

        $record = $genre->read(8);
        $this->assertEquals(15, $record->book_count);

        // Delete a record to go down to 14
        $book->delete(1, true);

        $record = $genre->read(8);
        $this->assertEquals(14, $record->book_count);

        // Delete multiple records
        $book->deleteMany(function(Query $where) {
            $where->where('series_id', 1);
        }, true);

        $record = $genre->read(8);
        $this->assertEquals(10, $record->book_count);
    }

    public function testOnCreateManyToOne() {
        $this->loadFixtures(['Topics', 'Posts']);

        $topic = new Topic();

        $post = new Post();
        $post->addBehavior($this->object->track('Topic', 'post_count', function(Query $query) {
            $query->where('active', 1);
        }));

        $record = $topic->read(1);
        $this->assertEquals(4, $record->post_count);

        // Create an inactive record, count shouldn't change
        $post->create(['topic_id' => 1, 'active' => 0, 'content' => 'Inactive']);

        $record = $topic->read(1);
        $this->assertEquals(4, $record->post_count);

        // Create an active record
        $post->create(['topic_id' => 1, 'active' => 1, 'content' => 'Active']);

        $record = $topic->read(1);
        $this->assertEquals(5, $record->post_count);
    }

    public function testOnUpdateManyToOne() {
        $this->loadFixtures(['Topics', 'Posts']);

        $topic = new Topic();
        $post = new Post();
        $post->addBehavior($this->object->track('Topic', 'post_count', function(Query $query) {
            $query->where('active', 1);
        }));

        $record = $topic->read(1);
        $this->assertEquals(4, $record->post_count);

        // Update record to be inactive, count should change to 3
        $post->update(3, ['active' => 0]);

        $record = $topic->read(1);
        $this->assertEquals(3, $record->post_count);

        // Update records to be active, count should change to 5
        $post->update([3, 4], ['active' => 1]);

        $record = $topic->read(1);
        $this->assertEquals(5, $record->post_count);

        // Update all to be inactive
        $post->updateMany(['active' => 0], function(Query $query) {
            $query->where('topic_id', 1);
        });

        $record = $topic->read(1);
        $this->assertEquals(0, $record->post_count);
    }

    public function testOnDeleteManyToOne() {
        $this->loadFixtures(['Topics', 'Posts']);

        $topic = new Topic();
        $post = new Post();
        $post->addBehavior($this->object->track('Topic', 'post_count', function(Query $query) {
            $query->where('active', 1);
        }));

        $record = $topic->read(1);
        $this->assertEquals(4, $record->post_count);

        // Delete record with active = 0, count shouldn't change
        $post->delete(4, false);

        $record = $topic->read(1);
        $this->assertEquals(4, $record->post_count);

        // Delete active record, could should change
        $post->delete(3, false);

        $record = $topic->read(1);
        $this->assertEquals(3, $record->post_count);

        // Delete all records
        $post->deleteMany(function(Query $query) {
            $query->where('topic_id', 1);
        }, false);

        $record = $topic->read(1);
        $this->assertEquals(0, $record->post_count);
    }

}