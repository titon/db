<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Data;

use Titon\Model\Entity;
use Titon\Model\Query;
use Titon\Test\Stub\Model\Book;
use Titon\Test\Stub\Model\Series;
use Titon\Test\Stub\Model\Stat;
use Titon\Test\Stub\Model\User;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for database updating.
 */
class AbstractUpdateTest extends TestCase {

	/**
	 * Unload fixtures.
	 */
	protected function tearDown() {
		parent::tearDown();

		$this->unloadFixtures();
	}

	/**
	 * Test basic database record updating.
	 */
	public function testUpdate() {
		$this->loadFixtures('Users');

		$user = new User();
		$data = [
			'country_id' => 3,
			'username' => 'milesj'
		];

		$this->assertTrue($user->update(1, $data));

		$this->assertEquals([
			'id' => 1,
			'country_id' => 3,
			'username' => 'milesj',
			'password' => '1Z5895jf72yL77h',
			'email' => 'miles@email.com',
			'firstName' => 'Miles',
			'lastName' => 'Johnson',
			'age' => 25,
			'created' => '1988-02-26 21:22:34',
			'modified' => null
		], $user->select()->where('id', 1)->fetch(false));
	}

	/**
	 * Test database record updating with one to one relations.
	 */
	public function testUpdateWithOneToOne() {
		$this->loadFixtures(['Users', 'Profiles']);

		$user = new User();
		$data = [
			'country_id' => 3,
			'username' => 'milesj',
			'Profile' => [
				'id' => 4,
				'lastLogin' => '2012-06-24 17:30:33'
			]
		];

		$this->assertTrue($user->update(1, $data));

		$this->assertEquals([
			'id' => 1,
			'country_id' => 3,
			'username' => 'milesj',
			'password' => '',
			'email' => '',
			'firstName' => '',
			'lastName' => '',
			'age' => '',
			'created' => '',
			'modified' => '',
			'Profile' => [
				'id' => 4,
				'user_id' => 1,
				'lastLogin' => '2012-06-24 17:30:33',
				'currentLogin' => ''
			]
		], $user->data);

		// Should throw errors for invalid array structure
		unset($data['id'], $data['Profile']);

		$data['Profile'] = [
			['lastLogin' => '2012-06-24 17:30:33'] // Nested array
		];

		try {
			$this->assertTrue($user->update(1, $data));
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}

		// Will upsert if no one-to-one ID is present
		$data = [
			'country_id' => 3,
			'username' => 'miles',
			'Profile' => [
				'currentLogin' => '2012-06-24 17:30:33'
			]
		];

		$this->assertTrue($user->update(1, $data));

		$this->assertEquals([
			'id' => 1,
			'country_id' => 3,
			'username' => 'miles',
			'password' => '',
			'email' => '',
			'firstName' => '',
			'lastName' => '',
			'age' => '',
			'created' => '',
			'modified' => '',
			'Profile' => [
				'id' => 6,
				'user_id' => 1,
				'lastLogin' => '',
				'currentLogin' => '2012-06-24 17:30:33',
			]
		], $user->data);
	}

