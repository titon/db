<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Data;

use DateTime;
use Titon\Db\Entity;
use Titon\Db\EntityCollection;
use Titon\Db\Query;
use Titon\Db\Query\SubQuery;
use Titon\Test\Stub\Repository\Stat;
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

        $this->assertEquals(0, $user->getDriver()->executeQuery($sql)->count());

        $user->createTable();

        $this->assertEquals(1, $user->getDriver()->executeQuery($sql)->count());

        $user->query(Query::DROP_TABLE)->save();

        $this->assertEquals(0, $user->getDriver()->executeQuery($sql)->count());
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
        ]), $user->select()->with('Profile')->where('id', 1)->first());

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
        $result = $user->select()->with('Profile')->where('id', 1)->first();
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
        $result = $user->select()->with('Profile')->where('id', 1)->first();
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
        $result = $user->select()->with('Profile')->where('id', 1)->first();
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

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => 3, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => 2, 'username' => 'superman']),
            new Entity(['id' => 4, 'country_id' => 5, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 4, 'username' => 'wolverine']),
        ]), $query->all());

        // Single record
        $query = $user->select('id', 'country_id', 'username');
        $query->where('country_id', '=', $query->subQuery('id')->from('countries')->where('iso', 'USA'));

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles'])
        ]), $query->all());
    }

    /**
     * Test type casting for insert fields.
     */
    public function testInsertFieldTypeCasting() {
        $this->loadFixtures('Stats');

        $stat = new Stat();
        $user = new User();
        $time = time();
        $date = date('Y-m-d H:i:s', $time);
        $driver = $stat->getDriver();

        // int
        $query = $driver->executeQuery($stat->query(Query::INSERT)->fields(['health' => '100', 'energy' => 200]));
        $this->assertRegExp("/^INSERT INTO `stats` \(`health`, `energy`\) VALUES \(100, 200\);$/i", $query->getStatement());

        // string
        $query = $driver->executeQuery($stat->query(Query::INSERT)->fields(['name' => 12345]));
        $this->assertRegExp("/^INSERT INTO `stats` \(`name`\) VALUES \('12345'\);$/i", $query->getStatement());

        // float, double, decimal (they are strings in PDO)
        $query = $driver->executeQuery($stat->query(Query::INSERT)->fields(['damage' => '123.45', 'defense' => 456.78, 'range' => 999.00]));
        $this->assertRegExp("/^INSERT INTO `stats` \(`damage`, `defense`, `range`\) VALUES \('123.45', '456.78', '999'\);$/i", $query->getStatement());

        // bool
        $query = $driver->executeQuery($stat->query(Query::INSERT)->fields(['isMelee' => 'true']));
        $this->assertRegExp("/^INSERT INTO `stats` \(`isMelee`\) VALUES \(1\);$/i", $query->getStatement());

        $query = $driver->executeQuery($stat->query(Query::INSERT)->fields(['isMelee' => false]));
        $this->assertRegExp("/^INSERT INTO `stats` \(`isMelee`\) VALUES \(0\);$/i", $query->getStatement());

        // datetime
        $query = $driver->executeQuery($user->query(Query::INSERT)->fields(['created' => $time]));
        $this->assertRegExp("/^INSERT INTO `users` \(`created`\) VALUES \('" . $date . "'\);$/i", $query->getStatement());

        $query = $driver->executeQuery($user->query(Query::INSERT)->fields(['created' => new DateTime($date)]));
        $this->assertRegExp("/^INSERT INTO `users` \(`created`\) VALUES \('" . $date . "'\);$/i", $query->getStatement());

        $query = $driver->executeQuery($user->query(Query::INSERT)->fields(['created' => $date]));
        $this->assertRegExp("/^INSERT INTO `users` \(`created`\) VALUES \('" . $date . "'\);$/i", $query->getStatement());

        // null
        $query = $driver->executeQuery($user->query(Query::INSERT)->fields(['created' => null]));
        $this->assertRegExp("/^INSERT INTO `users` \(`created`\) VALUES \(NULL\);$/i", $query->getStatement());
    }

    /**
     * Test type casting for update fields.
     */
    public function testUpdateFieldTypeCasting() {
        $this->loadFixtures('Stats');

        $stat = new Stat();
        $user = new User();
        $time = time();
        $date = date('Y-m-d H:i:s', $time);
        $driver = $stat->getDriver();

        // int
        $query = $driver->executeQuery($stat->query(Query::UPDATE)->fields(['health' => '100', 'energy' => 200]));
        $this->assertRegExp("/^UPDATE `stats` SET `health` = 100, `energy` = 200;$/i", $query->getStatement());

        // string
        $query = $driver->executeQuery($stat->query(Query::UPDATE)->fields(['name' => 12345]));
        $this->assertRegExp("/^UPDATE `stats` SET `name` = '12345';$/i", $query->getStatement());

        // float, double, decimal (they are strings in PDO)
        $query = $driver->executeQuery($stat->query(Query::UPDATE)->fields(['damage' => '123.45', 'defense' => 456.78, 'range' => 999.00]));
        $this->assertRegExp("/^UPDATE `stats` SET `damage` = '123.45', `defense` = '456.78', `range` = '999';$/i", $query->getStatement());

        // bool
        $query = $driver->executeQuery($stat->query(Query::UPDATE)->fields(['isMelee' => 'true']));
        $this->assertRegExp("/^UPDATE `stats` SET `isMelee` = 1;$/i", $query->getStatement());

        $query = $driver->executeQuery($stat->query(Query::UPDATE)->fields(['isMelee' => false]));
        $this->assertRegExp("/^UPDATE `stats` SET `isMelee` = 0;$/i", $query->getStatement());

        // datetime
        $query = $driver->executeQuery($user->query(Query::UPDATE)->fields(['created' => $time]));
        $this->assertRegExp("/^UPDATE `users` SET `created` = '" . $date . "';$/i", $query->getStatement());

        $query = $driver->executeQuery($user->query(Query::UPDATE)->fields(['created' => new DateTime($date)]));
        $this->assertRegExp("/^UPDATE `users` SET `created` = '" . $date . "';$/i", $query->getStatement());

        $query = $driver->executeQuery($user->query(Query::UPDATE)->fields(['created' => $date]));
        $this->assertRegExp("/^UPDATE `users` SET `created` = '" . $date . "';$/i", $query->getStatement());

        // null
        $query = $driver->executeQuery($user->query(Query::UPDATE)->fields(['created' => null]));
        $this->assertRegExp("/^UPDATE `users` SET `created` = NULL;$/i", $query->getStatement());
    }

    /**
     * Test type casting in where clauses.
     */
    public function testWhereTypeCasting() {
        $this->loadFixtures('Stats');

        $stat = new Stat();
        $user = new User();
        $time = time();
        $date = date('Y-m-d H:i:s', $time);
        $driver = $stat->getDriver();

        // int
        $query = $driver->executeQuery($stat->select()->where('health', '>', '100'));
        $this->assertRegExp("/^SELECT \* FROM `stats` WHERE `health` > 100;$/i", $query->getStatement());

        $query = $driver->executeQuery($stat->select()->where('id', [1, '2', 3]));
        $this->assertRegExp("/^SELECT \* FROM `stats` WHERE `id` IN \(1, 2, 3\);$/i", $query->getStatement());

        // string
        $query = $driver->executeQuery($stat->select()->where('name', '!=', 123.45));
        $this->assertRegExp("/^SELECT \* FROM `stats` WHERE `name` != '123.45';$/i", $query->getStatement());

        // float (they are strings in PDO)
        $query = $driver->executeQuery($stat->select()->where('damage', '<', 55.25));
        $this->assertRegExp("/^SELECT \* FROM `stats` WHERE `damage` < '55.25';$/i", $query->getStatement());

        // bool
        $query = $driver->executeQuery($stat->select()->where('isMelee', true));
        $this->assertRegExp("/^SELECT \* FROM `stats` WHERE `isMelee` = 1;$/i", $query->getStatement());

        $query = $driver->executeQuery($stat->select()->where('isMelee', '0'));
        $this->assertRegExp("/^SELECT \* FROM `stats` WHERE `isMelee` = 0;$/i", $query->getStatement());

        // datetime
        $query = $driver->executeQuery($user->select()->where('created', '>', $time));
        $this->assertRegExp("/^SELECT \* FROM `users` WHERE `created` > '" . $date . "';$/i", $query->getStatement());

        $query = $driver->executeQuery($user->select()->where('created', '<=', new DateTime($date)));
        $this->assertRegExp("/^SELECT \* FROM `users` WHERE `created` <= '" . $date . "';$/i", $query->getStatement());

        $query = $driver->executeQuery($user->select()->where('created', '!=', $date));
        $this->assertRegExp("/^SELECT \* FROM `users` WHERE `created` != '" . $date . "';$/i", $query->getStatement());

        // null
        $query = $driver->executeQuery($user->select()->where('created', null));
        $this->assertRegExp("/^SELECT \* FROM `users` WHERE `created` IS NULL;$/i", $query->getStatement());

        $query = $driver->executeQuery($user->select()->where('created', '!=', null));
        $this->assertRegExp("/^SELECT \* FROM `users` WHERE `created` IS NOT NULL;$/i", $query->getStatement());
    }

}