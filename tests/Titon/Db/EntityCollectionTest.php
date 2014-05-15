<?php
namespace Titon\Db;

use Titon\Test\TestCase;

/**
 * @property \Titon\Db\EntityCollection $object
 */
class EntityCollectionTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new EntityCollection([
            new Entity(['id' => 1, 'series_id' => 1, 'name' => 'A Game of Thrones', 'isbn' => '0-553-10354-7', 'released' => '1996-08-02']),
            new Entity(['id' => 2, 'series_id' => 1, 'name' => 'A Clash of Kings', 'isbn' => '0-553-10803-4', 'released' => '1999-02-25']),
            new Entity(['id' => 3, 'series_id' => 1, 'name' => 'A Storm of Swords', 'isbn' => '0-553-10663-5', 'released' => '2000-11-11']),
            new Entity(['id' => 4, 'series_id' => 1, 'name' => 'A Feast for Crows', 'isbn' => '0-553-80150-3', 'released' => '2005-11-02']),
            new Entity(['id' => 5, 'series_id' => 1, 'name' => 'A Dance with Dragons', 'isbn' => '0-553-80147-3', 'released' => '2011-07-19']),
            new Entity(['id' => 6, 'series_id' => 2, 'name' => 'Harry Potter and the Philosopher\'s Stone', 'isbn' => '0-7475-3269-9', 'released' => '1997-06-27']),
            new Entity(['id' => 7, 'series_id' => 2, 'name' => 'Harry Potter and the Chamber of Secrets', 'isbn' => '0-7475-3849-2', 'released' => '1998-07-02']),
            new Entity(['id' => 8, 'series_id' => 2, 'name' => 'Harry Potter and the Prisoner of Azkaban', 'isbn' => '0-7475-4215-5', 'released' => '1999-07-09']),
            new Entity(['id' => 9, 'series_id' => 2, 'name' => 'Harry Potter and the Goblet of Fire', 'isbn' => '0-7475-4624-X', 'released' => '2000-07-08']),
            new Entity(['id' => 10, 'series_id' => 2, 'name' => 'Harry Potter and the Order of the Phoenix', 'isbn' => '0-7475-5100-6', 'released' => '2003-06-21']),
            new Entity(['id' => 11, 'series_id' => 2, 'name' => 'Harry Potter and the Half-blood Prince', 'isbn' => '0-7475-8108-8', 'released' => '2005-07-16']),
            new Entity(['id' => 12, 'series_id' => 2, 'name' => 'Harry Potter and the Deathly Hallows', 'isbn' => '0-545-01022-5', 'released' => '2007-07-21']),
            new Entity(['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring', 'isbn' => '', 'released' => '1954-07-24']),
            new Entity(['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers', 'isbn' => '', 'released' => '1954-11-11']),
            new Entity(['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King', 'isbn' => '', 'released' => '1955-10-25']),
        ]);
    }

    public function testFind() {
        $this->assertEquals(new Entity([
            'id' => 3,
            'series_id' => 1,
            'name' => 'A Storm of Swords',
            'isbn' => '0-553-10663-5',
            'released' => '2000-11-11'
        ]), $this->object->find(3));
    }

    public function testFindByKey() {
        $this->assertEquals(new Entity([
            'id' => 3,
            'series_id' => 1,
            'name' => 'A Storm of Swords',
            'isbn' => '0-553-10663-5',
            'released' => '2000-11-11'
        ]), $this->object->find('0-553-10663-5', 'isbn'));
    }

    public function testFindNoRecord() {
        $this->assertEquals(null, $this->object->find(666));
    }

    public function testPluck() {
        $this->assertEquals(range(1, 15), $this->object->pluck());
    }

    public function testPluckByKey() {
        $this->assertEquals([
            '0-553-10354-7',
            '0-553-10803-4',
            '0-553-10663-5',
            '0-553-80150-3',
            '0-553-80147-3',
            '0-7475-3269-9',
            '0-7475-3849-2',
            '0-7475-4215-5',
            '0-7475-4624-X',
            '0-7475-5100-6',
            '0-7475-8108-8',
            '0-545-01022-5',
            '',
            '',
            ''
        ], $this->object->pluck('isbn'));
    }

    public function testSortBy() {
        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'series_id' => 1, 'name' => 'A Clash of Kings', 'isbn' => '0-553-10803-4', 'released' => '1999-02-25']),
            new Entity(['id' => 5, 'series_id' => 1, 'name' => 'A Dance with Dragons', 'isbn' => '0-553-80147-3', 'released' => '2011-07-19']),
            new Entity(['id' => 4, 'series_id' => 1, 'name' => 'A Feast for Crows', 'isbn' => '0-553-80150-3', 'released' => '2005-11-02']),
            new Entity(['id' => 1, 'series_id' => 1, 'name' => 'A Game of Thrones', 'isbn' => '0-553-10354-7', 'released' => '1996-08-02']),
            new Entity(['id' => 3, 'series_id' => 1, 'name' => 'A Storm of Swords', 'isbn' => '0-553-10663-5', 'released' => '2000-11-11']),
            new Entity(['id' => 7, 'series_id' => 2, 'name' => 'Harry Potter and the Chamber of Secrets', 'isbn' => '0-7475-3849-2', 'released' => '1998-07-02']),
            new Entity(['id' => 12, 'series_id' => 2, 'name' => 'Harry Potter and the Deathly Hallows', 'isbn' => '0-545-01022-5', 'released' => '2007-07-21']),
            new Entity(['id' => 9, 'series_id' => 2, 'name' => 'Harry Potter and the Goblet of Fire', 'isbn' => '0-7475-4624-X', 'released' => '2000-07-08']),
            new Entity(['id' => 11, 'series_id' => 2, 'name' => 'Harry Potter and the Half-blood Prince', 'isbn' => '0-7475-8108-8', 'released' => '2005-07-16']),
            new Entity(['id' => 10, 'series_id' => 2, 'name' => 'Harry Potter and the Order of the Phoenix', 'isbn' => '0-7475-5100-6', 'released' => '2003-06-21']),
            new Entity(['id' => 6, 'series_id' => 2, 'name' => 'Harry Potter and the Philosopher\'s Stone', 'isbn' => '0-7475-3269-9', 'released' => '1997-06-27']),
            new Entity(['id' => 8, 'series_id' => 2, 'name' => 'Harry Potter and the Prisoner of Azkaban', 'isbn' => '0-7475-4215-5', 'released' => '1999-07-09']),
            new Entity(['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring', 'isbn' => '', 'released' => '1954-07-24']),
            new Entity(['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King', 'isbn' => '', 'released' => '1955-10-25']),
            new Entity(['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers', 'isbn' => '', 'released' => '1954-11-11']),
        ]), $this->object->sortBy('name'));
    }

    public function testSortByCustomComparator() {
        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 5, 'series_id' => 1, 'name' => 'A Dance with Dragons', 'isbn' => '0-553-80147-3', 'released' => '2011-07-19']),
            new Entity(['id' => 12, 'series_id' => 2, 'name' => 'Harry Potter and the Deathly Hallows', 'isbn' => '0-545-01022-5', 'released' => '2007-07-21']),
            new Entity(['id' => 4, 'series_id' => 1, 'name' => 'A Feast for Crows', 'isbn' => '0-553-80150-3', 'released' => '2005-11-02']),
            new Entity(['id' => 11, 'series_id' => 2, 'name' => 'Harry Potter and the Half-blood Prince', 'isbn' => '0-7475-8108-8', 'released' => '2005-07-16']),
            new Entity(['id' => 10, 'series_id' => 2, 'name' => 'Harry Potter and the Order of the Phoenix', 'isbn' => '0-7475-5100-6', 'released' => '2003-06-21']),
            new Entity(['id' => 3, 'series_id' => 1, 'name' => 'A Storm of Swords', 'isbn' => '0-553-10663-5', 'released' => '2000-11-11']),
            new Entity(['id' => 9, 'series_id' => 2, 'name' => 'Harry Potter and the Goblet of Fire', 'isbn' => '0-7475-4624-X', 'released' => '2000-07-08']),
            new Entity(['id' => 8, 'series_id' => 2, 'name' => 'Harry Potter and the Prisoner of Azkaban', 'isbn' => '0-7475-4215-5', 'released' => '1999-07-09']),
            new Entity(['id' => 2, 'series_id' => 1, 'name' => 'A Clash of Kings', 'isbn' => '0-553-10803-4', 'released' => '1999-02-25']),
            new Entity(['id' => 7, 'series_id' => 2, 'name' => 'Harry Potter and the Chamber of Secrets', 'isbn' => '0-7475-3849-2', 'released' => '1998-07-02']),
            new Entity(['id' => 6, 'series_id' => 2, 'name' => 'Harry Potter and the Philosopher\'s Stone', 'isbn' => '0-7475-3269-9', 'released' => '1997-06-27']),
            new Entity(['id' => 1, 'series_id' => 1, 'name' => 'A Game of Thrones', 'isbn' => '0-553-10354-7', 'released' => '1996-08-02']),
            new Entity(['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King', 'isbn' => '', 'released' => '1955-10-25']),
            new Entity(['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers', 'isbn' => '', 'released' => '1954-11-11']),
            new Entity(['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring', 'isbn' => '', 'released' => '1954-07-24']),
        ]), $this->object->sortBy(function($a, $b) {
            return strtotime($a->released) - strtotime($b->released);
        }, SORT_REGULAR, true));
    }

    public function testToArray() {
        $this->assertEquals([
            ['id' => 1, 'series_id' => 1, 'name' => 'A Game of Thrones', 'isbn' => '0-553-10354-7', 'released' => '1996-08-02'],
            ['id' => 2, 'series_id' => 1, 'name' => 'A Clash of Kings', 'isbn' => '0-553-10803-4', 'released' => '1999-02-25'],
            ['id' => 3, 'series_id' => 1, 'name' => 'A Storm of Swords', 'isbn' => '0-553-10663-5', 'released' => '2000-11-11'],
            ['id' => 4, 'series_id' => 1, 'name' => 'A Feast for Crows', 'isbn' => '0-553-80150-3', 'released' => '2005-11-02'],
            ['id' => 5, 'series_id' => 1, 'name' => 'A Dance with Dragons', 'isbn' => '0-553-80147-3', 'released' => '2011-07-19'],
            ['id' => 6, 'series_id' => 2, 'name' => 'Harry Potter and the Philosopher\'s Stone', 'isbn' => '0-7475-3269-9', 'released' => '1997-06-27'],
            ['id' => 7, 'series_id' => 2, 'name' => 'Harry Potter and the Chamber of Secrets', 'isbn' => '0-7475-3849-2', 'released' => '1998-07-02'],
            ['id' => 8, 'series_id' => 2, 'name' => 'Harry Potter and the Prisoner of Azkaban', 'isbn' => '0-7475-4215-5', 'released' => '1999-07-09'],
            ['id' => 9, 'series_id' => 2, 'name' => 'Harry Potter and the Goblet of Fire', 'isbn' => '0-7475-4624-X', 'released' => '2000-07-08'],
            ['id' => 10, 'series_id' => 2, 'name' => 'Harry Potter and the Order of the Phoenix', 'isbn' => '0-7475-5100-6', 'released' => '2003-06-21'],
            ['id' => 11, 'series_id' => 2, 'name' => 'Harry Potter and the Half-blood Prince', 'isbn' => '0-7475-8108-8', 'released' => '2005-07-16'],
            ['id' => 12, 'series_id' => 2, 'name' => 'Harry Potter and the Deathly Hallows', 'isbn' => '0-545-01022-5', 'released' => '2007-07-21'],
            ['id' => 13, 'series_id' => 3, 'name' => 'The Fellowship of the Ring', 'isbn' => '', 'released' => '1954-07-24'],
            ['id' => 14, 'series_id' => 3, 'name' => 'The Two Towers', 'isbn' => '', 'released' => '1954-11-11'],
            ['id' => 15, 'series_id' => 3, 'name' => 'The Return of the King', 'isbn' => '', 'released' => '1955-10-25'],
        ], $this->object->toArray());
    }

}