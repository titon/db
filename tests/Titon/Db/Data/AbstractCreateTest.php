<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Data;

use Titon\Db\Query;
use Titon\Test\Stub\Repository\Book;
use Titon\Test\Stub\Repository\Series;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for database inserting.
 */
class AbstractCreateTest extends TestCase {

    /**
     * Test basic row inserting. Response should be the new ID.
     */
    public function testCreate() {
        $this->loadFixtures('Users');

        $user = new User();
        $data = [
            'country_id' => 1,
            'username' => 'ironman',
            'firstName' => 'Tony',
            'lastName' => 'Stark',
            'password' => '7NAks9193KAkjs1',
            'email' => 'ironman@email.com',
            'age' => 38
        ];

        $this->assertEquals(6, $user->create($data));

        $this->assertEquals([
            'id' => 6,
            'country_id' => 1,
            'username' => 'ironman',
            'firstName' => 'Tony',
            'lastName' => 'Stark',
            'password' => '7NAks9193KAkjs1',
            'email' => 'ironman@email.com',
            'age' => 38,
            'created' => '',
            'modified' => ''
        ], $user->data);

        // Trying again should throw a unique error on username
        unset($data['id']);

        try {
            $this->assertEquals(7, $user->create($data));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test that create fails with empty data.
     */
    public function testCreateEmptyData() {
        $this->loadFixtures('Users');

        $user = new User();

        try {
            $this->assertSame(0, $user->create([]));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test inserting multiple records with a single statement.
     */
    public function testCreateMany() {
        // Dont load fixtures

        $user = new User();
        $user->createTable();

        $this->assertEquals(0, $user->select()->count());

        $this->assertEquals(5, $user->createMany([
            ['country_id' => 1, 'username' => 'miles', 'firstName' => 'Miles', 'lastName' => 'Johnson', 'password' => '1Z5895jf72yL77h', 'email' => 'miles@email.com', 'age' => 25, 'created' => '1988-02-26 21:22:34'],
            ['country_id' => 3, 'username' => 'batman', 'firstName' => 'Bruce', 'lastName' => 'Wayne', 'created' => '1960-05-11 21:22:34'],
            ['country_id' => 2, 'username' => 'superman', 'email' => 'superman@email.com', 'age' => 33, 'created' => '1970-09-18 21:22:34'],
            ['country_id' => 5, 'username' => 'spiderman', 'firstName' => 'Peter', 'lastName' => 'Parker', 'password' => '1Z5895jf72yL77h', 'email' => 'spiderman@email.com', 'age' => 22, 'created' => '1990-01-05 21:22:34'],
            ['country_id' => 4, 'username' => 'wolverine', 'password' => '1Z5895jf72yL77h', 'email' => 'wolverine@email.com'],
        ]));

        $this->assertEquals(5, $user->select()->count());

        $user->query(Query::DROP_TABLE)->save();
    }

}