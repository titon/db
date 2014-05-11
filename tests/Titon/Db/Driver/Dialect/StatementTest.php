<?php
namespace Titon\Db\Driver\Dialect;

use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Driver\Dialect\Statement $object
 */
class StatementTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new Statement('SELECT {fields} FROM {table} {joins} {where} {groupBy} {having} {compounds} {orderBy} {limit}');
    }

    public function testGetStatement() {
        $this->assertEquals('SELECT {fields} FROM {table} {joins} {where} {groupBy} {having} {compounds} {orderBy} {limit}', $this->object->getStatement());
    }

    public function testGetParams() {
        $this->assertEquals(['fields', 'table', 'joins', 'where', 'groupBy', 'having', 'compounds', 'orderBy', 'limit'], array_keys($this->object->getParams()));
    }

    public function testRender() {
        $this->assertEquals('SELECT  FROM;', $this->object->render());
        $this->assertEquals('SELECT * FROM table;', $this->object->render(['table' => 'table', 'fields' => '*']));
    }

}