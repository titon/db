<?php
namespace Titon\Db\Query;

use Exception;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Query\SubQuery
 */
class SubQueryTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new SubQuery(SubQuery::SELECT, new User());
    }

    public function testAsAlias() {
        $this->assertEquals(null, $this->object->getAlias());
        $this->object->asAlias('column');
        $this->assertEquals('column', $this->object->getAlias());
    }

    public function testWithFilter() {
        $this->assertEquals(null, $this->object->getFilter());
        $this->object->withFilter(SubQuery::ALL);
        $this->assertEquals('all', $this->object->getFilter());
    }

    public function testWithFilterInvalidFilter() {
        try {
            $this->object->withFilter('foobar');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

}