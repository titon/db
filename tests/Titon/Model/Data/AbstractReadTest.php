<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Data;

use Titon\Model\Entity;
use Titon\Test\Stub\Model\Author;
use Titon\Test\Stub\Model\Book;
use Titon\Test\Stub\Model\Genre;
use Titon\Test\Stub\Model\Series;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for database reading.
 *
 * @property \Titon\Model\Driver\Dialect\AbstractDialect $object
 */
abstract class AbstractReadTest extends TestCase {

	/**
	 * Unload fixtures.
	 */
	protected function tearDown() {
		parent::tearDown();

		$this->unloadFixtures();
	}

	/**
	 * Test basic fetching of rows.
	 */
	public function testFetch() {
		$this->loadFixtures('Books');

		$book = new Book();

		// Single
		$this->assertEquals(new Entity([
			'id' => 5,
			'series_id' => 1,
			'name' => 'A Dance with Dragons',
			'isbn' => '0-553-80147-3',
			'released' => '2011-07-19'
		]), $book->select()->where('id', 5)->fetch());

		// Multiple
		$this->assertEquals([
			new Entity([
				'id' => 13,
				'series_id' => 3,
				'name' => 'The Fellowship of the Ring',
				'isbn' => '',
				'released' => '1954-07-24'
			]),
			new Entity([
				'id' => 14,
				'series_id' => 3,
				'name' => 'The Two Towers',
				'isbn' => '',
				'released' => '1954-11-11'
			]),
			new Entity([
				'id' => 15,
				'series_id' => 3,
				'name' => 'The Return of the King',
				'isbn' => '',
				'released' => '1955-10-25'
			]),
		], $book->select()->where('series_id', 3)->fetchAll());
	}

	/**
	 * Test fetching of rows while including one to one (has one) relations.
	 */
	public function testFetchWithOneToOne() {
		$this->loadFixtures(['Authors', 'Series']);

		$author = new Author();

		// Single
		$this->assertEquals(new Entity([
			'id' => 1,
			'name' => 'George R. R. Martin',
			'Series' => new Entity([
				'id' => 1,
				'author_id' => 1,
				'name' => 'A Song of Ice and Fire'
			])
		]), $author->select()->where('id', 1)->with('Series')->fetch());

		// Multiple
		$this->assertEquals([
			new Entity([
				'id' => 1,
				'name' => 'George R. R. Martin',
				'Series' => new Entity([
					'id' => 1,
					'author_id' => 1,
					'name' => 'A Song of Ice and Fire'
				])
			]),
			new Entity([
				'id' => 2,
				'name' => 'J. K. Rowling',
				'Series' => new Entity([
					'id' => 2,
					'author_id' => 2,
					'name' => 'Harry Potter'
				])
			]),
			new Entity([
				'id' => 3,
				'name' => 'J. R. R. Tolkien',
				'Series' => new Entity([
					'id' => 3,
					'author_id' => 3,
					'name' => 'The Lord of the Rings'
				])
			])
		], $author->select()->with('Series')->fetchAll());
	}

