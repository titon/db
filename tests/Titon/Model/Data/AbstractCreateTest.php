<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Data;

use Titon\Model\Query;
use Titon\Test\Stub\Model\Book;
use Titon\Test\Stub\Model\Series;
use Titon\Test\Stub\Model\User;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for database inserting.
 */
class AbstractCreateTest extends TestCase {

	/**
	 * Unload fixtures.
	 */
	protected function tearDown() {
		parent::tearDown();

		$this->unloadFixtures();
	}

	/**
	 * Test basic row inserting. Response should be the new ID.
	 */
	public function testCreate() {
		$this->loadFixtures('Users');

		$user = new User();
		$data = [
			'country_id' => 1,
			'username' => 'ironman',
			'firstName' => 'Tony',
			'lastName' => 'Stark',
			'password' => '7NAks9193KAkjs1',
			'email' => 'ironman@email.com',
			'age' => 38
		];

		$this->assertEquals(6, $user->create($data));

		$this->assertEquals([
			'id' => 6,
			'country_id' => 1,
			'username' => 'ironman',
			'firstName' => 'Tony',
			'lastName' => 'Stark',
			'password' => '7NAks9193KAkjs1',
			'email' => 'ironman@email.com',
			'age' => 38,
			'created' => '',
			'modified' => ''
		], $user->data);

		// Trying again should throw a unique error on username
		unset($data['id']);

		try {
			$this->assertEquals(7, $user->create($data));
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}

	/**
	 * Test row inserting with one to one relation data.
	 */
	public function testCreateWithOneToOne() {
		$this->loadFixtures(['Users', 'Profiles']);

		$user = new User();
		$data = [
			'country_id' => 1,
			'username' => 'ironman',
			'firstName' => 'Tony',
			'lastName' => 'Stark',
			'password' => '7NAks9193KAkjs1',
			'email' => 'ironman@email.com',
			'age' => 38,
			'Profile' => [
				'lastLogin' => '2012-06-24 17:30:33'
			]
		];

		$this->assertEquals(6, $user->create($data));

		$this->assertEquals([
			'id' => 6,
			'country_id' => 1,
			'username' => 'ironman',
			'firstName' => 'Tony',
			'lastName' => 'Stark',
			'password' => '7NAks9193KAkjs1',
			'email' => 'ironman@email.com',
			'age' => 38,
			'created' => '',
			'modified' => '',
			'Profile' => [
				'id' => 6,
				'user_id' => 6,
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
			$this->assertEquals(7, $user->create($data));
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}

	/**
	 * Test row inserting with one to many relation data.
	 */
	public function testCreateWithOneToMany() {
		$this->loadFixtures(['Series', 'Books']);

		$series = new Series();
		$books = [
			['name' => 'The Bad Beginning'],
			['name' => 'The Reptile Room'],
			['name' => 'The Wide Window'],
			['name' => 'The Miserable Mill'],
			['name' => 'The Austere Academy'],
			['name' => 'The Ersatz Elevator'],
			['name' => 'The Vile Village'],
			['name' => 'The Hostile Hospital'],
			['name' => 'The Carnivorous Carnival'],
			['name' => 'The Slippery Slope'],
			['name' => 'The Grim Grotto'],
			['name' => 'The Penultimate Peril'],
			['name' => 'The End'],
		];

		$data = [
			'name' => 'A Series Of Unfortunate Events',
			'Books' => $books
		];

		$this->assertEquals(4, $series->create($data));

		$this->assertEquals([
			'id' => 4,
			'author_id' => '',
			'name' => 'A Series Of Unfortunate Events',
			'Books' => [
				['id' => 16, 'series_id' => 4, 'name' => 'The Bad Beginning', 'isbn' => '', 'released' => ''],
				['id' => 17, 'series_id' => 4, 'name' => 'The Reptile Room', 'isbn' => '', 'released' => ''],
				['id' => 18, 'series_id' => 4, 'name' => 'The Wide Window', 'isbn' => '', 'released' => ''],
				['id' => 19, 'series_id' => 4, 'name' => 'The Miserable Mill', 'isbn' => '', 'released' => ''],
				['id' => 20, 'series_id' => 4, 'name' => 'The Austere Academy', 'isbn' => '', 'released' => ''],
				['id' => 21, 'series_id' => 4, 'name' => 'The Ersatz Elevator', 'isbn' => '', 'released' => ''],
				['id' => 22, 'series_id' => 4, 'name' => 'The Vile Village', 'isbn' => '', 'released' => ''],
				['id' => 23, 'series_id' => 4, 'name' => 'The Hostile Hospital', 'isbn' => '', 'released' => ''],
				['id' => 24, 'series_id' => 4, 'name' => 'The Carnivorous Carnival', 'isbn' => '', 'released' => ''],
				['id' => 25, 'series_id' => 4, 'name' => 'The Slippery Slope', 'isbn' => '', 'released' => ''],
				['id' => 26, 'series_id' => 4, 'name' => 'The Grim Grotto', 'isbn' => '', 'released' => ''],
				['id' => 27, 'series_id' => 4, 'name' => 'The Penultimate Peril', 'isbn' => '', 'released' => ''],
				['id' => 28, 'series_id' => 4, 'name' => 'The End', 'isbn' => '', 'released' => ''],
			]
		], $series->data);

		// Should throw errors for invalid array structure
		unset($data['id'], $data['Books']);

		$data['Books'] = [
			'name' => 'The Bad Beginning'
		]; // Non numeric array

		try {
			$this->assertEquals(4, $series->create($data));
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}

	/**
	 * Test row inserting with many to many relation data.
	 */
	public function testCreateWithManyToMany() {
		$this->loadFixtures(['Genres', 'Books', 'BookGenres']);

		$book = new Book();
		$data = [
			'series_id' => 1,
			'name' => 'The Winds of Winter',
			'Genres' => [
				['id' => 3, 'name' => 'Action-Adventure'], // Existing genre
				['name' => 'Epic-Horror'], // New genre
				['genre_id' => 8] // Existing genre by ID
			]
		];

		$this->assertEquals(16, $book->create($data));

		$this->assertEquals([
			'id' => 16,
			'series_id' => 1,
			'name' => 'The Winds of Winter',
			'isbn' => '',
			'released' => '',
			'Genres' => [
				[
					'id' => 3,
					'name' => 'Action-Adventure',
					'Junction' => [
						'book_id' => 16,
						'genre_id' => 3,
						'id' => 47
					]
				],
				[
					'id' => 12,
					'name' => 'Epic-Horror',
					'Junction' => [
						'book_id' => 16,
						'genre_id' => 12,
						'id' => 48
					]
				],
				[
					// Data isn't set when using foreign keys
					'Junction' => [
						'book_id' => 16,
						'genre_id' => 8,
						'id' => 49
					]
				]
			]
		], $book->data);
	}

	/**
	 * Test that create fails with empty data.
	 */
	public function testCreateEmptyData() {
		$this->loadFixtures('Users');

		$user = new User();

		$this->assertSame(0, $user->create([]));

		// Relation without data
		try {
			$this->assertSame(0, $user->create([
				'Profile' => [
					'lastLogin' => time()
				]
			]));
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}

	/**
	 * Test inserting multiple records with a single statement.
	 */
	public function testCreateMany() {
		// Dont load fixtures

		$user = new User();
		$user->createTable();

		$this->assertEquals(0, $user->select()->count());

		$this->assertEquals(5, $user->createMany([
			['country_id' => 1, 'username' => 'miles', 'firstName' => 'Miles', 'lastName' => 'Johnson', 'password' => '1Z5895jf72yL77h', 'email' => 'miles@email.com', 'age' => 25, 'created' => '1988-02-26 21:22:34'],
			['country_id' => 3, 'username' => 'batman', 'firstName' => 'Bruce', 'lastName' => 'Wayne', 'created' => '1960-05-11 21:22:34'],
			['country_id' => 2, 'username' => 'superman', 'email' => 'superman@email.com', 'age' => 33, 'created' => '1970-09-18 21:22:34'],
			['country_id' => 5, 'username' => 'spiderman', 'firstName' => 'Peter', 'lastName' => 'Parker', 'password' => '1Z5895jf72yL77h', 'email' => 'spiderman@email.com', 'age' => 22, 'created' => '1990-01-05 21:22:34'],
			['country_id' => 4, 'username' => 'wolverine', 'password' => '1Z5895jf72yL77h', 'email' => 'wolverine@email.com'],
		]));

		$this->assertEquals(5, $user->select()->count());

		$user->query(Query::DROP_TABLE)->save();
	}

}