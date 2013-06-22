<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver;

use Titon\Model\Query\Predicate;
use Titon\Model\Query;
use Titon\Test\Stub\DialectStub;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for Titon\Model\Driver\Dialect.
 *
 * @property \Titon\Model\Driver\Dialect\AbstractDialect $object
 */
class DialectTest extends TestCase {

	/**
	 * @type \Titon\Model\Driver
	 */
	public $driver;

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->driver = new DriverStub('default', []);
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
	 * Test nested predicates get parsed correctly.
	 */
	public function testFormatPredicate() {
		$pred = new Predicate(Predicate::ALSO);
		$pred->eq('id', 1)->gte('age', 12);

		$this->assertEquals('`id` = ? AND `age` >= ?', $this->object->formatPredicate($pred));

		$pred->either(function() {
			$this->like('name', '%Titon%')->notLike('name', '%Symfony%');
			$this->also(function() {
				$this->eq('active', 1)->notEq('status', 2);
			});
		});

		$this->assertEquals('`id` = ? AND `age` >= ? AND (`name` LIKE ? OR `name` NOT LIKE ? OR (`active` = ? AND `status` != ?))', $this->object->formatPredicate($pred));
	}

	/**
	 * Test table column formatting builds according to the attributes defined.
	 */
	public function testFormatColumns() {
		$schema = new Schema('foobar');
		$schema->addColumn('column', [
			'type' => 'int'
		]);

		$this->assertEquals('`column` int NOT NULL', $this->object->formatColumns($schema));

		$schema->addColumn('column', [
			'type' => 'int',
			'unsigned' => true,
			'zerofill' => true
		]);

		$this->assertEquals('`column` int UNSIGNED ZEROFILL NOT NULL', $this->object->formatColumns($schema));

		$schema->addColumn('column', [
			'type' => 'int',
			'null' => true,
			'comment' => 'Some comment here'
		]);

		$this->assertEquals('`column` int NULL COMMENT \'Some comment here\'', $this->object->formatColumns($schema));

		$schema->addColumn('column', [
			'type' => 'int',
			'ai' => true,
			'length' => 11
		]);

		$this->assertEquals('`column` int(11) NOT NULL AUTO_INCREMENT', $this->object->formatColumns($schema));

		$schema->addColumn('column', [
			'type' => 'int',
			'ai' => true,
			'length' => 11,
			'unsigned' => true,
			'zerofill' => true,
			'null' => true,
			'default' => null,
			'comment' => 'Some comment here'
		]);

		$expected = '`column` int(11) UNSIGNED ZEROFILL NULL DEFAULT NULL AUTO_INCREMENT COMMENT \'Some comment here\'';

		$this->assertEquals($expected, $this->object->formatColumns($schema));

		$schema->addColumn('column2', [
			'type' => 'varchar',
			'length' => 255,
			'null' => true
		]);

		$expected .= ",\n`column2` varchar(255) NULL";

		$this->assertEquals($expected, $this->object->formatColumns($schema));

		$schema->addColumn('column3', [
			'type' => 'smallint',
			'default' => 3
		]);

		$expected .= ",\n`column3` smallint NOT NULL DEFAULT '3'";

		$this->assertEquals($expected, $this->object->formatColumns($schema));

		// inherits values from type
		$schema->addColumn('column4', [
			'type' => 'datetime'
		]);

		$expected .= ",\n`column4` datetime NULL DEFAULT NULL";

		$this->assertEquals($expected, $this->object->formatColumns($schema));
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

		$this->assertEquals('(`id`, `username`, `email`)', $this->object->formatFields($fields, Query::INSERT));
		$this->assertEquals('`id` = ?, `username` = ?, `email` = ?', $this->object->formatFields($fields, Query::UPDATE));

		$func = new Query\Func('SUM', ['id' => Query\Func::FIELD]);
		$func->setDriver($this->object->getDriver());

		$fields = array_keys($fields);
		$fields[] = $func;

		$this->assertEquals('`id`, `username`, `email`, SUM(`id`)', $this->object->formatFields($fields, Query::SELECT));
		$this->assertEquals('*', $this->object->formatFields([], Query::SELECT));
	}

	/**
	 * Test group by formatting.
	 */
	public function testFormatGroupBy() {
		$this->assertEquals('', $this->object->formatGroupBy([]));
		$this->assertEquals('GROUP BY `id`, `username`', $this->object->formatGroupBy(['id', 'username']));
	}

	/**
	 * Test having predicate is formatted.
	 */
	public function testFormatHaving() {
		$pred = new Predicate(Predicate::ALSO);

		$this->assertEquals('', $this->object->formatHaving($pred));

		$pred->gte('age', 12);

		$this->assertEquals('HAVING `age` >= ?', $this->object->formatHaving($pred));
	}

