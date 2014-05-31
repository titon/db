<?php
namespace Titon\Db;

use Titon\Db\Query;
use Titon\Db\Query\Expr;
use Titon\Db\Query\Join;
use Titon\Db\Query\Predicate;
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

    public function testData() {
        $query = new Query(Query::INSERT, new User());

        $query->data(['id' => 1, 'title' => 'Titon']);
        $this->assertEquals(['id' => 1, 'title' => 'Titon'], $query->getData());

        $query->data(['username' => 'miles']);
        $this->assertEquals(['username' => 'miles'], $query->getData());
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

    /**
     * @expectedException \Titon\Db\Exception\InvalidQueryException
     */
    public function testExceptFailsNonSelect() {
        $this->object->except(new Query(Query::UPDATE));
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
    }

    public function testFieldsAutoPopulateViaJoins() {
        $this->object->leftJoin('profiles', [], ['profiles.user_id' => 'users.id']);

        $this->assertEquals([
            'id', 'country_id', 'username', 'password', 'email', 'firstName',
            'lastName', 'age', 'created', 'modified'
        ], $this->object->getFields());
    }

    public function testFieldsAutoPopulateViaJoinsNoSchema() {
        $query = new Query(Query::SELECT, new Repository(['table' => 'users'])); // Repo has no schema
        $query->leftJoin('profiles', [], ['profiles.user_id' => 'users.id']);

        $this->assertEquals([], $query->getFields());
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
        $expected = [new Expr('id', '=', 1)];

        $this->object->having('id', 1);
        $this->assertEquals($expected, $this->object->getHaving()->getParams());
        $this->assertEquals(Predicate::ALSO, $this->object->getHaving()->getType());

        $this->object->having(function(Predicate $having) {
            $having->notLike('title', '%Titon%');
        });
        $expected[] = new Expr('title', 'notLike', '%Titon%');
        $this->assertEquals($expected, $this->object->getHaving()->getParams());

        $this->object->having('size', '!=', 15);
        $expected[] = new Expr('size', '!=', 15);
        $this->assertEquals($expected, $this->object->getHaving()->getParams());
    }

    /**
     * @expectedException \Titon\Db\Exception\ExistingPredicateException
     */
    public function testHavingErrorsOnTypeMismatch() {
        $this->object->having('id', 1);
        $this->object->orHaving('id', 1);
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

    /**
     * @expectedException \Titon\Db\Exception\InvalidQueryException
     */
    public function testIntersectFailsNonSelect() {
        $this->object->intersect(new Query(Query::UPDATE));
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

    /**
     * @expectedException \Titon\Db\Exception\InvalidArgumentException
     */
    public function testOrderByErrorsInvalidType() {
        $this->object->orderBy('id', 'ascending');
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
        $expected = [new Expr('id', '=', 1)];

        $this->object->orHaving('id', 1);
        $this->assertEquals($expected, $this->object->getHaving()->getParams());
        $this->assertEquals(Predicate::EITHER, $this->object->getHaving()->getType());

        $this->object->orHaving(function(Predicate $having) {
            $having->notLike('title', '%Titon%');
        });
        $expected[] = new Expr('title', 'notLike', '%Titon%');
        $this->assertEquals($expected, $this->object->getHaving()->getParams());

        $this->object->orHaving('size', '>=', 15);
        $expected[] = new Expr('size', '>=', 15);
        $this->assertEquals($expected, $this->object->getHaving()->getParams());
    }

    /**
     * @expectedException \Titon\Db\Exception\ExistingPredicateException
     */
    public function testOrHavingErrorsOnTypeMismatch() {
        $this->object->orHaving('id', 1);
        $this->object->having('id', 1);
    }

    public function testOrWhere() {
        $expected = [new Expr('id', '=', 152)];

        $this->object->orWhere('id', 152);
        $this->assertEquals($expected, $this->object->getWhere()->getParams());
        $this->assertEquals(Predicate::EITHER, $this->object->getWhere()->getType());

        $this->object->orWhere(function(Predicate $where) {
            $where->between('level', 1, 100);
        });
        $expected[] = new Expr('level', 'between', [1, 100]);
        $this->assertEquals($expected, $this->object->getWhere()->getParams());

        $this->object->orWhere('size', Expr::NOT_IN, [1, 2]);
        $expected[] = new Expr('size', 'notIn', [1, 2]);
        $this->assertEquals($expected, $this->object->getWhere()->getParams());
    }

    /**
     * @expectedException \Titon\Db\Exception\ExistingPredicateException
     */
    public function testOrWhereErrorsOnTypeMismatch() {
        $this->object->orWhere('id', 1);
        $this->object->where('id', 1);
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

    /**
     * @expectedException \Titon\Db\Exception\InvalidQueryException
     */
    public function testUnionFailsNonSelect() {
        $this->object->union(new Query(Query::UPDATE));
    }

    public function testWhere() {
        $expected = [new Expr('id', '=', 152)];

        $this->object->where('id', 152);
        $this->assertEquals($expected, $this->object->getWhere()->getParams());
        $this->assertEquals(Predicate::ALSO, $this->object->getWhere()->getType());

        $this->object->where(function(Predicate $where) {
            $where->between('level', 1, 100);
        });
        $expected[] = new Expr('level', 'between', [1, 100]);
        $this->assertEquals($expected, $this->object->getWhere()->getParams());

        $this->object->where('size', '>', 25);
        $expected[] = new Expr('size', '>', 25);
        $this->assertEquals($expected, $this->object->getWhere()->getParams());
    }

    /**
     * @expectedException \Titon\Db\Exception\ExistingPredicateException
     */
    public function testWhereErrorsOnTypeMismatch() {
        $this->object->where('id', 1);
        $this->object->orWhere('id', 1);
    }

    public function testXorHaving() {
        $expected = [new Expr('id', '=', 1)];

        $this->object->xorHaving('id', 1);
        $this->assertEquals($expected, $this->object->getHaving()->getParams());
        $this->assertEquals(Predicate::MAYBE, $this->object->getHaving()->getType());

        $this->object->xorHaving(function(Predicate $having) {
            $having->notLike('title', '%Titon%');
        });
        $expected[] = new Expr('title', 'notLike', '%Titon%');
        $this->assertEquals($expected, $this->object->getHaving()->getParams());

        $this->object->xorHaving('size', '>=', 15);
        $expected[] = new Expr('size', '>=', 15);
        $this->assertEquals($expected, $this->object->getHaving()->getParams());
    }

    /**
     * @expectedException \Titon\Db\Exception\ExistingPredicateException
     */
    public function testXorHavingErrorsOnTypeMismatch() {
        $this->object->xorHaving('id', 1);
        $this->object->orHaving('id', 1);
    }

    public function testXorWhere() {
        $expected = [new Expr('id', '=', 152)];

        $this->object->xorWhere('id', 152);
        $this->assertEquals($expected, $this->object->getWhere()->getParams());
        $this->assertEquals(Predicate::MAYBE, $this->object->getWhere()->getType());

        $this->object->xorWhere(function(Predicate $where) {
            $where->between('level', 1, 100);
        });
        $expected[] = new Expr('level', 'between', [1, 100]);
        $this->assertEquals($expected, $this->object->getWhere()->getParams());

        $this->object->xorWhere('size', Expr::NOT_IN, [1, 2]);
        $expected[] = new Expr('size', 'notIn', [1, 2]);
        $this->assertEquals($expected, $this->object->getWhere()->getParams());
    }

    /**
     * @expectedException \Titon\Db\Exception\ExistingPredicateException
     */
    public function testXorWhereErrorsOnTypeMismatch() {
        $this->object->xorWhere('id', 1);
        $this->object->where('id', 1);
    }

    public function testCloning() {
        $this->object->where('id', 1)->having('id', '>', 1);

        $clone = clone $this->object;

        $this->assertEquals($this->object, $clone);
        $this->assertNotSame($this->object, $clone);
    }

    public function testToString() {
        $this->assertRegExp('/^[a-z0-9]{32}$/', $this->object->toString());
        $this->assertRegExp('/^[a-z0-9]{32}$/', (string) $this->object);
    }

}