	/**
	 * Test fetching of rows while including one to many (has many) relations.
	 */
	public function testFetchWithOneToMany() {
		$this->loadFixtures(['Books', 'Series']);

		$series = new Series();

		// Single
		$this->assertEquals(new Entity([
			'id' => 1,
			'author_id' => 1,
			'name' => 'A Song of Ice and Fire',
			'Books' => [
				new Entity(['id' => 1, 'series_id' => 1, 'name' => 'A Game of Thrones', 'isbn' => '0-553-10354-7', 'released' => '1996-08-02']),
				new Entity(['id' => 2, 'series_id' => 1, 'name' => 'A Clash of Kings', 'isbn' => '0-553-10803-4', 'released' => '1999-02-25']),
				new Entity(['id' => 3, 'series_id' => 1, 'name' => 'A Storm of Swords', 'isbn' => '0-553-10663-5', 'released' => '2000-11-11']),
				new Entity(['id' => 4, 'series_id' => 1, 'name' => 'A Feast for Crows', 'isbn' => '0-553-80150-3', 'released' => '2005-11-02']),
				new Entity(['id' => 5, 'series_id' => 1, 'name' => 'A Dance with Dragons', 'isbn' => '0-553-80147-3', 'released' => '2011-07-19']),
			]
		]), $series->select()->where('id', 1)->with('Books')->fetch());

		// Multiple
		$this->assertEquals([
			new Entity([
				'id' => 3,
				'author_id' => 3,
				'name' => 'The Lord of the Rings',
				'Books' => [
					new Entity(['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring', 'isbn' => '', 'released' => '1954-07-24']),
					new Entity(['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers', 'isbn' => '', 'released' => '1954-11-11']),
					new Entity(['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King', 'isbn' => '', 'released' => '1955-10-25']),
				]
			]),
			new Entity([
				'id' => 2,
				'author_id' => 2,
				'name' => 'Harry Potter',
				'Books' => [
					new Entity(['id' => 6, 'series_id' => 2, 'name' => 'Harry Potter and the Philosopher\'s Stone', 'isbn' => '0-7475-3269-9', 'released' => '1997-06-27']),
					new Entity(['id' => 7, 'series_id' => 2, 'name' => 'Harry Potter and the Chamber of Secrets', 'isbn' => '0-7475-3849-2', 'released' => '1998-07-02']),
					new Entity(['id' => 8, 'series_id' => 2, 'name' => 'Harry Potter and the Prisoner of Azkaban', 'isbn' => '0-7475-4215-5', 'released' => '1999-07-09']),
					new Entity(['id' => 9, 'series_id' => 2, 'name' => 'Harry Potter and the Goblet of Fire', 'isbn' => '0-7475-4624-X', 'released' => '2000-07-08']),
					new Entity(['id' => 10, 'series_id' => 2, 'name' => 'Harry Potter and the Order of the Phoenix', 'isbn' => '0-7475-5100-6', 'released' => '2003-06-21']),
					new Entity(['id' => 11, 'series_id' => 2, 'name' => 'Harry Potter and the Half-blood Prince', 'isbn' => '0-7475-8108-8', 'released' => '2005-07-16']),
					new Entity(['id' => 12, 'series_id' => 2, 'name' => 'Harry Potter and the Deathly Hallows', 'isbn' => '0-545-01022-5', 'released' => '2007-07-21']),
				]
			]),
			new Entity([
				'id' => 1,
				'author_id' => 1,
				'name' => 'A Song of Ice and Fire',
				'Books' => [
					new Entity(['id' => 1, 'series_id' => 1, 'name' => 'A Game of Thrones', 'isbn' => '0-553-10354-7', 'released' => '1996-08-02']),
					new Entity(['id' => 2, 'series_id' => 1, 'name' => 'A Clash of Kings', 'isbn' => '0-553-10803-4', 'released' => '1999-02-25']),
					new Entity(['id' => 3, 'series_id' => 1, 'name' => 'A Storm of Swords', 'isbn' => '0-553-10663-5', 'released' => '2000-11-11']),
					new Entity(['id' => 4, 'series_id' => 1, 'name' => 'A Feast for Crows', 'isbn' => '0-553-80150-3', 'released' => '2005-11-02']),
					new Entity(['id' => 5, 'series_id' => 1, 'name' => 'A Dance with Dragons', 'isbn' => '0-553-80147-3', 'released' => '2011-07-19']),
				]
			])
		], $series->select()->orderBy('id', 'desc')->with('Books')->fetchAll());
	}

	/**
	 * Test fetching of rows while including many to one (belongs to) relations.
	 */
	public function testFetchWithManyToOne() {
		$this->loadFixtures(['Books', 'Series']);

		$book = new Book();

		// Single
		$this->assertEquals(new Entity([
			'id' => 5,
			'series_id' => 1,
			'name' => 'A Dance with Dragons',
			'isbn' => '0-553-80147-3',
			'released' => '2011-07-19',
			'Series' => new Entity([
				'id' => 1,
				'author_id' => 1,
				'name' => 'A Song of Ice and Fire'
			])
		]), $book->select()->where('id', 5)->with('Series')->fetch());

		// Multiple
		$this->assertEquals([
			new Entity([
				'id' => 13,
				'series_id' => 3,
				'name' => 'The Fellowship of the Ring',
				'isbn' => '',
				'released' => '1954-07-24',
				'Series' => new Entity([
					'id' => 3,
					'author_id' => 3,
					'name' => 'The Lord of the Rings'
				])
			]),
			new Entity([
				'id' => 14,
				'series_id' => 3,
				'name' => 'The Two Towers',
				'isbn' => '',
				'released' => '1954-11-11',
				'Series' => new Entity([
					'id' => 3,
					'author_id' => 3,
					'name' => 'The Lord of the Rings'
				])
			]),
			new Entity([
				'id' => 15,
				'series_id' => 3,
				'name' => 'The Return of the King',
				'isbn' => '',
				'released' => '1955-10-25',
				'Series' => new Entity([
					'id' => 3,
					'author_id' => 3,
					'name' => 'The Lord of the Rings'
				])
			]),
		], $book->select()->where('series_id', 3)->with('Series')->fetchAll());
	}

