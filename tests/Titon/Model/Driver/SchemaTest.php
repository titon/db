<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Driver;

use Titon\Model\Driver\Schema;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Driver\Schema.
 *
 * @property \Titon\Model\Driver\Schema $object
 */
class SchemaTest extends TestCase {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->object = new Schema('table');
	}

	/**
	 * Test column building.
	 */
	public function testColumns() {
		$this->assertFalse($this->object->hasColumn('id'));

		// Add a column
		$this->object->addColumn('id', [
			'type' => 'integer',
			'ai' => true,
			'primary' => true
		]);

		$this->assertTrue($this->object->hasColumn('id'));
		$this->assertEquals([
			'id' => [
				'type' => 'integer',
				'ai' => true,
				'primary' => true,
				'field' => 'id'
			]
		], $this->object->getColumns());

		// Add multiple columns
		$this->object->addColumns([
			'status' => [
				'type' => 'smallint',
				'length' => 10,
				'index' => true,
				'null' => true,
				'default' => null
			],
			'username' => [
				'type' => 'varchar',
				'length' => 30,
				'unique' => true
			]
		]);

		$this->assertEquals([
			'id' => [
				'type' => 'integer',
				'ai' => true,
				'primary' => true,
				'field' => 'id'
			],
			'status' => [
				'type' => 'smallint',
				'length' => 10,
				'index' => true,
				'null' => true,
				'default' => null,
				'field' => 'status'
			],
			'username' => [
				'type' => 'varchar',
				'length' => 30,
				'unique' => true,
				'field' => 'username'
			]
		], $this->object->getColumns());

		// Check indexes were set
		$this->assertEquals([
			'constraint' => '',
			'columns' => ['id']
		], $this->object->getPrimaryKey());

		$this->assertEquals([
			'username' => [
				'index' => 'username',
				'constraint' => '',
				'columns' => ['username']
			]
		], $this->object->getUniqueKeys());

		$this->assertEquals([
			'status' => ['status']
		], $this->object->getIndexes());
	}

	/**
	 * Test table name is returned.
	 */
	public function testTable() {
		$this->assertEquals('table', $this->object->getTable());
	}

	/**
	 * Test index building.
	 */
	public function testIndexes() {
		$this->assertEquals([], $this->object->getIndexes());

		$this->object->addIndex('column1');
		$this->assertEquals([
			'column1' => ['column1']
		], $this->object->getIndexes());

		$this->object->addIndex('column2');
		$this->assertEquals([
			'column1' => ['column1'],
			'column2' => ['column2']
		], $this->object->getIndexes());

		// Add to another index
		$this->object->addIndex('column3', 'column1');
		$this->assertEquals([
			'column1' => ['column1', 'column3'],
			'column2' => ['column2']
		], $this->object->getIndexes());
	}

	/**
	 * Test primary key building.
	 */
	public function testPrimaryKey() {
		$this->assertEquals([], $this->object->getPrimaryKey());

		$this->object->addPrimary('column1');

		$this->assertEquals([
			'constraint' => '',
			'columns' => ['column1']
		], $this->object->getPrimaryKey());

		// Adds another column
		$this->object->addPrimary('column2', 'ignoredSymbol');

		$this->assertEquals([
			'constraint' => '',
			'columns' => ['column1', 'column2']
		], $this->object->getPrimaryKey());
	}

	/**
	 * Test primary key with constraint symbol.
	 */
	public function testPrimaryKeyConstraint() {
		$this->assertEquals([], $this->object->getPrimaryKey());

		$this->object->addPrimary('column1', 'symbolName');

		$this->assertEquals([
			'constraint' => 'symbolName',
			'columns' => ['column1']
		], $this->object->getPrimaryKey());
	}

	/**
	 * Test unique key building.
	 */
	public function testUniqueKey() {
		$this->assertEquals([], $this->object->getUniqueKeys());

		// Use column name as index if its empty
		$this->object->addUnique('column1');

		$this->assertEquals([
			'column1' => [
				'index' => 'column1',
				'constraint' => '',
				'columns' => ['column1']
			]
		], $this->object->getUniqueKeys());

		// Add another column with a different index
		$this->object->addUnique('column2', 'indexName');

		$this->assertEquals([
			'column1' => [
				'index' => 'column1',
				'constraint' => '',
				'columns' => ['column1']
			],
			'indexName' => [
				'index' => 'indexName',
				'constraint' => '',
				'columns' => ['column2']
			]
		], $this->object->getUniqueKeys());

		// Add another column to a current index
		$this->object->addUnique('column3', 'indexName');

		$this->assertEquals([
			'column1' => [
				'index' => 'column1',
				'constraint' => '',
				'columns' => ['column1']
			],
			'indexName' => [
				'index' => 'indexName',
				'constraint' => '',
				'columns' => ['column2', 'column3']
			]
		], $this->object->getUniqueKeys());
	}

	/**
	 * Test unique key with constraint symbol.
	 */
	public function testUniqueKeyConstraint() {
		$this->assertEquals([], $this->object->getUniqueKeys());

		// Use a constraint symbol
		$this->object->addUnique('column1', ['constraint' => 'symbolName']);

		$this->assertEquals([
			'column1' => [
				'index' => 'column1',
				'constraint' => 'symbolName',
				'columns' => ['column1']
			]
		], $this->object->getUniqueKeys());

		// Use a constraint symbol and custom index
		$this->object->addUnique('column2', ['constraint' => 'otherSymbol', 'index' => 'indexName']);

		$this->assertEquals([
			'column1' => [
				'index' => 'column1',
				'constraint' => 'symbolName',
				'columns' => ['column1']
			],
			'indexName' => [
				'index' => 'indexName',
				'constraint' => 'otherSymbol',
				'columns' => ['column2']
			]
		], $this->object->getUniqueKeys());
	}

	/**
	 * Test foreign key building.
	 */
	public function testForeignKey() {
		$this->assertEquals([], $this->object->getForeignKeys());

		// Add reference to table.id
		$this->object->addForeign('column1', 'table.id');

		$this->assertEquals([
			'column1' => [
				'column' => 'column1',
				'references' => 'table.id',
				'constraint' => ''
			]
		], $this->object->getForeignKeys());

		// Add update/delete actions
		$this->object->addForeign('column2', [
			'references' => 'table.id',
			'onUpdate' => Dialect::RESTRICT,
			'onDelete' => Dialect::SET_NULL
		]);

		$this->assertEquals([
			'column1' => [
				'column' => 'column1',
				'references' => 'table.id',
				'constraint' => ''
			],
			'column2' => [
				'column' => 'column2',
				'references' => 'table.id',
				'constraint' => '',
				'onUpdate' => Dialect::RESTRICT,
				'onDelete' => Dialect::SET_NULL
			]
		], $this->object->getForeignKeys());
	}

	/**
	 * Test foreign key with constraint symbol.
	 */
	public function testForeignKeyConstraint() {
		$this->assertEquals([], $this->object->getForeignKeys());

		// Add reference to table.id
		$this->object->addForeign('column1', [
			'references' => 'table.id',
			'constraint' => 'symbolName'
		]);

		$this->assertEquals([
			'column1' => [
				'column' => 'column1',
				'references' => 'table.id',
				'constraint' => 'symbolName'
			]
		], $this->object->getForeignKeys());
	}

	/**
	 * Test option setting and getting.
	 */
	public function testOptions() {
		$this->assertEquals([], $this->object->getOptions());

		$this->object->addOption('engine', 'MyIsam');

		$this->assertEquals([
			'engine' => 'MyIsam'
		], $this->object->getOptions());

		$this->object->addOptions([
			'engine' => 'InnoDB',
			'comment' => 'Foobar'
		]);

		$this->assertEquals([
			'engine' => 'InnoDB',
			'comment' => 'Foobar'
		], $this->object->getOptions());
	}

}