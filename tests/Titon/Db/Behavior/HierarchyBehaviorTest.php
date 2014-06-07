<?php
namespace Titon\Db\Behavior;

use Titon\Db\Entity;
use Titon\Db\EntityCollection;
use Titon\Test\Stub\Repository\Category;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Repository $object
 */
class HierarchyBehaviorTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new Category();
        $this->object->addBehavior(new HierarchyBehavior());

        $this->loadFixtures('Categories');
    }

    public function testSave() {
        $this->object->create([
            'parent_id' => 22,
            'name' => 'Prawn'
        ]);

        // All nodes after the newly inserted should have increased left and right
        $this->assertEquals(
            new Entity(['id' => 20, 'name' => 'Seafood', 'parent_id' => null, 'left' => 39, 'right' => 54, 'Nodes' => [
                new Entity(['id' => 21, 'name' => 'Fish', 'parent_id' => 20, 'left' => 40, 'right' => 41]),
                new Entity(['id' => 22, 'name' => 'Shellfish', 'parent_id' => 20, 'left' => 42, 'right' => 51, 'Nodes' => [
                    new Entity(['id' => 23, 'name' => 'Shrimp', 'parent_id' => 22, 'left' => 43, 'right' => 44]),
                    new Entity(['id' => 24, 'name' => 'Crab', 'parent_id' => 22, 'left' => 45, 'right' => 46]),
                    new Entity(['id' => 25, 'name' => 'Lobster', 'parent_id' => 22, 'left' => 47, 'right' => 48]),
                    new Entity(['id' => 27, 'name' => 'Prawn', 'parent_id' => 22, 'left' => 49, 'right' => 50]),
                ]]),
                new Entity(['id' => 26, 'name' => 'Calamari', 'parent_id' => 20, 'left' => 52, 'right' => 53])
            ]])
        , $this->object->Hierarchy->getTree(20));

        // Add to root
        $this->object->create([
            'parent_id' => null,
            'name' => 'Vegetables'
        ]);

        $this->assertEquals(new Entity([
            'id' => 28,
            'parent_id' => null,
            'name' => 'Vegetables',
            'left' => 55,
            'right' => 56
        ]), $this->object->Hierarchy->getLastNode());

        // Add some children
        $this->object->create(['parent_id' => 28, 'name' => 'Broccoli']);
        $this->object->create(['parent_id' => 28, 'name' => 'Spinach']);
        $this->object->create(['parent_id' => 16, 'name' => 'Duck']);
        $this->object->create(['parent_id' => 5, 'name' => 'Raspberry']);
        $this->object->create(['parent_id' => 1, 'name' => 'Mango']);

        // Check the tree
        $this->assertEquals([
            new Entity(['id' => 1, 'name' => 'Fruit', 'parent_id' => null, 'left' => 1, 'right' => 24, 'Nodes' => [
                new Entity(['id' => 2, 'name' => 'Banana', 'parent_id' => 1, 'left' => 2, 'right' => 3]),
                new Entity(['id' => 3, 'name' => 'Apple', 'parent_id' => 1, 'left' => 4, 'right' => 5]),
                new Entity(['id' => 4, 'name' => 'Pear', 'parent_id' => 1, 'left' => 6, 'right' => 7]),
                new Entity(['id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 8, 'right' => 17, 'Nodes' => [
                    new Entity(['id' => 6, 'name' => 'Blueberry', 'parent_id' => 5, 'left' => 9, 'right' => 10]),
                    new Entity(['id' => 7, 'name' => 'Blackberry', 'parent_id' => 5, 'left' => 11, 'right' => 12]),
                    new Entity(['id' => 8, 'name' => 'Strawberry', 'parent_id' => 5, 'left' => 13, 'right' => 14]),
                    new Entity(['id' => 32, 'name' => 'Raspberry', 'parent_id' => 5, 'left' => 15, 'right' => 16]),
                ]]),
                new Entity(['id' => 9, 'name' => 'Pineapple', 'parent_id' => 1, 'left' => 18, 'right' => 19]),
                new Entity(['id' => 10, 'name' => 'Watermelon', 'parent_id' => 1, 'left' => 20, 'right' => 21]),
                new Entity(['id' => 33, 'name' => 'Mango', 'parent_id' => 1, 'left' => 22, 'right' => 23]),
            ]]),
            new Entity(['id' => 11, 'name' => 'Grain', 'parent_id' => null, 'left' => 25, 'right' => 34, 'Nodes' => [
                new Entity(['id' => 12, 'name' => 'Wheat', 'parent_id' => 11, 'left' => 26, 'right' => 27]),
                new Entity(['id' => 13, 'name' => 'Bulgur', 'parent_id' => 11, 'left' => 28, 'right' => 29]),
                new Entity(['id' => 14, 'name' => 'Barley', 'parent_id' => 11, 'left' => 30, 'right' => 31]),
                new Entity(['id' => 15, 'name' => 'Farro', 'parent_id' => 11, 'left' => 32, 'right' => 33]),
            ]]),
            new Entity(['id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 35, 'right' => 44, 'Nodes' => [
                new Entity(['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 36, 'right' => 37]),
                new Entity(['id' => 18, 'name' => 'Pork', 'parent_id' => 16, 'left' => 38, 'right' => 39]),
                new Entity(['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 40, 'right' => 41]),
                new Entity(['id' => 31, 'name' => 'Duck', 'parent_id' => 16, 'left' => 42, 'right' => 43]),
            ]]),
            new Entity(['id' => 20, 'name' => 'Seafood', 'parent_id' => null, 'left' => 45, 'right' => 60, 'Nodes' => [
                new Entity(['id' => 21, 'name' => 'Fish', 'parent_id' => 20, 'left' => 46, 'right' => 47]),
                new Entity(['id' => 22, 'name' => 'Shellfish', 'parent_id' => 20, 'left' => 48, 'right' => 57, 'Nodes' => [
                    new Entity(['id' => 23, 'name' => 'Shrimp', 'parent_id' => 22, 'left' => 49, 'right' => 50]),
                    new Entity(['id' => 24, 'name' => 'Crab', 'parent_id' => 22, 'left' => 51, 'right' => 52]),
                    new Entity(['id' => 25, 'name' => 'Lobster', 'parent_id' => 22, 'left' => 53, 'right' => 54]),
                    new Entity(['id' => 27, 'name' => 'Prawn', 'parent_id' => 22, 'left' => 55, 'right' => 56]),
                ]]),
                new Entity(['id' => 26, 'name' => 'Calamari', 'parent_id' => 20, 'left' => 58, 'right' => 59])
            ]]),
            new Entity(['id' => 28, 'name' => 'Vegetables', 'parent_id' => null, 'left' => 61, 'right' => 66, 'Nodes' => [
                new Entity(['id' => 29, 'name' => 'Broccoli', 'parent_id' => 28, 'left' => 62, 'right' => 63]),
                new Entity(['id' => 30, 'name' => 'Spinach', 'parent_id' => 28, 'left' => 64, 'right' => 65])
            ]]),
        ], $this->object->Hierarchy->getTree());

        // Add a child with custom left and right (should be removed)
        $this->object->create([
            'parent_id' => 28,
            'name' => 'Corn',
            'left' => 123,
            'right' => 456
        ]);

        $this->assertEquals([
            1  => 'Fruit',
            2  => '    Banana',
            3  => '    Apple',
            4  => '    Pear',
            5  => '    Berry',
            6  => '        Blueberry',
            7  => '        Blackberry',
            8  => '        Strawberry',
            32 => '        Raspberry',
            9  => '    Pineapple',
            10 => '    Watermelon',
            33 => '    Mango',
            11 => 'Grain',
            12 => '    Wheat',
            13 => '    Bulgur',
            14 => '    Barley',
            15 => '    Farro',
            16 => 'Meat',
            17 => '    Beef',
            18 => '    Pork',
            19 => '    Chicken',
            31 => '    Duck',
            20 => 'Seafood',
            21 => '    Fish',
            22 => '    Shellfish',
            23 => '        Shrimp',
            24 => '        Crab',
            25 => '        Lobster',
            27 => '        Prawn',
            26 => '    Calamari',
            28 => 'Vegetables',
            29 => '    Broccoli',
            30 => '    Spinach',
            34 => '    Corn'
        ], $this->object->Hierarchy->getList());
    }

    public function testSaveCheckIsSkipped() {
        $this->object->Hierarchy->setConfig('onSave', false);

        $this->object->update(18, [
            'name' => 'Pork-y',
            'left' => 666,
            'right' => 1337
        ]);

        $this->assertEquals(new Entity([
            'id' => 18,
            'name' => 'Pork-y',
            'parent_id' => 16,
            'left' => 666,
            'right' => 1337
        ]), $this->object->Hierarchy->getNode(18));
    }

    public function testDelete() {
        $this->assertEquals(1, $this->object->delete(18));

        $this->assertEquals(new Entity([
            'id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 31, 'right' => 36, 'Nodes' => [
                new Entity(['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 32, 'right' => 33]),
                new Entity(['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 34, 'right' => 35]),
        ]]), $this->object->Hierarchy->getTree(16));
    }

    public function testDeleteFailsWithChildren() {
        $this->assertEquals(0, $this->object->delete(16));

        $this->assertEquals(new Entity([
            'id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 31, 'right' => 38, 'Nodes' => [
                new Entity(['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 32, 'right' => 33]),
                new Entity(['id' => 18, 'name' => 'Pork', 'parent_id' => 16, 'left' => 34, 'right' => 35]),
                new Entity(['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 36, 'right' => 37]),
        ]]), $this->object->Hierarchy->getTree(16));
    }

    public function testDeleteChildrenCheckIsSkipped() {
        $this->object->Hierarchy->setConfig('onDelete', false);

        $this->assertEquals(1, $this->object->delete(16));

        $this->assertEquals([], $this->object->Hierarchy->getTree(16));
    }

    public function testGetTree() {
        $this->assertEquals([
            new Entity(['id' => 1, 'name' => 'Fruit', 'parent_id' => null, 'left' => 1, 'right' => 20, 'Nodes' => [
                new Entity(['id' => 2, 'name' => 'Banana', 'parent_id' => 1, 'left' => 2, 'right' => 3]),
                new Entity(['id' => 3, 'name' => 'Apple', 'parent_id' => 1, 'left' => 4, 'right' => 5]),
                new Entity(['id' => 4, 'name' => 'Pear', 'parent_id' => 1, 'left' => 6, 'right' => 7]),
                new Entity(['id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 8, 'right' => 15, 'Nodes' => [
                    new Entity(['id' => 6, 'name' => 'Blueberry', 'parent_id' => 5, 'left' => 9, 'right' => 10]),
                    new Entity(['id' => 7, 'name' => 'Blackberry', 'parent_id' => 5, 'left' => 11, 'right' => 12]),
                    new Entity(['id' => 8, 'name' => 'Strawberry', 'parent_id' => 5, 'left' => 13, 'right' => 14]),
                ]]),
                new Entity(['id' => 9, 'name' => 'Pineapple', 'parent_id' => 1, 'left' => 16, 'right' => 17]),
                new Entity(['id' => 10, 'name' => 'Watermelon', 'parent_id' => 1, 'left' => 18, 'right' => 19]),
            ]]),
            new Entity(['id' => 11, 'name' => 'Grain', 'parent_id' => null, 'left' => 21, 'right' => 30, 'Nodes' => [
                new Entity(['id' => 12, 'name' => 'Wheat', 'parent_id' => 11, 'left' => 22, 'right' => 23]),
                new Entity(['id' => 13, 'name' => 'Bulgur', 'parent_id' => 11, 'left' => 24, 'right' => 25]),
                new Entity(['id' => 14, 'name' => 'Barley', 'parent_id' => 11, 'left' => 26, 'right' => 27]),
                new Entity(['id' => 15, 'name' => 'Farro', 'parent_id' => 11, 'left' => 28, 'right' => 29]),
            ]]),
            new Entity(['id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 31, 'right' => 38, 'Nodes' => [
                new Entity(['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 32, 'right' => 33]),
                new Entity(['id' => 18, 'name' => 'Pork', 'parent_id' => 16, 'left' => 34, 'right' => 35]),
                new Entity(['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 36, 'right' => 37]),
            ]]),
            new Entity(['id' => 20, 'name' => 'Seafood', 'parent_id' => null, 'left' => 39, 'right' => 52, 'Nodes' => [
                new Entity(['id' => 21, 'name' => 'Fish', 'parent_id' => 20, 'left' => 40, 'right' => 41]),
                new Entity(['id' => 22, 'name' => 'Shellfish', 'parent_id' => 20, 'left' => 42, 'right' => 49, 'Nodes' => [
                    new Entity(['id' => 23, 'name' => 'Shrimp', 'parent_id' => 22, 'left' => 43, 'right' => 44]),
                    new Entity(['id' => 24, 'name' => 'Crab', 'parent_id' => 22, 'left' => 45, 'right' => 46]),
                    new Entity(['id' => 25, 'name' => 'Lobster', 'parent_id' => 22, 'left' => 47, 'right' => 48]),
                ]]),
                new Entity(['id' => 26, 'name' => 'Calamari', 'parent_id' => 20, 'left' => 50, 'right' => 51])
            ]]),
        ], $this->object->Hierarchy->getTree());

        $this->assertEquals(
            new Entity(['id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 31, 'right' => 38, 'Nodes' => [
                new Entity(['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 32, 'right' => 33]),
                new Entity(['id' => 18, 'name' => 'Pork', 'parent_id' => 16, 'left' => 34, 'right' => 35]),
                new Entity(['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 36, 'right' => 37]),
            ]]), $this->object->Hierarchy->getTree(16));

        $this->assertEquals(new Entity([
            'id' => 10, 'name' => 'Watermelon', 'parent_id' => 1, 'left' => 18, 'right' => 19
        ]), $this->object->Hierarchy->getTree(10));
    }

    public function testGetTreeInvalidID() {
        $this->assertEquals([], $this->object->Hierarchy->getTree(100));
    }

    public function testGetTreeNoRecords() {
        $this->object->truncate();

        $this->assertEquals([], $this->object->Hierarchy->getTree());
    }

    public function testGetList() {
        $this->assertEquals([
            1 => 'Fruit',
            2 => '    Banana',
            3 => '    Apple',
            4 => '    Pear',
            5 => '    Berry',
            6 => '        Blueberry',
            7 => '        Blackberry',
            8 => '        Strawberry',
            9 => '    Pineapple',
            10 => '    Watermelon',
            11 => 'Grain',
            12 => '    Wheat',
            13 => '    Bulgur',
            14 => '    Barley',
            15 => '    Farro',
            16 => 'Meat',
            17 => '    Beef',
            18 => '    Pork',
            19 => '    Chicken',
            20 => 'Seafood',
            21 => '    Fish',
            22 => '    Shellfish',
            23 => '        Shrimp',
            24 => '        Crab',
            25 => '        Lobster',
            26 => '    Calamari'
        ], $this->object->Hierarchy->getList());

        $this->assertEquals([
            5 => 'Berry',
            6 => '    Blueberry',
            7 => '    Blackberry',
            8 => '    Strawberry',
        ], $this->object->Hierarchy->getList(5));

        $this->assertEquals([
            5 => 'Berry',
            6 => '- Blueberry',
            7 => '- Blackberry',
            8 => '- Strawberry',
        ], $this->object->Hierarchy->getList(5, null, null, '- '));
    }

    public function testGetListInvalidID() {
        $this->assertEquals([], $this->object->Hierarchy->getList(666));
    }

    public function testGetPath() {
        $this->assertEquals(new EntityCollection([
            new Entity(['id' => 1, 'name' => 'Fruit', 'parent_id' => null, 'left' => 1, 'right' => 20]),
            new Entity(['id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 8, 'right' => 15])
        ]), $this->object->Hierarchy->getPath(8));

        $this->assertEquals(new EntityCollection(), $this->object->Hierarchy->getPath(20));
    }

    public function testGetPathInvalidID() {
        $this->assertEquals([], $this->object->Hierarchy->getPath(666));
    }

    public function testGetFirstNode() {
        $this->assertEquals(new Entity([
            'id' => 1,
            'name' => 'Fruit',
            'parent_id' => null,
            'left' => 1,
            'right' => 20
        ]), $this->object->Hierarchy->getFirstNode());
    }

    public function testGetLastNode() {
        $this->assertEquals(new Entity([
            'id' => 20,
            'name' => 'Seafood',
            'parent_id' => null,
            'left' => 39,
            'right' => 52
        ]), $this->object->Hierarchy->getLastNode());
    }

    public function testMoveDown() {
        $this->assertTrue($this->object->Hierarchy->moveDown(12, 2));

        $this->assertEquals(new Entity([
            'id' => 11, 'name' => 'Grain', 'parent_id' => null, 'left' => 21, 'right' => 30, 'Nodes' => [
                new Entity(['id' => 13, 'name' => 'Bulgur', 'parent_id' => 11, 'left' => 22, 'right' => 23]),
                new Entity(['id' => 14, 'name' => 'Barley', 'parent_id' => 11, 'left' => 24, 'right' => 25]),
                new Entity(['id' => 12, 'name' => 'Wheat', 'parent_id' => 11, 'left' => 26, 'right' => 27]),
                new Entity(['id' => 15, 'name' => 'Farro', 'parent_id' => 11, 'left' => 28, 'right' => 29]),
        ]]), $this->object->Hierarchy->getTree(11));

        // Move beef to outside the bottom
        $this->assertTrue($this->object->Hierarchy->moveDown(17, 8));

        $this->assertEquals(new Entity([
            'id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 31, 'right' => 38, 'Nodes' => [
                new Entity(['id' => 18, 'name' => 'Pork', 'parent_id' => 16, 'left' => 32, 'right' => 33]),
                new Entity(['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 34, 'right' => 35]),
                new Entity(['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 36, 'right' => 37]),
        ]]), $this->object->Hierarchy->getTree(16));

        // Move strawberry down, but it wont since its already last
        $this->assertTrue($this->object->Hierarchy->moveDown(8));

        $this->assertEquals(new Entity([
            'id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 8, 'right' => 15, 'Nodes' => [
                new Entity(['id' => 6, 'name' => 'Blueberry', 'parent_id' => 5, 'left' => 9, 'right' => 10]),
                new Entity(['id' => 7, 'name' => 'Blackberry', 'parent_id' => 5, 'left' => 11, 'right' => 12]),
                new Entity(['id' => 8, 'name' => 'Strawberry', 'parent_id' => 5, 'left' => 13, 'right' => 14]),
        ]]), $this->object->Hierarchy->getTree(5));
    }

    public function testMoveDownFailsOnMissingNode() {
        $this->assertFalse($this->object->Hierarchy->moveDown(666));
    }

    public function testMoveDownFailsOnRootNode() {
        $this->assertFalse($this->object->Hierarchy->moveDown(1));
    }

    public function testMoveUp() {
        $this->assertTrue($this->object->Hierarchy->moveUp(25));

        $this->assertEquals(new Entity([
            'id' => 22, 'name' => 'Shellfish', 'parent_id' => 20, 'left' => 42, 'right' => 49, 'Nodes' => [
                new Entity(['id' => 23, 'name' => 'Shrimp', 'parent_id' => 22, 'left' => 43, 'right' => 44]),
                new Entity(['id' => 25, 'name' => 'Lobster', 'parent_id' => 22, 'left' => 45, 'right' => 46]),
                new Entity(['id' => 24, 'name' => 'Crab', 'parent_id' => 22, 'left' => 47, 'right' => 48]),
        ]]), $this->object->Hierarchy->getTree(22));

        // Move farro to the top
        $this->assertTrue($this->object->Hierarchy->moveUp(15, 4));

        $this->assertEquals(new Entity([
            'id' => 11, 'name' => 'Grain', 'parent_id' => null, 'left' => 21, 'right' => 30, 'Nodes' => [
                new Entity(['id' => 15, 'name' => 'Farro', 'parent_id' => 11, 'left' => 22, 'right' => 23]),
                new Entity(['id' => 12, 'name' => 'Wheat', 'parent_id' => 11, 'left' => 24, 'right' => 25]),
                new Entity(['id' => 13, 'name' => 'Bulgur', 'parent_id' => 11, 'left' => 26, 'right' => 27]),
                new Entity(['id' => 14, 'name' => 'Barley', 'parent_id' => 11, 'left' => 28, 'right' => 29]),
        ]]), $this->object->Hierarchy->getTree(11));

        // Move Blueberry up, but it wont since its already first
        $this->assertTrue($this->object->Hierarchy->moveUp(6));

        $this->assertEquals(new Entity([
            'id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 8, 'right' => 15, 'Nodes' => [
                new Entity(['id' => 6, 'name' => 'Blueberry', 'parent_id' => 5, 'left' => 9, 'right' => 10]),
                new Entity(['id' => 7, 'name' => 'Blackberry', 'parent_id' => 5, 'left' => 11, 'right' => 12]),
                new Entity(['id' => 8, 'name' => 'Strawberry', 'parent_id' => 5, 'left' => 13, 'right' => 14]),
        ]]), $this->object->Hierarchy->getTree(5));
    }

    public function testMoveUpFailsOnMissingNode() {
        $this->assertFalse($this->object->Hierarchy->moveUp(666));
    }

    public function testMoveUpFailsOnRootNode() {
        $this->assertFalse($this->object->Hierarchy->moveUp(1));
    }

    public function testMoveTo() {
        $this->assertTrue($this->object->Hierarchy->moveTo(2, 5));

        $this->assertEquals(new Entity([
            'id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 6, 'right' => 15, 'Nodes' => [
                new Entity(['id' => 6, 'name' => 'Blueberry', 'parent_id' => 5, 'left' => 7, 'right' => 8]),
                new Entity(['id' => 7, 'name' => 'Blackberry', 'parent_id' => 5, 'left' => 9, 'right' => 10]),
                new Entity(['id' => 8, 'name' => 'Strawberry', 'parent_id' => 5, 'left' => 11, 'right' => 12]),
                new Entity(['id' => 2, 'name' => 'Banana', 'parent_id' => 5, 'left' => 13, 'right' => 14]),
        ]]), $this->object->Hierarchy->getTree(5));

        // Move barley to the root
        $this->assertTrue($this->object->Hierarchy->moveTo(14, null));

        $this->assertEquals(new Entity([
            'id' => 14,
            'parent_id' => null,
            'name' => 'Barley',
            'left' => 51,
            'right' => 52
        ]), $this->object->Hierarchy->getLastNode());
    }

    public function testMoveToFailsOnMissingNode() {
        $this->assertFalse($this->object->Hierarchy->moveTo(666, 1));
    }

    public function testMoveToFailsToSameParent() {
        $this->assertFalse($this->object->Hierarchy->moveTo(3, 1));
    }

    public function testReOrder() {
        $this->object->Hierarchy->reOrder(['name' => 'asc']);

        $this->assertEquals([
            new Entity(['id' => 1, 'name' => 'Fruit', 'parent_id' => null, 'left' => 1, 'right' => 20, 'Nodes' => [
                new Entity(['id' => 3, 'name' => 'Apple', 'parent_id' => 1, 'left' => 2, 'right' => 3]),
                new Entity(['id' => 2, 'name' => 'Banana', 'parent_id' => 1, 'left' => 4, 'right' => 5]),
                new Entity(['id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 6, 'right' => 13, 'Nodes' => [
                    new Entity(['id' => 7, 'name' => 'Blackberry', 'parent_id' => 5, 'left' => 7, 'right' => 8]),
                    new Entity(['id' => 6, 'name' => 'Blueberry', 'parent_id' => 5, 'left' => 9, 'right' => 10]),
                    new Entity(['id' => 8, 'name' => 'Strawberry', 'parent_id' => 5, 'left' => 11, 'right' => 12]),
                ]]),
                new Entity(['id' => 4, 'name' => 'Pear', 'parent_id' => 1, 'left' => 14, 'right' => 15]),
                new Entity(['id' => 9, 'name' => 'Pineapple', 'parent_id' => 1, 'left' => 16, 'right' => 17]),
                new Entity(['id' => 10, 'name' => 'Watermelon', 'parent_id' => 1, 'left' => 18, 'right' => 19]),
            ]]),
            new Entity(['id' => 11, 'name' => 'Grain', 'parent_id' => null, 'left' => 21, 'right' => 30, 'Nodes' => [
                new Entity(['id' => 14, 'name' => 'Barley', 'parent_id' => 11, 'left' => 22, 'right' => 23]),
                new Entity(['id' => 13, 'name' => 'Bulgur', 'parent_id' => 11, 'left' => 24, 'right' => 25]),
                new Entity(['id' => 15, 'name' => 'Farro', 'parent_id' => 11, 'left' => 26, 'right' => 27]),
                new Entity(['id' => 12, 'name' => 'Wheat', 'parent_id' => 11, 'left' => 28, 'right' => 29]),
            ]]),
            new Entity(['id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 31, 'right' => 38, 'Nodes' => [
                new Entity(['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 32, 'right' => 33]),
                new Entity(['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 34, 'right' => 35]),
                new Entity(['id' => 18, 'name' => 'Pork', 'parent_id' => 16, 'left' => 36, 'right' => 37]),
            ]]),
            new Entity(['id' => 20, 'name' => 'Seafood', 'parent_id' => null, 'left' => 39, 'right' => 52, 'Nodes' => [
                new Entity(['id' => 26, 'name' => 'Calamari', 'parent_id' => 20, 'left' => 40, 'right' => 41]),
                new Entity(['id' => 21, 'name' => 'Fish', 'parent_id' => 20, 'left' => 42, 'right' => 43]),
                new Entity(['id' => 22, 'name' => 'Shellfish', 'parent_id' => 20, 'left' => 44, 'right' => 51, 'Nodes' => [
                    new Entity(['id' => 24, 'name' => 'Crab', 'parent_id' => 22, 'left' => 45, 'right' => 46]),
                    new Entity(['id' => 25, 'name' => 'Lobster', 'parent_id' => 22, 'left' => 47, 'right' => 48]),
                    new Entity(['id' => 23, 'name' => 'Shrimp', 'parent_id' => 22, 'left' => 49, 'right' => 50]),
                ]])
            ]]),
        ], $this->object->Hierarchy->getTree());
    }

}