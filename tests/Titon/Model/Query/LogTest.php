<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query;

use Titon\Test\Stub\QueryLogStub;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Query\Log.
 *
 * @property \Titon\Model\Query\Log $object
 */
class LogTest extends TestCase {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new QueryLogStub('SELECT * FROM users WHERE id = ?', [
			'id' => 1
		]);
	}

	/**
	 * Test that execution time is generated.
	 */
	public function testGetExecutionTime() {
		$this->assertEquals(0.01337, $this->object->getExecutionTime());
	}

	/**
	 * Test that params are returned.
	 */
	public function testGetParams() {
		$this->assertEquals(['id' => 1], $this->object->getParams());
	}

	/**
	 * Test that row count is returned.
	 */
	public function testGetRowCount() {
		$this->assertEquals(5, $this->object->getRowCount());
	}

	/**
	 * Test final statement is returned.
	 */
	public function testGetStatement() {
		$this->assertEquals('SELECT * FROM users WHERE id = 1', $this->object->getStatement());
	}

}