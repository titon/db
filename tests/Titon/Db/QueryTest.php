<?php
namespace Titon\Db;

use Titon\Db\Query;
use Titon\Db\Query\Expr;
use Titon\Db\Query\Join;
use Titon\Db\Query\Predicate;
use Titon\Test\Stub\Repository\Topic;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;
use \Exception;

/**
 * @property \Titon\Db\Query $object
 */
class QueryTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new Query(Query::SELECT, new User());
    }

    public function testAttributes() {
        $this->assertEquals([], $this->object->getAttributes());

        $this->object->attribute('readonly', true);
        $this->assertEquals(['readonly' => true], $this->object->getAttributes());

        $this->object->attribute([
            'readonly' => false,
            'distinct' => true
        ]);
        $this->assertEquals(['readonly' => false, 'distinct' => true], $this->object->getAttributes());
    }

    public function testBindCallback() {
        $this->assertEquals([], $this->object->getFields());

        $this->object->bindCallback(function(Query $query) {
            $query->fields('id', 'created');
        }, null);

        $this->assertEquals(['id', 'created'], $this->object->getFields());
    }

    public function testCache() {
        $this->object->cache('foobar', '+25 minutes');

        $this->assertEquals('foobar', $this->object->getCacheKey());
        $this->assertEquals('+25 minutes', $this->object->getCacheLength());
    }

    public function testCacheFailsNonSelect() {
        $query = new Query(Query::UPDATE);
        $query->cache('foobar', '+25 minutes');

        $this->assertEquals(null, $this->object->getCacheKey());
        $this->assertEquals(null, $this->object->getCacheLength());
    }

    public function testDistinct() {
        $this->assertEquals([], $this->object->getAttributes());
        $this->object->distinct();
        $this->assertEquals(['distinct' => true], $this->object->getAttributes());
    }

    public function testExcepts() {
        $query1 = $this->object->subQuery();
        $this->object->except($query1);

        $this->assertEquals([$query1], $this->object->getCompounds());

        $query2 = $this->object->subQuery();
        $this->object->except($query2, 'all');

        $this->assertEquals([$query1, $query2], $this->object->getCompounds());
        $this->assertEquals(['compound' => 'except', 'flag' => 'all'], $query2->getAttributes());

        $query3 = $this->object->subQuery();
        $this->object->except($query3, 'foobar');

        $this->assertEquals([$query1, $query2, $query3], $this->object->getCompounds());
        $this->assertEquals(['compound' => 'except'], $query3->getAttributes());
    }

    public function testExceptFailsNonSelect() {
        try {
            $this->object->except(new Query(Query::UPDATE));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testExpr() {
        $this->assertInstanceOf('Titon\Db\Query\Expr', Query::expr('column', '+', 5));
    }

    public function testFields() {
        $this->object->fields('id', 'title', 'id');
        $this->assertEquals(['id', 'title'], $this->object->getFields());

        // Merge in
        $this->object->fields(['id', 'created'], true);
        $this->assertEquals(['id', 'title', 'created'], $this->object->getFields());

        // Override
        $this->object->fields(['username', 'created']);
        $this->assertEquals(['username', 'created'], $this->object->getFields());

        // Non-select
        $query = new Query(Query::INSERT, new User());

        $query->fields(['id' => 1, 'title' => 'Titon']);
        $this->assertEquals(['id' => 1, 'title' => 'Titon'], $query->getFields());

        $query->fields(['username' => 'miles'], true);
        $this->assertEquals(['id' => 1, 'title' => 'Titon', 'username' => 'miles'], $query->getFields());
    }

    public function testFrom() {
        $this->object->from('users');
        $this->assertEquals('users', $this->object->getTable());
    }

    public function testFunc() {
        $this->assertInstanceOf('Titon\Db\Query\Func', Query::func('SUBSTRING', ['Foo', 1, 2]));
    }

    public function testGroupBy() {
        $this->object->groupBy('id', 'created');
        $this->assertEquals(['id', 'created'], $this->object->getGroupBy());

        $this->object->groupBy('title');
        $this->assertEquals(['id', 'created', 'title'], $this->object->getGroupBy());

        $this->object->groupBy(['content', 'modified']);
        $this->assertEquals(['id', 'created', 'title', 'content', 'modified'], $this->object->getGroupBy());
    }

    public function testHaving() {
        $expected = ['id=1' => new Expr('id', '=', 1)];

        $this->object->having('id', 1);
        $this->assertEquals($expected, $this->object->getHaving()->getParams());

        $this->assertEquals(Predicate::ALSO, $this->object->getHaving()->getType());

        $expected['titlenotLike%Titon%'] = new Expr('title', 'notLike', '%Titon%');

        $this->object->having(function(Predicate $having) {
            $having->notLike('title', '%Titon%');
        });
        $this->assertEquals($expected, $this->object->getHaving()->getParams());

        try {
            $this->object->orHaving('id', 1);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // Custom operator
        $this->object->having('size', '!=', 15);

        $expected['size!=15'] = new Expr('size', '!=', 15);

        $this->assertEquals($expected, $this->object->getHaving()->getParams());
    }

    public function testIntersects() {
        $query1 = $this->object->subQuery();
        $this->object->intersect($query1);

        $this->assertEquals([$query1], $this->object->getCompounds());

        $query2 = $this->object->subQuery();
        $this->object->intersect($query2, 'all');

        $this->assertEquals([$query1, $query2], $this->object->getCompounds());
        $this->assertEquals(['compound' => 'intersect', 'flag' => 'all'], $query2->getAttributes());

        $query3 = $this->object->subQuery();
        $this->object->intersect($query3, 'foobar');

        $this->assertEquals([$query1, $query2, $query3], $this->object->getCompounds());
        $this->assertEquals(['compound' => 'intersect'], $query3->getAttributes());
    }

    public function testIntersectFailsNonSelect() {
        try {
            $this->object->intersect(new Query(Query::UPDATE));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testLeftJoin() {
        $j1 = new Join(Join::LEFT);
        $j1->from('profiles', 'profiles')->on('User.profile_id', 'profiles.id');

        $this->object->leftJoin('profiles', [], ['profile_id' => 'id']);
        $this->assertEquals($j1, $this->object->getJoins()[0]);
    }

    public function testRightJoin() {
        $j1 = new Join(Join::RIGHT);
        $j1->from('profiles', 'profiles')->on('users.id', 'profiles.id')->fields(['id', 'created']);

        $this->object->rightJoin('profiles', ['id', 'created'], ['users.id' => 'profiles.id']);
        $this->assertEquals($j1, $this->object->getJoins()[0]);
    }

    public function testInnerJoin() {
        $j1 = new Join(Join::INNER);
        $j1->from('profiles', 'Profile')->on('User.id', 'Profile.user_id')->fields('id', 'user_id', 'lastLogin', 'currentLogin');

        $this->object->innerJoin(['profiles', 'Profile'], ['id', 'user_id', 'lastLogin', 'currentLogin'], ['User.id' => 'Profile.user_id']);
        $this->assertEquals($j1, $this->object->getJoins()[0]);
    }

    public function testLimit() {
        $this->object->limit(15);
        $this->assertEquals(15, $this->object->getLimit());
        $this->assertEquals(0, $this->object->getOffset());

        $this->object->limit(25, 50);
        $this->assertEquals(25, $this->object->getLimit());
        $this->assertEquals(50, $this->object->getOffset());
    }

    public function testOffset() {
        $this->assertEquals(0, $this->object->getOffset());
        $this->object->offset(15);
        $this->assertEquals(15, $this->object->getOffset());
    }

    public function testOrderBy() {
        $this->object->orderBy('id', 'asc');
        $this->assertEquals(['id' => 'asc'], $this->object->getOrderBy());

        $this->object->orderBy([
            'id' => 'DESC',
            'created' => 'asc'
        ]);
        $this->assertEquals(['id' => 'desc', 'created' => 'asc'], $this->object->getOrderBy());
    }

    public function testOrderByErrorsInvalidType() {
        try {
            $this->object->orderBy('id', 'ascending');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testOrderByRand() {
        $rand1 = new Query\Func('RAND');
        $this->object->orderBy('RAND');
        $this->assertEquals([$rand1], $this->object->getOrderBy());

        $rand2 = new Query\Func('RAND');
        $this->object->orderBy($rand2);
        $this->assertEquals([$rand1, $rand2], $this->object->getOrderBy());
    }

    public function testOrHaving() {
        $expected = ['id=1' => new Expr('id', '=', 1)];

        $this->object->orHaving('id', 1);
        $this->assertEquals($expected, $this->object->getHaving()->getParams());

        $this->assertEquals(Predicate::EITHER, $this->object->getHaving()->getType());

        $expected['titlenotLike%Titon%'] = new Expr('title', 'notLike', '%Titon%');

        $this->object->orHaving(function(Predicate $having) {
            $having->notLike('title', '%Titon%');
        });
        $this->assertEquals($expected, $this->object->getHaving()->getParams());

        try {
            $this->object->having('id', 1);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // Custom operator
        $this->object->orHaving('size', '>=', 15);

        $expected['size>=15'] = new Expr('size', '>=', 15);

        $this->assertEquals($expected, $this->object->getHaving()->getParams());
    }

    public function testOrWhere() {
        $expected = ['id=152' => new Expr('id', '=', 152)];

        $this->object->orWhere('id', 152);
        $this->assertEquals($expected, $this->object->getWhere()->getParams());

        $this->assertEquals(Predicate::EITHER, $this->object->getWhere()->getType());

        $expected['levelbetween[1,100]'] = new Expr('level', 'between', [1, 100]);

        $this->object->orWhere(function(Predicate $where) {
            $where->between('level', 1, 100);
        });
        $this->assertEquals($expected, $this->object->getWhere()->getParams());

        try {
            $this->object->where('id', 1);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $this->object->orWhere('size', Expr::NOT_IN, [1, 2]);

        $expected['sizenotIn[1,2]'] = new Expr('size', 'notIn', [1, 2]);

        $this->assertEquals($expected, $this->object->getWhere()->getParams());
    }

    public function testUnions() {
        $query1 = $this->object->subQuery();
        $this->object->union($query1);

        $this->assertEquals([$query1], $this->object->getCompounds());

        $query2 = $this->object->subQuery();
        $this->object->union($query2, 'all');

        $this->assertEquals([$query1, $query2], $this->object->getCompounds());
        $this->assertEquals(['compound' => 'union', 'flag' => 'all'], $query2->getAttributes());

        $query3 = $this->object->subQuery();
        $this->object->union($query3, 'distinct');

        $this->assertEquals([$query1, $query2, $query3], $this->object->getCompounds());
        $this->assertEquals(['compound' => 'union', 'flag' => 'distinct'], $query3->getAttributes());

        $query4 = $this->object->subQuery();
        $this->object->union($query4, 'foobar');

        $this->assertEquals([$query1, $query2, $query3, $query4], $this->object->getCompounds());
        $this->assertEquals(['compound' => 'union'], $query4->getAttributes());
    }

    public function testUnionFailsNonSelect() {
        try {
            $this->object->union(new Query(Query::UPDATE));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testWhere() {
        $expected = ['id=152' => new Expr('id', '=', 152)];

        $this->object->where('id', 152);
        $this->assertEquals($expected, $this->object->getWhere()->getParams());

        $this->assertEquals(Predicate::ALSO, $this->object->getWhere()->getType());

        $expected['levelbetween[1,100]'] = new Expr('level', 'between', [1, 100]);

        $this->object->where(function(Predicate $where) {
            $where->between('level', 1, 100);
        });
        $this->assertEquals($expected, $this->object->getWhere()->getParams());

        try {
            $this->object->orWhere('id', 1);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // Custom operator
        $this->object->where('size', '>', 25);

        $expected['size>25'] = new Expr('size', '>', 25);

        $this->assertEquals($expected, $this->object->getWhere()->getParams());
    }

    public function testXorHaving() {
        $expected = ['id=1' => new Expr('id', '=', 1)];

        $this->object->xorHaving('id', 1);
        $this->assertEquals($expected, $this->object->getHaving()->getParams());

        $this->assertEquals(Predicate::MAYBE, $this->object->getHaving()->getType());

        $expected['titlenotLike%Titon%'] = new Expr('title', 'notLike', '%Titon%');

        $this->object->xorHaving(function(Predicate $having) {
            $having->notLike('title', '%Titon%');
        });
        $this->assertEquals($expected, $this->object->getHaving()->getParams());

        try {
            $this->object->having('id', 1);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // Custom operator
        $this->object->xorHaving('size', '>=', 15);

        $expected['size>=15'] = new Expr('size', '>=', 15);

        $this->assertEquals($expected, $this->object->getHaving()->getParams());
    }

    public function testXorWhere() {
        $expected = ['id=152' => new Expr('id', '=', 152)];

        $this->object->xorWhere('id', 152);
        $this->assertEquals($expected, $this->object->getWhere()->getParams());

        $this->assertEquals(Predicate::MAYBE, $this->object->getWhere()->getType());

        $expected['levelbetween[1,100]'] = new Expr('level', 'between', [1, 100]);

        $this->object->xorWhere(function(Predicate $where) {
            $where->between('level', 1, 100);
        });
        $this->assertEquals($expected, $this->object->getWhere()->getParams());

        try {
            $this->object->where('id', 1);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $this->object->xorWhere('size', Expr::NOT_IN, [1, 2]);

        $expected['sizenotIn[1,2]'] = new Expr('size', 'notIn', [1, 2]);

        $this->assertEquals($expected, $this->object->getWhere()->getParams());
    }

    public function testCloning() {
        $query1 = new Query(Query::SELECT, new User());
        $query1->where('id', 1)->having('id', '>', 1);

        $query2 = clone $query1;

        $this->assertEquals($query1, $query2);
        $this->assertNotSame($query1, $query2);
    }

}