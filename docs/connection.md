# Connections #

The Connection class manages the loading and fetching of drivers. A driver represents a type of database or external data source.
Simply instantiate a Connection object and set the available drivers.

```php
use Titon\Common\Registry;
use Titon\Db\Mysql\MysqlDriver;

$conn = Registry::factory('Titon\Db\Connection'); // Registry required

// Requires the db-mysql package
$conn->addDriver(new MysqlDriver('default', [
    'host' => '127.0.0.1',
    'user' => 'user',
    'pass' => 'pass'
]));
```

To retrieve the driver, use the key name.

```php
$driver = $conn->getDriver('default');
```

The `connection` configuration in the Repository represents the driver key.