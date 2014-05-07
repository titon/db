<?php
namespace Titon\Db\Finder;

use Titon\Db\Entity;
use Titon\Db\EntityCollection;
use Titon\Db\Query;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Finder\AllFinder $object
 */
class AllFinderTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new AllFinder();
    }

    public function testAfter() {
        $data = [
            new Entity(['a' => 1]),
            new Entity(['b' => 2]),
            new Entity(['c' => 3])
        ];

        $this->assertEquals(new EntityCollection($data), $this->object->after($data));
    }

    public function testBefore() {
        $query = new Query(Query::SELECT);

        $this->assertEquals($query, $this->object->before($query));
    }

    public function testNoResults() {
        $this->assertEquals(new EntityCollection(), $this->object->noResults());
    }

}