	/**
	 * Test fetching of rows while including many to many (has and belongs to many) relations.
	 */
	public function testFetchWithManyToMany() {
		$this->loadFixtures(['Books', 'Genres', 'BookGenres']);

		$book = new Book();

		// Single
		$actual = $book->select()->where('id', 5)->with('Genres')->fetch();

		$this->assertEquals(new Entity([
			'id' => 5,
			'series_id' => 1,
			'name' => 'A Dance with Dragons',
			'isbn' => '0-553-80147-3',
			'released' => '2011-07-19',
			'Genres' => [
				new Entity([
					'id' => 3,
					'name' => 'Action-Adventure',
					'Junction' => [
						'id' => 15,
						'book_id' => 5,
						'genre_id' => 3
					]
				]),
				new Entity([
					'id' => 5,
					'name' => 'Horror',
					'Junction' => [
						'id' => 16,
						'book_id' => 5,
						'genre_id' => 5
					]
				]),
				new Entity([
					'id' => 8,
					'name' => 'Fantasy',
					'Junction' => [
						'id' => 14,
						'book_id' => 5,
						'genre_id' => 8
					]
				]),
			]
		]), $actual);

		// Multiple
		$actual = $book->select()->where('series_id', 3)->with('Genres')->fetchAll();

		$this->assertEquals([
			new Entity([
				'id' => 13,
				'series_id' => 3,
				'name' => 'The Fellowship of the Ring',
				'isbn' => '',
				'released' => '1954-07-24',
				'Genres' => [
					new Entity([
						'id' => 3,
						'name' => 'Action-Adventure',
						'Junction' => [
							'id' => 39,
							'book_id' => 13,
							'genre_id' => 3
						]
					]),
					new Entity([
						'id' => 6,
						'name' => 'Thriller',
						'Junction' => [
							'id' => 40,
							'book_id' => 13,
							'genre_id' => 6
						]
					]),
					new Entity([
						'id' => 8,
						'name' => 'Fantasy',
						'Junction' => [
							'id' => 38,
							'book_id' => 13,
							'genre_id' => 8
						]
					]),
				]
			]),
			new Entity([
				'id' => 14,
				'series_id' => 3,
				'name' => 'The Two Towers',
				'isbn' => '',
				'released' => '1954-11-11',
				'Genres' => [
					new Entity([
						'id' => 3,
						'name' => 'Action-Adventure',
						'Junction' => [
							'id' => 42,
							'book_id' => 14,
							'genre_id' => 3
						]
					]),
					new Entity([
						'id' => 6,
						'name' => 'Thriller',
						'Junction' => [
							'id' => 43,
							'book_id' => 14,
							'genre_id' => 6
						]
					]),
					new Entity([
						'id' => 8,
						'name' => 'Fantasy',
						'Junction' => [
							'id' => 41,
							'book_id' => 14,
							'genre_id' => 8
						]
					]),
				]
			]),
			new Entity([
				'id' => 15,
				'series_id' => 3,
				'name' => 'The Return of the King',
				'isbn' => '',
				'released' => '1955-10-25',
				'Genres' => [
					new Entity([
						'id' => 3,
						'name' => 'Action-Adventure',
						'Junction' => [
							'id' => 45,
							'book_id' => 15,
							'genre_id' => 3
						]
					]),
					new Entity([
						'id' => 6,
						'name' => 'Thriller',
						'Junction' => [
							'id' => 46,
							'book_id' => 15,
							'genre_id' => 6
						]
					]),
					new Entity([
						'id' => 8,
						'name' => 'Fantasy',
						'Junction' => [
							'id' => 44,
							'book_id' => 15,
							'genre_id' => 8
						]
					]),
				]
			]),
		], $actual);
	}

