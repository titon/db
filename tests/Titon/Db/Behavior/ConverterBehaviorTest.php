<?php
namespace Titon\Db\Behavior;

use Titon\Db\Entity;
use Titon\Test\Stub\Repository\Post;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Behavior\ConverterBehavior $object
 */
class ConverterBehaviorTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new ConverterBehavior();
    }

    public function testConvert() {
        $this->object->convert('content', 'json', [
            'object' => true,
            'decode' => false
        ]);

        $this->assertEquals([
            'content' => [
                'encode' => true,
                'decode' => false,
                'type' => 'json',
                'object' => true
            ]
        ], $this->object->getConverters());
    }

    /**
     * @expectedException \Titon\Db\Exception\InvalidArgumentException
     */
    public function testConvertInvalidType() {
        $this->object->convert('content', 'foobar');
    }

    public function testCustom() {
        $this->loadFixtures('Posts');

        $post = new Post();

        // No decoding
        $behavior = $this->object->convert('content', 'custom', [
            'encode' => 'toHash',
            'decode' => false
        ]);

        $post->addBehavior($behavior);

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
            'content' => 'YToxOntzOjM6ImZvbyI7czozOiJiYXIiO30=',
            'created_at' => null,
            'deleted_at' => null
        ]), $post->read($post_id));

        // With decoding
        $post->getBehavior('Converter')
            ->convert('content', 'custom', ['decode' => 'fromHash']);

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

    public function testSerialize() {
        $this->loadFixtures('Posts');

        $post = new Post();

        // No decoding
        $behavior = $this->object->convert('content', 'serialize', ['decode' => false]);

        $post->addBehavior($behavior);

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

    public function testJson() {
        $this->loadFixtures('Posts');

        $post = new Post();

        // No decoding
        $behavior = $this->object->convert('content', 'json', ['decode' => false]);

        $post->addBehavior($behavior);

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

    public function testBase64() {
        $this->loadFixtures('Posts');

        // No decoding
        $behavior = $this->object->convert('content', 'base64', ['decode' => false]);

        $post = new Post();
        $post->addBehavior($behavior);

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

    public function testEncodeDecodeSkip() {
        $this->loadFixtures('Posts');

        $behavior = $this->object->convert('content', 'base64', ['encode' => false, 'decode' => false]);

        $post = new Post();
        $post->addBehavior($behavior);

        $post_id = $post->create([
            'topic_id' => 3,
            'active' => 1,
            'content' => 'This data will remain intact'
        ]);

        $this->assertEquals(new Entity([
            'id' => $post_id,
            'topic_id' => 3,
            'active' => 1,
            'deleted' => 0,
            'content' => 'This data will remain intact',
            'created_at' => null,
            'deleted_at' => null
        ]), $post->read($post_id));
    }

    public function testListFinderSkip() {
        $this->loadFixtures('Posts');

        $behavior = $this->object->convert('content', 'base64');

        $post = new Post();
        $post->addBehavior($behavior);

        $post->create([
            'topic_id' => 3,
            'active' => 1,
            'content' => 'This data will be base64 encoded'
        ]);

        $this->assertEquals([
            1 => 'Curabitur vulputate sem eget metus dignissim varius.',
            2 => 'Proin sed magna accumsan, mattis dolor at, commodo nisl.',
            3 => 'Nullam vel pulvinar lorem. Ut id egestas justo.',
            4 => 'Vestibulum dapibus nunc quis erat placerat accumsan.',
            5 => 'Nullam congue dolor sed luctus pulvinar.',
            6 => 'Suspendisse faucibus lacus eget ullamcorper dictum.',
            7 => 'Quisque dui nulla, semper nec sagittis quis.',
            8 => 'VGhpcyBkYXRhIHdpbGwgYmUgYmFzZTY0IGVuY29kZWQ=' // Not decoded for lists
        ], $post->select()->lists('content'));
    }

}