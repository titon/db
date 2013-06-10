<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Source\Dbo;

use Titon\Common\Config;
use Titon\Model\Query;
use Titon\Model\Query\Clause;
use Titon\Model\Source\Dbo\Mysql;
use Titon\Test\TestCase;
use \Exception;

/**
 * Test class for Titon\Model\Source\Dbo\Mysql.
 *
 * @property \Titon\Model\Source\Dbo\Mysql $object
 */
class MysqlTest extends TestCase {

	/**
	 * Use MySql as the base.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new Mysql('mysql', Config::get('db'));
		$this->object->connect();
	}

	/**
	 * Test that SQL escaping works.
	 */
	public function testEscape() {
		$this->assertSame(null, $this->object->escape(null));
		$this->assertSame("'123'", $this->object->escape(123));
		$this->assertSame("'123'", $this->object->escape('123'));
		$this->assertSame("'1337.5'", $this->object->escape(1337.50));
		$this->assertSame("'1337.5'", $this->object->escape('1337.50'));
		$this->assertSame("'A string with \\\"double\\\" quotes'", $this->object->escape('A string with "double" quotes'));
		$this->assertSame("'A string with \\'single\\' quotes'", $this->object->escape("A string with 'single' quotes"));
		$this->assertSame("'1'", $this->object->escape(true));
		$this->assertSame("'0'", $this->object->escape(false));
	}

	/**
	 * Test that table names are quoted.
	 */
	public function testFormatTable() {
		$this->assertEquals('`tableName`', $this->object->formatTable('tableName'));
	}

	/**
	 * Test that field SQLs is correct.
	 */
	public function testFormatFields() {
		$data = [
			'id' => 1337,
			'title' => 'Titon',
			'created' => date('Y-m-d H:i:s')
		];

		$this->assertEquals('(`id`, `title`, `created`)', $this->object->formatFields($data, Query::INSERT));
		$this->assertEquals('*', $this->object->formatFields([], Query::SELECT));
		$this->assertEquals('`id`, `title`, `created`', $this->object->formatFields(array_keys($data), Query::SELECT));
		$this->assertEquals('`id` = ?, `title` = ?, `created` = ?', $this->object->formatFields($data, Query::UPDATE));
	}

	/**
	 * Test that values SQLs is correct.
	 */
	public function testFormatValues() {
		$data = [
			'id' => 1337,
			'title' => 'Titon',
			'created' => date('Y-m-d H:i:s')
		];

		$this->assertEquals('(?, ?, ?)', $this->object->formatValues($data, Query::INSERT));
		$this->assertEquals('', $this->object->formatValues($data, Query::SELECT));
		$this->assertEquals('', $this->object->formatValues($data, Query::UPDATE));
	}

	/**
	 * Test that where clause builds.
	 */
	public function testFormatWhere() {
		$clause = new Clause();

		$this->assertEquals('', $this->object->formatWhere($clause));

		$clause->also('id', 5, '>=')->also('id', 25, '<=');

		$this->assertEquals('WHERE `id` >= ? AND `id` <= ?', $this->object->formatWhere($clause));
	}

	/**
	 * Test that group by SQL is correct.
	 */
	public function testFormatGroupBy() {
		$this->assertEquals('', $this->object->formatGroupBy([]));
		$this->assertEquals('GROUP BY `id`', $this->object->formatGroupBy(['id']));
		$this->assertEquals('GROUP BY `id`, `created`', $this->object->formatGroupBy(['id', 'created']));
	}

	/**
	 * Test that having clause builds.
	 */
	public function testFormatHaving() {
		$clause = new Clause();

		$this->assertEquals('', $this->object->formatHaving($clause));

		$clause->either('title', 'Titon', Clause::LIKE)->either('title', 'Titon', Clause::NOT_LIKE);

		$this->assertEquals('HAVING `title` LIKE ? OR `title` NOT LIKE ?', $this->object->formatHaving($clause));
	}

	/**
	 * Test that order by SQL is correct.
	 */
	public function testFormatOrderBy() {
		$this->assertEquals('', $this->object->formatOrderBy([]));
		$this->assertEquals('ORDER BY `id` ASC', $this->object->formatOrderBy(['id' => 'ASC']));
		$this->assertEquals('ORDER BY `id` ASC, `created` DESC', $this->object->formatOrderBy(['id' => 'ASC', 'created' => 'DESC']));
	}