	/**
	 * Test fetching of rows while including deeply nested relations.
	 */
	public function testFetchWithComplexRelations() {
		$this->loadFixtures(['Books', 'Series', 'Authors', 'Genres', 'BookGenres']);

		$series = new Series();

		// Single
		$actual = $series->select()
			->where('id', 1)
			->with('Author')
			->with('Books', function() {
				$this->with('Genres');
			})
			->fetch();

		$this->assertEquals(new Entity([
			'id' => 1,
			'author_id' => 1,
			'name' => 'A Song of Ice and Fire',
			'Author' => new Entity([
				'id' => 1,
				'name' => 'George R. R. Martin'
			]),
			'Books' => [
				new Entity([
					'id' => 1,
					'series_id' => 1,
					'name' => 'A Game of Thrones',
					'isbn' => '0-553-10354-7',
					'released' => '1996-08-02',
					'Genres' => [
						new Entity([
							'id' => 3,
							'name' => 'Action-Adventure',
							'Junction' => [
								'id' => 2,
								'book_id' => 1,
								'genre_id' => 3
							]
						]),
						new Entity([
							'id' => 5,
							'name' => 'Horror',
							'Junction' => [
								'id' => 3,
								'book_id' => 1,
								'genre_id' => 5
							]
						]),
						new Entity([
							'id' => 8,
							'name' => 'Fantasy',
							'Junction' => [
								'id' => 1,
								'book_id' => 1,
								'genre_id' => 8
							]
						]),
					]
				]),
				new Entity([
					'id' => 2,
					'series_id' => 1,
					'name' => 'A Clash of Kings',
					'isbn' => '0-553-10803-4',
					'released' => '1999-02-25',
					'Genres' => [
						new Entity([
							'id' => 3,
							'name' => 'Action-Adventure',
							'Junction' => [
								'id' => 6,
								'book_id' => 2,
								'genre_id' => 3
							]
						]),
						new Entity([
							'id' => 5,
							'name' => 'Horror',
							'Junction' => [
								'id' => 7,
								'book_id' => 2,
								'genre_id' => 5
							]
						]),
						new Entity([
							'id' => 8,
							'name' => 'Fantasy',
							'Junction' => [
								'id' => 5,
								'book_id' => 2,
								'genre_id' => 8
							]
						]),
					]
				]),
				new Entity([
					'id' => 3,
					'series_id' => 1,
					'name' => 'A Storm of Swords',
					'isbn' => '0-553-10663-5',
					'released' => '2000-11-11',
					'Genres' => [
						new Entity([
							'id' => 3,
							'name' => 'Action-Adventure',
							'Junction' => [
								'id' => 9,
								'book_id' => 3,
								'genre_id' => 3
							]
						]),
						new Entity([
							'id' => 5,
							'name' => 'Horror',
							'Junction' => [
								'id' => 10,
								'book_id' => 3,
								'genre_id' => 5
							]
						]),
						new Entity([
							'id' => 8,
							'name' => 'Fantasy',
							'Junction' => [
								'id' => 8,
								'book_id' => 3,
								'genre_id' => 8
							]
						]),
					]
				]),
				new Entity([
					'id' => 4,
					'series_id' => 1,
					'name' => 'A Feast for Crows',
					'isbn' => '0-553-80150-3',
					'released' => '2005-11-02',
					'Genres' => [
						new Entity([
							'id' => 3,
							'name' => 'Action-Adventure',
							'Junction' => [
								'id' => 12,
								'book_id' => 4,
								'genre_id' => 3
							]
						]),
						new Entity([
							'id' => 5,
							'name' => 'Horror',
							'Junction' => [
								'id' => 13,
								'book_id' => 4,
								'genre_id' => 5
							]
						]),
						new Entity([
							'id' => 8,
							'name' => 'Fantasy',
							'Junction' => [
								'id' => 11,
								'book_id' => 4,
								'genre_id' => 8
							]
						]),
					]
				]),
				new Entity([
					'id' => 5,
					'series_id' => 1,
					'name' => 'A Dance with Dragons',
					'isbn' => '0-553-80147-3',
					'released' => '2011-07-19',
					'Genres' => [
						new Entity([
							'id' => 3,
							'name' => 'Action-Adventure',
							'Junction' => [
								'id' => 15,
								'book_id' => 5,
								'genre_id' => 3
							]
						]),
						new Entity([
							'id' => 5,
							'name' => 'Horror',
							'Junction' => [
								'id' => 16,
								'book_id' => 5,
								'genre_id' => 5
							]
						]),
						new Entity([
							'id' => 8,
							'name' => 'Fantasy',
							'Junction' => [
								'id' => 14,
								'book_id' => 5,
								'genre_id' => 8
							]
						]),
					]
				]),
			]
		]), $actual);
	}

