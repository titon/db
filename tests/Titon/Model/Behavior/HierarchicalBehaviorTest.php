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
	 * Test that a nested tree of arrays is returned.
	 */
	public function testGetTree() {
		$this->loadFixtures('Categories');

		$category = new Category();
		$category->addBehavior(new HierarchicalBehavior());

		$this->assertEquals([
			['id' => 1, 'name' => 'Fruit', 'parent_id' => null, 'left' => 1, 'right' => 52, 'Nodes' => [
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
			['id' => 11, 'name' => 'Grain', 'parent_id' => null, 'left' => 20, 'right' => 29, 'Nodes' => [
				['id' => 12, 'name' => 'Wheat', 'parent_id' => 11, 'left' => 21, 'right' => 22],
				['id' => 13, 'name' => 'Bulgur', 'parent_id' => 11, 'left' => 23, 'right' => 24],
				['id' => 14, 'name' => 'Barley', 'parent_id' => 11, 'left' => 25, 'right' => 26],
				['id' => 15, 'name' => 'Farro', 'parent_id' => 11, 'left' => 27, 'right' => 28],
			]],
			['id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 30, 'right' => 37, 'Nodes' => [
				['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 31, 'right' => 32],
				['id' => 18, 'name' => 'Pork', 'parent_id' => 16, 'left' => 33, 'right' => 34],
				['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 35, 'right' => 36],
			]],
			['id' => 20, 'name' => 'Seafood', 'parent_id' => null, 'left' => 38, 'right' => 51, 'Nodes' => [
				['id' => 21, 'name' => 'Fish', 'parent_id' => 20, 'left' => 39, 'right' => 40],
				['id' => 22, 'name' => 'Shellfish', 'parent_id' => 20, 'left' => 41, 'right' => 48, 'Nodes' => [
					['id' => 23, 'name' => 'Shrimp', 'parent_id' => 22, 'left' => 42, 'right' => 43],
					['id' => 24, 'name' => 'Crab', 'parent_id' => 22, 'left' => 44, 'right' => 45],
					['id' => 25, 'name' => 'Lobster', 'parent_id' => 22, 'left' => 46, 'right' => 47],
				]],
				['id' => 26, 'name' => 'Calamari', 'parent_id' => 20, 'left' => 49, 'right' => 50]
			]],
		], $category->getBehavior('Hierarchical')->getTree());

		$this->assertEquals([
			'id' => 16, 'name' => 'Meat', 'parent_id' => null, 'left' => 30, 'right' => 37, 'Nodes' => [
				['id' => 17, 'name' => 'Beef', 'parent_id' => 16, 'left' => 31, 'right' => 32],
				['id' => 18, 'name' => 'Pork', 'parent_id' => 16, 'left' => 33, 'right' => 34],
				['id' => 19, 'name' => 'Chicken', 'parent_id' => 16, 'left' => 35, 'right' => 36],
		]], $category->getBehavior('Hierarchical')->getTree(16));

		$this->assertEquals([
			'id' => 10, 'name' => 'Watermelon', 'parent_id' => 1, 'left' => 18, 'right' => 19
		], $category->getBehavior('Hierarchical')->getTree(10));

		$this->assertEquals([], $category->getBehavior('Hierarchical')->getTree(100));
	}

	/**
	 * Test that a nested tree of values is returned.
	 */
	public function testGetTreeList() {
		$this->loadFixtures('Categories');

		$category = new Category();
		$category->addBehavior(new HierarchicalBehavior());

		//$this->assertEquals([], $category->getBehavior('Hierarchical')->getTreeList());
	}

}