<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Data;

use DateTime;
use Titon\Db\Entity;
use Titon\Db\Query;
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