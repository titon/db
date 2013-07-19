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
		'treeField' => '',
		'scope' => [],
		'onSave' => true,
		'onDelete' => true
	];

	/**
	 * Index to start shifting down all nodes after a save.
	 *
	 * @type int
	 */
	protected $_saveIndex;

	/**
	 * Before an insert, determine the correct left and right using the parent or root node as a base.
	 * Do not shift the nodes until postSave() just in case the insert fails.
	 *
	 * Before an update, remove the left and right fields so that the tree cannot be modified.
	 * Use moveUp(), moveDown(), moveTo(), sync() or reorder() to update existing nodes.
	 *
	 * @param int|int[] $id
	 * @param array $data
	 * @return array
	 */
	public function preSave($id, array $data) {
		if (!$this->config->onSave) {
			return $data;
		}

		$parent = $this->config->parentField;
		$left = $this->config->leftField;
		$right = $this->config->rightField;

		// Append left and right during create
		if (!$id) {
			$indexes = [];

			// Is a child
			if (isset($data[$parent])) {
				if ($node = $this->getNode($data[$parent])) {
					$parentRight = $node[$right];

					// Save index for moving postSave
					$this->_saveIndex = $parentRight;

					$indexes = [
						$left => $parentRight,
						$right => $parentRight + 1
					];
				}
			}

			// Fallback to root
			if (empty($indexes)) {
				$node = $this->getLastNode();
				$rootRight = $node[$right];

				$indexes = [
					$left => $rootRight + 1,
					$right => $rootRight + 2
				];
			}

			$data = $indexes + $data;

		// Remove left and right fields from updates as it should not be modified
		} else {
			unset($data[$this->config->leftField], $data[$this->config->rightField]);
		}

		return $data;
	}

	/**
	 * After an insert, shift all nodes down using the base index.
	 *
	 * @param int|int[] $id
	 * @param bool $created
	 */
	public function postSave($id, $created = false) {
		if (!$this->config->onSave || !$created || !$this->_saveIndex) {
			return;
		}

		$this->_insertNode($id, $this->_saveIndex);
		$this->_saveIndex = null;
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
	 * If no ID is provided, the top level root will be used.
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
	 * Return a node by ID. If $withParent is true, parent data will be joined in.
	 *
	 * @param int $id
	 * @param bool $withParent
	 * @return array
	 */
	public function getNode($id, $withParent = false) {
		$model = $this->getModel();
		$pk = $model->getPrimaryKey();
		$query = $model->select();

		if ($withParent) {
			$query
				->where($model->getAlias() . '.' . $pk, $id)
				->leftJoin([$model->getTable(), 'Parent'], [], [$this->config->parentField => 'Parent.' . $pk]);
		} else {
			$query->where($pk, $id);
		}

		return $query->fetch(false);
	}

	/**
	 * Return the hierarchical path to the current node.
	 *
	 * @param int $id
	 * @return array
	 */
	public function getPath($id) {
		$node = $this->getNode($id);

		if (!$node) {
			return [];
		}

		$left = $this->config->leftField;
		$right = $this->config->rightField;

		return $this->getModel()->select()
			->where($left, '<', $node[$left])
			->where($right, '>', $node[$right])
			->orderBy($left, 'asc')
			->fetchAll(false);
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
	 * Nested lists will be prepended with a spacer to indicate indentation.
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

			while ($count && $stack[$count - 1] < $node[$right]) {
				array_pop($stack);
				$count--;
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
				$node[$this->config->treeField ?: 'Nodes'] = $this->mapTree($mappedNodes[$id], $mappedNodes);
			}

			$tree[] = $node;
		}

		return $tree;
	}

	/**
	 * Move a child node down in the list and move up neighboring nodes.
	 * If the node does not have a parent (is a root node), this method will not work.
	 *
	 * @param int $id
	 * @param int $count
	 * @return bool
	 */
	public function moveDown($id, $count = 1) {
		$model = $this->getModel();
		$node = $this->getNode($id, true);

		if (!$node || empty($node['Parent'])) {
			return false;
		}

		$left = $this->config->leftField;
		$right = $this->config->rightField;
		$nodeLeft = $node[$left];
		$nodeRight = $node[$right];
		$inc = ($count * 2);

		$newNodeLeft = $nodeLeft + $inc;
		$newNodeRight = $nodeRight + $inc;

		// Can't go outside of the parent
		$parentRight = $node['Parent'][$right];

		if ($newNodeLeft >= $parentRight) {
			$newNodeLeft = $parentRight - 2;
			$newNodeRight = $parentRight - 1;
		}

		// Exit early if the values are the same
		if ($nodeLeft === $newNodeLeft) {
			return true;
		}

		// Move following nodes up
		$model->query(Query::UPDATE)
			->fields([
				$left => Query::expr($left, '-', 2),
				$right => Query::expr($right, '-', 2)
			])
			->where($left, '>', $nodeRight)
			->where($right, '<=', $newNodeRight)
			->save();

		// Move node down
		$model->query(Query::UPDATE)
			->fields([
				$left => $newNodeLeft,
				$right => $newNodeRight
			])
			->where($model->getPrimaryKey(), $id)
			->save();

		return true;
	}

	/**
	 * Move a child node up in the list and move down neighboring nodes.
	 * If the node does not have a parent (is a root node), this method will not work.
	 *
	 * @param int $id
	 * @param int $count
	 * @return bool
	 */
	public function moveUp($id, $count = 1) {
		$model = $this->getModel();
		$node = $this->getNode($id, true);

		if (!$node || empty($node['Parent'])) {
			return false;
		}

		$left = $this->config->leftField;
		$right = $this->config->rightField;
		$nodeLeft = $node[$left];
		$nodeRight = $node[$right];
		$inc = ($count * 2);

		$newNodeLeft = $nodeLeft - $inc;
		$newNodeRight = $nodeRight - $inc;

		// Can't go outside of the parent
		$parentLeft = $node['Parent'][$left];

		if ($newNodeRight <= $parentLeft) {
			$newNodeLeft = $parentLeft + 1;
			$newNodeRight = $parentLeft + 2;
		}

		// Exit early if the values are the same
		if ($nodeLeft === $newNodeLeft) {
			return true;
		}

		// Move previous nodes down
		$model->query(Query::UPDATE)
			->fields([
				$left => Query::expr($left, '+', 2),
				$right => Query::expr($right, '+', 2)
			])
			->where($left, '>=', $newNodeLeft)
			->where($right, '<', $nodeLeft)
			->save();

		// Move node up
		$model->query(Query::UPDATE)
			->fields([
				$left => $newNodeLeft,
				$right => $newNodeRight
			])
			->where($model->getPrimaryKey(), $id)
			->save();

		return true;
	}

	/**
	 * Move a node between parents and the root. This will re-order the tree accordingly.
	 * If $parent_id is null, the node will be moved to the root.
	 *
	 * @param int $id
	 * @param int $parent_id
	 * @return bool
	 */
	public function moveTo($id, $parent_id) {
		$model = $this->getModel();
		$node = $this->getNode($id);

		if (!$node || $node[$this->config->parentField] == $parent_id) {
			return false;
		}

		$left = $this->config->leftField;
		$right = $this->config->rightField;
		$data = [];

		// Remove the node and reset others
		$model->query(Query::UPDATE)
			->fields([
				$left => Query::expr($left, '-', 2),
				$right => Query::expr($right, '-', 2)
			])
			->where($right, '>', $node[$right])
			->save();

		// Insert into parent
		if ($parent_id && ($parentNode = $this->getNode($parent_id))) {
			$data = [
				$left => $parentNode[$right],
				$right => $parentNode[$right] + 1
			];

		// Or the root
		} else if ($lastNode = $this->getLastNode()) {
			$data = [
				$left => $lastNode[$right] + 1,
				$right => $lastNode[$right] + 2
			];
		}

		if (!$data) {
			return false;
		}

		$model->query(Query::UPDATE)
			->fields($data + [$this->config->parentField => $parent_id])
			->where($model->getPrimaryKey(), $id)
			->save();

		$this->_insertNode($id, $data[$left]);

		return true;
	}

	/**
	 * Prepares a node for insertion by moving all following nodes down.
	 *
	 * @param int $id
	 * @param int $index
	 */
	protected function _insertNode($id, $index) {
		$model = $this->getModel();

		foreach ([$this->config->leftField, $this->config->rightField] as $field) {
			$query = $model->query(Query::UPDATE)
				->fields([$field => Query::expr($field, '+', 2)])
				->where($field, '>=', $index);

			if ($id) {
				$query->where($model->getPrimaryKey(), '!=', $id);
			}

			$query->save();
		}
	}

}