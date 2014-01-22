# Database v0.9.2 [![Build Status](https://travis-ci.org/titon/db.png)](https://travis-ci.org/titon/db) #

The Titon database package provides a basic database abstraction layer and an object relational mapper.
Supported drivers are implemented in external packages that can be installed through composer.

### Drivers ###

* `MySQL` - https://github.com/titon/db-mysql
* `PostgreSQL` - https://github.com/titon/db-postgresql
* `SQLite` - https://github.com/titon/db-sqlite
* `MongoDB` - https://github.com/titon/db-mongodb

### Features ###

* `Repository` - Queries drivers, maps relations and returns entities
* `Behavior` - Executes logic during database callbacks
* `Relation` - Object relation mapper (ORM)
* `Entity` - Single record of data
* `Connection` - Driver manager
* `Driver` - Interacts with a database or remote service (DBAL)
    * `Dialect` - Driver specific SQL formatting
    * `Type` - Data type mapping
    * `Schema` - Repository schema
* `Query` - Object oriented query builder
    * `Expr` - Expression builder
    * `Func` - Function builder
    * `Join` - Join builder
    * `Predicate` - Clause builder
    * `Result` - Result set mapper

### Dependencies ###

* `Common`
* `Cache` (optional)
* `Psr\Log` (optional)

### Requirements ###

* PHP 5.4.0
    * PDO