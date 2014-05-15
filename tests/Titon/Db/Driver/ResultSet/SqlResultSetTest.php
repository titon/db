<?php
namespace Titon\Db\Driver\ResultSet;

use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Driver\ResultSet\SqlResultSet
 */
class SqlResultSetTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new SqlResultSet('SELECT * FROM `users` LIMIT 25');
    }

    public function testCount() {
        $this->assertEquals(0, $this->object->count());
    }

    public function testFind() {
        $this->assertEquals([], $this->object->find());
    }

    public function testGetStatement() {
        $this->assertEquals('SELECT * FROM `users` LIMIT 25', $this->object->getStatement());
    }

    public function testSave() {
        $this->assertTrue($this->object->save());
    }

}