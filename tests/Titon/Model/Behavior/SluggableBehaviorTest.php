<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model\Behavior;

use Titon\Test\Stub\Model\Topic;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Behavior\SluggableBehavior.
 *
 * @property \Titon\Model\Behavior\SluggableBehavior $object
 */
class SluggableBehaviorTest extends TestCase {

    /**
     * Test unique slugs increment.
     */
    public function testUniqueSlugs() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SluggableBehavior());

        $topic_id = $topic->create(['title' => 'Bruce Wayne']);

        $this->assertEquals([
            'title' => 'Bruce Wayne',
            'slug' => 'bruce-wayne'
        ], $topic->select('title', 'slug')->where('id', $topic_id)->fetch(false));

        $topic_id = $topic->create(['title' => 'Bruce Wayne']);

        $this->assertEquals([
            'title' => 'Bruce Wayne',
            'slug' => 'bruce-wayne-1'
        ], $topic->select('title', 'slug')->where('id', $topic_id)->fetch(false));
    }

    /**
     * Test that non-unique slugs can use same name.
     */
    public function testNonUniqueSlugs() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SluggableBehavior(['unique' => false]));

        $topic_id = $topic->create(['title' => 'Bruce Wayne']);

        $this->assertEquals([
            'title' => 'Bruce Wayne',
            'slug' => 'bruce-wayne'
        ], $topic->select('title', 'slug')->where('id', $topic_id)->fetch(false));

        $topic_id = $topic->create(['title' => 'Bruce Wayne']);

        $this->assertEquals([
            'title' => 'Bruce Wayne',
            'slug' => 'bruce-wayne'
        ], $topic->select('title', 'slug')->where('id', $topic_id)->fetch(false));
    }

    /**
     * Should not slug without a title.
     */
    public function testSaveWithMissingTitle() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SluggableBehavior());

        $topic_id = $topic->create(['content' => 'Testing with no title or slug.']);

        $this->assertEquals([
            'title' => '',
            'slug' => '',
            'content' => 'Testing with no title or slug.'
        ], $topic->select('title', 'slug', 'content')->where('id', $topic_id)->fetch(false));
    }

    /**
     * Test that supplied slug takes precedence.
     */
    public function testSaveWithSlug() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SluggableBehavior());

        $topic_id = $topic->create(['title' => 'This is the title', 'slug' => 'and-different-slug']);

        $this->assertEquals([
            'title' => 'This is the title',
            'slug' => 'and-different-slug'
        ], $topic->select('title', 'slug')->where('id', $topic_id)->fetch(false));
    }

    /**
     * Test that slug is changed on update.
     */
    public function testOnUpdate() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SluggableBehavior());

        $topic->update(1, ['title' => 'Batman vs Ironman']);

        $this->assertNotEquals([
            'title' => 'Batman vs Superman?',
            'slug' => 'batman-vs-superman'
        ], $topic->select('title', 'slug')->where('id', 1)->fetch(false));
    }

    /**
     * Test that slug isn't changed on update.
     */
    public function testOnUpdateDisabled() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SluggableBehavior(['onUpdate' => false]));

        $topic->update(1, ['title' => 'Batman vs Ironman']);

        $this->assertEquals([
            'title' => 'Batman vs Ironman',
            'slug' => 'batman-vs-superman'
        ], $topic->select('title', 'slug')->where('id', 1)->fetch(false));
    }

    /**
     * Test that slug isn't changed on update.
     */
    public function testOnUpdateSameSlug() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SluggableBehavior(['onUpdate' => false]));

        $topic->update(1, ['title' => 'Batman vs Superman']);

        $this->assertEquals([
            'title' => 'Batman vs Superman',
            'slug' => 'batman-vs-superman'
        ], $topic->select('title', 'slug')->where('id', 1)->fetch(false));
    }

    /**
     * Test that scope is applied and slug adheres to it.
     */
    public function testWithScope() {
        $this->loadFixtures('Topics');

        $topic = new Topic();
        $topic->addBehavior(new SluggableBehavior(['scope' => function() {
            $this->where('post_count', '<=', 3);
        }]));

        $topic_id = $topic->create(['title' => 'Batman vs Superman']);

        // Should not increment since other records do not meet scope
        $this->assertEquals([
            'title' => 'Batman vs Superman',
            'slug' => 'batman-vs-superman'
        ], $topic->select('title', 'slug')->where('id', $topic_id)->fetch(false));
    }

    /**
     * Test that certain words are slugged.
     */
    public function testSlugify() {
        $this->assertEquals('batman-and-robin', SluggableBehavior::slugify('Batman & Robin'));
        $this->assertEquals('batman-and-robin', SluggableBehavior::slugify('Batman &amp; Robin'));
        $this->assertEquals('this-is-some-kind-of-html', SluggableBehavior::slugify('This is <b>some</b> kind of HTML'));
        $this->assertEquals('game-starts-at-1030pm', SluggableBehavior::slugify('Game starts @ 10:30pm'));
    }

}