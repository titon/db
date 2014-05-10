<?php
namespace Titon\Db\Behavior;

use Titon\Db\Entity;
use Titon\Test\Stub\Repository\Post;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Behavior\FilterBehavior $object
 */
class FilterBehaviorTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new FilterBehavior();

        $this->loadFixtures('Posts');
    }

    public function testFilter() {
        $this->object->filter('field', 'html', ['foo' => 'bar']);

        $this->assertEquals([
            'field' => [
                'html' => ['foo' => 'bar']
            ]
        ], $this->object->getFilters());

        $this->object->filter('field', 'xss', ['foo' => 'bar']);

        $this->assertEquals([
            'field' => [
                'html' => ['foo' => 'bar'],
                'xss' => ['foo' => 'bar']
            ]
        ], $this->object->getFilters());
    }

    /**
     * @expectedException \Titon\Db\Exception\InvalidArgumentException
     */
    public function testFilterInvalidType() {
        $this->object->filter('field', 'foobar', ['foo' => 'bar']);
    }

    public function testStrip() {
        $post = new Post();
        $post->addBehavior($this->object->filter('content', 'html'));

        $post_id = $post->create([
            'topic_id' => 5,
            'active' => 0,
            'content' => 'These <b>html</b> tags should be <i>stripped!</i>'
        ]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 5,
            'active' => 0,
            'deleted' => 0,
            'content' => 'These html tags should be stripped!',
            'created_at' => null,
            'deleted_at' => null
        ]), $post->read($post_id));
    }

    public function testHtml() {
        $post = new Post();
        $post->addBehavior($this->object->filter('content', 'html', ['strip' => false]));

        $post_id = $post->create([
            'topic_id' => 5,
            'active' => 0,
            'content' => 'These <b>html</b> tags should be <i>escaped!</i>'
        ]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 5,
            'active' => 0,
            'deleted' => 0,
            'content' => 'These &lt;b&gt;html&lt;/b&gt; tags should be &lt;i&gt;escaped!&lt;/i&gt;',
            'created_at' => null,
            'deleted_at' => null
        ]), $post->read($post_id));
    }

    public function testNewLines() {
        $post = new Post();
        $post->addBehavior($this->object->filter('content', 'newlines'));

        $post_id = $post->create([
            'topic_id' => 5,
            'active' => 0,
            'content' => "These\r\n\r\n\r\nextraneous newlines should be\n\nremoved"
        ]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 5,
            'active' => 0,
            'deleted' => 0,
            'content' => "These\r\nextraneous newlines should be\nremoved",
            'created_at' => null,
            'deleted_at' => null
        ]), $post->read($post_id));
    }

    public function testWhitespace() {
        $post = new Post();
        $post->addBehavior($this->object->filter('content', 'whitespace'));

        $post_id = $post->create([
            'topic_id' => 5,
            'active' => 0,
            'content' => "These     extraneous whitespace should be\t\t\tremoved"
        ]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 5,
            'active' => 0,
            'deleted' => 0,
            'content' => "These extraneous whitespace should be\tremoved",
            'created_at' => null,
            'deleted_at' => null
        ]), $post->read($post_id));
    }

    public function testXss() {
        $post = new Post();
        $post->addBehavior($this->object->filter('content', 'xss', ['strip' => false]));

        $post_id = $post->create([
            'topic_id' => 5,
            'active' => 0,
            'content' => 'These <iframe></iframe> <div onclick="">html</div> <i>tags</i> should <ns:b>be</ns:b> removed!'
        ]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 5,
            'active' => 0,
            'deleted' => 0,
            'content' => 'These  &lt;div&gt;html&lt;/div&gt; &lt;i&gt;tags&lt;/i&gt; should be removed!',
            'created_at' => null,
            'deleted_at' => null
        ]), $post->read($post_id));
    }

}