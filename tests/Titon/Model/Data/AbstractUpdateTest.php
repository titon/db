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
			'Profile' => [
				'id' => 4,
				'lastLogin' => '2012-06-24 17:30:33',
				'user_id' => 1
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
			'Profile' => [
				'currentLogin' => '2012-06-24 17:30:33',
				'user_id' => 1,
				'id' => 6
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

		$this->assertEquals(['id' => 3] + $data, $series->data);

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

		$data = [
			'id' => 5,
			'series_id' => 1,
			'name' => 'A Dance with Dragons (Updated)',
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
						'id' => 15,
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
		];

		$this->assertEquals($data, $book->data);

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

}