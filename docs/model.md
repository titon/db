# Model #

A Model represents a table in a database. It provides methods for interacting with data in that specific table by utilizing the loaded drivers.

Database relationships can be mapped through the model layer using `Relation` classes. Jump to the relation docs on how to support this.

Furthermore, a `Behavior` can be used to modify data or logic while performing CRUD operations. Jump to the behavior docs on how to use them.

### Setup ###

Create a model for each database table and provide default configuration.

```php
use Titon\Model\Model;

class User extends Model {
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

View the base model class for the available configuration.

The configuration can also be set through the constructor. This allows for quick mocking.

```php
$model = new Model([
	'table' => 'users',
	'primaryKey' => 'id'
]);
```

### Methods ###

The base model implements the singleton pattern through a singleton trait. All data fetching or saving methods can be called statically.

```php
class User extends Model {
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

The base model provides methods for all basic CRUD functionality.

Creating records.

```php
$model->create(['username' => 'miles']); // returns new ID
$model->createMany([
	['username' => 'foo'],
	['username' => 'bar']
]); // returns inserted row count
```

Reading records.

```php
$model->read(1); // Returns row

// Or through a select
$model->select()->fetchAll(); // Returns all rows
$model->select()->fetchList(); // Returns rows as a list
$model->select('id', 'username')->where('id', 1)->fetch(); // Returns row
$model->select()->count(); // Return row count
```

Updating records.

```php
$model->update(1, ['username' => 'miles']); // returns affected row count
$model->updateMany(['active' => true], function() {
	// Closure represents a query object
	$this->where('active', false);
});  // returns affected row count
```

Deleting records.

```php
$model->delete(1); // returns affected row count
$model->deleteMany(function() {
	// Closure represents a query object
	$this->where('active', true);
});  // returns affected row count
```

And a few other helpful methods.

```php
$model->exists(1); // returns a bool
$model->upsert($data); // either update or insert, checks for primaryKey field in $data or 2nd argument
```