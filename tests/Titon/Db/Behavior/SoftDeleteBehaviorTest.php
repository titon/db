<?php
namespace Titon\Db\Behavior;

use Titon\Db\Entity;
use Titon\Db\EntityCollection;
use Titon\Test\Stub\Repository\Post;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Repository $object
 */
class SoftDeleteBehaviorTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new Post();
        $this->object->addBehavior(new SoftDeleteBehavior());

        $this->loadFixtures('Posts');
    }

    public function testSoftDelete() {
        $this->assertEquals(new Entity([
            'id' => 3,
            'topic_id' => 1,
            'active' => 1,
            'deleted' => 0,
            'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.',
            'created_at' => '2012-07-29 11:36:12',
            'deleted_at' => null
        ]), $this->object->read(3));

        // Soft delete it
        $time = time();
        $this->object->getBehavior('SoftDelete')->softDelete(3);

        // Reading should return nothing as its filtered
        $this->assertEquals(null, $this->object->read(3));

        // Fetch it without callbacks
        $this->assertEquals(new Entity([
            'id' => 3,
            'topic_id' => 1,
            'active' => 1,
            'deleted' => 1,
            'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.',
            'created_at' => '2012-07-29 11:36:12',
            'deleted_at' => date('Y-m-d H:i:s', $time)
        ]), $this->object->read(3, ['before' => false]));
    }

    public function testHardDelete() {
        /** @type \Titon\Db\Behavior\SoftDeleteBehavior $behavior */
        $behavior = $this->object->getBehavior('SoftDelete');

        // Do not filter
        $behavior->setConfig('filterDeleted', false);

        // Test on deleted record
        $this->assertTrue($this->object->exists(1));

        $behavior->hardDelete(1);

        $this->assertFalse($this->object->exists(1));

        // Test on non-deleted record
        $this->assertTrue($this->object->exists(3));

        $behavior->hardDelete(3);

        $this->assertFalse($this->object->exists(3));
    }

    public function testPurgeDeletedTimeframe() {
        /** @type \Titon\Db\Behavior\SoftDeleteBehavior $behavior */
        $behavior = $this->object->getBehavior('SoftDelete');

        // All records
        $this->assertEquals(7, $this->object->select()->count());

        // Delete in 2013
        $behavior->purgeDeleted('2013-01-01 00:00:00');

        $this->assertEquals(5, $this->object->select()->count());

        // Delete in 2012
        $behavior->purgeDeleted('2012-01-01 00:00:00');

        $this->assertEquals(4, $this->object->select()->count());
    }

    public function testPurgeDeletedFlag() {
        $this->assertEquals(7, $this->object->select()->count());

        $this->object->getBehavior('SoftDelete')->purgeDeleted(null);

        // 1 record has deleted = true, but no deleted_at timestamp
        $this->assertEquals(3, $this->object->select()->count());
    }

    /**
     * Test deleting records with a timestamp.
     */
    public function testPurgeDeletedTimestamp() {
        $this->object->getBehavior('SoftDelete')->setConfig('useFlag', false);

        $this->assertEquals(7, $this->object->select()->count());

        $this->object->getBehavior('SoftDelete')->purgeDeleted(null);

        $this->assertEquals(4, $this->object->select()->count());
    }

    public function testFilterDeletedWithFlag() {
        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Proin sed magna accumsan, mattis dolor at, commodo nisl.', 'created_at' => '2012-04-06 23:55:33', 'deleted_at' => null]),
            new Entity(['id' => 3, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.', 'created_at' => '2012-07-29 11:36:12', 'deleted_at' => null]),
            new Entity(['id' => 4, 'topic_id' => 1, 'active' => 0, 'deleted' => 0, 'content' => 'Vestibulum dapibus nunc quis erat placerat accumsan.', 'created_at' => '2012-11-30 22:42:22', 'deleted_at' => null]),
         ]), $this->object->select()->all());

        // Do not filter
        $this->object->getBehavior('SoftDelete')->setConfig('filterDeleted', false);

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'topic_id' => 1, 'active' => 1, 'deleted' => 1, 'content' => 'Curabitur vulputate sem eget metus dignissim varius.', 'created_at' => '2012-01-01 00:12:34', 'deleted_at' => '2012-02-06 23:55:33']),
            new Entity(['id' => 2, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Proin sed magna accumsan, mattis dolor at, commodo nisl.', 'created_at' => '2012-04-06 23:55:33', 'deleted_at' => null]),
            new Entity(['id' => 3, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.', 'created_at' => '2012-07-29 11:36:12', 'deleted_at' => null]),
            new Entity(['id' => 4, 'topic_id' => 1, 'active' => 0, 'deleted' => 0, 'content' => 'Vestibulum dapibus nunc quis erat placerat accumsan.', 'created_at' => '2012-11-30 22:42:22', 'deleted_at' => null]),
            new Entity(['id' => 5, 'topic_id' => 1, 'active' => 1, 'deleted' => 1, 'content' => 'Nullam congue dolor sed luctus pulvinar.', 'created_at' => '2013-02-26 11:44:33', 'deleted_at' => '2013-11-06 22:13:27']),
            new Entity(['id' => 6, 'topic_id' => 2, 'active' => 1, 'deleted' => 1, 'content' => 'Suspendisse faucibus lacus eget ullamcorper dictum.', 'created_at' => '2013-06-18 03:25:03', 'deleted_at' => '2013-08-08 02:03:11']),
            new Entity(['id' => 7, 'topic_id' => 2, 'active' => 0, 'deleted' => 1, 'content' => 'Quisque dui nulla, semper nec sagittis quis.', 'created_at' => '2013-08-08 02:03:11', 'deleted_at' => null])
        ]), $this->object->select()->all());
    }

    public function testFilterDeletedWithTimestamp() {
        $this->object->getBehavior('SoftDelete')->setConfig('useFlag', false);

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Proin sed magna accumsan, mattis dolor at, commodo nisl.', 'created_at' => '2012-04-06 23:55:33', 'deleted_at' => null]),
            new Entity(['id' => 3, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.', 'created_at' => '2012-07-29 11:36:12', 'deleted_at' => null]),
            new Entity(['id' => 4, 'topic_id' => 1, 'active' => 0, 'deleted' => 0, 'content' => 'Vestibulum dapibus nunc quis erat placerat accumsan.', 'created_at' => '2012-11-30 22:42:22', 'deleted_at' => null]),
            new Entity(['id' => 7, 'topic_id' => 2, 'active' => 0, 'deleted' => 1, 'content' => 'Quisque dui nulla, semper nec sagittis quis.', 'created_at' => '2013-08-08 02:03:11', 'deleted_at' => null])
         ]), $this->object->select()->all());

        // Do not filter
        $this->object->getBehavior('SoftDelete')->setConfig('filterDeleted', false);

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'topic_id' => 1, 'active' => 1, 'deleted' => 1, 'content' => 'Curabitur vulputate sem eget metus dignissim varius.', 'created_at' => '2012-01-01 00:12:34', 'deleted_at' => '2012-02-06 23:55:33']),
            new Entity(['id' => 2, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Proin sed magna accumsan, mattis dolor at, commodo nisl.', 'created_at' => '2012-04-06 23:55:33', 'deleted_at' => null]),
            new Entity(['id' => 3, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.', 'created_at' => '2012-07-29 11:36:12', 'deleted_at' => null]),
            new Entity(['id' => 4, 'topic_id' => 1, 'active' => 0, 'deleted' => 0, 'content' => 'Vestibulum dapibus nunc quis erat placerat accumsan.', 'created_at' => '2012-11-30 22:42:22', 'deleted_at' => null]),
            new Entity(['id' => 5, 'topic_id' => 1, 'active' => 1, 'deleted' => 1, 'content' => 'Nullam congue dolor sed luctus pulvinar.', 'created_at' => '2013-02-26 11:44:33', 'deleted_at' => '2013-11-06 22:13:27']),
            new Entity(['id' => 6, 'topic_id' => 2, 'active' => 1, 'deleted' => 1, 'content' => 'Suspendisse faucibus lacus eget ullamcorper dictum.', 'created_at' => '2013-06-18 03:25:03', 'deleted_at' => '2013-08-08 02:03:11']),
            new Entity(['id' => 7, 'topic_id' => 2, 'active' => 0, 'deleted' => 1, 'content' => 'Quisque dui nulla, semper nec sagittis quis.', 'created_at' => '2013-08-08 02:03:11', 'deleted_at' => null])
        ]), $this->object->select()->all());
    }

    public function testFilterDeletedOverride() {
        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'topic_id' => 1, 'active' => 1, 'deleted' => 1, 'content' => 'Curabitur vulputate sem eget metus dignissim varius.', 'created_at' => '2012-01-01 00:12:34', 'deleted_at' => '2012-02-06 23:55:33']),
            new Entity(['id' => 2, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Proin sed magna accumsan, mattis dolor at, commodo nisl.', 'created_at' => '2012-04-06 23:55:33', 'deleted_at' => null]),
            new Entity(['id' => 3, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.', 'created_at' => '2012-07-29 11:36:12', 'deleted_at' => null]),
            new Entity(['id' => 4, 'topic_id' => 1, 'active' => 0, 'deleted' => 0, 'content' => 'Vestibulum dapibus nunc quis erat placerat accumsan.', 'created_at' => '2012-11-30 22:42:22', 'deleted_at' => null]),
            new Entity(['id' => 5, 'topic_id' => 1, 'active' => 1, 'deleted' => 1, 'content' => 'Nullam congue dolor sed luctus pulvinar.', 'created_at' => '2013-02-26 11:44:33', 'deleted_at' => '2013-11-06 22:13:27']),
            new Entity(['id' => 6, 'topic_id' => 2, 'active' => 1, 'deleted' => 1, 'content' => 'Suspendisse faucibus lacus eget ullamcorper dictum.', 'created_at' => '2013-06-18 03:25:03', 'deleted_at' => '2013-08-08 02:03:11']),
            new Entity(['id' => 7, 'topic_id' => 2, 'active' => 0, 'deleted' => 1, 'content' => 'Quisque dui nulla, semper nec sagittis quis.', 'created_at' => '2013-08-08 02:03:11', 'deleted_at' => null])
        ]), $this->object->select()->where('deleted', [0, 1])->all());

        $this->object->getBehavior('SoftDelete')->setConfig('useFlag', false);

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 5, 'topic_id' => 1, 'active' => 1, 'deleted' => 1, 'content' => 'Nullam congue dolor sed luctus pulvinar.', 'created_at' => '2013-02-26 11:44:33', 'deleted_at' => '2013-11-06 22:13:27']),
            new Entity(['id' => 6, 'topic_id' => 2, 'active' => 1, 'deleted' => 1, 'content' => 'Suspendisse faucibus lacus eget ullamcorper dictum.', 'created_at' => '2013-06-18 03:25:03', 'deleted_at' => '2013-08-08 02:03:11']),
        ]), $this->object->select()->where('deleted_at', '>', '2013-01-01 00:00:00')->all());
    }

    public function testDeleteEvent() {
        $this->assertEquals(new Entity([
            'id' => 3,
            'topic_id' => 1,
            'active' => 1,
            'deleted' => 0,
            'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.',
            'created_at' => '2012-07-29 11:36:12',
            'deleted_at' => null
        ]), $this->object->read(3));

        $time = time();
        $this->object->delete(3);

        // Reading should return nothing as its filtered
        $this->assertEquals(null, $this->object->read(3));

        // Fetch it without callbacks
        $this->assertEquals(new Entity([
            'id' => 3,
            'topic_id' => 1,
            'active' => 1,
            'deleted' => 1,
            'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.',
            'created_at' => '2012-07-29 11:36:12',
            'deleted_at' => date('Y-m-d H:i:s', $time)
        ]), $this->object->read(3, ['before' => false]));
    }

}