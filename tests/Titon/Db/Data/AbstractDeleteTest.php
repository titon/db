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
use Titon\Test\Stub\Repository\BookGenre;
use Titon\Test\Stub\Repository\Series;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for database record deleting.
 */
class AbstractDeleteTest extends TestCase {

    /**
     * Test single record deletion.
     */
    public function testDelete() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertTrue($user->exists(1));
        $this->assertSame(1, $user->delete(1));
        $this->assertFalse($user->exists(1));
    }

    /**
     * Test delete with where conditions.
     */
    public function testDeleteConditions() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertSame(5, $user->select()->count());
        $this->assertSame(3, $user->query(Query::DELETE)->where('age', '>', 30)->save());
        $this->assertSame(2, $user->select()->count());
    }

    /**
     * Test delete with ordering.
     */
    public function testDeleteOrdering() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'username' => 'superman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $user->select('id', 'username')->orderBy('id', 'asc')->all());

        $this->assertSame(3, $user->query(Query::DELETE)->orderBy('age', 'asc')->limit(3)->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $user->select('id', 'username')->orderBy('id', 'asc')->all());
    }

    /**
     * Test delete with a limit applied.
     */
    public function testDeleteLimit() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'username' => 'superman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $user->select('id', 'username')->orderBy('id', 'asc')->all());

        $this->assertSame(2, $user->query(Query::DELETE)->limit(2)->save());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 3, 'username' => 'superman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $user->select('id', 'username')->orderBy('id', 'asc')->all());
    }

    /**
     * Test multiple deletion through conditions.
     */
    public function testDeleteMany() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new User();

        // Throws exceptions if no conditions applied
        try {
            $user->deleteMany(function() {
                // Nothing
            });
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $this->assertEquals(3, $user->deleteMany(function(Query $query) {
            $query->where('age', '>', 30);
        }));
    }

}