	/**
	 * Test field filtering. Foreign keys and primary keys should always be present even if excluded.
	 */
	public function testFieldFiltering() {
		$this->loadFixtures(['Books', 'Series']);

		$book = new Book();

		$this->assertEquals([
			new Entity(['id' => 1, 'name' => 'A Game of Thrones']),
			new Entity(['id' => 2, 'name' => 'A Clash of Kings']),
			new Entity(['id' => 3, 'name' => 'A Storm of Swords']),
			new Entity(['id' => 4, 'name' => 'A Feast for Crows']),
			new Entity(['id' => 5, 'name' => 'A Dance with Dragons']),
		], $book->select('id', 'name')->where('series_id', 1)->fetchAll());

		// Invalid field
		try {
			$book->select('id', 'name', 'author')->fetchAll();
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}

		// When joining data, the foreign key should be included
		$actual = $book->select('id', 'name')
			->where('series_id', 3)
			->with('Series', function() {
				$this->fields('name'); // Always include the ID
			})
			->fetchAll();

		$this->assertEquals([
			new Entity([
				'id' => 13,
				'name' => 'The Fellowship of the Ring',
				'series_id' => 3,
				'Series' => new Entity([
					'name' => 'The Lord of the Rings',
					'id' => 3
				])
			]),
			new Entity([
				'id' => 14,
				'name' => 'The Two Towers',
				'series_id' => 3,
				'Series' => new Entity([
					'name' => 'The Lord of the Rings',
					'id' => 3
				])
			]),
			new Entity([
				'id' => 15,
				'name' => 'The Return of the King',
				'series_id' => 3,
				'Series' => new Entity([
					'name' => 'The Lord of the Rings',
					'id' => 3
				])
			]),
		], $actual);
	}

	/**
	 * Test group by clause.
	 */
	public function testGrouping() {
		$this->loadFixtures('Books');

		$book = new Book();

		$this->assertEquals([
			new Entity(['id' => 1, 'name' => 'A Game of Thrones']),
			new Entity(['id' => 6, 'name' => 'Harry Potter and the Philosopher\'s Stone']),
			new Entity(['id' => 13, 'name' => 'The Fellowship of the Ring'])
		], $book->select('id', 'name')->groupBy('series_id')->fetchAll());
	}

	/**
	 * Test limit and offset.
	 */
	public function testLimiting() {
		$this->loadFixtures('Genres');

		$genre = new Genre();

		// Limit only
		$this->assertEquals([
			new Entity(['id' => 5, 'name' => 'Horror']),
			new Entity(['id' => 6, 'name' => 'Thriller']),
			new Entity(['id' => 7, 'name' => 'Mystery'])
		], $genre->select()->where('id', 5, '>=')->limit(3)->fetchAll());

		// Limit and offset
		$this->assertEquals([
			new Entity(['id' => 10, 'name' => 'Sci-fi']),
			new Entity(['id' => 11, 'name' => 'Fiction'])
		], $genre->select()->where('id', 7, '>=')->limit(3, 3)->fetchAll());
	}

