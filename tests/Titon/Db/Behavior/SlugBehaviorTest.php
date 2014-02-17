<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Db\Entity;
use Titon\Db\Query;
use Titon\Test\Stub\Repository\Topic;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Behavior\SlugBehavior.
 *
 * @property \Titon\Db\Behavior\SlugBehavior $object
 */
class SlugBehaviorTest extends TestCase {

    /**
     * Test unique slugs increment.
     */
    public function testUniqueSlugs() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SlugBehavior());

        $topic_id = $topic->create(['title' => 'Bruce Wayne']);

        $this->assertEquals(new Entity([
            'title' => 'Bruce Wayne',
            'slug' => 'bruce-wayne'
        ]), $topic->select('title', 'slug')->where('id', $topic_id)->first());

        $topic_id = $topic->create(['title' => 'Bruce Wayne']);

        $this->assertEquals(new Entity([
            'title' => 'Bruce Wayne',
            'slug' => 'bruce-wayne-1'
        ]), $topic->select('title', 'slug')->where('id', $topic_id)->first());
    }

    /**
     * Test that non-unique slugs can use same name.
     */
    public function testNonUniqueSlugs() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SlugBehavior(['unique' => false]));

        $topic_id = $topic->create(['title' => 'Bruce Wayne']);

        $this->assertEquals(new Entity([
            'title' => 'Bruce Wayne',
            'slug' => 'bruce-wayne'
        ]), $topic->select('title', 'slug')->where('id', $topic_id)->first());

        $topic_id = $topic->create(['title' => 'Bruce Wayne']);

        $this->assertEquals(new Entity([
            'title' => 'Bruce Wayne',
            'slug' => 'bruce-wayne'
        ]), $topic->select('title', 'slug')->where('id', $topic_id)->first());
    }

    /**
     * Should not slug without a title.
     */
    public function testSaveWithMissingTitle() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SlugBehavior());

        $topic_id = $topic->create(['content' => 'Testing with no title or slug.']);

        $this->assertEquals(new Entity([
            'title' => '',
            'slug' => '',
            'content' => 'Testing with no title or slug.'
        ]), $topic->select('title', 'slug', 'content')->where('id', $topic_id)->first());
    }

    /**
     * Test that supplied slug takes precedence.
     */
    public function testSaveWithSlug() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SlugBehavior());

        $topic_id = $topic->create(['title' => 'This is the title', 'slug' => 'and-different-slug']);

        $this->assertEquals(new Entity([
            'title' => 'This is the title',
            'slug' => 'and-different-slug'
        ]), $topic->select('title', 'slug')->where('id', $topic_id)->first());
    }

    /**
     * Test that slug is changed on update.
     */
    public function testOnUpdate() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SlugBehavior());

        $topic->update(1, ['title' => 'Batman vs Ironman']);

        $this->assertNotEquals(new Entity([
            'title' => 'Batman vs Superman?',
            'slug' => 'batman-vs-superman'
        ]), $topic->select('title', 'slug')->where('id', 1)->first());
    }

    /**
     * Test that slug isn't changed on update.
     */
    public function testOnUpdateDisabled() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SlugBehavior(['onUpdate' => false]));

        $topic->update(1, ['title' => 'Batman vs Ironman']);

        $this->assertEquals(new Entity([
            'title' => 'Batman vs Ironman',
            'slug' => 'batman-vs-superman'
        ]), $topic->select('title', 'slug')->where('id', 1)->first());
    }

    /**
     * Test that slug isn't changed on update.
     */
    public function testOnUpdateSameSlug() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SlugBehavior(['onUpdate' => false]));

        $topic->update(1, ['title' => 'Batman vs Superman']);

        $this->assertEquals(new Entity([
            'title' => 'Batman vs Superman',
            'slug' => 'batman-vs-superman'
        ]), $topic->select('title', 'slug')->where('id', 1)->first());
    }

    /**
     * Test that scope is applied and slug adheres to it.
     */
    public function testWithScope() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SlugBehavior(['scope' => function(Query $query) {
            $query->where('post_count', '<=', 3);
        }]));

        $topic_id = $topic->create(['title' => 'Batman vs Superman']);

        // Should not increment since other records do not meet scope
        $this->assertEquals(new Entity([
            'title' => 'Batman vs Superman',
            'slug' => 'batman-vs-superman'
        ]), $topic->select('title', 'slug')->where('id', $topic_id)->first());
    }

    /**
     * Test that certain words are slugged.
     */
    public function testSlugify() {
        $this->assertEquals('batman-and-robin', SlugBehavior::slugify('Batman & Robin'));
        $this->assertEquals('batman-and-robin', SlugBehavior::slugify('Batman &amp; Robin'));
        $this->assertEquals('this-is-some-kind-of-html', SlugBehavior::slugify('This is <b>some</b> kind of HTML'));
        $this->assertEquals('game-starts-at-1030pm', SlugBehavior::slugify('Game starts @ 10:30pm'));
    }

}