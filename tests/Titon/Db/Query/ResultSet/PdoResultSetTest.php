<?php
namespace Titon\Db\Query\ResultSet;

use Titon\Db\Entity;
use Titon\Db\EntityCollection;
use Titon\Db\Query;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Repository $object
 */
class PdoResultSetTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new User();
        $this->loadFixtures('Users');
    }

    public function testToString() {
        $result = $this->object->getDriver()->executeQuery($this->object->select()->where('id', 1)->where('username', 'like', '%titon%'));

        $this->assertRegExp('/\[SQL\] SELECT \* FROM (`|\")?users(`|\")? WHERE (`|\")?id(`|\")? = 1 AND (`|\")?username(`|\")? LIKE \'%titon%\'; \[TIME\] [0-9\.,]+ \[COUNT\] [0-9]+ \[STATE\] (Executed|Prepared)/i', (string) $result);

        $result->close();
    }

    public function testCount() {
        $this->assertEquals(5, $this->object->select()->count());
    }

    public function testExecute() {
        $result = $this->object->getDriver()->executeQuery($this->object->select()->where('id', 1));

        $this->assertFalse($result->hasExecuted());

        $result->execute();
        $this->assertTrue($result->hasExecuted());

        // Assert it doesn't run twice
        $result->execute();
        $this->assertTrue($result->hasExecuted());

        $result->close();
    }

    public function testFetch() {
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
            'modified' => null
        ]), $this->object->select()->first());
    }

    public function testFetchAll() {
        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'country_id' => 1,
                'username' => 'miles',
                'password' => '1Z5895jf72yL77h',
                'email' => 'miles@email.com',
                'firstName' => 'Miles',
                'lastName' => 'Johnson',
                'age' => 25,
                'created' => '1988-02-26 21:22:34',
                'modified' => null
            ]),
            new Entity([
                'id' => 2,
                'country_id' => 3,
                'username' => 'batman',
                'password' => '1Z5895jf72yL77h',
                'email' => 'batman@email.com',
                'firstName' => 'Bruce',
                'lastName' => 'Wayne',
                'age' => 35,
                'created' => '1960-05-11 21:22:34',
                'modified' => null
            ]),
            new Entity([
                'id' => 3,
                'country_id' => 2,
                'username' => 'superman',
                'password' => '1Z5895jf72yL77h',
                'email' => 'superman@email.com',
                'firstName' => 'Clark',
                'lastName' => 'Kent',
                'age' => 33,
                'created' => '1970-09-18 21:22:34',
                'modified' => null
            ]),
            new Entity([
                'id' => 4,
                'country_id' => 5,
                'username' => 'spiderman',
                'password' => '1Z5895jf72yL77h',
                'email' => 'spiderman@email.com',
                'firstName' => 'Peter',
                'lastName' => 'Parker',
                'age' => 22,
                'created' => '1990-01-05 21:22:34',
                'modified' => null
            ]),
            new Entity([
                'id' => 5,
                'country_id' => 4,
                'username' => 'wolverine',
                'password' => '1Z5895jf72yL77h',
                'email' => 'wolverine@email.com',
                'firstName' => 'Logan',
                'lastName' => null,
                'age' => 355,
                'created' => '2000-11-30 21:22:34',
                'modified' => null
            ])
        ]), $this->object->select()->all());
    }

    public function testGetStatement() {
        $stmt = $this->object->getDriver()->executeQuery($this->object->select('id', 'username')->where('id', 5));

        $this->assertRegExp('/SELECT (`|\")?id(`|\")?, (`|\")?username(`|\")? FROM (`|\")?users(`|\")? WHERE (`|\")?id(`|\")? = 5;/', $stmt->getStatement());
    }

    public function testSave() {
        $this->assertEquals(5, $this->object->query(Query::DELETE)->save());
    }

}