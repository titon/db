# Entities #

An Entity is a class that represents a set of data, usually an array. This class is instantiated for every row in a database result.

```php
$row = new Entity(['foo' => 'bar']);
$row->foo; // bar
$row['foo'] // bar
```

It's extremely helpful in evaluating keys that do not exist. Instead of throwing errors, it would simply return null. Very useful for conditional blocks!

```php
if ($row->someValue) {
    // Returns false since someValue doesn't exist in the array
}
```

By default, all database results use a generic entity. Why not use a custom entity for each model? This allows for custom methods and logic.

```php
use App\Model\User;
use Titon\Model\Entity;

class UserEntity extends Entity {
    public function isActive() {
        return ($this->status == User::ACTIVE);
    }
}
```

Now see it in action!

```php
$row = new UserEntity(['user' => 'titon', 'active' => 1]);

if ($row->isActive()) {
    echo $row->user;
}
```

To set the entity class used by models, update the `entity` configuration with the fully qualified class name.