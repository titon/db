<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Db\Entity;
use Titon\Test\Stub\Table\Post;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Behavior\FilterableBehavior.
 *
 * @property \Titon\Db\Behavior\FilterableBehavior $object
 */
class FilterableBehaviorTest extends TestCase {

    /**
     * Test that HTML is stripped.
     */
    public function testStrip() {
        $this->loadFixtures('Posts');

        $post = new Post();
        $post->addBehavior(new FilterableBehavior())
            ->filter('content', 'html');

        $post_id = $post->create([
            'topic_id' => 5,
            'active' => 0,
            'content' => 'These <b>html</b> tags should be <i>stripped!</i>'
        ]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 5,
            'active' => 0,
            'content' => 'These html tags should be stripped!'
        ]), $post->read($post_id));
    }

    /**
     * Test that HTML is escaped.
     */
    public function testHtml() {
        $this->loadFixtures('Posts');

        $post = new Post();
        $post->addBehavior(new FilterableBehavior())
            ->filter('content', 'html', ['strip' => false]);

        $post_id = $post->create([
            'topic_id' => 5,
            'active' => 0,
            'content' => 'These <b>html</b> tags should be <i>escaped!</i>'
        ]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 5,
            'active' => 0,
            'content' => 'These &lt;b&gt;html&lt;/b&gt; tags should be &lt;i&gt;escaped!&lt;/i&gt;'
        ]), $post->read($post_id));
    }

    /**
     * Test that newlines are trimmed.
     */
    public function testNewLines() {
        $this->loadFixtures('Posts');

        $post = new Post();
        $post->addBehavior(new FilterableBehavior())
            ->filter('content', 'newlines');

        $post_id = $post->create([
            'topic_id' => 5,
            'active' => 0,
            'content' => "These\r\n\r\n\r\nextraneous newlines should be\n\nremoved"
        ]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 5,
            'active' => 0,
            'content' => "These\r\nextraneous newlines should be\nremoved"
        ]), $post->read($post_id));
    }

    /**
     * Test that whitespace is trimmed.
     */
    public function testWhitespace() {
        $this->loadFixtures('Posts');

        $post = new Post();
        $post->addBehavior(new FilterableBehavior())
            ->filter('content', 'whitespace');

        $post_id = $post->create([
            'topic_id' => 5,
            'active' => 0,
            'content' => "These     extraneous whitespace should be\t\t\tremoved"
        ]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 5,
            'active' => 0,
            'content' => "These extraneous whitespace should be\tremoved"
        ]), $post->read($post_id));
    }

    /**
     * Test that HTML is removed.
     */
    public function testXss() {
        $this->loadFixtures('Posts');

        $post = new Post();
        $post->addBehavior(new FilterableBehavior())
            ->filter('content', 'xss', ['strip' => false]);

        $post_id = $post->create([
            'topic_id' => 5,
            'active' => 0,
            'content' => 'These <iframe></iframe> <div onclick="">html</div> <i>tags</i> should <ns:b>be</ns:b> removed!'
        ]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 5,
            'active' => 0,
            'content' => 'These  &lt;div&gt;html&lt;/div&gt; &lt;i&gt;tags&lt;/i&gt; should be removed!'
        ]), $post->read($post_id));
    }

}