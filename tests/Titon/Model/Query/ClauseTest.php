<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Model\Query\Clause;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Query\Clause.
 *
 * @property \Titon\Model\Query\Clause $object
 */
class ClauseTest extends TestCase {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new Clause();
	}

	/**
	 * Test that also sets the type and params.
	 */
	public function testAlso() {
		$this->object->also('id', 1);
		$params = [
			['field' => 'id', 'value' => 1, 'op' => '=']
		];

		$this->assertEquals(Clause::ALSO, $this->object->getType());
		$this->assertEquals($params, $this->object->getParams());

		$this->object->also('id', 1, Clause::NOT_LIKE);
		$params[] = ['field' => 'id', 'value' => 1, 'op' => 'NOT LIKE'];

		$this->assertEquals($params, $this->object->getParams());
	}

	/**
	 * Test that also sets the type and params.
	 */
	public function testEither() {
		$this->object->either('id', 1);
		$params = [
			['field' => 'id', 'value' => 1, 'op' => '=']
		];

		$this->assertEquals(Clause::EITHER, $this->object->getType());
		$this->assertEquals($params, $this->object->getParams());

		$this->object->either('id', 1, Clause::NOT_LIKE);
		$params[] = ['field' => 'id', 'value' => 1, 'op' => 'NOT LIKE'];

		$this->assertEquals($params, $this->object->getParams());
	}

	/**
	 * Test group clause nesting works.
	 */
	public function testGroup() {
		$this->object
			->group(function() {
				$this->also('color', 'red')->also('color', 'black');
			})
			->also('id', 1)
			->group(function() {
				$this->either('color', 'red')->either('color', 'black');
			});

		$also = new Clause();
		$also->also('color', 'red')->also('color', 'black');

		$either = new Clause();
		$either->either('color', 'red')->either('color', 'black');

		$this->assertEquals([
			$also,
			['field' => 'id', 'value' => 1, 'op' => '='],
			$either
		], $this->object->getParams());
	}

	/**
	 * Test that clause casting and sanity checks work.
	 */
	public function testProcess() {
		$clause = new Clause();
		$clause->also('id', 'foobar', 'like');

		$this->assertEquals([
			['field' => 'id', 'value' => 'foobar', 'op' => 'LIKE'],
		], $clause->getParams());

		// Cast to IN
		$clause = new Clause();
		$clause->also('id', [1, 2]);

		$this->assertEquals([
			['field' => 'id', 'value' => [1, 2], 'op' => 'IN'],
		], $clause->getParams());

		// Cast to NOT IN
		$clause = new Clause();
		$clause->also('id', [1, 2], '!=');

		$this->assertEquals([
			['field' => 'id', 'value' => [1, 2], 'op' => 'NOT IN'],
		], $clause->getParams());

		// Cast to IS NULL
		$clause = new Clause();
		$clause->also('id', null);

		$this->assertEquals([
			['field' => 'id', 'value' => null, 'op' => 'IS NULL'],
		], $clause->getParams());

		// Cast to NOT NULL
		$clause = new Clause();
		$clause->also('id', null, '!=');

		$this->assertEquals([
			['field' => 'id', 'value' => null, 'op' => 'IS NOT NULL'],
		], $clause->getParams());

		// BETWEEN checks
		try {
			$clause = new Clause();
			$clause->also('id', [1, 2, 3, 4, 5], Clause::BETWEEN);

			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}

}