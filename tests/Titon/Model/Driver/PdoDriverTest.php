<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opendriver.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use PDO;
use Titon\Common\Config;
use Titon\Test\Stub\DriverStub;
use Titon\Test\Stub\Model\User;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Driver\AbstractPdoDriver.
 *
 * @property \Titon\Model\Driver\AbstractPdoDriver $object
 */
class PdoDriverTest extends TestCase {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new DriverStub('default', Config::get('db'));
		$this->object->connect();

		$this->model = new User();
	}

	/**
	 * Disconnect just in case.
	 */
	protected function tearDown() {
		$this->object->disconnect();
	}

	/**
	 * Test statement building.
	 */
	public function testBuildStatement() {

	}

	/**
	 * Test value escaping and quoting.
	 */
	public function testEscape() {
		$this->assertSame(null, $this->object->escape(null));
		$this->assertSame("'12345'", $this->object->escape(12345));
		$this->assertSame("'67890'", $this->object->escape('67890'));
		$this->assertSame("'666.25'", $this->object->escape(666.25));
		$this->assertSame("'abc'", $this->object->escape('abc'));
		$this->assertSame("'1'", $this->object->escape(true));
	}

	/**
	 * Test DSN building.
	 */
	public function testGetDsn() {
		$this->assertEquals('mysql:dbname=titon_test;host=127.0.0.1;port=3306;charset=utf8', $this->object->getDsn());

		$this->object->config->encoding = '';
		$this->object->config->port = 1337;
		$this->assertEquals('mysql:dbname=titon_test;host=127.0.0.1;port=1337', $this->object->getDsn());

		$this->object->config->dsn = 'custom:dsn';
		$this->assertEquals('custom:dsn', $this->object->getDsn());
	}

	/**
	 * Test query execution.
	 */
	public function testQuery() {

	}

	/**
	 * Test that query params are resolved for binds.
	 * Should be in correct order.
	 */
	public function testResolveParams() {
		$query1 = new Query(Query::SELECT, $this->model);
		$query1->where('id', 1)->where(function() {
			$this->like('name', 'Titon')->in('size', [1, 2, 3]);
		});

		$this->assertEquals([
			[1, PDO::PARAM_INT],
			['Titon', PDO::PARAM_STR],
			[1, PDO::PARAM_INT],
			[2, PDO::PARAM_INT],
			[3, PDO::PARAM_INT],
		], $this->object->resolveParams($query1));

		// Include fields
		$query2 = new Query(Query::UPDATE, $this->model);
		$query2->fields([
			'username' => 'miles',
			'age' => 26
		])->where('id', 666);

		$this->assertEquals([
			['miles', PDO::PARAM_STR],
			[26, PDO::PARAM_INT],
			[666, PDO::PARAM_INT],
		], $this->object->resolveParams($query2));

		// All at once!
		$query3 = new Query(Query::UPDATE, $this->model);
		$query3->fields([
			'username' => 'miles',
			'age' => 26
		])->orWhere(function() {
			$this
				->in('id', [4, 5, 6])
				->also(function() {
					$this->eq('status', true)->notEq('email', 'email@domain.com');
				})
				->between('age', 30, 50);
		});

		$this->assertEquals([
			['miles', PDO::PARAM_STR],
			[26, PDO::PARAM_INT],
			[4, PDO::PARAM_INT],
			[5, PDO::PARAM_INT],
			[6, PDO::PARAM_INT],
			[true, PDO::PARAM_BOOL],
			['email@domain.com', PDO::PARAM_STR],
			[30, PDO::PARAM_INT],
			[50, PDO::PARAM_INT],
		], $this->object->resolveParams($query3));
	}

	/**
	 * Test type introspecting.
	 */
	public function testResolveType() {
		$this->assertSame(PDO::PARAM_NULL, $this->object->resolveType(null));
		$this->assertSame(PDO::PARAM_INT, $this->object->resolveType(12345));
		$this->assertSame(PDO::PARAM_INT, $this->object->resolveType('67890'));
		$this->assertSame(PDO::PARAM_STR, $this->object->resolveType(666.25));
		$this->assertSame(PDO::PARAM_STR, $this->object->resolveType('abc'));
		$this->assertSame(PDO::PARAM_BOOL, $this->object->resolveType(true));
	}

}