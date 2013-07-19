<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opendriver.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Behavior;

use Titon\Test\Stub\Model\Category;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Behavior\HierarchicalBehavior.
 *
 * @property \Titon\Model\Behavior\HierarchicalBehavior $object
 */
class HierarchicalBehaviorTest extends TestCase {

	/**
	 * Unload fixtures.
	 */
	protected function tearDown() {
		parent::tearDown();

		$this->unloadFixtures();
	}

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
			['id' => 20, 'name' => 'Seafood', 'parent_id' => null, 'left' => 39, 'right' => 54, 'Nodes' => [
				['id' => 21, 'name' => 'Fish', 'parent_id' => 20, 'left' => 40, 'right' => 41],
				['id' => 22, 'name' => 'Shellfish', 'parent_id' => 20, 'left' => 42, 'right' => 51, 'Nodes' => [
					['id' => 23, 'name' => 'Shrimp', 'parent_id' => 22, 'left' => 43, 'right' => 44],
					['id' => 24, 'name' => 'Crab', 'parent_id' => 22, 'left' => 45, 'right' => 46],
					['id' => 25, 'name' => 'Lobster', 'parent_id' => 22, 'left' => 47, 'right' => 48],
					['id' => 27, 'name' => 'Prawn', 'parent_id' => 22, 'left' => 49, 'right' => 50],
				]],
				['id' => 26, 'name' => 'Calamari', 'parent_id' => 20, 'left' => 52, 'right' => 53]
			]]
		, $category->getBehavior('Hierarchical')->getTree(20));

		// Add to root
		$category->create([
			'parent_id' => null,
			'name' => 'Vegetables'
		]);

		$this->assertEquals([
			'id' => 28,
			'parent_id' => null,
			'name' => 'Vegetables',
			'left' => 55,
			'right' => 56
		], $category->getBehavior('Hierarchical')->getLastNode());

		// Add some children
		$category->create(['parent_id' => 28, 'name' => 'Broccoli']);
		$category->create(['parent_id' => 28, 'name' => 'Spinach']);
		$category->create(['parent_id' => 16, 'name' => 'Duck']);
		$category->create(['parent_id' => 5, 'name' => 'Raspberry']);
		$category->create(['parent_id' => 1, 'name' => 'Mango']);

		// Check the tree
		$this->assertEquals([
			['id' => 1, 'name' => 'Fruit', 'parent_id' => null, 'left' => 1, 'right' => 24, 'Nodes' => [
				['id' => 2, 'name' => 'Banana', 'parent_id' => 1, 'left' => 2, 'right' => 3],
				['id' => 3, 'name' => 'Apple', 'parent_id' => 1, 'left' => 4, 'right' => 5],
				['id' => 4, 'name' => 'Pear', 'parent_id' => 1, 'left' => 6, 'right' => 7],
				['id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 8, 'right' => 17, 'Nodes' => [
					['id' => 6, 'name' => 'Blueberry', 'parent_id' => 5, 'left' => 9, 'right' => 10],
					['id' => 7, 'name' => 'Blackberry', 'parent_id' => 5, 'left' => 11, 'right' => 12],
					['id' => 8, 'name' => 'Strawberry', 'parent_id' => 5, 'left' => 13, 'right' => 14],
					['id' => 32, 'name' => 'Raspberry', 'parent_id' => 5, 'left' => 15, 'right' => 16],
				]],
				['id' => 9, 'name' => 'Pineapple', 'parent_id' => 1, 'left' => 18, 'right' => 19],
				['id' => 10, 'name' => 'Watermelon', 'parent_id' => 1, 'left' => 20, 'right' => 21],
				['id' => 33, 'name' => 'Mango', 'parent_id' => 1, 'left' => 22, 'right' => 23],
			]],
			['id' => 11, 'name' => 'Grain', 'parent_id' => null, 'left' => 25, 'right' => 34, 'Nodes' => [
				['id' => 12, 'name' => 'Wheat', 'parent_id' => 11, 'left' => 26, 'right' => 27],
				['id' => 13, 'name' => 'Bulgur', 'parent_id' => 11, 'left' => 28, 'right' => 29],
				['id' => 14, 'name' => 'Barley', 'parent_id' => 11, 'left' => 30, 'right' => 31],
				['id' => 15, 'name' => 'Farro', 'parent_id' => 11, 'left' => 32, 'right' => 33],
			]],
			['id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 35, 'right' => 44, 'Nodes' => [
				['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 36, 'right' => 37],
				['id' => 18, 'name' => 'Pork', 'parent_id' => 16, 'left' => 38, 'right' => 39],
				['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 40, 'right' => 41],
				['id' => 31, 'name' => 'Duck', 'parent_id' => 16, 'left' => 42, 'right' => 43],
			]],
			['id' => 20, 'name' => 'Seafood', 'parent_id' => null, 'left' => 45, 'right' => 60, 'Nodes' => [
				['id' => 21, 'name' => 'Fish', 'parent_id' => 20, 'left' => 46, 'right' => 47],
				['id' => 22, 'name' => 'Shellfish', 'parent_id' => 20, 'left' => 48, 'right' => 57, 'Nodes' => [
					['id' => 23, 'name' => 'Shrimp', 'parent_id' => 22, 'left' => 49, 'right' => 50],
					['id' => 24, 'name' => 'Crab', 'parent_id' => 22, 'left' => 51, 'right' => 52],
					['id' => 25, 'name' => 'Lobster', 'parent_id' => 22, 'left' => 53, 'right' => 54],
					['id' => 27, 'name' => 'Prawn', 'parent_id' => 22, 'left' => 55, 'right' => 56],
				]],
				['id' => 26, 'name' => 'Calamari', 'parent_id' => 20, 'left' => 58, 'right' => 59]
			]],
			['id' => 28, 'name' => 'Vegetables', 'parent_id' => null, 'left' => 61, 'right' => 66, 'Nodes' => [
				['id' => 29, 'name' => 'Broccoli', 'parent_id' => 28, 'left' => 62, 'right' => 63],
				['id' => 30, 'name' => 'Spinach', 'parent_id' => 28, 'left' => 64, 'right' => 65]
			]],
		], $category->getBehavior('Hierarchical')->getTree());

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
		], $category->getBehavior('Hierarchical')->getList());
	}

	/**
	 * Test that a nested tree of arrays is returned.
	 */
	public function testGetTree() {
		$this->loadFixtures('Categories');

		$category = new Category();
		$category->addBehavior(new HierarchicalBehavior());

		$this->assertEquals([
			['id' => 1, 'name' => 'Fruit', 'parent_id' => null, 'left' => 1, 'right' => 20, 'Nodes' => [
				['id' => 2, 'name' => 'Banana', 'parent_id' => 1, 'left' => 2, 'right' => 3],
				['id' => 3, 'name' => 'Apple', 'parent_id' => 1, 'left' => 4, 'right' => 5],
				['id' => 4, 'name' => 'Pear', 'parent_id' => 1, 'left' => 6, 'right' => 7],
				['id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 8, 'right' => 15, 'Nodes' => [
					['id' => 6, 'name' => 'Blueberry', 'parent_id' => 5, 'left' => 9, 'right' => 10],
					['id' => 7, 'name' => 'Blackberry', 'parent_id' => 5, 'left' => 11, 'right' => 12],
					['id' => 8, 'name' => 'Strawberry', 'parent_id' => 5, 'left' => 13, 'right' => 14],
				]],
				['id' => 9, 'name' => 'Pineapple', 'parent_id' => 1, 'left' => 16, 'right' => 17],
				['id' => 10, 'name' => 'Watermelon', 'parent_id' => 1, 'left' => 18, 'right' => 19],
			]],
			['id' => 11, 'name' => 'Grain', 'parent_id' => null, 'left' => 21, 'right' => 30, 'Nodes' => [
				['id' => 12, 'name' => 'Wheat', 'parent_id' => 11, 'left' => 22, 'right' => 23],
				['id' => 13, 'name' => 'Bulgur', 'parent_id' => 11, 'left' => 24, 'right' => 25],
				['id' => 14, 'name' => 'Barley', 'parent_id' => 11, 'left' => 26, 'right' => 27],
				['id' => 15, 'name' => 'Farro', 'parent_id' => 11, 'left' => 28, 'right' => 29],
			]],
			['id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 31, 'right' => 38, 'Nodes' => [
				['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 32, 'right' => 33],
				['id' => 18, 'name' => 'Pork', 'parent_id' => 16, 'left' => 34, 'right' => 35],
				['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 36, 'right' => 37],
			]],
			['id' => 20, 'name' => 'Seafood', 'parent_id' => null, 'left' => 39, 'right' => 52, 'Nodes' => [
				['id' => 21, 'name' => 'Fish', 'parent_id' => 20, 'left' => 40, 'right' => 41],
				['id' => 22, 'name' => 'Shellfish', 'parent_id' => 20, 'left' => 42, 'right' => 49, 'Nodes' => [
					['id' => 23, 'name' => 'Shrimp', 'parent_id' => 22, 'left' => 43, 'right' => 44],
					['id' => 24, 'name' => 'Crab', 'parent_id' => 22, 'left' => 45, 'right' => 46],
					['id' => 25, 'name' => 'Lobster', 'parent_id' => 22, 'left' => 47, 'right' => 48],
				]],
				['id' => 26, 'name' => 'Calamari', 'parent_id' => 20, 'left' => 50, 'right' => 51]
			]],
		], $category->getBehavior('Hierarchical')->getTree());

		$this->assertEquals([
			'id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 31, 'right' => 38, 'Nodes' => [
				['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 32, 'right' => 33],
				['id' => 18, 'name' => 'Pork', 'parent_id' => 16, 'left' => 34, 'right' => 35],
				['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 36, 'right' => 37],
		]], $category->getBehavior('Hierarchical')->getTree(16));

		$this->assertEquals([
			'id' => 10, 'name' => 'Watermelon', 'parent_id' => 1, 'left' => 18, 'right' => 19
		], $category->getBehavior('Hierarchical')->getTree(10));

		$this->assertEquals([], $category->getBehavior('Hierarchical')->getTree(100));
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
		], $category->getBehavior('Hierarchical')->getList());

		$this->assertEquals([
			5 => 'Berry',
			6 => '    Blueberry',
			7 => '    Blackberry',
			8 => '    Strawberry',
		], $category->getBehavior('Hierarchical')->getList(5));

		$this->assertEquals([
			5 => 'Berry',
			6 => '- Blueberry',
			7 => '- Blackberry',
			8 => '- Strawberry',
		], $category->getBehavior('Hierarchical')->getList(5, null, null, '- '));
	}

	/**
	 * Test child nodes can move down.
	 */
	public function testMoveDown() {
		$this->loadFixtures('Categories');

		$category = new Category();
		$category->addBehavior(new HierarchicalBehavior());

		// Move wheat down 2 places
		$category->getBehavior('Hierarchical')->moveDown(12, 2);

		$this->assertEquals([
			'id' => 11, 'name' => 'Grain', 'parent_id' => null, 'left' => 21, 'right' => 30, 'Nodes' => [
				['id' => 13, 'name' => 'Bulgur', 'parent_id' => 11, 'left' => 22, 'right' => 23],
				['id' => 14, 'name' => 'Barley', 'parent_id' => 11, 'left' => 24, 'right' => 25],
				['id' => 12, 'name' => 'Wheat', 'parent_id' => 11, 'left' => 26, 'right' => 27],
				['id' => 15, 'name' => 'Farro', 'parent_id' => 11, 'left' => 28, 'right' => 29],
		]], $category->getBehavior('Hierarchical')->getTree(11));

		// Move beef to outside the bottom
		$category->getBehavior('Hierarchical')->moveDown(17, 8);

		$this->assertEquals([
			'id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 31, 'right' => 38, 'Nodes' => [
				['id' => 18, 'name' => 'Pork', 'parent_id' => 16, 'left' => 32, 'right' => 33],
				['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 34, 'right' => 35],
				['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 36, 'right' => 37],
		]], $category->getBehavior('Hierarchical')->getTree(16));

		// Move strawberry down, but it wont since its already last
		$category->getBehavior('Hierarchical')->moveDown(8);

		$this->assertEquals([
			'id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 8, 'right' => 15, 'Nodes' => [
				['id' => 6, 'name' => 'Blueberry', 'parent_id' => 5, 'left' => 9, 'right' => 10],
				['id' => 7, 'name' => 'Blackberry', 'parent_id' => 5, 'left' => 11, 'right' => 12],
				['id' => 8, 'name' => 'Strawberry', 'parent_id' => 5, 'left' => 13, 'right' => 14],
		]], $category->getBehavior('Hierarchical')->getTree(5));
	}

	/**
	 * Test child nodes can move up.
	 */
	public function testMoveUp() {
		$this->loadFixtures('Categories');

		$category = new Category();
		$category->addBehavior(new HierarchicalBehavior());

		// Move lobster up
		$category->getBehavior('Hierarchical')->moveUp(25);

		$this->assertEquals([
			'id' => 22, 'name' => 'Shellfish', 'parent_id' => 20, 'left' => 42, 'right' => 49, 'Nodes' => [
				['id' => 23, 'name' => 'Shrimp', 'parent_id' => 22, 'left' => 43, 'right' => 44],
				['id' => 25, 'name' => 'Lobster', 'parent_id' => 22, 'left' => 45, 'right' => 46],
				['id' => 24, 'name' => 'Crab', 'parent_id' => 22, 'left' => 47, 'right' => 48],
		]], $category->getBehavior('Hierarchical')->getTree(22));

		// Move farro to the top
		$category->getBehavior('Hierarchical')->moveUp(15, 4);

		$this->assertEquals([
			'id' => 11, 'name' => 'Grain', 'parent_id' => null, 'left' => 21, 'right' => 30, 'Nodes' => [
				['id' => 15, 'name' => 'Farro', 'parent_id' => 11, 'left' => 22, 'right' => 23],
				['id' => 12, 'name' => 'Wheat', 'parent_id' => 11, 'left' => 24, 'right' => 25],
				['id' => 13, 'name' => 'Bulgur', 'parent_id' => 11, 'left' => 26, 'right' => 27],
				['id' => 14, 'name' => 'Barley', 'parent_id' => 11, 'left' => 28, 'right' => 29],
		]], $category->getBehavior('Hierarchical')->getTree(11));

		// Move Blueberry up, but it wont since its already first
		$category->getBehavior('Hierarchical')->moveUp(6);

		$this->assertEquals([
			'id' => 5, 'name' => 'Berry', 'parent_id' => 1, 'left' => 8, 'right' => 15, 'Nodes' => [
				['id' => 6, 'name' => 'Blueberry', 'parent_id' => 5, 'left' => 9, 'right' => 10],
				['id' => 7, 'name' => 'Blackberry', 'parent_id' => 5, 'left' => 11, 'right' => 12],
				['id' => 8, 'name' => 'Strawberry', 'parent_id' => 5, 'left' => 13, 'right' => 14],
		]], $category->getBehavior('Hierarchical')->getTree(5));
	}

}