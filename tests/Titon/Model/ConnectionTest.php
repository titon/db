<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Model\Source\Dbo\Mysql;
use Titon\Model\Source\Dbo\Mongo;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Connection.
 *
 * @property \Titon\Model\Connection $object
 */
class ConnectionTest extends TestCase {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new Connection();
		$this->object->addSource(new Mysql('mysql', []));
	}

	/**
	 * Test getting and setting data sources.
	 */
	public function testAddGetSource() {
		$this->assertInstanceOf('Titon\Model\Source\Dbo\Mysql', $this->object->getSource('mysql'));

		try {
			$this->object->getSource('mongo');
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}

		$this->object->addSource(new Mongo('mongo', []));
		$this->assertInstanceOf('Titon\Model\Source\Dbo\Mongo', $this->object->getSource('mongo'));
	}

	/**
	 * Test that getSources() returns all.
	 */
	public function testGetSources() {
		$this->assertEquals(1, count($this->object->getSources()));

		$this->object->addSource(new Mongo('mongo', []));
		$this->assertEquals(2, count($this->object->getSources()));
	}

}