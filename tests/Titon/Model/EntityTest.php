<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Model\Entity;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Entity.
 *
 * @property \Titon\Model\Entity $object
 */
class EntityTest extends TestCase {

	/**
	 * Create complex entity object.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new Entity([
			'id' => 1,
			'username' => 'Miles',
			'password' => 'iamasecret',
			'email' => 'email@domain.com',
			'created' => '1988-02-26 10:22:33',
			'Profile' => new Entity([
				'id' => 1,
				'age' => 25,
				'country' => 'USA'
			]),
			'Posts' => [
				new Entity([
					'id' => 666,
					'title' => 'Post #1'
				]),
				new Entity([
					'id' => 1337,
					'title' => 'Post #2'
				])
			]
		]);
	}

	/**
	 * Test that property access works.
	 */
	public function testMapData() {
		$this->assertEquals(1, $this->object->id);
		$this->assertEquals(null, $this->object->age);

		// Isset
		$this->assertTrue(isset($this->object->username));
		$this->assertFalse(isset($this->object->name));

		// Nested
		$this->assertEquals(25, $this->object->Profile->age);
		$this->assertEquals('Post #1', $this->object->Posts[0]->title);
	}

	/**
	 * Test that array access works.
	 */
	public function testArrayAccess() {
		$this->assertEquals(1, $this->object['id']);
		$this->assertEquals(null, $this->object['age']);

		// Isset
		$this->assertTrue(isset($this->object['username']));
		$this->assertFalse(isset($this->object['name']));

		// Nested
		$this->assertEquals(25, $this->object['Profile']['age']);
		$this->assertEquals('Post #1', $this->object['Posts'][0]['title']);
	}

	/**
	 * Test class serialization.
	 */
	public function testSerialize() {
		$data = serialize($this->object);
		$this->assertEquals('C:18:"Titon\Model\Entity":442:{a:7:{s:2:"id";i:1;s:8:"username";s:5:"Miles";s:8:"password";s:10:"iamasecret";s:5:"email";s:16:"email@domain.com";s:7:"created";s:19:"1988-02-26 10:22:33";s:7:"Profile";C:18:"Titon\Model\Entity":58:{a:3:{s:2:"id";i:1;s:3:"age";i:25;s:7:"country";s:3:"USA";}}s:5:"Posts";a:2:{i:0;C:18:"Titon\Model\Entity":47:{a:2:{s:2:"id";i:666;s:5:"title";s:7:"Post #1";}}i:1;C:18:"Titon\Model\Entity":48:{a:2:{s:2:"id";i:1337;s:5:"title";s:7:"Post #2";}}}}}', $data);

		$entity = unserialize($data);
		$this->assertEquals($this->object, $entity);

		$this->assertEquals([
			'id' => 1,
			'username' => 'Miles',
			'password' => 'iamasecret',
			'email' => 'email@domain.com',
			'created' => '1988-02-26 10:22:33',
			'Profile' => new Entity([
				'id' => 1,
				'age' => 25,
				'country' => 'USA'
			]),
			'Posts' => [
				new Entity([
					'id' => 666,
					'title' => 'Post #1'
				]),
				new Entity([
					'id' => 1337,
					'title' => 'Post #2'
				])
			]
		], $this->object->jsonSerialize());
	}

	/**
	 * Test looping works.
	 */
	public function testIterator() {
		$keys = [];

		foreach ($this->object as $key => $value) {
			$keys[] = $key;
		}

		$this->assertEquals($keys, $this->object->keys());
	}

}