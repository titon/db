<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Event\Event;
use Titon\Db\Query;
use \Closure;

/**
 * The HierarchyBehavior implements a pattern of tree traversal which allows for a nested hierarchy of nodes.
 * After every node insertion and deletion, the tree is updated accordingly.
 * The tree is based off the Modified Preorder Tree Traversal (MPTT) pattern.
 *
 * @link http://en.wikipedia.org/wiki/Tree_traversal
 * @link http://www.sitepoint.com/hierarchical-data-database-2/
 *
 * @package Titon\Db\Behavior
 */
class HierarchyBehavior extends AbstractBehavior {

    /**
     * Configuration.
     *
     * @type array {
     *      @type string $parentField   The foreign key field for the parent record
     *      @type string $leftField     The field name for the left tree node
     *      @type string $rightField    The field name for the right tree node
     *      @type bool $onSave          Trigger the behavior on insert and update
     *      @type bool $onDelete        Trigger the behavior on delete
     * }
     */
    protected $_config = [
        'parentField' => 'parent_id',
        'leftField' => 'left',
        'rightField' => 'right',
        'treeField' => '',
        'onSave' => true,
        'onDelete' => true
    ];

    /**
     * Index to shift up all nodes after a delete.
     *
     * @type int
     */
    protected $_deleteIndex;

    /**
     * Index to shift down all nodes after a save.
     *
     * @type int
     */
    protected $_saveIndex;

    /**
     * Return the fetch node in the tree.
     *
     * @return \Titon\Db\Entity
     */
    public function getFirstNode() {
        return $this->getRepository()->select()
            ->orderBy($this->getConfig('leftField'), 'asc')
            ->limit(1)
            ->first();
    }

    /**
     * Return the last node in the tree.
     *
     * @return \Titon\Db\Entity
     */
    public function getLastNode() {
        return $this->getRepository()->select()
            ->orderBy($this->getConfig('rightField'), 'desc')
            ->limit(1)
            ->first();
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
        $left = $this->getConfig('leftField');
        $right = $this->getConfig('rightField');

        $query = $this->getRepository()->select()->orderBy($left, 'asc');

        if ($id) {
            if ($parentNode = $this->getNode($id)) {
                $query->where($left, 'between', [$parentNode[$left], $parentNode[$right]]);
            } else {
                return [];
            }
        }

        return $this->mapList($query->all()->toArray(), $key, $value, $spacer);
    }

    /**
     * Return a node by ID. If $withParent is true, parent data will be joined in.
     *
     * @param int $id
     * @param bool $withParent
     * @return \Titon\Db\Entity
     */
    public function getNode($id, $withParent = false) {
        $repo = $this->getRepository();
        $pk = $repo->getPrimaryKey();
        $query = $repo->select();

        if ($withParent) {
            $query
                ->where($repo->getAlias() . '.' . $pk, $id)
                ->leftJoin(
                    [$repo->getTable(), 'Parent'],
                    array_keys($repo->getSchema()->getColumns()), // We need the fields or joins fail
                    [$this->getConfig('parentField') => 'Parent.' . $pk]
                );
        } else {
            $query->where($pk, $id);
        }

        return $query->first();
    }

    /**
     * Return the hierarchical path to the current node.
     *
     * @param int $id
     * @return \Titon\Db\EntityCollection
     */
    public function getPath($id) {
        $node = $this->getNode($id);

        if (!$node) {
            return [];
        }

        $left = $this->getConfig('leftField');
        $right = $this->getConfig('rightField');

        return $this->getRepository()->select()
            ->where($left, '<', $node[$left])
            ->where($right, '>', $node[$right])
            ->orderBy($left, 'asc')
            ->all();
    }