	/**
	 * Test limit and offset is formatted.
	 */
	public function testFormatLimit() {
		$this->assertEquals('', $this->object->formatLimit(0));
		$this->assertEquals('LIMIT 5', $this->object->formatLimit(5));
		$this->assertEquals('LIMIT 10,5', $this->object->formatLimit(5, 10));
	}

	/**
	 * Test order by formatting.
	 */
	public function testFormatOrderBy() {
		$this->assertEquals('', $this->object->formatOrderBy([]));
		$this->assertEquals('ORDER BY `id` ASC', $this->object->formatOrderBy(['id' => 'asc']));
		$this->assertEquals('ORDER BY `id` ASC, `username` DESC', $this->object->formatOrderBy(['id' => 'asc', 'username' => 'desc']));
	}

	/**
	 * Test table is quoted.
	 */
	public function testFormatTable() {
		$this->assertEquals('`foobar`', $this->object->formatTable('foobar'));
	}

	/**
	 * Test table keys are built with primary, unique, foreign and index.
	 */
	public function testFormatTableKeys() {
		$schema = new Schema('foobar');
		$schema->addConstraint(Schema::CONSTRAINT_UNIQUE, 'primary');

		$expected = ",\nUNIQUE KEY `primary` (`primary`)";

		$this->assertEquals($expected, $this->object->formatTableKeys($schema));

		$schema->addConstraint(Schema::CONSTRAINT_UNIQUE, 'unique', [
			'constraint' => 'uniqueSymbol'
		]);

		$expected .= ",\nCONSTRAINT `uniqueSymbol` UNIQUE KEY `unique` (`unique`)";

		$this->assertEquals($expected, $this->object->formatTableKeys($schema));

		$schema->addConstraint(Schema::CONSTRAINT_FOREIGN, 'fk1', 'users.id');

		$expected .= ",\nFOREIGN KEY (`fk1`) REFERENCES `users`(`id`)";

		$this->assertEquals($expected, $this->object->formatTableKeys($schema));

		$schema->addConstraint(Schema::CONSTRAINT_FOREIGN, 'fk2', [
			'references' => 'posts.id',
			'onUpdate' => Schema::ACTION_SET_NULL,
			'onDelete' => Schema::ACTION_NONE
		]);

		$expected .= ",\nFOREIGN KEY (`fk2`) REFERENCES `posts`(`id`) ON DELETE NO ACTION ON UPDATE SET NULL";

		$this->assertEquals($expected, $this->object->formatTableKeys($schema));

		$schema->addIndex('column1');
		$schema->addIndex('column2');

		$expected .= ",\nKEY `column1` (`column1`),\nKEY `column2` (`column2`)";

		$this->assertEquals($expected, $this->object->formatTableKeys($schema));
	}

	/**
	 * Test table options are formatted.
	 */
	public function testFormatTableOptions() {
		$options = [];
		$this->assertEquals('', $this->object->formatTableOptions($options));

		$options['comment'] = 'Another comment';
		$this->assertEquals("DEFAULT COMMENT='Another comment'", $this->object->formatTableOptions($options));

		$options['characterSet'] = 'utf8';
		$this->assertEquals("DEFAULT COMMENT='Another comment' CHARACTER SET='utf8'", $this->object->formatTableOptions($options));

		$options['engine'] = 'MyISAM';
		$this->assertEquals("ENGINE=MyISAM DEFAULT COMMENT='Another comment' CHARACTER SET='utf8'", $this->object->formatTableOptions($options));
	}

	/**
	 * Test values are represented with question marks.
	 */
	public function testFormatValues() {
		$fields = [
			'id' => 1,
			'username' => 'miles',
			'email' => 'email@domain.com'
		];

		$this->assertEquals('(?, ?, ?)', $this->object->formatValues($fields, Query::INSERT));
	}

	/**
	 * Test where predicate is formatted.
	 */
	public function testFormatWhere() {
		$pred = new Predicate(Predicate::EITHER);

		$this->assertEquals('', $this->object->formatWhere($pred));

		$pred->between('id', 1, 100)->eq('status', 1);

		$this->assertEquals('WHERE `id` BETWEEN ? AND ? OR `status` = ?', $this->object->formatWhere($pred));
	}

	/**
	 * Test single clause fetching.
	 */
	public function testGetClause() {
		$this->assertEquals('AND', $this->object->getClause('and'));

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
		$this->assertInstanceOf('Titon\Model\Driver', $this->object->getDriver());
	}

	/**
	 * Test single statement fetching.
	 */
	public function testGetStatement() {
		$this->assertEquals('TRUNCATE {table}', $this->object->getStatement('truncate'));

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
	}

	/**
	 * Test multiple identifier quoting.
	 */
	public function testQuoteList() {
		$this->assertEquals('`foo`, `bar`, `baz`', $this->object->quoteList(['foo', '`bar', '`baz`']));
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