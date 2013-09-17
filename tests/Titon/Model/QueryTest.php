<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model;

use Titon\Model\Query;
use Titon\Model\Query\Expr;
use Titon\Model\Query\Join;
use Titon\Model\Query\Predicate;
use Titon\Test\Stub\Model\Profile;
use Titon\Test\Stub\Model\User;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for Titon\Model\Query.
 *
 * @property \Titon\Model\Query $object
 */
class QueryTest extends TestCase {

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new Query(Query::SELECT, new User());
    }

    /**
     * Test getting and setting of attributes.
     */
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

    /**
     * Test that callbacks modify the query.
     */
    public function testBindCallback() {
        $this->assertEquals([], $this->object->getFields());

        $this->object->bindCallback(function() {
            $this->fields('id', 'created');
        }, null);

        $this->assertEquals(['id', 'created'], $this->object->getFields());
    }

    /**
     * Test that an expression object is returned.
     */
    public function testExpr() {
        $this->assertInstanceOf('Titon\Model\Query\Expr', $this->object->expr('column', '+', 5));
    }

    /**
     * Test getting and setting fields.
     */
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

        // Now with joins
        $query = new Query(Query::SELECT, new User());
        $query->innerJoin($query->getModel()->getRelation('Profile'), []);

        $this->assertEquals(['id', 'country_id', 'username', 'password', 'email', 'firstName', 'lastName', 'age', 'created', 'modified'], $query->getFields());
    }

    /**
     * Test getting and setting the table.
     */
    public function testFrom() {
        $this->object->from('users');
        $this->assertEquals('users', $this->object->getTable());
    }

    /**
     * Test that a function object is returned.
     */
    public function testFunc() {
        $this->assertInstanceOf('Titon\Model\Query\Func', $this->object->func('SUBSTRING', ['Foo', 1, 2]));
    }

    /**
     * Test getting and setting group by fields.
     */
    public function testGroupBy() {
        $this->object->groupBy('id', 'created');
        $this->assertEquals(['id', 'created'], $this->object->getGroupBy());

        $this->object->groupBy('title');
        $this->assertEquals(['id', 'created', 'title'], $this->object->getGroupBy());

        $this->object->groupBy(['content', 'modified']);
        $this->assertEquals(['id', 'created', 'title', 'content', 'modified'], $this->object->getGroupBy());
    }

    /**
     * Test that having clause returns params.
     */
    public function testHaving() {
        $expected = ['id=1' => new Expr('id', '=', 1)];

        $this->object->having('id', 1);
        $this->assertEquals($expected, $this->object->getHaving()->getParams());

        $this->assertEquals(Predicate::ALSO, $this->object->getHaving()->getType());

        $expected['titlenotLike%Titon%'] = new Expr('title', 'notLike', '%Titon%');

        $this->object->having(function() {
            $this->notLike('title', '%Titon%');
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

    /**
     * Test join building.
     */
    public function testJoins() {
        $expected = [];

        $j1 = new Join(Join::LEFT);
        $j1->from('profiles', 'profiles')->on('User.profile_id', 'profiles.id');

        $this->object->leftJoin('profiles', [], ['profile_id' => 'id']);
        $this->assertEquals($j1, $this->object->getJoins()[0]);

        $j2 = new Join(Join::RIGHT);
        $j2->from('profiles', 'profiles')->on('users.id', 'profiles.id')->fields(['id', 'created']);

        $this->object->rightJoin('profiles', ['id', 'created'], ['users.id' => 'profiles.id']);
        $this->assertEquals($j2, $this->object->getJoins()[1]);

        // With relation
        $j3 = new Join(Join::INNER);
        $j3->from('profiles', 'Profile')->on('User.id', 'Profile.user_id')->fields('id', 'user_id', 'lastLogin', 'currentLogin');

        $this->object->innerJoin($this->object->getModel()->getRelation('Profile'), []);
        $this->assertEquals($j3, $this->object->getJoins()[2]);
    }

    /**
     * Test getting and setting of limit and offset.
     */
    public function testLimit() {
        $this->object->limit(15);
        $this->assertEquals(15, $this->object->getLimit());
        $this->assertEquals(0, $this->object->getOffset());

        $this->object->limit(25, 50);
        $this->assertEquals(25, $this->object->getLimit());
        $this->assertEquals(50, $this->object->getOffset());
    }

    /**
     * Test getting and setting of offset.
     */
    public function testOffset() {
        $this->assertEquals(0, $this->object->getOffset());
        $this->object->offset(15);
        $this->assertEquals(15, $this->object->getOffset());
    }

    /**
     * Test getting and setting of order by directions.
     */
    public function testOrderBy() {
        $this->object->orderBy('id', 'asc');
        $this->assertEquals(['id' => 'asc'], $this->object->getOrderBy());

        $this->object->orderBy([
            'id' => 'DESC',
            'created' => 'asc'
        ]);
        $this->assertEquals(['id' => 'desc', 'created' => 'asc'], $this->object->getOrderBy());

        try {
            $this->object->orderBy('id', 'ascending');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $rand1 = new Query\Func('RAND');
        $this->object->orderBy('RAND');
        $this->assertEquals(['id' => 'desc', 'created' => 'asc', $rand1], $this->object->getOrderBy());

        $rand2 = new Query\Func('RAND');
        $this->object->orderBy($rand2);
        $this->assertEquals(['id' => 'desc', 'created' => 'asc', $rand1, $rand2], $this->object->getOrderBy());
    }

    /**
     * Test that OR having clause returns params.
     */
    public function testOrHaving() {
        $expected = ['id=1' => new Expr('id', '=', 1)];

        $this->object->orHaving('id', 1);
        $this->assertEquals($expected, $this->object->getHaving()->getParams());

        $this->assertEquals(Predicate::EITHER, $this->object->getHaving()->getType());

        $expected['titlenotLike%Titon%'] = new Expr('title', 'notLike', '%Titon%');

        $this->object->orHaving(function() {
            $this->notLike('title', '%Titon%');
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

    /**
     * Test that OR where clause returns params.
     */
    public function testOrWhere() {
        $expected = ['id=152' => new Expr('id', '=', 152)];

        $this->object->orWhere('id', 152);
        $this->assertEquals($expected, $this->object->getWhere()->getParams());

        $this->assertEquals(Predicate::EITHER, $this->object->getWhere()->getType());

        $expected['levelbetween[1,100]'] = new Expr('level', 'between', [1, 100]);

        $this->object->orWhere(function() {
            $this->between('level', 1, 100);
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

    /**
     * Test that where clause returns params.
     */
    public function testWhere() {
        $expected = ['id=152' => new Expr('id', '=', 152)];

        $this->object->where('id', 152);
        $this->assertEquals($expected, $this->object->getWhere()->getParams());

        $this->assertEquals(Predicate::ALSO, $this->object->getWhere()->getType());

        $expected['levelbetween[1,100]'] = new Expr('level', 'between', [1, 100]);

        $this->object->where(function() {
            $this->between('level', 1, 100);
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

    /**
     * Test that with() generates relational sub-queries.
     */
    public function testWith() {
        // Missing relation
        try {
            $this->object->with('Foobar', function() {});
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // Not a query
        try {
            $this->object->with('Profile', []);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $this->object->with('Profile', function(Relation $relation) {
            $this->where($relation->getRelatedForeignKey(), 1);
        });

        $query = new Query(Query::SELECT, new Profile());
        $query->from('profiles')->where('user_id', 1);

        $queries = $this->object->getRelationQueries();

        $this->assertEquals($query->jsonSerialize(), $queries['Profile']->jsonSerialize());

        // Test exceptions
        try {
            $query = new Query(Query::DELETE, new User());
            $query->with('Profile', function() {

            });

            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // Multiple relations
        $this->object->with(['Profile', 'Country']);
        $this->assertEquals(['Profile', 'Country'], array_keys($this->object->getRelationQueries()));

        // Test custom query
        try {
            $query = new Query(Query::SELECT, new User());
            $query->with('Profile', new Query(Query::DELETE, new User()));

            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $query = new Query(Query::SELECT, new User());
        $query->with('Profile', new Query(Query::SELECT, new User()));
    }

    /**
     * Test object serialization.
     */
    public function testSerialize() {
        $model = new User();
        $query = new Query(Query::SELECT, $model);
        $func = $query->func('COUNT', ['id' => Query\Func::FIELD])->asAlias('count');

        $query
            ->attribute('distinct', true)
            ->cache('cacheKey', '+5 minutes')
            ->fields(['id', $func])
            ->groupBy('id')
            ->having('count', '>', 5)
            ->leftJoin(['profiles', 'Profile'], [], ['User.id' => 'Profile.user_id'])
            ->limit(5, 10)
            ->orderBy('id', 'asc')
            ->from('users')
            ->where(function() {
                $this->notIn('id', [1, 2, 3])->gte('size', 15);
            });

        $expected = unserialize(serialize($query));
        $expected->setModel($model); // Asserting will fail unless we use the same model instance

        $this->assertEquals($expected, $query);
    }

    /**
     * Test JSON encoding of queries.
     */
    public function testJsonSerialize() {
        $query = new Query(Query::SELECT, new User());
        $query
            ->attribute('distinct', true)
            ->cache('cacheKey', '+5 minutes')
            ->fields([
                'id', // column
                $query->func('COUNT', ['id' => Query\Func::FIELD])->asAlias('count'), // function
            ])
            ->groupBy('id')
            ->having('count', '>', 5)
            ->leftJoin(['profiles', 'Profile'], [], ['User.id' => 'Profile.user_id'])
            ->limit(5, 10)
            ->orderBy('id', 'asc')
            ->from('users')
            ->where(function() {
                $this->notIn('id', [1, 2, 3])->gte('size', 15);
            });

        $this->assertEquals(json_encode([
            'alias' => null,
            'attributes' => ['distinct' => true],
            'cacheKey' => 'cacheKey',
            'cacheLength' => '+5 minutes',
            'fields' => [
                'id',
                [
                    'name' => 'COUNT',
                    'alias' => 'count',
                    'arguments' => [['type' => 'field', 'value' => 'id']],
                    'separator' => ', '
                ]
            ],
            'groupBy' => ['id'],
            'having' => [
                'type' => 'and',
                'params' => [
                    'count>5' => [
                        'field' => 'count',
                        'operator' => '>',
                        'value' => 5
                    ]
                ]
            ],
            'joins' => [
                [
                    'table' => 'profiles',
                    'alias' => 'Profile',
                    'fields' => [],
                    'type' => 'leftJoin',
                    'on' => ['User.id' => 'Profile.user_id']
                ]
            ],
            'limit' => 5,
            'model' => 'Titon\Test\Stub\Model\User',
            'offset' => 10,
            'orderBy' => ['id' => 'asc'],
            'relationQueries' => [],
            'schema' => null,
            'table' => 'users',
            'type' => 'select',
            'where' => [
                'type' => 'and',
                'params' => [
                    'idnotIn[1,2,3]' => [
                        'field' => 'id',
                        'operator' => 'notIn',
                        'value' => [1, 2, 3]
                    ],
                    'size>=15' => [
                        'field' => 'size',
                        'operator' => '>=',
                        'value' => 15
                    ]
                ]
            ]
        ]), json_encode($query));

        // Test expressions (cant be done in select queries)
        $query = new Query(Query::UPDATE, new User());
        $query->from('users')->fields([
            'size' => $this->object->expr('size', '+', 5) // expression
        ]);

        $this->assertEquals(json_encode([
            'alias' => null,
            'attributes' => [],
            'cacheKey' => null,
            'cacheLength' => null,
            'fields' => [
                'size' => [
                    'field' => 'size',
                    'operator' => '+',
                    'value' => 5
                ]
            ],
            'groupBy' => [],
            'having' => [
                'type' => 'and',
                'params' => []
            ],
            'joins' => [],
            'limit' => null,
            'model' => 'Titon\Test\Stub\Model\User',
            'offset' => null,
            'orderBy' => [],
            'relationQueries' => [],
            'schema' => null,
            'table' => 'users',
            'type' => 'update',
            'where' => [
                'type' => 'and',
                'params' => []
            ]
        ]), json_encode($query));
    }

    /**
     * Test where and having objects persist through cloning.
     */
    public function testCloning() {
        $query1 = new Query(Query::SELECT, new User());
        $query1->where('id', 1)->having('id', '>', 1);

        $query2 = clone $query1;

        $this->assertEquals($query1, $query2);
    }

}