	/**
	 * Test database record updating with one to many relations.
	 */
	public function testUpdateWithOneToMany() {
		$this->loadFixtures(['Books', 'Series']);

		$series = new Series();
		$data = [
			'author_id' => 3,
			'name' => 'The Lord of the Rings (Updated)',
			'Books' => [
				['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring (Updated)'],
				['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers (Updated)'],
				['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King (Updated)'],
			]
		];

		$this->assertTrue($series->update(3, $data));

		$this->assertEquals([
			'id' => 3,
			'author_id' => 3,
			'name' => 'The Lord of the Rings (Updated)',
			'Books' => [
				['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring (Updated)', 'isbn' => '', 'released' => ''],
				['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers (Updated)', 'isbn' => '', 'released' => ''],
				['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King (Updated)', 'isbn' => '', 'released' => ''],
			]
		], $series->data);

		// Should throw errors for invalid array structure
		unset($data['Books']);

		$data['Books'] = [
			'name' => 'The Bad Beginning'
		]; // Non numeric array

		try {
			$this->assertTrue($series->update(3, $data));
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}

	/**
	 * Test database record updating with many to many relations.
	 */
	public function testUpdateWithManyToMany() {
		$this->loadFixtures(['Genres', 'Books', 'BookGenres']);

		$book = new Book();
		$data = [
			'series_id' => 1,
			'name' => 'A Dance with Dragons (Updated)',
			'Genres' => [
				['id' => 3, 'name' => 'Action-Adventure'], // Existing genre
				['name' => 'Epic-Horror'], // New genre
				['genre_id' => 8] // Existing genre by ID
			]
		];

		$this->assertTrue($book->update(5, $data));

		$this->assertEquals([
			'id' => 5,
			'series_id' => 1,
			'name' => 'A Dance with Dragons (Updated)',
			'isbn' => '',
			'released' => '',
			'Genres' => [
				[
					'id' => 3,
					'name' => 'Action-Adventure',
					'Junction' => [
						'id' => 15,
						'book_id' => 5,
						'genre_id' => 3
					]
				], [
					'id' => 12,
					'name' => 'Epic-Horror',
					'Junction' => [
						'id' => 47,
						'book_id' => 5,
						'genre_id' => 12
					]
				], [
					'id' => 8,
					'name' => 'Fantasy',
					'Junction' => [
						'id' => 14,
						'book_id' => 5,
						'genre_id' => 8
					]
				]
			]
		], $book->data);

		// Should throw errors for invalid array structure
		unset($data['Genres']);

		$data['Genres'] = [
			'id' => 3,
			'name' => 'Action-Adventure'
		]; // Non numeric array

		try {
			$this->assertTrue($book->update(5, $data));
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}

		// Try again with another structure
		unset($data['Genres']);

		$data['Genres'] = [
			'Fantasy', 'Horror'
		]; // Non array value

		try {
			$this->assertTrue($book->update(5, $data));
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}

	/**
	 * Test multiple record updates.
	 */
	public function testUpdateMultiple() {
		$this->loadFixtures('Users');

		$user = new User();

		$this->assertEquals(4, $user->query(Query::UPDATE)->fields(['country_id' => 1])->where('country_id', '!=', 1)->save());

		$this->assertEquals([
			['id' => 1, 'country_id' => 1, 'username' => 'miles'],
			['id' => 2, 'country_id' => 1, 'username' => 'batman'],
			['id' => 3, 'country_id' => 1, 'username' => 'superman'],
			['id' => 4, 'country_id' => 1, 'username' => 'spiderman'],
			['id' => 5, 'country_id' => 1, 'username' => 'wolverine'],
		], $user->select('id', 'country_id', 'username')->fetchAll(false));

		// No where clause
		$this->assertEquals(5, $user->query(Query::UPDATE)->fields(['country_id' => 2])->save());

		$this->assertEquals([
			['id' => 1, 'country_id' => 2, 'username' => 'miles'],
			['id' => 2, 'country_id' => 2, 'username' => 'batman'],
			['id' => 3, 'country_id' => 2, 'username' => 'superman'],
			['id' => 4, 'country_id' => 2, 'username' => 'spiderman'],
			['id' => 5, 'country_id' => 2, 'username' => 'wolverine'],
		], $user->select('id', 'country_id', 'username')->fetchAll(false));
	}

	/**
	 * Test multiple record updates with a limit and offset applied.
	 */
	public function testUpdateMultipleWithLimit() {
		$this->loadFixtures('Users');

		$user = new User();

		$this->assertEquals(2, $user->query(Query::UPDATE)->fields(['country_id' => 1])->where('country_id', '!=', 1)->limit(2)->save());

		$this->assertEquals([
			['id' => 1, 'country_id' => 1, 'username' => 'miles'],
			['id' => 2, 'country_id' => 1, 'username' => 'batman'],
			['id' => 3, 'country_id' => 1, 'username' => 'superman'],
			['id' => 4, 'country_id' => 5, 'username' => 'spiderman'],
			['id' => 5, 'country_id' => 4, 'username' => 'wolverine'],
		], $user->select('id', 'country_id', 'username')->fetchAll(false));

		// No where clause, offset ignored
		$this->assertEquals(2, $user->query(Query::UPDATE)->fields(['country_id' => 5])->limit(2, 2)->save());

		$this->assertEquals([
			['id' => 1, 'country_id' => 5, 'username' => 'miles'],
			['id' => 2, 'country_id' => 5, 'username' => 'batman'],
			['id' => 3, 'country_id' => 1, 'username' => 'superman'],
			['id' => 4, 'country_id' => 5, 'username' => 'spiderman'],
			['id' => 5, 'country_id' => 4, 'username' => 'wolverine'],
		], $user->select('id', 'country_id', 'username')->fetchAll(false));
	}

	/**
	 * Test multiple record updates with an order by applied.
	 */
	public function testUpdateMultipleWithOrderBy() {
		$this->loadFixtures('Users');

		$user = new User();

		$this->assertEquals(2, $user->query(Query::UPDATE)
			->fields(['country_id' => 6])
			->orderBy('username', 'desc')
			->limit(2)
			->save());

		$this->assertEquals([
			['id' => 1, 'country_id' => 1, 'username' => 'miles'],
			['id' => 2, 'country_id' => 3, 'username' => 'batman'],
			['id' => 3, 'country_id' => 6, 'username' => 'superman'], // changed
			['id' => 4, 'country_id' => 5, 'username' => 'spiderman'],
			['id' => 5, 'country_id' => 6, 'username' => 'wolverine'], // changed
		], $user->select('id', 'country_id', 'username')->fetchAll(false));
	}

	/**
	 * Test multiple record updates with an order by applied.
	 */
	public function testUpdateMultipleWithConditions() {
		$this->loadFixtures('Users');

		$user = new User();

		$this->assertEquals(3, $user->query(Query::UPDATE)
			->fields(['country_id' => null])
			->where('username', 'like', '%man%')
			->save());

		$this->assertEquals([
			['id' => 1, 'country_id' => 1, 'username' => 'miles'],
			['id' => 2, 'country_id' => null, 'username' => 'batman'],
			['id' => 3, 'country_id' => null, 'username' => 'superman'],
			['id' => 4, 'country_id' => null, 'username' => 'spiderman'],
			['id' => 5, 'country_id' => 4, 'username' => 'wolverine'],
		], $user->select('id', 'country_id', 'username')->fetchAll(false));
	}

	/**
	 * Test updating and reading casts types.
	 */
	public function testUpdateTypeCasting() {
		$this->loadFixtures('Stats');

		$stat = new Stat();
		$data = [
			'health' => '2000', // to int
			'energy' => '300', // to int
			'damage' => 145, // to float
			'defense' => 60.25, // to double
			'range' => '2', // to decimal
			'isMelee' => null, // to boolean
		];

		$this->assertTrue($stat->update(1, $data));

		$expected = $stat->select()->where('id', 1)->fetch(false);
		unset($expected['data']);

		$this->assertSame([
			'id' => 1,
			'name' => 'Warrior',
			'health' => 2000,
			'energy' => 300,
			'damage' => 145.0,
			'defense' => 60.25,
			'range' => 2.0,
			'isMelee' => false
		], $expected);
	}

	/**
	 * Test updating blob data.
	 */
	public function testUpdateBlobData() {
		$this->loadFixtures('Stats');

		$handle = fopen(TEMP_DIR . '/blob.txt', 'rb');

		$stat = new Stat();
		$this->assertTrue($stat->update(1, [
			'data' => $handle
		]));

		// Match row
		$expected = $stat->select()->where('id', 1)->fetch(false);
		$handle = $expected['data'];
		$expected['data'] = stream_get_contents($handle, -1, 0);
		fclose($handle);

		$this->assertEquals([
			'id' => 1,
			'name' => 'Warrior',
			'health' => 1500,
			'energy' => 150,
			'damage' => 125.25,
			'defense' => 55.75,
			'range' => 1.0,
			'isMelee' => true,
			'data' => 'This is loading from a file handle'
		], $expected);
	}

}