<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Behavior;

use Titon\Model\Query;

class HierarchicalBehavior extends AbstractBehavior {

	/**
	 * Configuration.
	 *
	 * @type array
	 */
	protected $_config = [
		'parentField' => 'parent_id',
		'leftField' => 'left',
		'rightField' => 'right',
		'treeField' => 'Nodes',
		'scope' => [],
		'onSave' => true,
		'onDelete' => true
	];

	/**
	 * Settings to move nodes at a later time.
	 *
	 * @type int
	 */
	protected $_moveTo;

	public function preSave($id, array $data) {
		if (!$this->config->onSave) {
			return $data;
		}

		$parent = $this->config->parentField;

		// Append left and right during create
		if (!$id) {
			$data = $this->appendTo(isset($data[$parent]) ? $data[$parent] : null) + $data;

		// Remove left and right fields from updates as it should not be modified
		} else {
			unset($data[$this->config->leftField], $data[$this->config->rightField]);
		}

		return $data;
	}

	public function postSave($id, $created = false) {
		if ($created) {
			$this->moveDown($this->_moveTo['index'], $this->_moveTo['count'], $id);
		}

		return;
	}

	/**
	 * Determine the left and right values using the parent ID as a base.
	 *
	 * @param int $parent_id
	 * @return array
	 */
	public function appendTo($parent_id = null) {
		$left = $this->config->leftField;
		$right = $this->config->rightField;

		// Is a child
		if ($parent_id && ($node = $this->getNode($parent_id))) {
			$parentRight = $node[$right];

			// Save data for moving
			$this->_moveTo = ['count' => 1, 'index' => $parentRight];

			return [
				$left => $parentRight,
				$right => $parentRight + 1
			];
		}

		// Is root
		$node = $this->getLastNode();
		$rootRight = $node[$right];

		return [
			$left => $rootRight + 1,
			$right => $rootRight + 2
		];
	}

	/**
	 * Return the first node in the tree.
	 *
	 * @return array
	 */
	public function getFirstNode() {
		return $this->getModel()->select()
			->orderBy($this->config->leftField, 'asc')
			->limit(1)
			->fetch(false);
	}

	/**
	 * Return the last node in the tree.
	 *
	 * @return array
	 */
	public function getLastNode() {
		return $this->getModel()->select()
			->orderBy($this->config->rightField, 'desc')
			->limit(1)
			->fetch(false);
	}

	/**
	 * Return a list of nodes indented to indicate tree level.
	 * The $key and $value will be used to extract values from the node.
	 *
	 * @param int $id
	 * @param string $key
	 * @param string $value
	 * @param string $spacer
	 * @return array
	 */
	public function getList($id = null, $key = null, $value = null, $spacer = '    ') {
		$left = $this->config->leftField;
		$right = $this->config->rightField;

		$query = $this->getModel()->select()->orderBy($left, 'asc');

		if ($id) {
			if ($parentNode = $this->getNode($id)) {
				$query->where($left, 'between', [$parentNode[$left], $parentNode[$right]]);
			} else {
				return [];
			}
		}

		return $this->mapList($query->fetchAll(false), $key, $value, $spacer);
	}

	/**
	 * Return a node by ID.
	 *
	 * @param int $id
	 * @return array
	 */
	public function getNode($id) {
		return $this->getModel()->select()
			->where($this->getModel()->getPrimaryKey(), $id)
			->fetch(false);
	}

	/**
	 * Return a tree of nested nodes. If no ID is provided, the top level root will be used.
	 *
	 * @param int $id
	 * @return array
	 */
	public function getTree($id = null) {
		$parent = $this->config->parentField;
		$left = $this->config->leftField;
		$right = $this->config->rightField;

		$query = $this->getModel()->select()->orderBy($left, 'asc');

		if ($id) {
			if ($parentNode = $this->getNode($id)) {
				$query->where($left, 'between', [$parentNode[$left], $parentNode[$right]]);
			} else {
				return [];
			}
		}

		$nodes = $query->fetchAll(false);

		if (!$nodes) {
			return [];
		}

		$map = [];
		$stack = [];
		$pk = $this->getModel()->getPrimaryKey();

		foreach ($nodes as $node) {
			if ($node[$parent] && $node[$pk] != $id) {
				$map[$node[$parent]][] = $node;
			} else {
				$stack[] = $node;
			}
		}

		$results = $this->mapTree($stack, $map);

		if ($id) {
			return $results[0];
		}

		return $results;
	}

	/**
	 * Map a nested tree using the primary key and display field as the values to populate the list.
	 * Nested lists will be prepend with a spacer to integrate indentation.
	 *
	 * @param array $nodes
	 * @param string $key
	 * @param string $value
	 * @param string $spacer
	 * @return array
	 */
	public function mapList(array $nodes, $key, $value, $spacer) {
		$tree = [];
		$stack = [];
		$key = $key ?: $this->getModel()->getPrimaryKey();
		$value = $value ?: $this->getModel()->getDisplayField();
		$right = $this->config->rightField;

		foreach ($nodes as $node) {
			$count = count($stack);

			if ($count) {
				while ($stack[$count - 1] < $node[$right]) {
					array_pop($stack);
					$count--;
				}
			}

			$tree[$node[$key]] = str_repeat($spacer, $count) . $node[$value];

			$stack[] = $node[$right];
		}

		return $tree;
	}

	/**
	 * Map a nested tree of arrays using the parent node stack and the mapped nodes by parent ID.
	 *
	 * @param array $nodes
	 * @param array $mappedNodes
	 * @return array
	 */
	public function mapTree(array $nodes, array $mappedNodes = []) {
		if (!$mappedNodes) {
			return $nodes;
		}

		$tree = [];
		$pk = $this->getModel()->getPrimaryKey();

		foreach ($nodes as $node) {
			$id = $node[$pk];

			if (isset($mappedNodes[$id])) {
				$node[$this->config->treeField] = $this->mapTree($mappedNodes[$id], $mappedNodes);
			}

			$tree[] = $node;
		}

		return $tree;
	}

	/**
	 * Move all nodes down using the index as a base and the count as a multiplier.
	 * If an ID is provided, that node will be excluded from shifting.
	 *
	 * @param int $index
	 * @param int $count
	 * @param int $id
	 * @return \Titon\Model\Behavior\HierarchicalBehavior
	 */
	public function moveDown($index, $count = 1, $id = null) {
		$model = $this->getModel();
		$inc = ($count * 2);

		foreach ([$this->config->leftField, $this->config->rightField] as $field) {
			$query = $model->query(Query::UPDATE)
				->fields([$field => Query::expr($field, '+', $inc)])
				->where($field, '>=', $index);

			if ($id) {
				$query->where($model->getPrimaryKey(), '!=', $id);
			}

			$query->save();
		}

		return $this;
	}

	public function syncTree($parent_id = null, $left = 1) {

	}

}