<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Data;

use Titon\Db\Entity;
use Titon\Test\Stub\Table\UserDeleteCallbacks;
use Titon\Test\Stub\Table\UserFindCallbacks;
use Titon\Test\Stub\Table\UserSaveCallbacks;
use Titon\Test\TestCase;

/**
 * Test class for table callbacks.
 */
class AbstractCallbackTest extends TestCase {

    /**
     * Test Table::preDelete() callbacks.
     */
    public function testPreDelete() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new UserDeleteCallbacks();

        // Do not delete by exiting early in callback
        $this->assertEquals(0, $user->delete(1));

        // Disable cascading through callback
        $this->assertTrue($user->exists(3));
        $this->assertTrue($user->Profile->exists(2));

        $this->assertEquals(1, $user->delete(3));

        $this->assertFalse($user->exists(3));
        $this->assertTrue($user->Profile->exists(2));

        // Allow deletion + cascading
        $this->assertTrue($user->exists(5));
        $this->assertTrue($user->Profile->exists(3));

        $this->assertEquals(1, $user->delete(5));

        $this->assertFalse($user->exists(5));
        $this->assertFalse($user->Profile->exists(3));
    }

    /**
     * Test Table::postDelete() callbacks.
     */
    public function testPostDelete() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new UserDeleteCallbacks();

        // postDelete wont be called because delete failed
        $this->assertEquals(0, $user->delete(1));
        $this->assertEquals([], $user->data);

        // Data will be set because delete was successful
        $this->assertEquals(1, $user->delete(5));
        $this->assertEquals(['id' => 5], $user->data);
    }

    /**
     * Test Table::preSave() callbacks.
     */
    public function testPreSave() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new UserSaveCallbacks();

        // Wont save because callback exited early
        $this->assertEquals(0, $user->update(1, ['username' => 'foo']));

        // Create
        $this->assertEquals(6, $user->create(['username' => 'foo']));
        $this->assertEquals(new Entity([
            'id' => 6,
            'username' => 'foo',
            'firstName' => 'CREATE'
        ]), $user->select('id', 'username', 'firstName')->where('id', 6)->fetch());

        // Update
        $this->assertEquals(1, $user->update(5, ['username' => 'bar']));
        $this->assertEquals(new Entity([
            'id' => 5,
            'username' => 'bar',
            'firstName' => 'UPDATE'
        ]), $user->select('id', 'username', 'firstName')->where('id', 5)->fetch());
    }

    /**
     * Test Table::postSave() callbacks.
     */
    public function testPostSave() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new UserSaveCallbacks();

        // postSave wont be called because save failed
        $this->assertEquals(0, $user->update(1, ['username' => 'foo']));
        $this->assertEquals([], $user->data);

        // Create
        $this->assertEquals(6, $user->create(['username' => 'foo']));
        $this->assertEquals(['id' => 6, 'created' => true], $user->data);

        // Update
        $this->assertEquals(1, $user->update(5, ['username' => 'bar']));
        $this->assertEquals(['id' => 5, 'created' => false], $user->data);
    }

    /**
     * Test Table::preFind() callbacks.
     */
    public function testPreFind() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new UserFindCallbacks();

        // Exit early for list fetches
        $this->assertEquals([], $user->select()->fetchList());

        // Return custom data for fetch
        $this->assertEquals(new Entity(['custom' => 'data']), $user->select()->fetch());

        // Modify fields for fetch all
        $this->assertEquals([
            new Entity(['id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'username' => 'superman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'username' => 'wolverine']),
        ], $user->select('id', 'username', 'firstName', 'lastName')->orderBy('id', 'asc')->fetchAll());
    }

    /**
     * Test Table::postFind() callbacks.
     */
    public function testPostFind() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new UserFindCallbacks();
        $user->testApply = true;

        // Modify results after fetch
        $this->assertEquals([
            new Entity(['id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'username' => 'batman', 'foo' => 'bar']),
            new Entity(['id' => 3, 'username' => 'superman']),
            new Entity(['id' => 4, 'username' => 'spiderman', 'foo' => 'bar']),
            new Entity(['id' => 5, 'username' => 'wolverine']),
        ], $user->select('id', 'username', 'firstName', 'lastName')->orderBy('id', 'asc')->fetchAll());
    }

}