	/**
	 * Test order by clause.
	 */
	public function testOrdering() {
		$this->loadFixtures('Books');

		$book = new Book();

		$this->assertEquals([
			new Entity(['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King']),
			new Entity(['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers']),
			new Entity(['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring']),
			new Entity(['id' => 12, 'series_id' => 2, 'name' => 'Harry Potter and the Deathly Hallows']),
			new Entity(['id' => 11, 'series_id' => 2, 'name' => 'Harry Potter and the Half-blood Prince']),
			new Entity(['id' => 10, 'series_id' => 2, 'name' => 'Harry Potter and the Order of the Phoenix']),
			new Entity(['id' => 9, 'series_id' => 2, 'name' => 'Harry Potter and the Goblet of Fire']),
			new Entity(['id' => 8, 'series_id' => 2, 'name' => 'Harry Potter and the Prisoner of Azkaban']),
			new Entity(['id' => 7, 'series_id' => 2, 'name' => 'Harry Potter and the Chamber of Secrets']),
			new Entity(['id' => 6, 'series_id' => 2, 'name' => 'Harry Potter and the Philosopher\'s Stone']),
			new Entity(['id' => 5, 'series_id' => 1, 'name' => 'A Dance with Dragons']),
			new Entity(['id' => 4, 'series_id' => 1, 'name' => 'A Feast for Crows']),
			new Entity(['id' => 3, 'series_id' => 1, 'name' => 'A Storm of Swords']),
			new Entity(['id' => 2, 'series_id' => 1, 'name' => 'A Clash of Kings']),
			new Entity(['id' => 1, 'series_id' => 1, 'name' => 'A Game of Thrones']),
		], $book->select('id', 'series_id', 'name')->orderBy('series_id', 'desc')->fetchAll());

		$this->assertEquals([
			new Entity(['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring']),
			new Entity(['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King']),
			new Entity(['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers']),
			new Entity(['id' => 7, 'series_id' => 2, 'name' => 'Harry Potter and the Chamber of Secrets']),
			new Entity(['id' => 12, 'series_id' => 2, 'name' => 'Harry Potter and the Deathly Hallows']),
			new Entity(['id' => 9, 'series_id' => 2, 'name' => 'Harry Potter and the Goblet of Fire']),
			new Entity(['id' => 11, 'series_id' => 2, 'name' => 'Harry Potter and the Half-blood Prince']),
			new Entity(['id' => 10, 'series_id' => 2, 'name' => 'Harry Potter and the Order of the Phoenix']),
			new Entity(['id' => 6, 'series_id' => 2, 'name' => 'Harry Potter and the Philosopher\'s Stone']),
			new Entity(['id' => 8, 'series_id' => 2, 'name' => 'Harry Potter and the Prisoner of Azkaban']),
			new Entity(['id' => 2, 'series_id' => 1, 'name' => 'A Clash of Kings']),
			new Entity(['id' => 5, 'series_id' => 1, 'name' => 'A Dance with Dragons']),
			new Entity(['id' => 4, 'series_id' => 1, 'name' => 'A Feast for Crows']),
			new Entity(['id' => 1, 'series_id' => 1, 'name' => 'A Game of Thrones']),
			new Entity(['id' => 3, 'series_id' => 1, 'name' => 'A Storm of Swords']),
		], $book->select('id', 'series_id', 'name')->orderBy([
			'series_id' => 'desc',
			'name' => 'asc'
		])->fetchAll());

		// Randomizing
		$this->assertNotEquals([
			new Entity(['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King']),
			new Entity(['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers']),
			new Entity(['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring']),
			new Entity(['id' => 12, 'series_id' => 2, 'name' => 'Harry Potter and the Deathly Hallows']),
			new Entity(['id' => 11, 'series_id' => 2, 'name' => 'Harry Potter and the Half-blood Prince']),
			new Entity(['id' => 10, 'series_id' => 2, 'name' => 'Harry Potter and the Order of the Phoenix']),
			new Entity(['id' => 9, 'series_id' => 2, 'name' => 'Harry Potter and the Goblet of Fire']),
			new Entity(['id' => 8, 'series_id' => 2, 'name' => 'Harry Potter and the Prisoner of Azkaban']),
			new Entity(['id' => 7, 'series_id' => 2, 'name' => 'Harry Potter and the Chamber of Secrets']),
			new Entity(['id' => 6, 'series_id' => 2, 'name' => 'Harry Potter and the Philosopher\'s Stone']),
			new Entity(['id' => 5, 'series_id' => 1, 'name' => 'A Dance with Dragons']),
			new Entity(['id' => 4, 'series_id' => 1, 'name' => 'A Feast for Crows']),
			new Entity(['id' => 3, 'series_id' => 1, 'name' => 'A Storm of Swords']),
			new Entity(['id' => 2, 'series_id' => 1, 'name' => 'A Clash of Kings']),
			new Entity(['id' => 1, 'series_id' => 1, 'name' => 'A Game of Thrones']),
		], $book->select('id', 'series_id', 'name')->orderBy('RAND')->fetchAll());
	}

}