# Database v0.11.2 [![Build Status](https://travis-ci.org/titon/db.png)](https://travis-ci.org/titon/db) #

The Titon Database package provides a lightweight and low-level interface for interacting with database engines (known as drivers).
The DB package comes bundled with a robust database abstraction layer (DBAL), an object oriented query builder,
a powerful SQL dialect formatter, a data type caster, custom finder classes, behaviors, mappers, schemas, and many more.

```php
$db = Titon\Db\Database::registry();
$db->addDriver('default', new Titon\Db\Mysql\MysqlDriver([
    'user' => 'root',
    'pass' => 'pass'
]));

$users = new Titon\Db\Repository(['table' => 'users']);
$entities = $users->select()->where('status', 1)->orderBy('created_at', 'desc')->all();
```

Supported database engines are packaged as individual driver packages, which are listed below.

### Drivers ###

* `MySQL` - https://github.com/titon/db-mysql
* `PostgreSQL` - https://github.com/titon/db-postgresql
* `SQLite` - https://github.com/titon/db-sqlite
* `MongoDB` - https://github.com/titon/db-mongodb

### Features ###

* `Database` - Driver manager
* `Repository` - Table representation, queries drivers, maps relations and returns entities
* `Behavior` - Executes logic during database events
* `Entity` - Single record of data
* `EntityCollection` - Collection of entities
* `Finder` - Select query formatting
* `Driver` - Interacts with a database or remote service
    * `Dialect` - Driver specific SQL formatting
    * `Schema` - Repository schema
    * `Type` - Data type mapping
    * `ResultSet` - Result set mapper
* `Query` - Object oriented query builder
    * `RawExpr` - Raw expression builder
    * `Expr` - Expression builder
    * `Func` - Function builder
    * `Join` - Join builder
    * `Predicate` - Clause builder

### Dependencies ###

* `Common`
* `Event`
* `Type`
* `Cache` (optional)
* `Psr\Log` (optional)

### Requirements ###

* PHP 5.4.0
    * PDO

### Upcoming Features ###

* Built-in aggregate methods