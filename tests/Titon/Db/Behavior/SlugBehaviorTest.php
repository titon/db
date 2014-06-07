<?php
namespace Titon\Db\Behavior;

use Titon\Db\Entity;
use Titon\Db\Query;
use Titon\Event\Event;
use Titon\Test\Stub\Repository\Topic;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Behavior\SlugBehavior $object
 */
class SlugBehaviorTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new SlugBehavior();

        $this->loadFixtures('Topics');
    }

    public function testUniqueSlugs() {
        $topic = new Topic();
        $topic->addBehavior($this->object);

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

    public function testNonUniqueSlugs() {
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

    public function testSlugLength() {
        $topic = new Topic();
        $topic->addBehavior($this->object);

        $data = ['title' => 'This is something we need to slug and shorten'];

        $this->object->setConfig('length', 15);
        $this->object->preSave(new Event('db.preSave'), new Query('insert'), 1, $data);

        $this->assertEquals('this-is-some', $data['slug']);
    }

    public function testSaveWithMissingTitle() {
        $topic = new Topic();
        $topic->addBehavior($this->object);

        $topic_id = $topic->create(['content' => 'Testing with no title or slug.']);

        $this->assertEquals(new Entity([
            'title' => '',
            'slug' => '',
            'content' => 'Testing with no title or slug.'
        ]), $topic->select('title', 'slug', 'content')->where('id', $topic_id)->first());
    }

    public function testSaveWithSlug() {
        $topic = new Topic();
        $topic->addBehavior($this->object);

        $topic_id = $topic->create(['title' => 'This is the title', 'slug' => 'and-different-slug']);

        $this->assertEquals(new Entity([
            'title' => 'This is the title',
            'slug' => 'and-different-slug'
        ]), $topic->select('title', 'slug')->where('id', $topic_id)->first());
    }

    public function testOnUpdate() {
        $topic = new Topic();
        $topic->addBehavior($this->object);

        $topic->update(1, ['title' => 'Batman vs Ironman']);

        $this->assertNotEquals(new Entity([
            'title' => 'Batman vs Superman?',
            'slug' => 'batman-vs-superman'
        ]), $topic->select('title', 'slug')->where('id', 1)->first());
    }

    public function testOnUpdateDisabled() {
        $topic = new Topic();
        $topic->addBehavior(new SlugBehavior(['onUpdate' => false]));

        $topic->update(1, ['title' => 'Batman vs Ironman']);

        $this->assertEquals(new Entity([
            'title' => 'Batman vs Ironman',
            'slug' => 'batman-vs-superman'
        ]), $topic->select('title', 'slug')->where('id', 1)->first());
    }

    public function testOnUpdateSameSlug() {
        $topic = new Topic();
        $topic->addBehavior(new SlugBehavior(['onUpdate' => false]));

        $topic->update(1, ['title' => 'Batman vs Superman']);

        $this->assertEquals(new Entity([
            'title' => 'Batman vs Superman',
            'slug' => 'batman-vs-superman'
        ]), $topic->select('title', 'slug')->where('id', 1)->first());
    }

    public function testWithScope() {
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

    public function testSlugify() {
        $this->assertEquals('batman-and-robin', SlugBehavior::slugify('Batman & Robin'));
        $this->assertEquals('batman-and-robin', SlugBehavior::slugify('Batman &amp; Robin'));
        $this->assertEquals('this-is-some-kind-of-html', SlugBehavior::slugify('This is <b>some</b> kind of HTML'));
        $this->assertEquals('game-starts-at-1030pm', SlugBehavior::slugify('Game starts @ 10:30pm'));
    }

}