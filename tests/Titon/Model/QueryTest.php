<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Model\Query;
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
	}

	/**
	 * Test that having clause returns params.
	 */
	public function testHaving() {
		$expected = ['id=1' => ['field' => 'id', 'value' => 1, 'op' => '=']];

		$this->object->having('id', 1);
		$this->assertEquals($expected, $this->object->getHaving()->getParams());

		$this->assertEquals(Predicate::ALSO, $this->object->getHaving()->getType());

		$expected['titlenotLike%Titon%'] = ['field' => 'title', 'value' => '%Titon%', 'op' => 'notLike'];

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

		$expected['size!=15'] = ['field' => 'size', 'value' => 15, 'op' => '!='];

		$this->assertEquals($expected, $this->object->getHaving()->getParams());
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
	}

	/**
	 * Test that OR having clause returns params.
	 */
	public function testOrHaving() {
		$expected = ['id=1' => ['field' => 'id', 'value' => 1, 'op' => '=']];

		$this->object->orHaving('id', 1);
		$this->assertEquals($expected, $this->object->getHaving()->getParams());

		$this->assertEquals(Predicate::EITHER, $this->object->getHaving()->getType());

		$expected['titlenotLike%Titon%'] = ['field' => 'title', 'value' => '%Titon%', 'op' => 'notLike'];

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

		$expected['size>=15'] = ['field' => 'size', 'value' => 15, 'op' => '>='];

		$this->assertEquals($expected, $this->object->getHaving()->getParams());
	}

	/**
	 * Test that OR where clause returns params.
	 */
	public function testOrWhere() {
		$expected = ['id=152' => ['field' => 'id', 'value' => 152, 'op' => '=']];

		$this->object->orWhere('id', 152);
		$this->assertEquals($expected, $this->object->getWhere()->getParams());

		$this->assertEquals(Predicate::EITHER, $this->object->getWhere()->getType());

		$expected['levelbetween1100'] = ['field' => 'level', 'value' => [1, 100], 'op' => 'between'];

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

		$this->object->orWhere('size', Predicate::NOT_IN, [1, 2]);

		$expected['sizenotIn12'] = ['field' => 'size', 'value' => [1, 2], 'op' => 'notIn'];

		$this->assertEquals($expected, $this->object->getWhere()->getParams());
	}

	/**
	 * Test that where clause returns params.
	 */
	public function testWhere() {
		$expected = ['id=152' => ['field' => 'id', 'value' => 152, 'op' => '=']];

		$this->object->where('id', 152);
		$this->assertEquals($expected, $this->object->getWhere()->getParams());

		$this->assertEquals(Predicate::ALSO, $this->object->getWhere()->getType());

		$expected['levelbetween1100'] = ['field' => 'level', 'value' => [1, 100], 'op' => 'between'];

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

		$expected['size>25'] = ['field' => 'size', 'value' => 25, 'op' => '>'];

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

		$queries = $this->object->getSubQueries();

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
	}

}