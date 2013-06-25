<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Data;

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

		$data['id'] = 6;

		$this->assertEquals($data, $user->data);

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

		$data['id'] = 6;
		$data['Profile']['user_id'] = 6;

		$this->assertEquals($data, $user->data);

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

		$data['id'] = 4;

		foreach ($data['Books'] as &$book) {
			$book['series_id'] = 4;
		}

		$this->assertEquals($data, $series->data);

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
		$this->markTestIncomplete('Cannot finish to update() is tested');

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
			'series_id' => 1,
			'name' => 'The Winds of Winter',
			'id' => 16,
			'Genres' => [
				[
					'id' => 3,
					'name' => 'Action-Adventure2',
					'Junction' => ['book_id' => 16, 'genre_id' => 3, 'id' => 47]
				],
				[
					'name' => 'Epic-Horror',
					'id' => 12,
					'Junction' => ['book_id' => 16, 'genre_id' => 12, 'id' => 48]
				],
				[
					'id' => 8,
					'name' => 'Fantasy',
					'Junction' => ['book_id' => 16, 'genre_id' => 8, 'id' => 49]
				]
			]
		], $book->data);
	}

}