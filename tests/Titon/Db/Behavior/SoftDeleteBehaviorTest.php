<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Db\Entity;
use Titon\Test\Stub\Repository\Post;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Behavior\SoftDeleteBehavior.
 *
 * @property \Titon\Db\Repository $object
 */
class SoftDeleteBehaviorTest extends TestCase {

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new Post();
        $this->object->addBehavior(new SoftDeleteBehavior());
    }

    /**
     * Test that soft delete flags a record and doesn't delete it.
     */
    public function testSoftDelete() {
        $this->loadFixtures('Posts');

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
        $this->assertEquals([], $this->object->read(3));

        // Fetch it without callbacks
        $this->assertEquals(new Entity([
            'id' => 3,
            'topic_id' => 1,
            'active' => 1,
            'deleted' => 1,
            'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.',
            'created_at' => '2012-07-29 11:36:12',
            'deleted_at' => date('Y-m-d H:i:s', $time)
        ]), $this->object->read(3, ['preCallback' => false]));
    }

    /**
     * Test that hard delete removes the record.
     */
    public function testHardDelete() {
        $this->loadFixtures('Posts');

        /** @type \Titon\Db\Behavior\SoftDeleteBehavior $behavior */
        $behavior = $this->object->getBehavior('SoftDelete');

        // Do not filter
        $behavior->config->filterDeleted = false;

        // Test on deleted record
        $this->assertTrue($this->object->exists(1));

        $behavior->hardDelete(1);

        $this->assertFalse($this->object->exists(1));

        // Test on non-deleted record
        $this->assertTrue($this->object->exists(3));

        $behavior->hardDelete(3);

        $this->assertFalse($this->object->exists(3));
    }

    /**
     * Test deleting records within a time frame.
     */
    public function testPurgeDeletedTimeframe() {
        $this->loadFixtures('Posts');

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

    /**
     * Test deleting records with a flag.
     */
    public function testPurgeDeletedFlag() {
        $this->loadFixtures('Posts');

        $this->assertEquals(7, $this->object->select()->count());

        $this->object->getBehavior('SoftDelete')->purgeDeleted(null);

        // 1 record has deleted = true, but no deleted_at timestamp
        $this->assertEquals(3, $this->object->select()->count());
    }

    /**
     * Test deleting records with a timestamp.
     */
    public function testPurgeDeletedTimestamp() {
        $this->loadFixtures('Posts');

        $this->object->getBehavior('SoftDelete')->config->useFlag = false;

        $this->assertEquals(7, $this->object->select()->count());

        $this->object->getBehavior('SoftDelete')->purgeDeleted(null);

        $this->assertEquals(4, $this->object->select()->count());
    }

    /**
     * Test that find calls are filtered using the delete flag.
     */
    public function testFilterDeletedWithFlag() {
        $this->loadFixtures('Posts');

        $this->assertEquals([
            new Entity(['id' => 2, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Proin sed magna accumsan, mattis dolor at, commodo nisl.', 'created_at' => '2012-04-06 23:55:33', 'deleted_at' => null]),
            new Entity(['id' => 3, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.', 'created_at' => '2012-07-29 11:36:12', 'deleted_at' => null]),
            new Entity(['id' => 4, 'topic_id' => 1, 'active' => 0, 'deleted' => 0, 'content' => 'Vestibulum dapibus nunc quis erat placerat accumsan.', 'created_at' => '2012-11-30 22:42:22', 'deleted_at' => null]),
         ], $this->object->select()->fetchAll());

        // Do not filter
        $this->object->getBehavior('SoftDelete')->config->filterDeleted = false;

        $this->assertEquals([
            new Entity(['id' => 1, 'topic_id' => 1, 'active' => 1, 'deleted' => 1, 'content' => 'Curabitur vulputate sem eget metus dignissim varius.', 'created_at' => '2012-01-01 00:12:34', 'deleted_at' => '2012-02-06 23:55:33']),
            new Entity(['id' => 2, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Proin sed magna accumsan, mattis dolor at, commodo nisl.', 'created_at' => '2012-04-06 23:55:33', 'deleted_at' => null]),
            new Entity(['id' => 3, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.', 'created_at' => '2012-07-29 11:36:12', 'deleted_at' => null]),
            new Entity(['id' => 4, 'topic_id' => 1, 'active' => 0, 'deleted' => 0, 'content' => 'Vestibulum dapibus nunc quis erat placerat accumsan.', 'created_at' => '2012-11-30 22:42:22', 'deleted_at' => null]),
            new Entity(['id' => 5, 'topic_id' => 1, 'active' => 1, 'deleted' => 1, 'content' => 'Nullam congue dolor sed luctus pulvinar.', 'created_at' => '2013-02-26 11:44:33', 'deleted_at' => '2013-11-06 22:13:27']),
            new Entity(['id' => 6, 'topic_id' => 2, 'active' => 1, 'deleted' => 1, 'content' => 'Suspendisse faucibus lacus eget ullamcorper dictum.', 'created_at' => '2013-06-18 03:25:03', 'deleted_at' => '2013-08-08 02:03:11']),
            new Entity(['id' => 7, 'topic_id' => 2, 'active' => 0, 'deleted' => 1, 'content' => 'Quisque dui nulla, semper nec sagittis quis.', 'created_at' => '2013-08-08 02:03:11', 'deleted_at' => null])
        ], $this->object->select()->fetchAll());
    }

    /**
     * Test that find calls are filtered using the delete flag.
     */
    public function testFilterDeletedWithTimestamp() {
        $this->loadFixtures('Posts');

        $this->object->getBehavior('SoftDelete')->config->useFlag = false;

        $this->assertEquals([
            new Entity(['id' => 2, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Proin sed magna accumsan, mattis dolor at, commodo nisl.', 'created_at' => '2012-04-06 23:55:33', 'deleted_at' => null]),
            new Entity(['id' => 3, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.', 'created_at' => '2012-07-29 11:36:12', 'deleted_at' => null]),
            new Entity(['id' => 4, 'topic_id' => 1, 'active' => 0, 'deleted' => 0, 'content' => 'Vestibulum dapibus nunc quis erat placerat accumsan.', 'created_at' => '2012-11-30 22:42:22', 'deleted_at' => null]),
            new Entity(['id' => 7, 'topic_id' => 2, 'active' => 0, 'deleted' => 1, 'content' => 'Quisque dui nulla, semper nec sagittis quis.', 'created_at' => '2013-08-08 02:03:11', 'deleted_at' => null])
         ], $this->object->select()->fetchAll());

        // Do not filter
        $this->object->getBehavior('SoftDelete')->config->filterDeleted = false;

        $this->assertEquals([
            new Entity(['id' => 1, 'topic_id' => 1, 'active' => 1, 'deleted' => 1, 'content' => 'Curabitur vulputate sem eget metus dignissim varius.', 'created_at' => '2012-01-01 00:12:34', 'deleted_at' => '2012-02-06 23:55:33']),
            new Entity(['id' => 2, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Proin sed magna accumsan, mattis dolor at, commodo nisl.', 'created_at' => '2012-04-06 23:55:33', 'deleted_at' => null]),
            new Entity(['id' => 3, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.', 'created_at' => '2012-07-29 11:36:12', 'deleted_at' => null]),
            new Entity(['id' => 4, 'topic_id' => 1, 'active' => 0, 'deleted' => 0, 'content' => 'Vestibulum dapibus nunc quis erat placerat accumsan.', 'created_at' => '2012-11-30 22:42:22', 'deleted_at' => null]),
            new Entity(['id' => 5, 'topic_id' => 1, 'active' => 1, 'deleted' => 1, 'content' => 'Nullam congue dolor sed luctus pulvinar.', 'created_at' => '2013-02-26 11:44:33', 'deleted_at' => '2013-11-06 22:13:27']),
            new Entity(['id' => 6, 'topic_id' => 2, 'active' => 1, 'deleted' => 1, 'content' => 'Suspendisse faucibus lacus eget ullamcorper dictum.', 'created_at' => '2013-06-18 03:25:03', 'deleted_at' => '2013-08-08 02:03:11']),
            new Entity(['id' => 7, 'topic_id' => 2, 'active' => 0, 'deleted' => 1, 'content' => 'Quisque dui nulla, semper nec sagittis quis.', 'created_at' => '2013-08-08 02:03:11', 'deleted_at' => null])
        ], $this->object->select()->fetchAll());
    }

    /**
     * Test that auto filtered rows can be overridden of the where clause contains the field.
     */
    public function testFilterDeletedOverride() {
        $this->loadFixtures('Posts');

        $this->assertEquals([
            new Entity(['id' => 1, 'topic_id' => 1, 'active' => 1, 'deleted' => 1, 'content' => 'Curabitur vulputate sem eget metus dignissim varius.', 'created_at' => '2012-01-01 00:12:34', 'deleted_at' => '2012-02-06 23:55:33']),
            new Entity(['id' => 2, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Proin sed magna accumsan, mattis dolor at, commodo nisl.', 'created_at' => '2012-04-06 23:55:33', 'deleted_at' => null]),
            new Entity(['id' => 3, 'topic_id' => 1, 'active' => 1, 'deleted' => 0, 'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.', 'created_at' => '2012-07-29 11:36:12', 'deleted_at' => null]),
            new Entity(['id' => 4, 'topic_id' => 1, 'active' => 0, 'deleted' => 0, 'content' => 'Vestibulum dapibus nunc quis erat placerat accumsan.', 'created_at' => '2012-11-30 22:42:22', 'deleted_at' => null]),
            new Entity(['id' => 5, 'topic_id' => 1, 'active' => 1, 'deleted' => 1, 'content' => 'Nullam congue dolor sed luctus pulvinar.', 'created_at' => '2013-02-26 11:44:33', 'deleted_at' => '2013-11-06 22:13:27']),
            new Entity(['id' => 6, 'topic_id' => 2, 'active' => 1, 'deleted' => 1, 'content' => 'Suspendisse faucibus lacus eget ullamcorper dictum.', 'created_at' => '2013-06-18 03:25:03', 'deleted_at' => '2013-08-08 02:03:11']),
            new Entity(['id' => 7, 'topic_id' => 2, 'active' => 0, 'deleted' => 1, 'content' => 'Quisque dui nulla, semper nec sagittis quis.', 'created_at' => '2013-08-08 02:03:11', 'deleted_at' => null])
        ], $this->object->select()->where('deleted', [0, 1])->fetchAll());

        $this->object->getBehavior('SoftDelete')->config->useFlag = false;

        $this->assertEquals([
            new Entity(['id' => 5, 'topic_id' => 1, 'active' => 1, 'deleted' => 1, 'content' => 'Nullam congue dolor sed luctus pulvinar.', 'created_at' => '2013-02-26 11:44:33', 'deleted_at' => '2013-11-06 22:13:27']),
            new Entity(['id' => 6, 'topic_id' => 2, 'active' => 1, 'deleted' => 1, 'content' => 'Suspendisse faucibus lacus eget ullamcorper dictum.', 'created_at' => '2013-06-18 03:25:03', 'deleted_at' => '2013-08-08 02:03:11']),
        ], $this->object->select()->where('deleted_at', '>', '2013-01-01 00:00:00')->fetchAll());
    }

    /**
     * Test that a soft delete occurs when a delete query is called.
     */
    public function testDeleteEvent() {
        $this->loadFixtures('Posts');

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
        $this->assertEquals([], $this->object->read(3));

        // Fetch it without callbacks
        $this->assertEquals(new Entity([
            'id' => 3,
            'topic_id' => 1,
            'active' => 1,
            'deleted' => 1,
            'content' => 'Nullam vel pulvinar lorem. Ut id egestas justo.',
            'created_at' => '2012-07-29 11:36:12',
            'deleted_at' => date('Y-m-d H:i:s', $time)
        ]), $this->object->read(3, ['preCallback' => false]));
    }

}