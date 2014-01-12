<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Db\Entity;
use Titon\Test\Stub\Table\Category;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Behavior\HierarchicalBehavior.
 *
 * @property \Titon\Db\Behavior\HierarchicalBehavior $object
 */
class HierarchicalBehaviorTest extends TestCase {

    /**
     * Test that inserting nodes shifts other nodes around properly.
     */
    public function testCreateNode() {
        $this->loadFixtures('Categories');

        $category = new Category();
        $category->addBehavior(new HierarchicalBehavior());

        // Add as a child
        $category->create([
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
        , $category->Hierarchical->getTree(20));

        // Add to root
        $category->create([
            'parent_id' => null,
            'name' => 'Vegetables'
        ]);

        $this->assertEquals(new Entity([
            'id' => 28,
            'parent_id' => null,
            'name' => 'Vegetables',
            'left' => 55,
            'right' => 56
        ]), $category->Hierarchical->getLastNode());

        // Add some children
        $category->create(['parent_id' => 28, 'name' => 'Broccoli']);
        $category->create(['parent_id' => 28, 'name' => 'Spinach']);
        $category->create(['parent_id' => 16, 'name' => 'Duck']);
        $category->create(['parent_id' => 5, 'name' => 'Raspberry']);
        $category->create(['parent_id' => 1, 'name' => 'Mango']);

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
        ], $category->Hierarchical->getTree());

