<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Db\Entity;
use Titon\Test\Stub\Repository\Post;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Behavior\ConverterBehavior.
 *
 * @property \Titon\Db\Behavior\ConverterBehavior $object
 */
class ConverterBehaviorTest extends TestCase {

    /**
     * Test field serialization.
     */
    public function testSerialize() {
        $this->loadFixtures('Posts');

        $post = new Post();

        // No decoding
        $post->addBehavior(new ConverterBehavior())
            ->convert('content', 'serialize', ['decode' => false]);

        $post_id = $post->create([
            'topic_id' => 3,
            'active' => 1,
            'content' => ['foo' => 'bar']
        ]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 3,
            'active' => 1,
            'deleted' => 0,
            'content' => 'a:1:{s:3:"foo";s:3:"bar";}',
            'created_at' => null,
            'deleted_at' => null
        ]), $post->read($post_id));

        // With decoding
        $post->getBehavior('Converter')
            ->convert('content', 'serialize', ['decode' => true]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 3,
            'active' => 1,
            'deleted' => 0,
            'content' => ['foo' => 'bar'],
            'created_at' => null,
            'deleted_at' => null
        ]), $post->read($post_id));
    }

    /**
     * Test field JSON conversion.
     */
    public function testJson() {
        $this->loadFixtures('Posts');

        $post = new Post();

        // No decoding
        $post->addBehavior(new ConverterBehavior())
            ->convert('content', 'json', ['decode' => false]);

        $post_id = $post->create([
            'topic_id' => 3,
            'active' => 1,
            'content' => ['foo' => 'bar']
        ]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 3,
            'active' => 1,
            'deleted' => 0,
            'content' => '{"foo":"bar"}',
            'created_at' => null,
            'deleted_at' => null
        ]), $post->read($post_id));

        // With decoding
        $post->getBehavior('Converter')
            ->convert('content', 'json', ['decode' => true]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 3,
            'active' => 1,
            'deleted' => 0,
            'content' => ['foo' => 'bar'],
            'created_at' => null,
            'deleted_at' => null
        ]), $post->read($post_id));
    }

    /**
     * Test field base64 conversion.
     */
    public function testBase64() {
        $this->loadFixtures('Posts');

        $post = new Post();

        // No decoding
        $post->addBehavior(new ConverterBehavior())
            ->convert('content', 'base64', ['decode' => false]);

        $post_id = $post->create([
            'topic_id' => 3,
            'active' => 1,
            'content' => 'This data will be base64 encoded'
        ]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 3,
            'active' => 1,
            'deleted' => 0,
            'content' => 'VGhpcyBkYXRhIHdpbGwgYmUgYmFzZTY0IGVuY29kZWQ=',
            'created_at' => null,
            'deleted_at' => null
        ]), $post->read($post_id));

        // With decoding
        $post->getBehavior('Converter')
            ->convert('content', 'base64', ['decode' => true]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 3,
            'active' => 1,
            'deleted' => 0,
            'content' => 'This data will be base64 encoded',
            'created_at' => null,
            'deleted_at' => null
        ]), $post->read($post_id));
    }

}