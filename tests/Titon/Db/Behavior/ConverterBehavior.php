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

        $data = [
            'topic_id' => 3,
            'active' => 1,
            'content' => ['foo' => 'bar']
        ];

        $post_id = $post->create($data);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 3,
            'active' => 1,
            'content' => 'a:1:{s:3:"foo";s:3:"bar";}'
        ]), $post->read($post_id));

        // With decoding
        $post->getBehavior('Convertable')
            ->convert('content', 'serialize', ['decode' => true]);

        $data['id'] = $post_id;

        $this->assertEquals(new Entity($data), $post->read($post_id));
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

        $data = [
            'topic_id' => 3,
            'active' => 1,
            'content' => ['foo' => 'bar']
        ];

        $post_id = $post->create($data);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 3,
            'active' => 1,
            'content' => '{"foo":"bar"}'
        ]), $post->read($post_id));

        // With decoding
        $post->getBehavior('Convertable')
            ->convert('content', 'json', ['decode' => true]);

        $data['id'] = $post_id;

        $this->assertEquals(new Entity($data), $post->read($post_id));
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

        $data = [
            'topic_id' => 3,
            'active' => 1,
            'content' => 'This data will be base64 encoded'
        ];

        $post_id = $post->create($data);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 3,
            'active' => 1,
            'content' => 'VGhpcyBkYXRhIHdpbGwgYmUgYmFzZTY0IGVuY29kZWQ='
        ]), $post->read($post_id));

        // With decoding
        $post->getBehavior('Convertable')
            ->convert('content', 'base64', ['decode' => true]);

        $data['id'] = $post_id;

        $this->assertEquals(new Entity($data), $post->read($post_id));
    }

}