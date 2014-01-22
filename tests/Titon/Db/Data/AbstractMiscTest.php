<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Data;

use Titon\Db\Entity;
use Titon\Db\Query;
use Titon\Db\Query\SubQuery;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for misc database functionality.
 */
class AbstractMiscTest extends TestCase {

    /**
     * Test table creation and deletion.
     */
    public function testCreateDropTable() {
        $user = new User();

        $sql = sprintf("SELECT COUNT(table_name) FROM information_schema.tables WHERE table_schema = 'titon_test' AND table_name = '%s';", $user->getTable());

        $this->assertEquals(0, $user->getDriver()->query($sql)->count());

        $user->createTable();

        $this->assertEquals(1, $user->getDriver()->query($sql)->count());

        $user->query(Query::DROP_TABLE)->save();

        $this->assertEquals(0, $user->getDriver()->query($sql)->count());
    }

    /**
     * Test table truncation.
     */
    public function testTruncateTable() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertEquals(5, $user->select()->count());

        $user->query(Query::TRUNCATE)->save();

        $this->assertEquals(0, $user->select()->count());
    }

    /**
     * Test that all queries from the transaction run.
     */
    public function testTransactions() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new User();

        $this->assertEquals(new Entity([
            'id' => 1,
            'country_id' => 1,
            'username' => 'miles',
            'password' => '1Z5895jf72yL77h',
            'email' => 'miles@email.com',
            'firstName' => 'Miles',
            'lastName' => 'Johnson',
            'age' => 25,
            'created' => '1988-02-26 21:22:34',
            'modified' => null,
            'Profile' => function() {} // lazy-loaded
        ]), $user->select()->with('Profile')->where('id', 1)->fetch());

        // Update user and profile
        $time = time();

        $this->assertEquals(1, $user->update(1, [
            'modified' => $time,
            'Profile' => [
                'id' => 4,
                'lastLogin' => $time
            ]
        ]));

        // Trigger lazy-loaded queries
        $result = $user->select()->with('Profile')->where('id', 1)->fetch();
        $result->Profile;

        $this->assertEquals(new Entity([
            'id' => 1,
            'country_id' => 1,
            'username' => 'miles',
            'password' => '1Z5895jf72yL77h',
            'email' => 'miles@email.com',
            'firstName' => 'Miles',
            'lastName' => 'Johnson',
            'age' => 25,
            'created' => '1988-02-26 21:22:34',
            'modified' => date('Y-m-d H:i:s', $time),
            'Profile' => new Entity([
                'id' => 4,
                'user_id' => 1,
                'lastLogin' => date('Y-m-d H:i:s', $time),
                'currentLogin' => '2013-06-06 19:11:03'
            ])
        ]), $result);
    }

    /**
     * Test that changes dont persist if transaction fails.
     */
    public function testTransactionFailure() {
        $this->loadFixtures(['Users', 'Profiles']);

        $user = new User();
        $data = new Entity([
            'id' => 1,
            'country_id' => 1,
            'username' => 'miles',
            'password' => '1Z5895jf72yL77h',
            'email' => 'miles@email.com',
            'firstName' => 'Miles',
            'lastName' => 'Johnson',
            'age' => 25,
            'created' => '1988-02-26 21:22:34',
            'modified' => null,
            'Profile' => new Entity([
                'id' => 4,
                'user_id' => 1,
                'lastLogin' => '2012-02-15 21:22:34',
                'currentLogin' => '2013-06-06 19:11:03'
            ])
        ]);

        // Trigger lazy-loaded queries
        $result = $user->select()->with('Profile')->where('id', 1)->fetch();
        $result->Profile;

        $this->assertEquals($data, $result);

        // Update user and profile
        $time = time();

        try {
            $this->assertFalse($user->update(1, [
                'username' => 'batman',
                'modified' => $time,
                'Profile' => [
                    'id' => 4,
                    'lastLogin' => $time
                ]
            ]));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // Trigger lazy-loaded queries
        $result = $user->select()->with('Profile')->where('id', 1)->fetch();
        $result->Profile;

        $this->assertEquals($data, $result);
    }

    /**
     * Test that sub-queries return results.
     */
    public function testSubQueries() {
        $this->loadFixtures(['Users', 'Profiles', 'Countries']);

        $user = new User();

        // ANY filter
        $query = $user->select('id', 'country_id', 'username');
        $query->where('country_id', '=', $query->subQuery('id')->from('countries')->withFilter(SubQuery::ANY))->orderBy('id', 'asc');

        $this->assertEquals([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => 3, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => 2, 'username' => 'superman']),
            new Entity(['id' => 4, 'country_id' => 5, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 4, 'username' => 'wolverine']),
        ], $query->fetchAll());

        // Single record
        $query = $user->select('id', 'country_id', 'username');
        $query->where('country_id', '=', $query->subQuery('id')->from('countries')->where('iso', 'USA'));

        $this->assertEquals([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles'])
        ], $query->fetchAll());
    }

}