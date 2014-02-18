<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query\Result;

use Titon\Db\Entity;
use Titon\Db\EntityCollection;
use Titon\Db\Query;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Query\Result\PdoResult.
 *
 * @property \Titon\Db\Repository $object
 */
class PdoResultTest extends TestCase {

    /**
     * Create a result.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new User();

        $this->loadFixtures('Users');
    }

    /**
     * Test statement is generated when cast to string.
     */
    public function testToString() {
        $result = $this->object->getDriver()->executeQuery($this->object->select()->where('id', 1));

        $this->assertRegExp('/\[SQL\] SELECT \* FROM `users` WHERE `id` = 1; \[TIME\] [0-9\.,]+ \[COUNT\] [0-9]+ \[STATE\] (Executed|Prepared)/i', (string) $result);
    }

    /**
     * Test a count of records is returned.
     */
    public function testCount() {
        $this->assertEquals(5, $this->object->select()->count());
    }

    /**
     * Test a single result is returned.
     */
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

    /**
     * Test all results are returned.
     */
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

    /**
     * Test statement params are parsed in.
     */
    public function testGetStatement() {
        $stmt = $this->object->getDriver()->executeQuery($this->object->select('id', 'username')->where('id', 5));

        $this->assertRegExp('/SELECT (`|\")?id(`|\")?, (`|\")?username(`|\")? FROM (`|\")?users(`|\")? WHERE (`|\")?id(`|\")? = 5;/', $stmt->getStatement());
    }

    /**
     * Test row count is returned for deletes.
     */
    public function testSave() {
        $this->assertEquals(5, $this->object->query(Query::DELETE)->save());
    }

}