    /**
     * Return a tree of nested nodes. If no ID is provided, the top level root will be used.
     *
     * @param int $id
     * @return array
     */
    public function getTree($id = null) {
        $parent = $this->getConfig('parentField');
        $left = $this->getConfig('leftField');
        $right = $this->getConfig('rightField');

        $query = $this->getRepository()->select()->orderBy($left, 'asc');

        if ($id) {
            if ($parentNode = $this->getNode($id)) {
                $query->where($left, 'between', [$parentNode[$left], $parentNode[$right]]);
            } else {
                return [];
            }
        }

        $nodes = $query->all();

        if ($nodes->isEmpty()) {
            return [];
        }

        $map = [];
        $stack = [];
        $pk = $this->getRepository()->getPrimaryKey();

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
        $key = $key ?: $this->getRepository()->getPrimaryKey();
        $value = $value ?: $this->getRepository()->getDisplayField();
        $right = $this->getConfig('rightField');

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
        $pk = $this->getRepository()->getPrimaryKey();

        foreach ($nodes as $node) {
            $id = $node[$pk];

            if (isset($mappedNodes[$id])) {
                $node[$this->getConfig('treeField') ?: 'Nodes'] = $this->mapTree($mappedNodes[$id], $mappedNodes);
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
        $pk = $this->getRepository()->getPrimaryKey();
        $node = $this->getNode($id, true);

        if (!$node || empty($node['Parent']['id'])) {
            return false;
        }

        $left = $this->getConfig('leftField');
        $right = $this->getConfig('rightField');
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
        $this->_moveNode(function(Query $query) use ($left, $right, $nodeRight, $newNodeRight) {
            $query->where($left, '>', $nodeRight)->where($right, '<=', $newNodeRight);
        }, [
            $left => Query::expr($left, '-', 2),
            $right => Query::expr($right, '-', 2)
        ]);

        // Move node down
        $this->_moveNode(function(Query $query) use ($pk, $id) {
            $query->where($pk, $id);
        }, [
            $left => $newNodeLeft,
            $right => $newNodeRight
        ]);

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
        $pk = $this->getRepository()->getPrimaryKey();
        $node = $this->getNode($id, true);

        if (!$node || empty($node['Parent']['id'])) {
            return false;
        }

        $left = $this->getConfig('leftField');
        $right = $this->getConfig('rightField');
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
        $this->_moveNode(function(Query $query) use ($left, $right, $nodeLeft, $newNodeLeft) {
            $query->where($left, '>=', $newNodeLeft)->where($right, '<', $nodeLeft);
        }, [
            $left => Query::expr($left, '+', 2),
            $right => Query::expr($right, '+', 2)
        ]);

        // Move node up
        $this->_moveNode(function(Query $query) use ($pk, $id) {
            $query->where($pk, $id);
        }, [
            $left => $newNodeLeft,
            $right => $newNodeRight
        ]);

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
        $pk = $this->getRepository()->getPrimaryKey();
        $node = $this->getNode($id);

        if (!$node || $node[$this->getConfig('parentField')] == $parent_id) {
            return false;
        }

        $left = $this->getConfig('leftField');
        $right = $this->getConfig('rightField');
        $data = [];

        // Remove the node and reset others
        $this->_removeNode($id, $node[$left]);

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

        // Move it
        $this->_moveNode(function(Query $query) use ($pk, $id) {
            $query->where($pk, $id);
        }, $data + [
            $this->getConfig('parentField') => $parent_id
        ]);

        $this->_insertNode($id, $data[$left]);

        return true;
    }

    /**
     * Before a delete, fetch the node and save its left index.
     * If a node has children, the delete will fail.
     *
     * @param \Titon\Event\Event $event
     * @param \Titon\Db\Query $query
     * @param int|int[] $id
     * @return bool
     */
    public function preDelete(Event $event, Query $query, $id) {
        if (!$this->getConfig('onDelete')) {
            return true;
        }

        if ($node = $this->getNode($id)) {
            $count = $this->getRepository()->select()
                ->where($this->getConfig('parentField'), $id)
                ->count();

            if ($count) {
                return false;
            }

            $this->_deleteIndex = $node[$this->getConfig('leftField')];
        }

        return true;
    }

    /**
     * After a delete, shift all nodes up using the base index.
     *
     * @param \Titon\Event\Event $event
     * @param int|int[] $id
     * @param int $count
     */
    public function postDelete(Event $event, $id, $count) {
        if (!$this->getConfig('onDelete') || !$this->_deleteIndex) {
            return;
        }

        $this->_removeNode($id, $this->_deleteIndex);
        $this->_deleteIndex = null;
    }

    /**
     * Before an insert, determine the correct left and right using the parent or root node as a base.
     * Do not shift the nodes until postSave() just in case the insert fails.
     *
     * Before an update, remove the left and right fields so that the tree cannot be modified.
     * Use moveUp(), moveDown(), moveTo() or reOrder() to update existing nodes.
     *
     * @param \Titon\Event\Event $event
     * @param \Titon\Db\Query $query
     * @param int|int[] $id
     * @param array $data
     * @return bool
     */
    public function preSave(Event $event, Query $query, $id, array &$data) {
        if (!$this->getConfig('onSave')) {
            return true;
        }

        $parent = $this->getConfig('parentField');
        $left = $this->getConfig('leftField');
        $right = $this->getConfig('rightField');

        // Append left and right during create
        if ($query->getType() === Query::INSERT) {
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
        }

        return true;
    }

    /**
     * After an insert, shift all nodes down using the base index.
     *
     * @param \Titon\Event\Event $event
     * @param int|int[] $id
     * @Param int $count
     */
    public function postSave(Event $event, $id, $count) {
        if (!$this->getConfig('onSave') || !$this->_saveIndex) {
            return;
        }

        $this->_insertNode($id, $this->_saveIndex);
        $this->_saveIndex = null;
    }

    /**
     * {@inheritdoc}
     */
    public function registerEvents() {
        return [
            'db.preSave' => 'preSave',
            'db.postSave' => 'postSave',
            'db.preDelete' => 'preDelete',
            'db.postDelete' => 'postDelete'
        ];
    }

    /**
     * Re-order the tree.
     *
     * @param array $order
     * @return bool
     */
    public function reOrder(array $order = []) {
        $this->_reOrder(null, 0, $order);

        return true;
    }

    /**
     * Prepares a node for insertion by moving all following nodes down.
     *
     * @param int $id
     * @param int $index
     */
    protected function _insertNode($id, $index) {
        $pk = $this->getRepository()->getPrimaryKey();

        foreach ([$this->getConfig('leftField'), $this->getConfig('rightField')] as $field) {
            $this->_moveNode(function(Query $query) use ($field, $index, $id, $pk) {
                $query->where($field, '>=', $index);

                if ($id) {
                    $query->where($pk, '!=', $id);
                }
            }, [
                $field => Query::expr($field, '+', 2)
            ]);
        }
    }

    /**
     * Move a node, or nodes, by applying a where clause to an update query and saving data.
     * Disable before and after callbacks to recursive events don't trigger.
     *
     * @param callable $callback
     * @param array $data
     * @return int
     */
    protected function _moveNode(Closure $callback, array $data) {
        return $this->getRepository()->updateMany($data, $callback, [
            'before' => false,
            'after' => false
        ]);
    }

    /**
     * Prepares a node for removal by moving all following nodes up.
     *
     * @param int $id
     * @param int $index
     */
    protected function _removeNode($id, $index) {
        $pk = $this->getRepository()->getPrimaryKey();

        foreach ([$this->getConfig('leftField'), $this->getConfig('rightField')] as $field) {
            $this->_moveNode(function(Query $query) use ($field, $index, $id, $pk) {
                $query->where($field, '>=', $index);

                if ($id) {
                    $query->where($pk, '!=', $id);
                }
            }, [
                $field => Query::expr($field, '-', 2)
            ]);
        }
    }

    /**
     * Re-order the tree by recursively looping through all parents and children,
     * ordering the results, and generating the correct left and right indexes.
     *
     * @param int $parent_id
     * @param int $left
     * @param array $order
     * @return int
     */
    protected function _reOrder($parent_id, $left, array $order = []) {
        $parent = $this->getConfig('parentField');
        $repo = $this->getRepository();
        $pk = $repo->getPrimaryKey();
        $right = $left + 1;

        // Get children and sort
        $children = $repo->select()
            ->where($parent, $parent_id)
            ->orderBy($order)
            ->all();

        foreach ($children as $child) {
            $right = $this->_reOrder($child[$pk], $right, $order);
        }

        // Update parent node
        if ($parent_id) {
            $this->_moveNode(function(Query $query) use ($pk, $parent_id) {
                $query->where($pk, $parent_id);
            }, [
                $this->getConfig('leftField') => $left,
                $this->getConfig('rightField') => $right
            ]);
        }

        return $right + 1;
    }

}