<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Query;

use Titon\Common\Config;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Query\Func.
 *
 * @property \Titon\Model\Query\Func $object
 */
class FuncTest extends TestCase {

	/**
	 * @type \Titon\Model\Driver
	 */
	public $driver;

	/**
	 * Setup the driver.
	 */
	protected function setUp() {
		parent::setUp();

		$this->driver = new DriverStub('default', Config::get('db'));
		$this->driver->connect();
	}

	/**
	 * Test regular types in arguments.
	 */
	public function testTypeArguments() {
		$func = new Func('SUBSTRING', ['TitonFramework', 5]);
		$func->setDriver($this->driver);

		$this->assertEquals("SUBSTRING('TitonFramework', 5)", $func->toString());

		$func = new Func('INSERT', ['Titon', 3, 5, 'Framework']);
		$func->setDriver($this->driver);

		$this->assertEquals("INSERT('Titon', 3, 5, 'Framework')", $func->toString());

		$func = new Func('CHAR', [77, 77.3, '77.3']);
		$func->setDriver($this->driver);

		$this->assertEquals("CHAR(77, 77.3, '77.3')", $func->toString());

		$func = new Func('CONCAT', ['Titon', null, 'Framework']);
		$func->setDriver($this->driver);

		$this->assertEquals("CONCAT('Titon', null, 'Framework')", $func->toString());
	}

	/**
	 * Test literal values in arguments.
	 */
	public function testLiteralArguments() {
		$func = new Func('SUBSTRING', ['Titon', 'FROM -4 FOR 2' => Func::LITERAL], ' ');
		$func->setDriver($this->driver);

		$this->assertEquals("SUBSTRING('Titon' FROM -4 FOR 2)", $func->toString());

		$func = new Func('TRIM', ["TRAILING 'xyz' FROM 'barxxyz'" => Func::LITERAL]);
		$func->setDriver($this->driver);

		$this->assertEquals("TRIM(TRAILING 'xyz' FROM 'barxxyz')", $func->toString());
	}

	/**
	 * Test field quoting in arguments.
	 */
	public function testFieldArguments() {
		$func = new Func('COUNT', ['id' => Func::FIELD]);
		$func->setDriver($this->driver);

		$this->assertEquals("COUNT(`id`)", $func->toString());
	}

	/**
	 * Test nested functions.
	 */
	public function testNestedFunctions() {
		$func1 = new Func('HEX', 255);
		$func1->setDriver($this->driver);

		$func2 = new Func('CONV', [$func1, 16, 10]);
		$func2->setDriver($this->driver);

		$this->assertEquals("CONV(HEX(255), 16, 10)", $func2->toString());

		$func1 = new Func('CHAR', ['0x65 USING utf8' => Func::LITERAL]);
		$func1->setDriver($this->driver);

		$func2 = new Func('CHARSET', $func1);
		$func2->setDriver($this->driver);

		$this->assertEquals("CHARSET(CHAR(0x65 USING utf8))", $func2->toString());
	}

}