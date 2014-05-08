<?php
namespace Titon\Db\Driver;

use Titon\Test\TestCase;
use \Exception;

/**
 * @property \Titon\Db\Driver\Schema $object
 */
class SchemaTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new Schema('table');
    }

    public function testColumns() {
        $this->assertFalse($this->object->hasColumn('id'));
        $this->assertFalse($this->object->hasColumns());

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
                'field' => 'username',
                'null' => true
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

        // Single
        $this->assertEquals([
            'type' => 'varchar',
            'length' => 30,
            'unique' => true,
            'field' => 'username',
            'null' => true
        ], $this->object->getColumn('username'));

        $this->assertTrue($this->object->hasColumns());

        try {
            $this->object->getColumn('foobar');

            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $this->object->addColumn('country_id', [
            'type' => 'integer',
            'foreign' => 'countries.id'
        ]);

        $this->assertEquals([
            'type' => 'integer',
            'foreign' => 'countries.id',
            'field' => 'country_id',
            'null' => true
        ], $this->object->getColumn('country_id'));
    }

    public function testTable() {
        $this->assertEquals('table', $this->object->getTable());
    }

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

    public function testPrimaryKeyConstraint() {
        $this->assertEquals([], $this->object->getPrimaryKey());

        $this->object->addPrimary('column1', 'symbolName');

        $this->assertEquals([
            'constraint' => 'symbolName',
            'columns' => ['column1']
        ], $this->object->getPrimaryKey());
    }

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

    public function testForeignKeyFailNoReference() {
        try {
            $this->object->addForeign('column1', ['constraint' => 'foo']);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

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