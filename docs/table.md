# Tables #

A Table class represents a table in a database. It provides methods for interacting with data in that specific table by utilizing the loaded drivers.

Database relationships can be mapped through the table layer using `Relation` classes. Jump to the relation docs on how to support this.

Furthermore, a `Behavior` can be used to modify data or logic while performing CRUD operations. Jump to the behavior docs on how to use them.

### Setup ###

Create a table class for each database table and provide default configuration. View the base table class for the available configuration.

```php
use Titon\Db\Table;

class User extends Table {
    protected $_config = [
        'connection' => 'default', // key of driver to use
        'table' => 'users',
        'primaryKey' => 'id',
        'displayField' => 'username'
    ];

    public function initialize() {
        // Set behaviors
        // Set relations
    }
}
```

The configuration can also be set through the constructor. This allows for quick mocking.

```php
$table = new Table([
    'table' => 'users',
    'primaryKey' => 'id'
]);
```

### Methods ###

The base table implements the singleton pattern through a singleton trait. All data fetching or saving methods can be called statically.

```php
class User extends Table {
    // ...

    public static function getAll() {
        return self::getInstance()->select()->fetchAll();
    }

    public static function getById($id) {
        return self::getInstance()->read($id);
    }
}

// In the application
$user = User::getById(1);
$users = User::getAll();
```

### CRUD ###

The base table provides methods for all basic CRUD functionality.

Creating records.

```php
$table->create(['username' => 'miles']); // returns new ID
$table->createMany([
    ['username' => 'foo'],
    ['username' => 'bar']
]); // returns inserted row count
```

Reading records.

```php
$table->read(1); // Returns row

// Or through a select
$table->select()->fetchAll(); // Returns all rows
$table->select()->fetchList(); // Returns rows as a list
$table->select('id', 'username')->where('id', 1)->fetch(); // Returns row
$table->select()->count(); // Return row count
```

Updating records.

```php
$table->update(1, ['username' => 'miles']); // returns affected row count
$table->updateMany(['active' => true], function() {
    // Closure represents a query object
    $this->where('active', false);
});  // returns affected row count
```

Deleting records.

```php
$table->delete(1); // returns affected row count
$table->deleteMany(function() {
    // Closure represents a query object
    $this->where('active', true);
});  // returns affected row count
```

And a few other helpful methods.

```php
$table->exists(1); // returns a bool
$table->upsert($data); // either update or insert, checks for primaryKey field in $data or 2nd argument
```

For advanced query usage, view the query docs.