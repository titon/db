<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Model\Query;
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

		$this->object = new Query(Query::SELECT, Model::getInstance());
	}

	/**
	 * Test getting and setting fields.
	 */
	public function testFields() {
		$this->object->fields('id', 'title', 'id');
		$this->assertEquals(['id', 'title'], $this->object->getFields());

		$this->object->fields(['id', 'created'], 'title');
		$this->assertEquals(['id', 'created'], $this->object->getFields());

		$this->object->fields(['id' => 1, 'title' => 'Titon']);
		$this->assertEquals(['id' => 1, 'title' => 'Titon'], $this->object->getFields());
	}

	/**
	 * Test getting and setting the table.
	 */
	public function testFrom() {
		$this->object->from('users');
		$this->assertEquals('users', $this->object->getTable());
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
		$expected = [['field' => 'id', 'value' => 1, 'op' => '=']];

		$this->object->having('id', 1);
		$this->assertEquals($expected, $this->object->getHaving()->getParams());

		$expected[] = ['field' => 'title', 'value' => '%Titon%', 'op' => 'NOT LIKE'];

		$this->object->having(function() {
			$this->also('title', '%Titon%', Query\Clause::NOT_LIKE);
		});
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
	 * Test getting and setting of order by directions.
	 */
	public function testOrderBy() {
		$this->object->orderBy('id', 'asc');
		$this->assertEquals(['id' => 'ASC'], $this->object->getOrderBy());

		$this->object->orderBy([
			'id' => 'DESC',
			'created' => 'asc'
		]);
		$this->assertEquals(['id' => 'DESC', 'created' => 'ASC'], $this->object->getOrderBy());

		try {
			$this->object->orderBy('id', 'ascending');
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}

	/**
	 * Test that where clause returns params.
	 */
	public function testWhere() {
		$expected = [['field' => 'id', 'value' => 152, 'op' => '=']];

		$this->object->where('id', 152);
		$this->assertEquals($expected, $this->object->getWhere()->getParams());

		$expected[] = ['field' => 'level', 'value' => [1, 100], 'op' => 'BETWEEN'];

		$this->object->where(function() {
			$this->also('level', [1, 100], Query\Clause::BETWEEN);
		});
		$this->assertEquals($expected, $this->object->getWhere()->getParams());
	}

}