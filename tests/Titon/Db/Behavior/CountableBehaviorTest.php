<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Test\Stub\Table\Book;
use Titon\Test\Stub\Table\Genre;
use Titon\Test\Stub\Table\Post;
use Titon\Test\Stub\Table\Topic;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Behavior\CountableBehavior.
 *
 * @property \Titon\Db\Behavior\CountableBehavior $object
 */
class CountableBehaviorTest extends TestCase {

    /**
     * Test that counts are synced on create or update.
     */
    public function testOnUpsertManyToMany() {
        $this->loadFixtures(['Books', 'Genres', 'BookGenres']);

        $genre = new Genre();
        $book = new Book();
        $book->addBehavior(new CountableBehavior())
            ->addCounter('Genres', 'book_count');

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

    /**
     * Test that counts are synced on delete.
     */
    public function testOnDeleteManyToMany() {
        $this->loadFixtures(['Books', 'Genres', 'BookGenres']);

        $genre = new Genre();
        $book = new Book();
        $book->addBehavior(new CountableBehavior())
            ->addCounter('Genres', 'book_count');

        $record = $genre->read(8);
        $this->assertEquals(15, $record->book_count);

        // Delete a record to go down to 14
        $book->delete(1, true);

        $record = $genre->read(8);
        $this->assertEquals(14, $record->book_count);

        // Delete multiple records
        $book->deleteMany(function() {
            $this->where('series_id', 1);
        }, true);

        $record = $genre->read(8);
        $this->assertEquals(10, $record->book_count);
    }

    /**
     * Test that counts are synced on create.
     */
    public function testOnCreateManyToOne() {
        $this->loadFixtures(['Topics', 'Posts']);

        $topic = new Topic();
        $post = new Post();
        $post->addBehavior(new CountableBehavior())
            ->addCounter('Topic', 'post_count', function() {
                $this->where('active', 1);
            });

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

    /**
     * Test that counts are synced on update.
     */
    public function testOnUpdateManyToOne() {
        $this->loadFixtures(['Topics', 'Posts']);

        $topic = new Topic();
        $post = new Post();
        $post->addBehavior(new CountableBehavior())
            ->addCounter('Topic', 'post_count', function() {
                $this->where('active', 1);
            });

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
        $post->updateMany(['active' => 0], function() {
            $this->where('topic_id', 1);
        });

        $record = $topic->read(1);
        $this->assertEquals(0, $record->post_count);
    }

    /**
     * Test that counts are synced on delete.
     */
    public function testOnDeleteManyToOne() {
        $this->loadFixtures(['Topics', 'Posts']);

        $topic = new Topic();
        $post = new Post();
        $post->addBehavior(new CountableBehavior())
            ->addCounter('Topic', 'post_count', function() {
                $this->where('active', 1);
            });

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
        $post->deleteMany(function() {
            $this->where('topic_id', 1);
        }, false);

        $record = $topic->read(1);
        $this->assertEquals(0, $record->post_count);
    }

}