        // Add a child with custom left and right (should be removed)
        $category->create([
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
        ], $category->Hierarchical->getList());
    }

    /**
     * Test that removing nodes shifts other nodes around properly.
     */
    public function testDeleteNode() {
        $this->loadFixtures('Categories');

        $category = new Category();
        $category->addBehavior(new HierarchicalBehavior());

        // Delete pork
        $category->delete(18);

        $this->assertEquals(new Entity([
            'id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 31, 'right' => 36, 'Nodes' => [
                new Entity(['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 32, 'right' => 33]),
                new Entity(['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 34, 'right' => 35]),
        ]]), $category->Hierarchical->getTree(16));

        // Attempt to delete meat, should fail
        $this->assertEquals(0, $category->delete(16));

        $this->assertEquals(new Entity([
            'id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 31, 'right' => 36, 'Nodes' => [
                new Entity(['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 32, 'right' => 33]),
                new Entity(['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 34, 'right' => 35]),
        ]]), $category->Hierarchical->getTree(16));
    }

    /**
     * Test that a nested tree of arrays is returned.
     */
    public function testGetTree() {
        $this->loadFixtures('Categories');

        $category = new Category();
        $category->addBehavior(new HierarchicalBehavior());

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
        ], $category->Hierarchical->getTree());

        $this->assertEquals(
            new Entity(['id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 31, 'right' => 38, 'Nodes' => [
                new Entity(['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 32, 'right' => 33]),
                new Entity(['id' => 18, 'name' => 'Pork', 'parent_id' => 16, 'left' => 34, 'right' => 35]),
                new Entity(['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 36, 'right' => 37]),
            ]]), $category->Hierarchical->getTree(16));

        $this->assertEquals(new Entity([
            'id' => 10, 'name' => 'Watermelon', 'parent_id' => 1, 'left' => 18, 'right' => 19
        ]), $category->Hierarchical->getTree(10));

        $this->assertEquals([], $category->Hierarchical->getTree(100));
    }

    /**
     * Test that a nested list of values is returned.
     */
    public function testGetList() {
        $this->loadFixtures('Categories');

        $category = new Category();
        $category->addBehavior(new HierarchicalBehavior());

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
        ], $category->Hierarchical->getList());

        $this->assertEquals([
            5 => 'Berry',
            6 => '    Blueberry',
            7 => '    Blackberry',
            8 => '    Strawberry',
        ], $category->Hierarchical->getList(5));

        $this->assertEquals([
            5 => 'Berry',
            6 => '- Blueberry',
            7 => '- Blackberry',
            8 => '- Strawberry',
        ], $category->Hierarchical->getList(5, null, null, '- '));
    }

    /**
     * Test direct path is returned.
     */
    public function testGetPath() {
        $this->loadFixtures('Categories');

        $category = new Category();
        $category->addBehavior(new HierarchicalBehavior());

        $this->assertEquals([
            new Entity(['id' => 1, 'name' => 'Fruit', 'parent_id' => null, 'left' => 1, 'right' => 20]),
            new Entity(['id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 8, 'right' => 15])
        ], $category->Hierarchical->getPath(8));

        $this->assertEquals([], $category->Hierarchical->getPath(20));
    }

    /**
     * Test child nodes can move down.
     */
    public function testMoveDown() {
        $this->loadFixtures('Categories');

        $category = new Category();
        $category->addBehavior(new HierarchicalBehavior());

        // Move wheat down 2 places
        $category->Hierarchical->moveDown(12, 2);

        $this->assertEquals(new Entity([
            'id' => 11, 'name' => 'Grain', 'parent_id' => null, 'left' => 21, 'right' => 30, 'Nodes' => [
                new Entity(['id' => 13, 'name' => 'Bulgur', 'parent_id' => 11, 'left' => 22, 'right' => 23]),
                new Entity(['id' => 14, 'name' => 'Barley', 'parent_id' => 11, 'left' => 24, 'right' => 25]),
                new Entity(['id' => 12, 'name' => 'Wheat', 'parent_id' => 11, 'left' => 26, 'right' => 27]),
                new Entity(['id' => 15, 'name' => 'Farro', 'parent_id' => 11, 'left' => 28, 'right' => 29]),
        ]]), $category->Hierarchical->getTree(11));

        // Move beef to outside the bottom
        $category->Hierarchical->moveDown(17, 8);

        $this->assertEquals(new Entity([
            'id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 31, 'right' => 38, 'Nodes' => [
                new Entity(['id' => 18, 'name' => 'Pork', 'parent_id' => 16, 'left' => 32, 'right' => 33]),
                new Entity(['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 34, 'right' => 35]),
                new Entity(['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 36, 'right' => 37]),
        ]]), $category->Hierarchical->getTree(16));

        // Move strawberry down, but it wont since its already last
        $category->Hierarchical->moveDown(8);

        $this->assertEquals(new Entity([
            'id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 8, 'right' => 15, 'Nodes' => [
                new Entity(['id' => 6, 'name' => 'Blueberry', 'parent_id' => 5, 'left' => 9, 'right' => 10]),
                new Entity(['id' => 7, 'name' => 'Blackberry', 'parent_id' => 5, 'left' => 11, 'right' => 12]),
                new Entity(['id' => 8, 'name' => 'Strawberry', 'parent_id' => 5, 'left' => 13, 'right' => 14]),
        ]]), $category->Hierarchical->getTree(5));
    }

    /**
     * Test child nodes can move up.
     */
    public function testMoveUp() {
        $this->loadFixtures('Categories');

        $category = new Category();
        $category->addBehavior(new HierarchicalBehavior());

        // Move lobster up
        $category->Hierarchical->moveUp(25);

        $this->assertEquals(new Entity([
            'id' => 22, 'name' => 'Shellfish', 'parent_id' => 20, 'left' => 42, 'right' => 49, 'Nodes' => [
                new Entity(['id' => 23, 'name' => 'Shrimp', 'parent_id' => 22, 'left' => 43, 'right' => 44]),
                new Entity(['id' => 25, 'name' => 'Lobster', 'parent_id' => 22, 'left' => 45, 'right' => 46]),
                new Entity(['id' => 24, 'name' => 'Crab', 'parent_id' => 22, 'left' => 47, 'right' => 48]),
        ]]), $category->Hierarchical->getTree(22));

        // Move farro to the top
        $category->Hierarchical->moveUp(15, 4);

        $this->assertEquals(new Entity([
            'id' => 11, 'name' => 'Grain', 'parent_id' => null, 'left' => 21, 'right' => 30, 'Nodes' => [
                new Entity(['id' => 15, 'name' => 'Farro', 'parent_id' => 11, 'left' => 22, 'right' => 23]),
                new Entity(['id' => 12, 'name' => 'Wheat', 'parent_id' => 11, 'left' => 24, 'right' => 25]),
                new Entity(['id' => 13, 'name' => 'Bulgur', 'parent_id' => 11, 'left' => 26, 'right' => 27]),
                new Entity(['id' => 14, 'name' => 'Barley', 'parent_id' => 11, 'left' => 28, 'right' => 29]),
        ]]), $category->Hierarchical->getTree(11));

        // Move Blueberry up, but it wont since its already first
        $category->Hierarchical->moveUp(6);

        $this->assertEquals(new Entity([
            'id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 8, 'right' => 15, 'Nodes' => [
                new Entity(['id' => 6, 'name' => 'Blueberry', 'parent_id' => 5, 'left' => 9, 'right' => 10]),
                new Entity(['id' => 7, 'name' => 'Blackberry', 'parent_id' => 5, 'left' => 11, 'right' => 12]),
                new Entity(['id' => 8, 'name' => 'Strawberry', 'parent_id' => 5, 'left' => 13, 'right' => 14]),
        ]]), $category->Hierarchical->getTree(5));
    }

    /**
     * Test that nodes can be moved to other parents.
     */
    public function testMoveTo() {
        $this->loadFixtures('Categories');

        $category = new Category();
        $category->addBehavior(new HierarchicalBehavior());

        // Move banana to berry list
        $category->Hierarchical->moveTo(2, 5);

        $this->assertEquals(new Entity([
            'id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 6, 'right' => 15, 'Nodes' => [
                new Entity(['id' => 6, 'name' => 'Blueberry', 'parent_id' => 5, 'left' => 7, 'right' => 8]),
                new Entity(['id' => 7, 'name' => 'Blackberry', 'parent_id' => 5, 'left' => 9, 'right' => 10]),
                new Entity(['id' => 8, 'name' => 'Strawberry', 'parent_id' => 5, 'left' => 11, 'right' => 12]),
                new Entity(['id' => 2, 'name' => 'Banana', 'parent_id' => 5, 'left' => 13, 'right' => 14]),
        ]]), $category->Hierarchical->getTree(5));

        // Move barley to the root
        $category->Hierarchical->moveTo(14, null);

        $this->assertEquals(new Entity([
            'id' => 14,
            'parent_id' => null,
            'name' => 'Barley',
            'left' => 51,
            'right' => 52
        ]), $category->Hierarchical->getLastNode());
    }

    /**
     * Test that all nodes are re-ordered by name.
     */
    public function testReOrder() {
        $this->loadFixtures('Categories');

        $category = new Category();
        $category->addBehavior(new HierarchicalBehavior());

        // Reorder by name
        $category->Hierarchical->reOrder(['name' => 'asc']);

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
        ], $category->Hierarchical->getTree());
    }

}