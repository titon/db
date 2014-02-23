<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver;

use Titon\Db\Driver\Dialect\AbstractDialect;
use Titon\Db\Query\Expr;
use Titon\Db\Query\Func;
use Titon\Db\Query\Join;
use Titon\Db\Query\Predicate;
use Titon\Db\Query;
use Titon\Test\Stub\DialectStub;
use Titon\Test\Stub\DriverStub;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for Titon\Db\Driver\Dialect.
 *
 * @property \Titon\Db\Driver\Dialect\AbstractPdoDialect $object
 */
class DialectTest extends TestCase {

    /**
     * @type \Titon\Db\Driver
     */
    public $driver;

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->driver = new DriverStub([]);
        $this->driver->connect();

        $this->object = new DialectStub($this->driver);
    }

    /**
     * Close the connection.
     */
    protected function tearDown() {
        parent::tearDown();

        $this->driver->disconnect();
    }

    /**
     * Test create index statement building.
     */
    public function testBuildCreateIndex() {
        $query = new Query(Query::CREATE_INDEX, new User());
        $query->fields('profile_id')->from('users')->asAlias('idx');

        $this->assertRegExp('/CREATE\s+INDEX (`|\")?idx(`|\")? ON (`|\")?users(`|\")? \((`|\")?profile_id(`|\")?\)/', $this->object->buildCreateIndex($query));

        $query->fields(['profile_id' => 5]);
        $this->assertRegExp('/CREATE\s+INDEX (`|\")?idx(`|\")? ON (`|\")?users(`|\")? \((`|\")?profile_id(`|\")?\(5\)\)/', $this->object->buildCreateIndex($query));

        $query->fields(['profile_id' => 'asc', 'other_id']);
        $this->assertRegExp('/CREATE\s+INDEX (`|\")?idx(`|\")? ON (`|\")?users(`|\")? \((`|\")?profile_id(`|\")? ASC, (`|\")?other_id(`|\")?\)/', $this->object->buildCreateIndex($query));

        $query->fields(['profile_id' => ['length' => 5, 'order' => 'desc']]);
        $this->assertRegExp('/CREATE\s+INDEX (`|\")?idx(`|\")? ON (`|\")?users(`|\")? \((`|\")?profile_id(`|\")?\(5\) DESC\)/', $this->object->buildCreateIndex($query));
    }

    /**
     * Test create table statement creation.
     */
    public function testBuildCreateTable() {
        $schema = new Schema('foobar');
        $schema->addColumn('column', [
            'type' => 'int',
            'ai' => true
        ]);

        $query = new Query(Query::CREATE_TABLE, new User());
        $query->schema($schema);

        $this->assertRegExp('/CREATE\s+TABLE IF NOT EXISTS (`|\")?foobar(`|\")? \(\n(`|\")?column(`|\")? INT NOT NULL AUTO_INCREMENT\n\);/', $this->object->buildCreateTable($query));

        $schema->addColumn('column', [
            'type' => 'int',
            'ai' => true,
            'primary' => true
        ]);

        $this->assertRegExp('/CREATE\s+TABLE IF NOT EXISTS (`|\")?foobar(`|\")? \(\n(`|\")?column(`|\")? INT NOT NULL AUTO_INCREMENT,\nPRIMARY KEY \((`|\")?column(`|\")?\)\n\);/', $this->object->buildCreateTable($query));

        $schema->addColumn('column2', [
            'type' => 'int',
            'null' => true,
            'index' => true
        ]);

        $this->assertRegExp('/CREATE\s+TABLE IF NOT EXISTS (`|\")?foobar(`|\")? \(\n(`|\")?column(`|\")? INT NOT NULL AUTO_INCREMENT,\n(`|\")?column2(`|\")? INT NULL,\nPRIMARY KEY \((`|\")?column(`|\")?\)\n\);/', $this->object->buildCreateTable($query));

        $schema->addOption('engine', 'InnoDB');

        $this->assertRegExp('/CREATE\s+TABLE IF NOT EXISTS (`|\")?foobar(`|\")? \(\n(`|\")?column(`|\")? INT NOT NULL AUTO_INCREMENT,\n(`|\")?column2(`|\")? INT NULL,\nPRIMARY KEY \((`|\")?column(`|\")?\)\n\) ENGINE InnoDB;/', $this->object->buildCreateTable($query));
    }

    /**
     * Test delete statement creation.
     */
    public function testBuildDelete() {
        $query = new Query(Query::DELETE, new User());

        $query->from('foobar');
        $this->assertRegExp('/DELETE\s+FROM (`|\")?foobar(`|\")?;/', $this->object->buildDelete($query));

        $query->limit(5);
        $this->assertRegExp('/DELETE\s+FROM (`|\")?foobar(`|\")?\s+LIMIT 5;/', $this->object->buildDelete($query));

        $query->where('id', [1, 2, 3]);
        $this->assertRegExp('/DELETE\s+FROM (`|\")?foobar(`|\")?\s+WHERE (`|\")?id(`|\")? IN \(\?, \?, \?\)\s+LIMIT 5;/', $this->object->buildDelete($query));

        $query->orderBy('id', 'asc');
        $this->assertRegExp('/DELETE\s+FROM (`|\")?foobar(`|\")?\s+WHERE (`|\")?id(`|\")? IN \(\?, \?, \?\)\s+ORDER BY (`|\")?id(`|\")? ASC\s+LIMIT 5;/', $this->object->buildDelete($query));
    }

    /**
     * Test delete statements that contain joins.
     */
    public function testBuildDeleteJoins() {
        $user = new User();
        $query = $user->query(Query::DELETE);
        $query->rightJoin($user->getRelation('Profile'), []);

        $this->assertRegExp('/DELETE\s+FROM (`|\")?users(`|\")? AS (`|\")?User(`|\")? RIGHT JOIN (`|\")?profiles(`|\")? AS (`|\")?Profile(`|\")? ON (`|\")?User(`|\")?\.(`|\")?id(`|\")? = (`|\")?Profile(`|\")?\.(`|\")?user_id(`|\")?;/', $this->object->buildDelete($query));

        // Three joins
        $query = $user->select('id');
        $query->leftJoin('foo', ['id'], ['User.id' => 'foo.id']);
        $query->outerJoin(['bar', 'Bar'], ['id'], ['User.bar_id' => 'Bar.id']);

        $this->assertRegExp('/DELETE\s+FROM (`|\")?users(`|\")? AS (`|\")?User(`|\")? LEFT JOIN (`|\")?foo(`|\")? ON (`|\")?User(`|\")?.(`|\")?id(`|\")? = (`|\")?foo(`|\")?.(`|\")?id(`|\")? FULL OUTER JOIN (`|\")?bar(`|\")? AS (`|\")?Bar(`|\")? ON (`|\")?User(`|\")?.(`|\")?bar_id(`|\")? = (`|\")?Bar(`|\")?.(`|\")?id(`|\")?;/', $this->object->buildDelete($query));
    }

    /**
     * Test drop index statement building.
     */
    public function testBuildDropIndex() {
        $query = new Query(Query::DROP_INDEX, new User());
        $query->from('users')->asAlias('idx');

        $this->assertRegExp('/DROP\s+INDEX (`|\")?idx(`|\")? ON (`|\")?users(`|\")?/', $this->object->buildDropIndex($query));
    }

    /**
     * Test drop table statement creation.
     */
    public function testBuildDropTable() {
        $query = new Query(Query::DROP_TABLE, new User());
        $query->from('foobar');

        $this->assertRegExp('/DROP\s+TABLE IF EXISTS (`|\")?foobar(`|\")?;/', $this->object->buildDropTable($query));
    }

    /**
     * Test insert statement creation.
     */
    public function testBuildInsert() {
        $query = new Query(Query::INSERT, new User());
        $query->from('foobar')->fields([
            'username' => 'miles'
        ]);

        $this->assertRegExp('/INSERT\s+INTO (`|\")?foobar(`|\")? \((`|\")?username(`|\")?\) VALUES \(\?\);/', $this->object->buildInsert($query));

        $query->fields([
            'email' => 'email@domain.com',
            'website' => 'http://titon.io'
        ]);

        $this->assertRegExp('/INSERT\s+INTO (`|\")?foobar(`|\")? \((`|\")?email(`|\")?, (`|\")?website(`|\")?\) VALUES \(\?, \?\);/', $this->object->buildInsert($query));
    }

    /**
     * Test multi insert statement creation.
     */
    public function testBuildMultiInsert() {
        $query = new Query(Query::MULTI_INSERT, new User());
        $query->from('foobar')->fields([
            ['username' => 'miles', 'firstName' => 'Miles', 'lastName' => 'Johnson'],
            ['username' => 'batman', 'firstName' => 'Bruce', 'lastName' => 'Wayne'],
            ['username' => 'superman', 'firstName' => 'Clark', 'lastName' => 'Kent'],
            ['username' => 'spiderman', 'firstName' => 'Peter', 'lastName' => 'Parker'],
            ['username' => 'wolverine', 'firstName' => 'Logan', 'lastName' => ''],
        ]);

        $this->assertRegExp('/INSERT\s+INTO (`|\")?foobar(`|\")? \((`|\")?username(`|\")?, (`|\")?firstName(`|\")?, (`|\")?lastName(`|\")?\) VALUES \(\?, \?, \?\), \(\?, \?, \?\), \(\?, \?, \?\), \(\?, \?, \?\), \(\?, \?, \?\);/', $this->object->buildMultiInsert($query));
    }

    /**
     * Test select statement creation.
     */
    public function testBuildSelect() {
        $query = new Query(Query::SELECT, new User());

        $query->from('foobar');
        $this->assertRegExp('/SELECT\s+\* FROM (`|\")?foobar(`|\")?;/', $this->object->buildSelect($query));

        $query->where('status', 1)->where(function(Predicate $where) {
            $where->gte('rank', 15);
        });
        $this->assertRegExp('/SELECT\s+\* FROM (`|\")?foobar(`|\")?\s+WHERE (`|\")?status(`|\")? = \? AND (`|\")?rank(`|\")? >= \?;/', $this->object->buildSelect($query));

        $query->orderBy('id', 'desc');
        $this->assertRegExp('/SELECT\s+\* FROM (`|\")?foobar(`|\")?\s+WHERE (`|\")?status(`|\")? = \? AND (`|\")?rank(`|\")? >= \?\s+ORDER BY (`|\")?id(`|\")? DESC;/', $this->object->buildSelect($query));

        $query->groupBy('rank', 'created');
        $this->assertRegExp('/SELECT\s+\* FROM (`|\")?foobar(`|\")?\s+WHERE (`|\")?status(`|\")? = \? AND (`|\")?rank(`|\")? >= \?\s+GROUP BY (`|\")?rank(`|\")?, (`|\")?created(`|\")?\s+ORDER BY (`|\")?id(`|\")? DESC;/', $this->object->buildSelect($query));

        $query->limit(50, 10);
        $this->assertRegExp('/SELECT\s+\* FROM (`|\")?foobar(`|\")?\s+WHERE (`|\")?status(`|\")? = \? AND (`|\")?rank(`|\")? >= \?\s+GROUP BY (`|\")?rank(`|\")?, (`|\")?created(`|\")?\s+ORDER BY (`|\")?id(`|\")? DESC\s+LIMIT 50 OFFSET 10;/', $this->object->buildSelect($query));

        $query->having(function(Predicate $having) {
            $having->gte('id', 100);
        });
        $this->assertRegExp('/SELECT\s+\* FROM (`|\")?foobar(`|\")?\s+WHERE (`|\")?status(`|\")? = \? AND (`|\")?rank(`|\")? >= \?\s+GROUP BY (`|\")?rank(`|\")?, (`|\")?created(`|\")?\s+HAVING (`|\")?id(`|\")? >= \?\s+ORDER BY (`|\")?id(`|\")? DESC\s+LIMIT 50 OFFSET 10;/', $this->object->buildSelect($query));

        $query->fields('id', 'username', 'rank');
        $this->assertRegExp('/SELECT\s+(`|\")?id(`|\")?, (`|\")?username(`|\")?, (`|\")?rank(`|\")? FROM (`|\")?foobar(`|\")?\s+WHERE (`|\")?status(`|\")? = \? AND (`|\")?rank(`|\")? >= \?\s+GROUP BY (`|\")?rank(`|\")?, (`|\")?created(`|\")?\s+HAVING (`|\")?id(`|\")? >= \?\s+ORDER BY (`|\")?id(`|\")? DESC\s+LIMIT 50 OFFSET 10;/', $this->object->buildSelect($query));
    }

    /**
     * Test select statements that contain joins.
     */
    public function testBuildSelectJoins() {
        $user = new User();
        $query = $user->select();
        $query->rightJoin($user->getRelation('Profile'), []);

        $this->assertRegExp('/SELECT\s+(`|\")?User(`|\")?.*, (`|\")?Profile(`|\")?.* FROM (`|\")?users(`|\")? AS (`|\")?User(`|\")? RIGHT JOIN (`|\")?profiles(`|\")? AS (`|\")?Profile(`|\")? ON (`|\")?User(`|\")?.(`|\")?id(`|\")? = (`|\")?Profile(`|\")?.(`|\")?user_id(`|\")?;/', $this->object->buildSelect($query));

        // With fields
        $query = $user->select('id', 'username');
        $query->rightJoin($user->getRelation('Profile'), ['id', 'avatar', 'lastLogin']);

        $this->assertRegExp('/SELECT\s+(`|\")?User(`|\")?.(`|\")?id(`|\")?, (`|\")?User(`|\")?.(`|\")?username(`|\")?, (`|\")?Profile(`|\")?.(`|\")?id(`|\")?, (`|\")?Profile(`|\")?.(`|\")?avatar(`|\")?, (`|\")?Profile(`|\")?.(`|\")?lastLogin(`|\")? FROM (`|\")?users(`|\")? AS (`|\")?User(`|\")? RIGHT JOIN (`|\")?profiles(`|\")? AS (`|\")?Profile(`|\")? ON (`|\")?User(`|\")?.(`|\")?id(`|\")? = (`|\")?Profile(`|\")?.(`|\")?user_id(`|\")?;/', $this->object->buildSelect($query));

        // Three joins
        $query = $user->select('id');
        $query->leftJoin('foo', ['id'], ['User.id' => 'foo.id']);
        $query->outerJoin(['bar', 'Bar'], ['id'], ['User.bar_id' => 'Bar.id']);

        $this->assertRegExp('/SELECT\s+(`|\")?User(`|\")?.(`|\")?id(`|\")?, (`|\")?foo(`|\")?.(`|\")?id(`|\")?, (`|\")?Bar(`|\")?.(`|\")?id(`|\")? FROM (`|\")?users(`|\")? AS (`|\")?User(`|\")? LEFT JOIN (`|\")?foo(`|\")? ON (`|\")?User(`|\")?.(`|\")?id(`|\")? = (`|\")?foo(`|\")?.(`|\")?id(`|\")? FULL OUTER JOIN (`|\")?bar(`|\")? AS (`|\")?Bar(`|\")? ON (`|\")?User(`|\")?.(`|\")?bar_id(`|\")? = (`|\")?Bar(`|\")?.(`|\")?id(`|\")?;/', $this->object->buildSelect($query));
    }

    /**
     * Test union building and wrapping.
     */
    public function testBuildSelectUnions() {
        $user = new User();
        $query = $user->select('id');
        $query->union($query->subQuery('id')->from('u1'));

        $this->assertRegExp('/SELECT\s+(`|\")?id(`|\")? FROM (`|\")?users(`|\")?\s+ UNION  SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u1(`|\")?;/', $this->object->buildSelect($query));

        // more joins
        $query->union($query->subQuery('id')->from('u2'), 'all');

        $this->assertRegExp('/SELECT\s+(`|\")?id(`|\")? FROM (`|\")?users(`|\")?\s+ UNION  SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u1(`|\")? UNION ALL SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u2(`|\")?;/', $this->object->buildSelect($query));

        // order by limit
        $query->orderBy('id', 'DESC')->limit(10);

        $this->assertRegExp('/SELECT\s+(`|\")?id(`|\")? FROM (`|\")?users(`|\")?\s+ UNION  SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u1(`|\")? UNION ALL SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u2(`|\")? ORDER BY (`|\")?id(`|\")? DESC LIMIT 10;/', $this->object->buildSelect($query));
    }

    /**
     * Test truncate table statement creation.
     */
    public function testBuildTruncate() {
        $query = new Query(Query::TRUNCATE, new User());
        $query->from('foobar');

        $this->assertRegExp('/TRUNCATE (`|\")?foobar(`|\")?;/', $this->object->buildTruncate($query));
    }

    /**
     * Test update statement creation.
     */
    public function testBuildUpdate() {
        $query = new Query(Query::UPDATE, new User());

        // No fields
        try {
            $this->object->buildUpdate($query);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $query->fields(['username' => 'miles']);

        // No table
        try {
            $this->object->buildUpdate($query);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $query->from('foobar');
        $this->assertRegExp('/UPDATE\s+(`|\")?foobar(`|\")?\s+SET (`|\")?username(`|\")? = \?;/', $this->object->buildUpdate($query));

        $query->limit(15);
        $this->assertRegExp('/UPDATE\s+(`|\")?foobar(`|\")?\s+SET (`|\")?username(`|\")? = \?\s+LIMIT 15;/', $this->object->buildUpdate($query));

        $query->orderBy('username', 'desc');
        $this->assertRegExp('/UPDATE\s+(`|\")?foobar(`|\")?\s+SET (`|\")?username(`|\")? = \?\s+ORDER BY (`|\")?username(`|\")? DESC\s+LIMIT 15;/', $this->object->buildUpdate($query));

        $query->fields([
            'email' => 'email@domain.com',
            'website' => 'http://titon.io'
        ]);
        $this->assertRegExp('/UPDATE\s+(`|\")?foobar(`|\")?\s+SET (`|\")?email(`|\")? = \?, (`|\")?website(`|\")? = \?\s+ORDER BY (`|\")?username(`|\")? DESC\s+LIMIT 15;/', $this->object->buildUpdate($query));

        $query->where('status', 3);
        $this->assertRegExp('/UPDATE\s+(`|\")?foobar(`|\")?\s+SET (`|\")?email(`|\")? = \?, (`|\")?website(`|\")? = \?\s+WHERE (`|\")?status(`|\")? = \?\s+ORDER BY (`|\")?username(`|\")? DESC\s+LIMIT 15;/', $this->object->buildUpdate($query));
    }

    /**
     * Test update statements that contain joins.
     */
    public function testBuildUpdateJoins() {
        $user = new User();
        $query = $user->query(Query::UPDATE)->fields(['username' => 'foo']);
        $query->rightJoin($user->getRelation('Profile'), []);

        $this->assertRegExp('/UPDATE\s+(`|\")?users(`|\")? AS (`|\")?User(`|\")? RIGHT JOIN (`|\")?profiles(`|\")? AS (`|\")?Profile(`|\")? ON (`|\")?User(`|\")?\.(`|\")?id(`|\")? = (`|\")?Profile(`|\")?\.(`|\")?user_id(`|\")?\s+SET (`|\")?User(`|\")?\.(`|\")?username(`|\")? = \?;/', $this->object->buildUpdate($query));

        // With fields
        $query = $user->query(Query::UPDATE)->fields(['username' => 'foo']);
        $query->rightJoin($user->getRelation('Profile'), ['avatar' => 'image.jpg']);

        $this->assertRegExp('/UPDATE\s+(`|\")?users(`|\")? AS (`|\")?User(`|\")? RIGHT JOIN (`|\")?profiles(`|\")? AS (`|\")?Profile(`|\")? ON (`|\")?User(`|\")?\.(`|\")?id(`|\")? = (`|\")?Profile(`|\")?\.(`|\")?user_id(`|\")?\s+SET (`|\")?User(`|\")?\.(`|\")?username(`|\")? = \?, (`|\")?Profile(`|\")?\.(`|\")?avatar(`|\")? = \?;/', $this->object->buildUpdate($query));
    }

    /**
     * Test sub-query creation.
     */
    public function testBuildSubQuery() {
        // In fields
        $query = new Query(Query::SELECT, new User());
        $query->from('users')->fields($query->subQuery('id')->from('profiles'));

        $this->assertRegExp('/SELECT\s+\(SELECT\s+(`|\")?id(`|\")? FROM (`|\")?profiles(`|\")?\) FROM (`|\")?users(`|\")?;/', $this->object->buildSelect($query));

        // In fields with alias
        $query = new Query(Query::SELECT, new User());
        $query->from('users')->fields($query->subQuery('id')->from('profiles')->asAlias('column'));

        $this->assertRegExp('/SELECT\s+\(SELECT\s+(`|\")?id(`|\")? FROM (`|\")?profiles(`|\")?\) AS (`|\")?column(`|\")? FROM (`|\")?users(`|\")?;/', $this->object->buildSelect($query));

        // In function in fields
        $query = new Query(Query::SELECT, new User());
        $query->from('users')->fields(
            $query->func('UPPER', [$query->subQuery('id')->from('profiles')])
        );

        $this->assertRegExp('/SELECT\s+UPPER\(\(SELECT\s+(`|\")?id(`|\")? FROM (`|\")?profiles(`|\")?\)\) FROM (`|\")?users(`|\")?;/', $this->object->buildSelect($query));

        // In where clause w/ function
        $query = new Query(Query::SELECT, new User());
        $query->from('users')->where('column1', $query->subQuery($query->func('MAX', ['column2' => 'field']))->from('profiles'));

        $this->assertRegExp('/SELECT\s+\* FROM (`|\")?users(`|\")?\s+WHERE (`|\")?column1(`|\")? = \(SELECT\s+MAX\((`|\")?column2(`|\")?\) FROM (`|\")?profiles(`|\")?\);/', $this->object->buildSelect($query));

        // In where clause w/ SOME filter
        $query = new Query(Query::SELECT, new User());
        $query->from('users')->where('column1', $query->subQuery('column2')->from('profiles')->withFilter('some'));

        $this->assertRegExp('/SELECT\s+\* FROM (`|\")?users(`|\")?\s+WHERE (`|\")?column1(`|\")? = SOME \(SELECT\s+(`|\")?column2(`|\")? FROM (`|\")?profiles(`|\")?\);/', $this->object->buildSelect($query));

        // In where clause using IN operator
        $query = new Query(Query::SELECT, new User());
        $query->from('users')->where('column1', 'in', $query->subQuery('column2')->from('profiles'));

        $this->assertRegExp('/SELECT\s+\* FROM (`|\")?users(`|\")?\s+WHERE (`|\")?column1(`|\")? IN \(SELECT\s+(`|\")?column2(`|\")? FROM (`|\")?profiles(`|\")?\);/', $this->object->buildSelect($query));

        // In where clause using EXISTS operator
        $query = new Query(Query::SELECT, new User());
        $query->from('users')->where('column1', $query->subQuery('column2')->from('profiles')->withFilter('exists'));

        $this->assertRegExp('/SELECT\s+\* FROM (`|\")?users(`|\")?\s+WHERE EXISTS \(SELECT\s+(`|\")?column2(`|\")? FROM (`|\")?profiles(`|\")?\);/', $this->object->buildSelect($query));
    }

    /**
     * Test table column formatting builds according to the options defined.
     */
    public function testFormatColumns() {
        $schema = new Schema('foobar');
        $schema->addColumn('column', [
            'type' => 'int'
        ]);

        $this->assertRegExp('/(`|\")?column(`|\")? INT NULL/', $this->object->formatColumns($schema));

        $schema->addColumn('column', [
            'type' => 'int',
            'unsigned' => true,
            'zerofill' => true
        ]);

        $this->assertRegExp('/(`|\")?column(`|\")? INT UNSIGNED ZEROFILL NULL/', $this->object->formatColumns($schema));

        $schema->addColumn('column', [
            'type' => 'int',
            'null' => false,
            'comment' => 'Some comment here'
        ]);

        $this->assertRegExp('/(`|\")?column(`|\")? INT NOT NULL COMMENT \'Some comment here\'/', $this->object->formatColumns($schema));

        $schema->addColumn('column', [
            'type' => 'int',
            'ai' => true,
            'length' => 11
        ]);

        $this->assertRegExp('/(`|\")?column(`|\")? INT\(11\) NOT NULL AUTO_INCREMENT/', $this->object->formatColumns($schema));

        $schema->addColumn('column', [
            'type' => 'int',
            'ai' => true,
            'length' => 11,
            'unsigned' => true,
            'zerofill' => true,
            'null' => false,
            'default' => null,
            'comment' => 'Some comment here'
        ]);

        $expected = '(`|\")?column(`|\")? INT\(11\) UNSIGNED ZEROFILL NOT NULL DEFAULT NULL AUTO_INCREMENT COMMENT \'Some comment here\'';

        $this->assertRegExp('/' . $expected . '/', $this->object->formatColumns($schema));

        $schema->addColumn('column2', [
            'type' => 'varchar',
            'length' => 255,
            'null' => true
        ]);

        $expected .= ',\n(`|\")?column2(`|\")? VARCHAR\(255\) NULL';

        $this->assertRegExp('/' . $expected . '/', $this->object->formatColumns($schema));

        $schema->addColumn('column3', [
            'type' => 'smallint',
            'default' => 3,
            'null' => false
        ]);

        $expected .= ',\n(`|\")?column3(`|\")? SMALLINT NOT NULL DEFAULT 3';

        $this->assertRegExp('/' . $expected . '/', $this->object->formatColumns($schema));

        // inherits values from type
        $schema->addColumn('column4', [
            'type' => 'datetime'
        ]);

        $expected .= ',\n(`|\")?column4(`|\")? DATETIME NULL DEFAULT NULL';

        $this->assertRegExp('/' . $expected . '/', $this->object->formatColumns($schema));

        $schema->addColumn('column5', [
            'type' => 'varchar',
            'collate' => 'utf8_general_ci',
            'charset' => 'utf8'
        ]);

        $expected .= ',\n(`|\")?column5(`|\")? VARCHAR\(255\) CHARACTER SET utf8 COLLATE utf8_general_ci NULL';

        $this->assertRegExp('/' . $expected . '/', $this->object->formatColumns($schema));
    }

    /**
     * Test default formatting.
     */
    public function testFormatDefault() {
        $this->assertEquals('', $this->object->formatDefault(''));
        $this->assertEquals('DEFAULT \'test\'', $this->object->formatDefault('test'));
        $this->assertEquals('DEFAULT 5', $this->object->formatDefault(5));
        $this->assertEquals('DEFAULT NULL', $this->object->formatDefault(null));
        $this->assertEquals('DEFAULT CURRENT_TIMESTAMP', $this->object->formatDefault(function() {
            return 'CURRENT_TIMESTAMP';
        }));
    }

    /**
     * Test compound query building.
     */
    public function testFormatCompounds() {
        // union
        $query = new Query(Query::INSERT, new User());
        $query->union($query->subQuery('id')->from('u1'));
        $this->assertRegExp('/UNION\s+SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u1(`|\")?/', $this->object->formatCompounds($query->getCompounds()));

        // union all
        $query->union($query->subQuery('id')->from('u2'), 'all');
        $this->assertRegExp('/UNION\s+SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u1(`|\")? UNION ALL SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u2(`|\")?/', $this->object->formatCompounds($query->getCompounds()));

        // union distinct
        $query = new Query(Query::INSERT, new User());
        $query->union($query->subQuery('id')->from('u1'), 'distinct');
        $this->assertRegExp('/UNION DISTINCT SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u1(`|\")?/', $this->object->formatCompounds($query->getCompounds()));

        // intersects
        $query = new Query(Query::INSERT, new User());
        $query->intersect($query->subQuery('id')->from('u1'));
        $this->assertRegExp('/INTERSECT\s+SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u1(`|\")?/', $this->object->formatCompounds($query->getCompounds()));

        // intersects all
        $query->intersect($query->subQuery('id')->from('u2'), 'all');
        $this->assertRegExp('/INTERSECT\s+SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u1(`|\")? INTERSECT ALL SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u2(`|\")?/', $this->object->formatCompounds($query->getCompounds()));

        // excepts
        $query = new Query(Query::INSERT, new User());
        $query->except($query->subQuery('id')->from('u1'));
        $this->assertRegExp('/EXCEPT\s+SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u1(`|\")?/', $this->object->formatCompounds($query->getCompounds()));

        // excepts all
        $query->except($query->subQuery('id')->from('u2'), 'all');
        $this->assertRegExp('/EXCEPT\s+SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u1(`|\")? EXCEPT ALL SELECT\s+(`|\")?id(`|\")? FROM (`|\")?u2(`|\")?/', $this->object->formatCompounds($query->getCompounds()));

    }

    /**
     * Test expressions are built.
     */
    public function testFormatExpression() {
        $expr = new Expr('column', '+', 5);
        $this->assertRegExp('/(`|\")?column(`|\")? \+ \?/', $this->object->formatExpression($expr));

        $expr = new Expr('column', null, 5);
        $this->assertRegExp('/(`|\")?column(`|\")?/', $this->object->formatExpression($expr));

        $expr = new Expr('column', '+');
        $this->assertRegExp('/(`|\")?column(`|\")?/', $this->object->formatExpression($expr));

        $expr = new Expr('column');
        $this->assertRegExp('/(`|\")?column(`|\")?/', $this->object->formatExpression($expr));
    }

    /**
     * Test fields and values format depending on the query type.
     */
    public function testFormatFields() {
        $fields = [
            'id' => 1,
            'username' => 'miles',
            'email' => 'email@domain.com'
        ];

        $query = new Query(Query::INSERT, new User());
        $query->fields($fields);

        $this->assertRegExp('/\((`|\")?id(`|\")?, (`|\")?username(`|\")?, (`|\")?email(`|\")?\)/', $this->object->formatFields($query));

        $query = new Query(Query::UPDATE, new User());
        $query->fields($fields);

        $this->assertRegExp('/(`|\")?id(`|\")? = \?, (`|\")?username(`|\")? = \?, (`|\")?email(`|\")? = \?/', $this->object->formatFields($query));

        $query = new Query(Query::SELECT, new User());
        $func = new Func('SUM', ['id' => Func::FIELD]);

        $this->assertRegExp('/\*/', $this->object->formatFields($query));

        $fields = array_keys($fields);
        $fields[] = $func;
        $query->fields($fields);

        $this->assertRegExp('/(`|\")?id(`|\")?, (`|\")?username(`|\")?, (`|\")?email(`|\")?, SUM\((`|\")?id(`|\")?\)/', $this->object->formatFields($query));
    }

    /**
     * Test that function formatting parses types.
     */
    public function testFormatFunction() {
        $func = new Func('SUBSTRING', ['TitonFramework', 5]);
        $this->assertEquals("SUBSTRING('TitonFramework', 5)", $this->object->formatFunction($func));

        $func->asAlias('column');
        $this->assertRegExp('/SUBSTRING\(\'TitonFramework\', 5\) AS (`|\")?column(`|\")?/', $this->object->formatFunction($func));

        $func = new Func('INSERT', ['Titon', 3, 5, 'Framework']);
        $this->assertEquals("INSERT('Titon', 3, 5, 'Framework')", $this->object->formatFunction($func));

        $func = new Func('CHAR', [77, 77.3, '77.3']);
        $this->assertEquals("CHAR(77, 77.3, '77.3')", $this->object->formatFunction($func));

        $func = new Func('CONCAT', ['Titon', null, 'Framework']);
        $this->assertEquals("CONCAT('Titon', NULL, 'Framework')", $this->object->formatFunction($func));

        $func = new Func('SUBSTRING', ['Titon', 'FROM -4 FOR 2' => Func::LITERAL], ' ');
        $this->assertEquals("SUBSTRING('Titon' FROM -4 FOR 2)", $this->object->formatFunction($func));

        $func = new Func('TRIM', ["TRAILING 'xyz' FROM 'barxxyz'" => Func::LITERAL]);
        $this->assertEquals("TRIM(TRAILING 'xyz' FROM 'barxxyz')", $this->object->formatFunction($func));

        $func = new Func('COUNT', ['id' => Func::FIELD]);
        $this->assertRegExp("/COUNT\((`|\")?id(`|\")?\)/", $this->object->formatFunction($func));

        $func1 = new Func('HEX', 255);
        $func2 = new Func('CONV', [$func1, 16, 10]);
        $this->assertEquals("CONV(HEX(255), 16, 10)", $this->object->formatFunction($func2));

        $func1 = new Func('CHAR', ['0x65 USING utf8' => Func::LITERAL]);
        $func2 = new Func('CHARSET', $func1);
        $this->assertEquals("CHARSET(CHAR(0x65 USING utf8))", $this->object->formatFunction($func2));
    }

    /**
     * Test group by formatting.
     */
    public function testFormatGroupBy() {
        $this->assertEquals('', $this->object->formatGroupBy([]));
        $this->assertRegExp('/GROUP BY (`|\")?id(`|\")?, (`|\")?username(`|\")?/', $this->object->formatGroupBy(['id', 'username']));
    }

    /**
     * Test having predicate is formatted.
     */
    public function testFormatHaving() {
        $pred = new Predicate(Predicate::ALSO);

        $this->assertEquals('', $this->object->formatHaving($pred));

        $pred->gte('age', 12);

        $this->assertRegExp('/HAVING (`|\")?age(`|\")? >= \?/', $this->object->formatHaving($pred));
    }

    /**
     * Test join clause formatting.
     */
    public function testFormatJoins() {
        $join = new Join(Join::LEFT);
        $join->from('users')->on(['id' => 'id']);

        $this->assertRegExp('/LEFT JOIN (`|\")?users(`|\")? ON (`|\")?id(`|\")? = (`|\")?id(`|\")?/', $this->object->formatJoins([$join]));

        $join->asAlias('User');
        $this->assertRegExp('/LEFT JOIN (`|\")?users(`|\")? AS (`|\")?User(`|\")? ON (`|\")?id(`|\")? = (`|\")?id(`|\")?/', $this->object->formatJoins([$join]));

        $join = new Join(Join::RIGHT);
        $join->from('profiles', 'profiles')->on('User.profile_id', 'profiles.id');

        $this->assertRegExp('/RIGHT JOIN (`|\")?profiles(`|\")? ON (`|\")?User(`|\")?.(`|\")?profile_id(`|\")? = (`|\")?profiles(`|\")?.(`|\")?id(`|\")?/', $this->object->formatJoins([$join]));

        $join = new Join(Join::INNER);
        $join->from('profiles', 'Profile')->on('User.id', 'Profile.user_id');

        $this->assertRegExp('/INNER JOIN (`|\")?profiles(`|\")? AS (`|\")?Profile(`|\")? ON (`|\")?User(`|\")?.(`|\")?id(`|\")? = (`|\")?Profile(`|\")?.(`|\")?user_id(`|\")?/', $this->object->formatJoins([$join]));

        $join2 = new Join(Join::OUTER);
        $join2->from('settings', 'Setting')->on('User.setting_id', 'Setting.id');

        $this->assertRegExp('/INNER JOIN (`|\")?profiles(`|\")? AS (`|\")?Profile(`|\")? ON (`|\")?User(`|\")?.(`|\")?id(`|\")? = (`|\")?Profile(`|\")?.(`|\")?user_id(`|\")? FULL OUTER JOIN (`|\")?settings(`|\")? AS (`|\")?Setting(`|\")? ON (`|\")?User(`|\")?.(`|\")?setting_id(`|\")? = (`|\")?Setting(`|\")?.(`|\")?id(`|\")?/', $this->object->formatJoins([$join, $join2]));
    }

    /**
     * Test limit is formatted.
     */
    public function testFormatLimit() {
        $this->assertEquals('', $this->object->formatLimit(0));
        $this->assertEquals('LIMIT 5', $this->object->formatLimit(5));
    }

    /**
     * Test limit and offset is formatted.
     */
    public function testFormatLimitOffset() {
        $this->assertEquals('', $this->object->formatLimitOffset(0));
        $this->assertEquals('LIMIT 5', $this->object->formatLimitOffset(5));
        $this->assertEquals('LIMIT 5 OFFSET 10', $this->object->formatLimitOffset(5, 10));
    }

    /**
     * Test order by formatting.
     */
    public function testFormatOrderBy() {
        $this->assertEquals('', $this->object->formatOrderBy([]));
        $this->assertRegExp('/ORDER BY (`|\")?id(`|\")? ASC/', $this->object->formatOrderBy(['id' => 'asc']));
        $this->assertRegExp('/ORDER BY (`|\")?id(`|\")? ASC, (`|\")?username(`|\")? DESC/', $this->object->formatOrderBy(['id' => 'asc', 'username' => 'desc']));

        $func = new Func('RAND');

        $this->assertEquals('ORDER BY RAND()', $this->object->formatOrderBy([$func]));
    }

    /**
     * Test nested predicates get parsed correctly.
     */
    public function testFormatPredicate() {
        $pred = new Predicate(Predicate::ALSO);
        $pred->eq('id', 1)->gte('age', 12);

        $this->assertRegExp('/(`|\")?id(`|\")? = \? AND (`|\")?age(`|\")? >= \?/', $this->object->formatPredicate($pred));

        $pred->either(function(Predicate $where) {
            $where->like('name', '%Titon%')->notLike('name', '%Symfony%');
            $where->also(function(Predicate $where2) {
                $where2->eq('active', 1)->notEq('status', 2);
            });
        });

        $this->assertRegExp('/(`|\")?id(`|\")? = \? AND (`|\")?age(`|\")? >= \? AND \((`|\")?name(`|\")? LIKE \? OR (`|\")?name(`|\")? NOT LIKE \? OR \((`|\")?active(`|\")? = \? AND (`|\")?status(`|\")? != \?\)\)/', $this->object->formatPredicate($pred));

        $pred = new Predicate(Predicate::MAYBE);
        $pred->eq('id', 1)->gte('age', 12);

        $this->assertRegExp('/(`|\")?id(`|\")? = \? XOR (`|\")?age(`|\")? >= \?/', $this->object->formatPredicate($pred));
    }

    /**
     * Test table is quoted.
     */
    public function testFormatTable() {
        $this->assertRegExp('/(`|\")?foobar(`|\")?/', $this->object->formatTable('foobar'));
        $this->assertRegExp('/(`|\")?foobar(`|\")? AS (`|\")?Foo(`|\")?/', $this->object->formatTable('foobar', 'Foo'));
    }

    /**
     * Test foreign keys.
     */
    public function testFormatTableForeign() {
        $data = ['column' => 'id', 'references' => 'users.id', 'constraint' => ''];

        $this->assertRegExp('/FOREIGN KEY \((`|\")?id(`|\")?\) REFERENCES (`|\")?users(`|\")?\((`|\")?id(`|\")?\)/', $this->object->formatTableForeign($data));

        $data['constraint'] = 'symbol';
        $this->assertRegExp('/CONSTRAINT (`|\")?symbol(`|\")? FOREIGN KEY \((`|\")?id(`|\")?\) REFERENCES (`|\")?users(`|\")?\((`|\")?id(`|\")?\)/', $this->object->formatTableForeign($data));

        $data['onUpdate'] = Dialect::RESTRICT;
        $this->assertRegExp('/CONSTRAINT (`|\")?symbol(`|\")? FOREIGN KEY \((`|\")?id(`|\")?\) REFERENCES (`|\")?users(`|\")?\((`|\")?id(`|\")?\) ON UPDATE RESTRICT/', $this->object->formatTableForeign($data));

        $data['where'] = true; // fake example, used for actions with no value
        $this->assertRegExp('/CONSTRAINT (`|\")?symbol(`|\")? FOREIGN KEY \((`|\")?id(`|\")?\) REFERENCES (`|\")?users(`|\")?\((`|\")?id(`|\")?\) ON UPDATE RESTRICT WHERE /', $this->object->formatTableForeign($data));
    }

    /**
     * Test index keys.
     */
    public function testFormatTableIndex() {
        $this->assertRegExp('/KEY (`|\")?idx(`|\")? \((`|\")?foo(`|\")?\)/', $this->object->formatTableIndex('idx', ['foo']));
        $this->assertRegExp('/KEY (`|\")?idx(`|\")? \((`|\")?foo(`|\")?, (`|\")?bar(`|\")?\)/', $this->object->formatTableIndex('idx', ['foo', 'bar']));
    }

    /**
     * Test table keys are built with primary, unique, foreign and index.
     */
    public function testFormatTableKeys() {
        $schema = new Schema('foobar');
        $schema->addUnique('primary');

        $expected = ',\nUNIQUE KEY (`|\")?primary(`|\")? \((`|\")?primary(`|\")?\)';

        $this->assertRegExp('/' . $expected . '/', $this->object->formatTableKeys($schema));

        $schema->addUnique('unique', [
            'constraint' => 'uniqueSymbol'
        ]);

        $expected .= ',\nCONSTRAINT (`|\")?uniqueSymbol(`|\")? UNIQUE KEY (`|\")?unique(`|\")? \((`|\")?unique(`|\")?\)';

        $this->assertRegExp('/' . $expected . '/', $this->object->formatTableKeys($schema));

        $schema->addForeign('fk1', 'users.id');

        $expected .= ',\nFOREIGN KEY \((`|\")?fk1(`|\")?\) REFERENCES (`|\")?users(`|\")?\((`|\")?id(`|\")?\)';

        $this->assertRegExp('/' . $expected . '/', $this->object->formatTableKeys($schema));

        $schema->addForeign('fk2', [
            'references' => 'posts.id',
            'onUpdate' => Dialect::SET_NULL,
            'onDelete' => Dialect::NO_ACTION
        ]);

        $expected .= ',\nFOREIGN KEY \((`|\")?fk2(`|\")?\) REFERENCES (`|\")?posts(`|\")?\((`|\")?id(`|\")?\) ON UPDATE SET NULL ON DELETE NO ACTION';

        $this->assertRegExp('/' . $expected . '/', $this->object->formatTableKeys($schema));
    }

    /**
     * Test table options are formatted.
     */
    public function testFormatTableOptions() {
        $options = [];
        $this->assertEquals('', $this->object->formatTableOptions($options));

        $options['characterSet'] = 'utf8';
        $this->assertEquals("CHARACTER SET utf8", $this->object->formatTableOptions($options));

        $options['engine'] = 'MyISAM';
        $this->assertEquals("CHARACTER SET utf8 ENGINE MyISAM", $this->object->formatTableOptions($options));
    }

    /**
     * Test primary key.
     */
    public function testFormatTablePrimary() {
        $data = ['columns' => ['foo'], 'constraint' => ''];

        $this->assertRegExp('/PRIMARY KEY \((`|\")?foo(`|\")?\)/', $this->object->formatTablePrimary($data));

        $data['constraint'] = 'symbol';
        $this->assertRegExp('/CONSTRAINT (`|\")?symbol(`|\")? PRIMARY KEY \((`|\")?foo(`|\")?\)/', $this->object->formatTablePrimary($data));

        $data['columns'][] = 'bar';
        $this->assertRegExp('/CONSTRAINT (`|\")?symbol(`|\")? PRIMARY KEY \((`|\")?foo(`|\")?, (`|\")?bar(`|\")?\)/', $this->object->formatTablePrimary($data));
    }

    /**
     * Test unique keys.
     */
    public function testFormatTableUnique() {
        $data = ['columns' => ['foo'], 'constraint' => '', 'index' => 'idx'];

        $this->assertRegExp('/UNIQUE KEY (`|\")?idx(`|\")? \((`|\")?foo(`|\")?\)/', $this->object->formatTableUnique($data));

        $data['constraint'] = 'symbol';
        $this->assertRegExp('/CONSTRAINT (`|\")?symbol(`|\")? UNIQUE KEY (`|\")?idx(`|\")? \((`|\")?foo(`|\")?\)/', $this->object->formatTableUnique($data));

        $data['columns'][] = 'bar';
        $this->assertRegExp('/CONSTRAINT (`|\")?symbol(`|\")? UNIQUE KEY (`|\")?idx(`|\")? \((`|\")?foo(`|\")?, (`|\")?bar(`|\")?\)/', $this->object->formatTableUnique($data));
    }

    /**
     * Test values are represented with question marks.
     */
    public function testFormatValues() {
        $query = new Query(Query::INSERT, new User());
        $query->fields([
            'id' => 1,
            'username' => 'miles',
            'email' => 'email@domain.com'
        ]);

        $this->assertEquals('(?, ?, ?)', $this->object->formatValues($query));
    }

    /**
     * Test where predicate is formatted.
     */
    public function testFormatWhere() {
        $pred = new Predicate(Predicate::EITHER);

        $this->assertEquals('', $this->object->formatWhere($pred));

        $pred->between('id', 1, 100)->eq('status', 1);

        $this->assertRegExp('/WHERE (`|\")?id(`|\")? BETWEEN \? AND \? OR (`|\")?status(`|\")? = \?/', $this->object->formatWhere($pred));
    }

    /**
     * Test single clause fetching.
     */
    public function testGetClause() {
        $this->assertEquals('%s AS %s', $this->object->getClause(AbstractDialect::AS_ALIAS));

        try {
            $this->object->getClause('foobar');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test multiple clause fetching.
     */
    public function testGetClauses() {
        $this->assertNotEmpty($this->object->getClauses());
    }

    /**
     * Test the driver is returned.
     */
    public function testGetDriver() {
        $this->assertInstanceOf('Titon\Db\Driver', $this->object->getDriver());
    }

    /**
     * Test single statement fetching.
     */
    public function testGetStatement() {
        try {
            $this->object->getStatement('foobar');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test multiple statement fetching.
     */
    public function testGetStatements() {
        $this->assertNotEmpty($this->object->getStatements());
    }

    /**
     * Test identifier quoting.
     */
    public function testQuote() {
        $this->assertEquals('`foo`', $this->object->quote('foo'));
        $this->assertEquals('`foo`', $this->object->quote('foo`'));
        $this->assertEquals('`foo`', $this->object->quote('``foo`'));

        $this->assertEquals('`foo`.`bar`', $this->object->quote('foo.bar'));
        $this->assertEquals('`foo`.`bar`', $this->object->quote('foo`.`bar'));
        $this->assertEquals('`foo`.`bar`', $this->object->quote('`foo`.`bar`'));
        $this->assertEquals('`foo`.*', $this->object->quote('foo.*'));
    }

    /**
     * Test multiple identifier quoting.
     */
    public function testQuoteList() {
        $this->assertEquals('`foo`, `bar`, `baz`', $this->object->quoteList(['foo', '`bar', '`baz`']));
        $this->assertEquals('`foo`.`bar`, `baz`', $this->object->quoteList(['foo.bar', '`baz`']));
    }

    /**
     * Test attribute parsing.
     */
    public function testRenderAttributes() {
        $this->assertEquals(['a.distinct' => ''], $this->object->renderAttributes(['distinct' => false]));
        $this->assertEquals(['a.distinct' => 'DISTINCT'], $this->object->renderAttributes(['distinct' => true]));
        $this->assertEquals(['a.distinct' => 'DISTINCT'], $this->object->renderAttributes(['distinct' => 'distinct']));
        $this->assertEquals(['a.distinct' => 'DEFAULT test'], $this->object->renderAttributes(['distinct' => function(Dialect $dialect) {
            return sprintf($dialect->getClause(Dialect::DEFAULT_TO), 'test');
        }]));
    }

    /**
     * Test params are rendered in a statement.
     */
    public function testRenderStatement() {
        $this->assertEquals('SELECT * FROM tableName WHERE id = 1;', $this->object->renderStatement('SELECT * FROM {table} WHERE id = {id}', [
            'table' => 'tableName',
            'id' => 1
        ]));
    }

}