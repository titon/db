# Behaviors #

A Behavior is a class that can be loaded into a table and will hook into the CRUD process to modify accordingly.

The following callbacks are supported: preSave, postSave, preDelete, postDelete, preFetch, postFetch. View the `Titon\Db\Callback` interface for method and argument definitions.

A behavior can be loaded by calling `addBehavior()` within the table.

The following behaviors are supported. All examples will borrow from the relation docs.

### Countable ###

The CountableBehavior provides a way for many-to-one|many relations to track a count of how many related records exist.

```php
use Titon\Db\Table;
use Titon\Db\Relation\ManyToOne;
use Titon\Db\Behavior\CountableBehavior;

class Post extends Table {
    // ...

    public function initialize() {
        parent::initialize();

        $this->addRelation(new ManyToOne('User', 'App\Db\User'))
            ->setForeignKey('user_id');

        $this->addBehavior(new CountableBehavior())
            ->addCounter('User', 'post_count'); // users.post_count
    }
}
```

Each time a post is created, updated or deleted, the post_count will be updated in the user record.

### Hierarchical ###

The HierarchicalBehavior implements a pattern of tree traversal which allows for a nested hierarchy of nodes. The tree is based off the Modified Preorder Tree Traversal (MPTT) pattern: http://www.sitepoint.com/hierarchical-data-database-2/

Any table that implements this behavior will require a parent_id, left and right column.

```php
use Titon\Db\Table;
use Titon\Db\Behavior\HierarchicalBehavior;

class Category extends Table {
    // ...

    public function initialize() {
        parent::initialize();

        $this->addBehavior(new HierarchicalBehavior());

        // Or with custom field names
        $this->addBehavior(new HierarchicalBehavior([
            'parentField' => 'category_id',
            'leftField' => 'lft',
            'rightField' => 'rght'
        ]));
    }
}
```

Now after every record insertion and deletion, the tree is updated accordingly.

To fetch the tree, use the following methods within the table.

```php
$this->getBehavior('Hierarchical')->getList(); // returns an indented list
$this->getBehavior('Hierarchical')->getTree(); // returns a nested array tree
$this->getBehavior('Hierarchical')->getPath($id); // returns the tree path to the node
```

### Timestampable ###

The TimestampableBehavior will update a field with a timestamp anytime a record is created or updated. Columns default to created and updated.

```php
use Titon\Db\Table;
use Titon\Db\Behavior\TimestampableBehavior;

class Post extends Table {
    // ...

    public function initialize() {
        parent::initialize();

        $this->addBehavior(new TimestampableBehavior());

        // Or with custom field names
        $this->addBehavior(new TimestampableBehavior([
            'createField' => 'dateCreated',
            'updateField' => 'dateUpdated'
        ));
    }
}
```