# Repositories #

A Repository class represents a table in a database.
It provides methods for interacting with data in that specific table by utilizing the loaded drivers.

### Setup ###

Create a repository class for each database table and provide default configuration.
View the base Repository class for the available configuration.

```php
use Titon\Db\Repository;

class User extends Repository {
    protected $_config = [
        'connection' => 'default', // key of driver to use
        'table' => 'users',
        'primaryKey' => 'id',
        'displayField' => 'username'
    ];

    public function initialize() {
        parent::initialize();

        // Set behaviors
    }
}
```

The configuration can also be set through the constructor. This allows for quick mocking.

```php
$repo = new Repository([
    'table' => 'users',
    'primaryKey' => 'id'
]);
```

### CRUD ###

The base repository provides methods for all basic CRUD functionality.

Creating records.

```php
$repo->create(['username' => 'miles']); // returns new ID
$repo->createMany([
    ['username' => 'foo'],
    ['username' => 'bar']
]); // returns inserted row count
```

Reading records.

```php
$repo->read(1); // Returns row

// Or through a select
$repo->select()->all(); // Returns all rows
$repo->select()->lists(); // Returns rows as a list
$repo->select('id', 'username')->where('id', 1)->first(); // Returns row
$repo->select()->count(); // Return row count
```

Updating records.

```php
$repo->update(1, ['username' => 'miles']); // returns affected row count
$repo->updateMany(['active' => true], function(Query $query) {
    $query->where('active', false);
});  // returns affected row count
```

Deleting records.

```php
$repo->delete(1); // returns affected row count
$repo->deleteMany(function(Query $query) {
    $query->where('active', true);
});  // returns affected row count
```

And a few other helpful methods.

```php
$repo->exists(1); // returns a bool
$repo->upsert($data); // either update or insert, checks for primaryKey field in $data or 2nd argument
```

For advanced query usage, view the query docs.