<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Data;

use Titon\Db\Driver\AbstractPdoDriver;
use Titon\Db\Entity;
use Titon\Db\EntityCollection;
use Titon\Db\Query\Func;
use Titon\Db\Query;
use Titon\Db\Query\SubQuery;
use Titon\Test\Stub\Repository\Author;
use Titon\Test\Stub\Repository\Book;
use Titon\Test\Stub\Repository\Category;
use Titon\Test\Stub\Repository\Genre;
use Titon\Test\Stub\Repository\Order;
use Titon\Test\Stub\Repository\Series;
use Titon\Test\Stub\Repository\Stat;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for database reading.
 */
class AbstractReadTest extends TestCase {

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
        ]), $book->select()->where('id', 5)->first());

        // Multiple
        $this->assertEquals(new EntityCollection([
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
        ]), $book->select()->where('series_id', 3)->orderBy('id', 'asc')->all());
    }

    /**
     * Test expressions in select statements.
     */
    public function testSelectExpression() {
        $this->loadFixtures('Stats');

        $stat = new Stat();

        $query = $stat->select();
        $query->fields([
            'name as  role',
            $query->expr('name', Query\Expr::AS_ALIAS, 'class')
        ]);

        $this->assertEquals(new EntityCollection([
            new Entity(['role' => 'Warrior', 'class' => 'Warrior']),
            new Entity(['role' => 'Ranger', 'class' => 'Ranger']),
            new Entity(['role' => 'Mage', 'class' => 'Mage']),
        ]), $query->all());
    }

    /**
     * Test expressions in select statements.
     */
    public function testSelectRawExpression() {
        $this->loadFixtures('Stats');

        $stat = new Stat();

        // In place of expr()
        $query = $stat->select();
        $query->fields([
            'name AS role',
            Query::raw('`name` AS `class`')
        ]);

        $this->assertEquals(new EntityCollection([
            new Entity(['role' => 'Warrior', 'class' => 'Warrior']),
            new Entity(['role' => 'Ranger', 'class' => 'Ranger']),
            new Entity(['role' => 'Mage', 'class' => 'Mage']),
        ]), $query->all());

        // In place of func()
        $query = $stat->select();
        $query->fields([
            Query::raw('SUBSTR(`name`, 1, 3) as `shortName`')
        ]);

        $this->assertEquals(new EntityCollection([
            new Entity(['shortName' => 'War']),
            new Entity(['shortName' => 'Ran']),
            new Entity(['shortName' => 'Mag']),
        ]), $query->all());
    }

    /**
     * Test functions in select statements.
     */
    public function testSelectFunctions() {
        $this->loadFixtures('Stats');

        $stat = new Stat();

        // SUM
        $query = $stat->select();
        $query->fields([
            $query->func('SUM', ['health' => Func::FIELD])->asAlias('sum')
        ]);

        $this->assertEquals(new Entity(['sum' => 2900]), $query->first());

        // SUBSTRING
        $query = $stat->select();
        $query->fields([
            $query->func('SUBSTR', ['name' => Func::FIELD, 1, 3])->asAlias('shortName')
        ]);

        $this->assertEquals(new EntityCollection([
            new Entity(['shortName' => 'War']),
            new Entity(['shortName' => 'Ran']),
            new Entity(['shortName' => 'Mag']),
        ]), $query->all());

        // SUBSTRING as field in where
        $query = $stat->select('id', 'name');
        $query->where(
            $query->func('SUBSTR', ['name' => Func::FIELD, -3]),
            'ior'
        );

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'name' => 'Warrior'])
        ]), $query->all());
    }

    /**
     * Test row counting.
     */
    public function testSelectCount() {
        $this->loadFixtures('Books');

        $book = new Book();

        $query = $book->select();
        $this->assertEquals(15, $query->count());

        $query->where('series_id', 2);
        $this->assertEquals(7, $query->count());

        $query->where('name', 'like', '%prince%');
        $this->assertEquals(1, $query->count());
    }

    /**
     * Test LIKE and NOT LIKE clauses.
     */
    public function testSelectLike() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'username' => 'superman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
        ]), $user->select('id', 'username')->where('username', 'like', '%man%')->orderBy('id', 'asc')->all());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $user->select('id', 'username')->where('username', 'notLike', '%man%')->orderBy('id', 'asc')->all());
    }

    /**
     * Test REGEXP and NOT REGEXP clauses.
     */
    public function testSelectRegexp() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'username' => 'superman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
        ]), $user->select('id', 'username')->where('username', 'regexp', 'man$')->orderBy('id', 'asc')->all());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $user->select('id', 'username')->where('username', 'notRegexp', 'man$')->all());
    }

    /**
     * Test IN and NOT IN clauses.
     */
    public function testSelectIn() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles']),
            new Entity(['id' => 3, 'username' => 'superman']),
        ]), $user->select('id', 'username')->where('id', 'in', [1, 3, 10])->all()); // use fake 10

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $user->select('id', 'username')->where('id', 'notIn', [1, 3, 10])->all());
    }

    /**
     * Test BETWEEN and NOT BETWEEN clauses.
     */
    public function testSelectBetween() {
        $this->loadFixtures('Users');

        $user = new User();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'username' => 'superman']),
        ]), $user->select('id', 'username')->where('age', 'between', [30, 45])->all());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $user->select('id', 'username')->where('age', 'notBetween', [30, 45])->all());
    }

    /**
     * Test IS NULL and NOT NULL clauses.
     */
    public function testSelectNull() {
        $this->loadFixtures('Users');

        $user = new User();
        $user->query(Query::UPDATE)->fields(['created' => null])->where('country_id', 1)->save();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'username' => 'miles'])
        ]), $user->select('id', 'username')->where('created', 'isNull', null)->all());

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'username' => 'batman']),
            new Entity(['id' => 3, 'username' => 'superman']),
            new Entity(['id' => 4, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'username' => 'wolverine'])
        ]), $user->select('id', 'username')->where('created', 'isNotNull', null)->orderBy('id', 'asc')->all());
    }

    /**
     * Test field filtering. Foreign keys and primary keys should always be present even if excluded.
     */
    public function testFieldFiltering() {
        $this->loadFixtures(['Books', 'Series']);

        $book = new Book();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'name' => 'A Game of Thrones']),
            new Entity(['id' => 2, 'name' => 'A Clash of Kings']),
            new Entity(['id' => 3, 'name' => 'A Storm of Swords']),
            new Entity(['id' => 4, 'name' => 'A Feast for Crows']),
            new Entity(['id' => 5, 'name' => 'A Dance with Dragons']),
        ]), $book->select('id', 'name')->where('series_id', 1)->orderBy('id', 'asc')->all());

        // Invalid field
        try {
            $book->select('id', 'name', 'author')->all();
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $actual = $book->select('id', 'name', 'series_id')
            ->where('series_id', 3)
            ->leftJoin(['series', 'Series'], ['name'], ['Book.series_id' => 'Series.id'])
            ->orderBy('id', 'asc')
            ->all(['eager' => true]);

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 13,
                'name' => 'The Fellowship of the Ring',
                'series_id' => 3,
                'Series' => new Entity([
                    'name' => 'The Lord of the Rings'
                ])
            ]),
            new Entity([
                'id' => 14,
                'name' => 'The Two Towers',
                'series_id' => 3,
                'Series' => new Entity([
                    'name' => 'The Lord of the Rings'
                ])
            ]),
            new Entity([
                'id' => 15,
                'name' => 'The Return of the King',
                'series_id' => 3,
                'Series' => new Entity([
                    'name' => 'The Lord of the Rings'
                ])
            ]),
        ]), $actual);
    }

    /**
     * Test group by clause.
     */
    public function testGrouping() {
        $this->loadFixtures('Books');

        $book = new Book();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'name' => 'A Game of Thrones']),
            new Entity(['id' => 6, 'name' => 'Harry Potter and the Philosopher\'s Stone']),
            new Entity(['id' => 13, 'name' => 'The Fellowship of the Ring'])
        ]), $book->select('id', 'name')->groupBy('series_id')->orderBy('id', 'asc')->all());
    }

    /**
     * Test limit and offset.
     */
    public function testLimiting() {
        $this->loadFixtures('Genres');

        $genre = new Genre();

        // Limit only
        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 5, 'name' => 'Horror']),
            new Entity(['id' => 6, 'name' => 'Thriller']),
            new Entity(['id' => 7, 'name' => 'Mystery'])
        ]), $genre->select('id', 'name')->where('id', '>=', 5)->limit(3)->all());

        // Limit and offset
        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 10, 'name' => 'Sci-fi']),
            new Entity(['id' => 11, 'name' => 'Fiction'])
        ]), $genre->select('id', 'name')->where('id', '>=', 7)->limit(3, 3)->all());
    }

    /**
     * Test order by clause.
     */
    public function testOrdering() {
        $this->loadFixtures('Books');

        $book = new Book();

        $this->assertEquals(new EntityCollection([
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
        ]), $book->select('id', 'series_id', 'name')->orderBy([
            'series_id' => 'desc',
            'id' => 'desc'
        ])->all());

        $this->assertEquals(new EntityCollection([
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
        ]), $book->select('id', 'series_id', 'name')->orderBy([
            'series_id' => 'desc',
            'name' => 'asc'
        ])->all());

        // Randomizing
        $this->assertNotEquals(new EntityCollection([
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
        ]), $book->select('id', 'series_id', 'name')->orderBy('RAND')->all());
    }

    /**
     * Test where predicates using AND conjunction.
     */
    public function testWhereAnd() {
        $this->loadFixtures('Stats');

        $stat = new Stat();

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 2,
                'name' => 'Ranger',
                'health' => 800,
                'isMelee' => false
            ])
        ]), $stat->select('id', 'name', 'health', 'isMelee')
            ->where('isMelee', false)
            ->where('health', '>=', 700)
            ->all());

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 2,
                'name' => 'Ranger',
                'health' => 800,
                'energy' => 335,
                'range' => 6.75
            ]),
            new Entity([
                'id' => 3,
                'name' => 'Mage',
                'health' => 600,
                'energy' => 600,
                'range' => 8.33
            ])
        ]), $stat->select('id', 'name', 'health', 'energy', 'range')
            ->where('health', '<', 1000)
            ->where('range', '>=', 5)
            ->where('energy', '!=', 0)
            ->all());

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'name' => 'Warrior',
                'health' => 1500,
                'isMelee' => true,
                'range' => 1
            ])
        ]), $stat->select('id', 'name', 'health', 'isMelee', 'range')
            ->where(function(Query\Predicate $where) {
                $where->gte('health', 500)->lte('range', 7)->eq('isMelee', true);
            })->all());
    }

    /**
     * Test where predicates using OR conjunction.
     */
    public function testWhereOr() {
        $this->loadFixtures('Stats');

        $stat = new Stat();

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'name' => 'Warrior',
                'health' => 1500,
                'range' => 1
            ]),
            new Entity([
                'id' => 3,
                'name' => 'Mage',
                'health' => 600,
                'range' => 8.33
            ])
        ]), $stat->select('id', 'name', 'health', 'range')
            ->orWhere('health', '>', 1000)
            ->orWhere('range', '>', 7)
            ->all());

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'name' => 'Warrior',
                'damage' => 125.25,
                'defense' => 55.75,
                'range' => 1
            ]),
            new Entity([
                'id' => 2,
                'name' => 'Ranger',
                'damage' => 90.45,
                'defense' => 30.5,
                'range' => 6.75
            ]),
            new Entity([
                'id' => 3,
                'name' => 'Mage',
                'damage' => 55.84,
                'defense' => 40.15,
                'range' => 8.33
            ])
        ]), $stat->select('id', 'name', 'damage', 'defense', 'range')
            ->orWhere(function(Query\Predicate $where) {
                $where->gt('damage', 100)->gt('range', 5)->gt('defense', 50);
            })
            ->all());
    }

    /**
     * Test nested where predicates.
     */
    public function testWhereNested() {
        $this->loadFixtures('Stats');

        $stat = new Stat();

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 3, 'name' => 'Mage'])
        ]), $stat->select('id', 'name')
            ->where(function(Query\Predicate $where) {
                $where->eq('isMelee', false);
                $where->either(function(Query\Predicate $where2) {
                    $where2->lte('health', 600)->lte('damage', 60);
                });
            })->all());
    }

    /**
     * Test having predicates using AND conjunction.
     */
    public function testHavingAnd() {
        $this->loadFixtures('Orders');

        $order = new Order();
        $query = $order->select();
        $query
            ->fields([
                'id', 'user_id', 'quantity', 'status', 'shipped',
                $query->func('SUM', ['quantity' => 'field'])->asAlias('qty'),
                $query->func('COUNT', ['user_id' => 'field'])->asAlias('count')
            ])
            ->groupBy('user_id');

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'user_id' => 1, 'quantity' => 15, 'status' => 'pending', 'shipped' => null, 'qty' => 97, 'count' => 5]),
            new Entity(['id' => 2, 'user_id' => 2, 'quantity' => 33, 'status' => 'pending', 'shipped' => null, 'qty' => 77, 'count' => 5]),
            new Entity(['id' => 3, 'user_id' => 3, 'quantity' => 4, 'status' => 'pending', 'shipped' => null, 'qty' => 90, 'count' => 7]),
            new Entity(['id' => 4, 'user_id' => 4, 'quantity' => 24, 'status' => 'pending', 'shipped' => null, 'qty' => 114, 'count' => 7]),
            new Entity(['id' => 5, 'user_id' => 5, 'quantity' => 29, 'status' => 'pending', 'shipped' => null, 'qty' => 112, 'count' => 6]),
        ]), $query->all());

        $query->having('qty', '>', 100);

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 4, 'user_id' => 4, 'quantity' => 24, 'status' => 'pending', 'shipped' => null, 'qty' => 114, 'count' => 7]),
            new Entity(['id' => 5, 'user_id' => 5, 'quantity' => 29, 'status' => 'pending', 'shipped' => null, 'qty' => 112, 'count' => 6]),
        ]), $query->all());

        $query->having('count', '>', 6);

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 4, 'user_id' => 4, 'quantity' => 24, 'status' => 'pending', 'shipped' => null, 'qty' => 114, 'count' => 7])
        ]), $query->all());
    }

    /**
     * Test having predicates using AND conjunction.
     */
    public function testHavingOr() {
        $this->loadFixtures('Orders');

        $order = new Order();
        $query = $order->select();
        $query
            ->fields([
                'id', 'user_id', 'quantity', 'status', 'shipped',
                $query->func('SUM', ['quantity' => 'field'])->asAlias('qty'),
                $query->func('COUNT', ['user_id' => 'field'])->asAlias('count')
            ])
            ->groupBy('user_id');

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'user_id' => 1, 'quantity' => 15, 'status' => 'pending', 'shipped' => null, 'qty' => 97, 'count' => 5]),
            new Entity(['id' => 2, 'user_id' => 2, 'quantity' => 33, 'status' => 'pending', 'shipped' => null, 'qty' => 77, 'count' => 5]),
            new Entity(['id' => 3, 'user_id' => 3, 'quantity' => 4, 'status' => 'pending', 'shipped' => null, 'qty' => 90, 'count' => 7]),
            new Entity(['id' => 4, 'user_id' => 4, 'quantity' => 24, 'status' => 'pending', 'shipped' => null, 'qty' => 114, 'count' => 7]),
            new Entity(['id' => 5, 'user_id' => 5, 'quantity' => 29, 'status' => 'pending', 'shipped' => null, 'qty' => 112, 'count' => 6]),
        ]), $query->all());

        $query->orHaving('qty', '<=', 90);

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'user_id' => 2, 'quantity' => 33, 'status' => 'pending', 'shipped' => null, 'qty' => 77, 'count' => 5]),
            new Entity(['id' => 3, 'user_id' => 3, 'quantity' => 4, 'status' => 'pending', 'shipped' => null, 'qty' => 90, 'count' => 7]),
        ]), $query->all());

        $query->orHaving('count', '>=', 6);

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 2, 'user_id' => 2, 'quantity' => 33, 'status' => 'pending', 'shipped' => null, 'qty' => 77, 'count' => 5]),
            new Entity(['id' => 3, 'user_id' => 3, 'quantity' => 4, 'status' => 'pending', 'shipped' => null, 'qty' => 90, 'count' => 7]),
            new Entity(['id' => 4, 'user_id' => 4, 'quantity' => 24, 'status' => 'pending', 'shipped' => null, 'qty' => 114, 'count' => 7]),
            new Entity(['id' => 5, 'user_id' => 5, 'quantity' => 29, 'status' => 'pending', 'shipped' => null, 'qty' => 112, 'count' => 6]),
        ]), $query->all());
    }

    /**
     * Test nested having predicates.
     */
    public function testHavingNested() {
        $this->loadFixtures('Orders');

        $order = new Order();
        $query = $order->select();
        $query
            ->fields([
                'id', 'user_id', 'quantity', 'status', 'shipped',
                $query->func('SUM', ['quantity' => 'field'])->asAlias('qty'),
                $query->func('COUNT', ['user_id' => 'field'])->asAlias('count')
            ])
            ->where('status', '!=', 'pending')
            ->groupBy('user_id')
            ->having(function(Query\Predicate $having) {
                $having->between('qty', 40, 50);
                $having->either(function(Query\Predicate $having2) {
                    $having2->eq('status', 'shipped');
                    $having2->eq('status', 'delivered');
                });
            });

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 21, 'user_id' => 1, 'quantity' => 17, 'status' => 'delivered', 'shipped' => '2013-05-27 12:33:02', 'qty' => 49, 'count' => 3]),
            new Entity(['id' => 17, 'user_id' => 2, 'quantity' => 26, 'status' => 'shipped', 'shipped' => '2013-06-28 12:33:02', 'qty' => 41, 'count' => 2]),
            new Entity(['id' => 19, 'user_id' => 4, 'quantity' => 20, 'status' => 'delivered', 'shipped' => '2013-06-30 12:33:02', 'qty' => 40, 'count' => 3]),
        ]), $query->all());
    }

    /**
     * Test that inner join fetches data.
     */
    public function testInnerJoin() {
        $this->loadFixtures(['Users', 'Countries']);

        $user = new User();
        $user->update([2, 5], ['country_id' => null]); // Reset some records

        $query = $user->select('id', 'username')
            ->innerJoin(['countries', 'Country'], ['id', 'name', 'iso'], ['User.country_id' => 'Country.id'])
            ->orderBy('User.id', 'asc');

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'username' => 'miles',
                'Country' => new Entity([
                    'id' => 1,
                    'name' => 'United States of America',
                    'iso' => 'USA'
                ])
            ]),
            new Entity([
                'id' => 3,
                'username' => 'superman',
                'Country' => new Entity([
                    'id' => 2,
                    'name' => 'Canada',
                    'iso' => 'CAN'
                ])
            ]),
            new Entity([
                'id' => 4,
                'username' => 'spiderman',
                'Country' => new Entity([
                    'id' => 5,
                    'name' => 'Mexico',
                    'iso' => 'MEX'
                ])
            ])
        ]), $query->all());
    }

    public function testInnerJoinCustom() {
        $this->loadFixtures(['Users', 'Countries']);

        $user = new User();
        $user->update([2, 5], ['country_id' => null]); // Reset some records

        $query = $user->select('id', 'username')
            ->innerJoin(['countries', 'Country'], ['id', 'name', 'iso'], ['country_id' => 'Country.id'])
            ->orderBy('User.id', 'asc');

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'username' => 'miles',
                'Country' => new Entity([
                    'id' => 1,
                    'name' => 'United States of America',
                    'iso' => 'USA'
                ])
            ]),
            new Entity([
                'id' => 3,
                'username' => 'superman',
                'Country' => new Entity([
                    'id' => 2,
                    'name' => 'Canada',
                    'iso' => 'CAN'
                ])
            ]),
            new Entity([
                'id' => 4,
                'username' => 'spiderman',
                'Country' => new Entity([
                    'id' => 5,
                    'name' => 'Mexico',
                    'iso' => 'MEX'
                ])
            ])
        ]), $query->all());
    }

    /**
     * Test that outer join fetches data.
     */
    public function testOuterJoin() {
        $this->loadFixtures(['Users', 'Countries']);

        $user = new User();
        $user->update([2, 5], ['country_id' => null]); // Reset some records

        if ($user->getDriver() instanceof AbstractPdoDriver) {
            if ($user->getDriver()->getDriver() === 'mysql') {
                $this->markTestSkipped('MySQL does not support outer joins');
            }
        }

        $query = $user->select()
            ->fields('id', 'username')
            ->outerJoin(['countries', 'Country'], ['id', 'name', 'iso'], ['country_id' => 'Country.id'])
            ->orderBy('User.id', 'asc');

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'username' => 'miles',
                'Country' => new Entity([
                    'id' => 1,
                    'name' => 'United States of America',
                    'iso' => 'USA'
                ])
            ]),
            new Entity([
                'id' => 2,
                'username' => 'batman',
                'Country' => new Entity([
                    'id' => null,
                    'name' => null,
                    'iso' => null
                ])
            ]),
            new Entity([
                'id' => 3,
                'username' => 'superman',
                'Country' => new Entity([
                    'id' => 2,
                    'name' => 'Canada',
                    'iso' => 'CAN'
                ])
            ]),
            new Entity([
                'id' => 4,
                'username' => 'spiderman',
                'Country' => new Entity([
                    'id' => 5,
                    'name' => 'Mexico',
                    'iso' => 'MEX'
                ])
            ]),
            new Entity([
                'id' => 5,
                'username' => 'wolverine',
                'Country' => new Entity([
                    'id' => null,
                    'name' => null,
                    'iso' => null
                ])
            ]),
            new Entity([
                'id' => null,
                'username' => null,
                'Country' => new Entity([
                    'id' => 4,
                    'name' => 'Australia',
                    'iso' => 'AUS'
                ])
            ]),
            new Entity([
                'id' => null,
                'username' => null,
                'Country' => new Entity([
                    'id' => 3,
                    'name' => 'England',
                    'iso' => 'ENG'
                ])
            ]),
        ]), $query->all());
    }

    /**
     * Test that left join fetches data.
     */
    public function testLeftJoin() {
        $this->loadFixtures(['Users', 'Countries']);

        $user = new User();
        $user->update([2, 5], ['country_id' => null]); // Reset some records

        $query = $user->select('id', 'username')
            ->leftJoin(['countries', 'Country'], ['id', 'name', 'iso'], ['country_id' => 'Country.id'])
            ->orderBy('User.id', 'asc');

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'username' => 'miles',
                'Country' => new Entity([
                    'id' => 1,
                    'name' => 'United States of America',
                    'iso' => 'USA'
                ])
            ]),
            // Empty country
            new Entity([
                'id' => 2,
                'username' => 'batman',
                'Country' => new Entity([
                    'id' => null,
                    'name' => null,
                    'iso' => null
                ])
            ]),
            new Entity([
                'id' => 3,
                'username' => 'superman',
                'Country' => new Entity([
                    'id' => 2,
                    'name' => 'Canada',
                    'iso' => 'CAN'
                ])
            ]),
            new Entity([
                'id' => 4,
                'username' => 'spiderman',
                'Country' => new Entity([
                    'id' => 5,
                    'name' => 'Mexico',
                    'iso' => 'MEX'
                ])
            ]),
            // Empty country
            new Entity([
                'id' => 5,
                'username' => 'wolverine',
                'Country' => new Entity([
                    'id' => null,
                    'name' => null,
                    'iso' => null
                ])
            ]),
        ]), $query->all());
    }

    /**
     * Test that right join fetches data.
     */
    public function testRightJoin() {
        $this->loadFixtures(['Users', 'Countries']);

        $user = new User();
        $user->update([2, 5], ['country_id' => null]); // Reset some records

        $query = $user->select('id', 'username')
            ->rightJoin(['countries', 'Country'], ['id', 'name', 'iso'], ['country_id' => 'Country.id'])
            ->orderBy('User.id', 'asc');

        $this->assertEquals(new EntityCollection([
            // Empty user
            new Entity([
                'id' => null,
                'username' => null,
                'Country' => new Entity([
                    'id' => 3,
                    'name' => 'England',
                    'iso' => 'ENG'
                ])
            ]),
            // Empty user
            new Entity([
                'id' => null,
                'username' => null,
                'Country' => new Entity([
                    'id' => 4,
                    'name' => 'Australia',
                    'iso' => 'AUS'
                ])
            ]),
            new Entity([
                'id' => 1,
                'username' => 'miles',
                'Country' => new Entity([
                    'id' => 1,
                    'name' => 'United States of America',
                    'iso' => 'USA'
                ])
            ]),
            new Entity([
                'id' => 3,
                'username' => 'superman',
                'Country' => new Entity([
                    'id' => 2,
                    'name' => 'Canada',
                    'iso' => 'CAN'
                ])
            ]),
            new Entity([
                'id' => 4,
                'username' => 'spiderman',
                'Country' => new Entity([
                    'id' => 5,
                    'name' => 'Mexico',
                    'iso' => 'MEX'
                ])
            ])
        ]), $query->all());
    }

    /**
     * Test that straight join fetches data.
     */
    public function testStraightJoin() {
        $this->loadFixtures(['Users', 'Countries']);

        $user = new User();
        $user->update([2, 5], ['country_id' => null]); // Reset some records

        $query = $user->select('id', 'username')
            ->straightJoin(['countries', 'Country'], ['id', 'name', 'iso'], ['country_id' => 'Country.id'])
            ->orderBy('User.id', 'asc');

        $this->assertEquals(new EntityCollection([
            new Entity([
                'id' => 1,
                'username' => 'miles',
                'Country' => new Entity([
                    'id' => 1,
                    'name' => 'United States of America',
                    'iso' => 'USA'
                ])
            ]),
            new Entity([
                'id' => 3,
                'username' => 'superman',
                'Country' => new Entity([
                    'id' => 2,
                    'name' => 'Canada',
                    'iso' => 'CAN'
                ])
            ]),
            new Entity([
                'id' => 4,
                'username' => 'spiderman',
                'Country' => new Entity([
                    'id' => 5,
                    'name' => 'Mexico',
                    'iso' => 'MEX'
                ])
            ])
        ]), $query->all());
    }

    /**
     * Test joins with function aliasing.
     */
    public function testJoinWithFunction() {
        $this->loadFixtures(['Users', 'Countries']);

        $user = new User();
        $query = $user->select()->where('User.id', 1);
        $query->fields([
            'id', 'username',
            $query->func('SUBSTR', ['username' => Func::FIELD, 1, 3])->asAlias('shortName')
        ]);
        $query->leftJoin(['countries', 'Country'], [
            'id', 'name', 'iso',
            $query->func('SUBSTR', ['Country.name' => Func::FIELD, 1, 6])->asAlias('countryName')
        ], ['country_id' => 'Country.id']);

        $this->assertEquals(new Entity([
            'id' => 1,
            'username' => 'miles',
            'shortName' => 'mil',
            'countryName' => 'United',
            'Country' => new Entity([
                'id' => 1,
                'name' => 'United States of America',
                'iso' => 'USA'
            ])
        ]), $query->first());
    }

    /**
     * Test joining on the same table works.
     */
    public function testSelfJoin() {
        $this->loadFixtures('Categories');

        $category = new Category();

        $query = $category->select()
            ->leftJoin(['categories', 'Parent'], ['id', 'parent_id', 'name', 'left', 'right'], ['Category.parent_id' => 'Parent.id'])->where('Category.id', 2);

        $this->assertEquals(new Entity([
            'id' => 2,
            'parent_id' => 1,
            'name' => 'Banana',
            'left' => 2,
            'right' => 3,
            'Parent' => new Entity([
                'id' => 1,
                'parent_id' => null,
                'name' => 'Fruit',
                'left' => 1,
                'right' => 20,
            ])
        ]), $query->first());
    }

    /**
     * Test unions merge multiple selects.
     */
    public function testUnions() {
        $this->loadFixtures(['Users', 'Books', 'Authors']);

        $user = new User();
        $query = $user->select('username AS name');
        $query->union($query->subQuery('name')->from('books')->where('series_id', 1));
        $query->union($query->subQuery('name')->from('authors'));

        $this->assertEquals(new EntityCollection([
            new Entity(['name' => 'batman']),
            new Entity(['name' => 'miles']),
            new Entity(['name' => 'spiderman']),
            new Entity(['name' => 'superman']),
            new Entity(['name' => 'wolverine']),
            new Entity(['name' => 'A Game of Thrones']),
            new Entity(['name' => 'A Clash of Kings']),
            new Entity(['name' => 'A Storm of Swords']),
            new Entity(['name' => 'A Feast for Crows']),
            new Entity(['name' => 'A Dance with Dragons']),
            new Entity(['name' => 'George R. R. Martin']),
            new Entity(['name' => 'J. K. Rowling']),
            new Entity(['name' => 'J. R. R. Tolkien']),
        ]), $query->all());

        $query->orderBy('name', 'desc')->limit(10);

        $this->assertEquals(new EntityCollection([
            new Entity(['name' => 'wolverine']),
            new Entity(['name' => 'superman']),
            new Entity(['name' => 'spiderman']),
            new Entity(['name' => 'miles']),
            new Entity(['name' => 'J. R. R. Tolkien']),
            new Entity(['name' => 'J. K. Rowling']),
            new Entity(['name' => 'George R. R. Martin']),
            new Entity(['name' => 'batman']),
            new Entity(['name' => 'A Storm of Swords']),
            new Entity(['name' => 'A Game of Thrones']),
        ]), $query->all());
    }

    /**
     * Test that sub-queries return results.
     */
    public function testSubQueries() {
        $this->loadFixtures(['Users', 'Profiles', 'Countries']);

        $user = new User();

        // ANY filter
        $query = $user->select('id', 'country_id', 'username');
        $query->where('country_id', '=', $query->subQuery('id')->from('countries')->withFilter(SubQuery::ANY))->orderBy('id', 'asc');

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles']),
            new Entity(['id' => 2, 'country_id' => 3, 'username' => 'batman']),
            new Entity(['id' => 3, 'country_id' => 2, 'username' => 'superman']),
            new Entity(['id' => 4, 'country_id' => 5, 'username' => 'spiderman']),
            new Entity(['id' => 5, 'country_id' => 4, 'username' => 'wolverine']),
        ]), $query->all());

        // Single record
        $query = $user->select('id', 'country_id', 'username');
        $query->where('country_id', '=', $query->subQuery('id')->from('countries')->where('iso', 'USA'));

        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'country_id' => 1, 'username' => 'miles'])
        ]), $query->all());
    }

}