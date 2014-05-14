<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Data;

use Titon\Db\Entity;
use Titon\Db\EntityCollection;
use Titon\Db\Query;
use Titon\Test\Stub\Repository\Book;
use Titon\Test\Stub\Repository\Series;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;

/**
 * Test class for database upserting.
 */
class AbstractUpsertTest extends TestCase {

    /**
     * Test that the record is created.
     */
    public function testUpsertNoId() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertFalse($user->exists(6));

        $this->assertEquals(6, $user->upsert([
            'username' => 'ironman'
        ]));

        $this->assertTrue($user->exists(6));
    }

    /**
     * Test that the record is updated if the ID exists in the data.
     */
    public function testUpsertWithId() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertFalse($user->exists(6));

        $this->assertEquals(1, $user->upsert([
            'id' => 1,
            'username' => 'ironman'
        ]));

        $this->assertFalse($user->exists(6));
    }

    /**
     * Test that the record is updated if the ID is passed as an argument.
     */
    public function testUpsertWithIdArg() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertFalse($user->exists(6));

        $this->assertEquals(1, $user->upsert([
            'username' => 'ironman'
        ], 1));

        $this->assertFalse($user->exists(6));
    }

    /**
     * Test that the record is created if the ID doesn't exist.
     */
    public function testUpsertWithFakeId() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertFalse($user->exists(6));

        $this->assertEquals(6, $user->upsert([
            'id' => 10,
            'username' => 'ironman'
        ]));

        $this->assertTrue($user->exists(6));
    }

    /**
     * Test that the record is created if the ID argument doesn't exist.
     */
    public function testUpsertWithFakeIdArg() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertFalse($user->exists(6));

        $this->assertEquals(6, $user->upsert([
            'username' => 'ironman'
        ], 10));

        $this->assertTrue($user->exists(6));
    }

}