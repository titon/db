<?php
namespace Titon\Db\Finder;

use Titon\Db\Entity;
use Titon\Db\Query;
use Titon\Test\TestCase;
use \Exception;

/**
 * @property \Titon\Db\Finder\ListFinder $object
 */
class ListFinderTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new ListFinder();
    }

    public function testAfter() {
        $data = [
            new Entity(['id' => 1, 'name' => 'a']),
            new Entity(['id' => 2, 'name' => 'b']),
            new Entity(['id' => 3, 'name' => 'c'])
        ];

        $this->assertEquals([
            1 => 'a',
            2 => 'b',
            3 => 'c'
        ], $this->object->after($data, ['key' => 'id', 'value' => 'name']));
    }

    public function testAfterNonEntity() {
        $data = [
            ['id' => 1, 'name' => 'a'],
            ['id' => 2, 'name' => 'b'],
            ['id' => 3, 'name' => 'c']
        ];

        $this->assertEquals([
            1 => 'a',
            2 => 'b',
            3 => 'c'
        ], $this->object->after($data, ['key' => 'id', 'value' => 'name']));
    }

    public function testAfterNestedData() {
        $data = [
            new Entity(['id' => 1, 'name' => 'a', 'profile' => new Entity(['name' => 'aa'])]),
            new Entity(['id' => 2, 'name' => 'b', 'profile' => new Entity(['name' => 'bb'])]),
            new Entity(['id' => 3, 'name' => 'c', 'profile' => new Entity(['name' => 'cc'])])
        ];

        $this->assertEquals([
            1 => 'aa',
            2 => 'bb',
            3 => 'cc'
        ], $this->object->after($data, ['key' => 'id', 'value' => 'profile.name']));
    }

    public function testAfterMissingOptions() {
        try {
            $this->object->after([]);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testBefore() {
        $query = new Query(Query::SELECT);

        $this->assertEquals($query, $this->object->before($query));
    }

    public function testNoResults() {
        $this->assertEquals([], $this->object->noResults());
    }

}