	/**
	 * Test that limit and offset SQL is correct.
	 */
	public function testFormatLimit() {
		$this->assertEquals('', $this->object->formatLimit(0));
		$this->assertEquals('LIMIT 25', $this->object->formatLimit(25));
		$this->assertEquals('LIMIT 25,45', $this->object->formatLimit('45', 25));
	}

	/**
	 * Test that clause conditions build correctly.
	 */
	public function testFormatClause() {
		$clause = new Clause();
		$this->assertEquals('', $this->object->formatClause($clause));

		// AND
		$clause = new Clause();
		$clause->also('id', 1)->also('id', 5, '>=')->also('title', 'Titon', Clause::NOT_LIKE);
		$this->assertEquals('`id` = ? AND `id` >= ? AND `title` NOT LIKE ?', $this->object->formatClause($clause));

		// OR
		$clause = new Clause();
		$clause->either('color', 'red', '!=')->either('color', 'blue', '!=');
		$this->assertEquals('`color` != ? OR `color` != ?', $this->object->formatClause($clause));

		// AND, OR order
		// The first one called takes precedence
		$clause = new Clause();
		$clause->either('color', 'red', '!=')->either('color', 'blue', '!=')->also('color', 'green');
		$this->assertEquals('`color` != ? OR `color` != ? OR `color` = ?', $this->object->formatClause($clause));

		// IN, NOT IN
		$clause = new Clause();
		$clause->also('id', [1, 2, 3, 4, 5], Clause::IN)->also('id', [1337, 666], Clause::NOT_IN);
		$this->assertEquals('`id` IN (?, ?, ?, ?, ?) AND `id` NOT IN (?, ?)', $this->object->formatClause($clause));

		// NULL, NOT NULL
		$clause = new Clause();
		$clause->also('id', null)->also('id', null, Clause::NOT_NULL);
		$this->assertEquals('`id` IS NULL AND `id` IS NOT NULL', $this->object->formatClause($clause));

		// BETWEEN, NOT BETWEEN
		$clause = new Clause();
		$clause->also('id', [4, 5], Clause::BETWEEN)->also('id', [1337, 666], Clause::NOT_BETWEEN);
		$this->assertEquals('`id` BETWEEN ? AND ? AND `id` NOT BETWEEN ? AND ?', $this->object->formatClause($clause));

		// NESTING
		$clause = new Clause();
		$clause->either('color', 'red', '!=')->either('color', 'blue', '!=')->either('color', 'green', '!=');
		$clause->group(function() {
			$this->also('size', 1, '>=');
			$this->also('size', 10, '<=');
			$this->group(function() {
				$this->also('size', 15);
				$this->also('size', 20);
			});
		});
		$this->assertEquals('`color` != ? OR `color` != ? OR `color` != ? OR (`size` >= ? AND `size` <= ? AND (`size` = ? AND `size` = ?))', $this->object->formatClause($clause));
	}

	/**
	 * Test that DSN is built and returned.
	 */
	public function testGetDsn() {
		$this->assertEquals('mysql:dbname=titon_test;host=localhost;port=3306;charset=UTF-8', $this->object->getDsn());

		$this->object->config->socket = '/path/to/db.sock';
		$this->assertEquals('mysql:dbname=titon_test;unix_socket=/path/to/db.sock', $this->object->getDsn());

		$this->object->config->dsn = 'mysql:complete=override';
		$this->assertEquals('mysql:complete=override', $this->object->getDsn());
	}

	/**
	 * Test that fields are quoted.
	 */
	public function testQuote() {
		$this->assertEquals('`field`', $this->object->formatTable('field'));
		$this->assertEquals('`field`', $this->object->formatTable('`field'));
		$this->assertEquals('`field`', $this->object->formatTable('field`'));
		$this->assertEquals('`field`', $this->object->formatTable('`field`'));
		$this->assertEquals('`field`', $this->object->formatTable('``field``'));
	}

	/**
	 * Test that an array of fields are returned as a quoted string.
	 */
	public function testQuoteFields() {
		$this->assertEquals('', $this->object->quoteFields([]));
		$this->assertEquals('`foo`, `bar`', $this->object->quoteFields(['foo', 'bar']));
	}

}