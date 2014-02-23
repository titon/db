# Database v0.11.0 [![Build Status](https://travis-ci.org/titon/db.png)](https://travis-ci.org/titon/db) #

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

Alongside the DBAL is an extensible object relational mapper (ORM) that permits repositories (database tables) to
relate records to other repository records through foreign keys. Related data can also be saved automatically while saving parent records,
and can be pulled in automatically and easily through the query builder. The ORM is fully compatible with schemaless/NoSQL database drivers.

```php
$users->hasOne('Profile', 'App\Repository\Profile', 'profile_id');

$entity = $users->select()->with('Profile')->where('id', 1)->first();
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
* `Relation` - Object relation mapper
* `Entity` - Single record of data
* `Finder` - Select query formatting
* `Driver` - Interacts with a database or remote service
    * `Dialect` - Driver specific SQL formatting
    * `Schema` - Repository schema
    * `Type` - Data type mapping
* `Query` - Object oriented query builder
    * `RawExpr` - Raw expression builder
    * `Expr` - Expression builder
    * `Func` - Function builder
    * `Join` - Join builder
    * `Predicate` - Clause builder
    * `ResultSet` - Result set mapper

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
* Polymorphic relations
* Refactored lazy/eager loading of relations