<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Source\Dbo;

use Titon\Common\Config;
use Titon\Model\Source\Dbo\Mysql;
use Titon\Test\TestCase;

/**
 * Test database queries for Titon\Model\Source\Dbo\Mysql.
 *
 * @property \Titon\Model\Source\Dbo\Mysql $object
 */
class MysqlQueryTest extends TestCase {

	/**
	 * Use MySql as the base.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new Mysql('mysql', Config::get('db'));
		$this->object->connect();
	}

	public function testSelect() {

	}

}