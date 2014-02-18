# Database v0.10.1 [![Build Status](https://travis-ci.org/titon/db.png)](https://travis-ci.org/titon/db) #

The Titon Database package provides a lightweight and low-level interface for interacting with database engines (known as drivers).
The DB package comes bundled with a robust database abstraction layer (DBAL), an object oriented query builder,
a powerful SQL dialect formatter, a data type caster, custom finder classes, behaviors, mappers, schemas, and many more.

Alongside the DBAL is an extensible object relational mapper (ORM) that permits repositories (database tables) to
relate records to other repository records through foreign keys. Related data can also be saved automatically while saving parent records,
and can be pulled in automatically and easily through the query builder. The ORM is fully compatible with schemaless/NoSQL database drivers.

Supported database engines are packaged as individual driver packages, which are listed below.

### Drivers ###

* `MySQL` - https://github.com/titon/db-mysql
* `PostgreSQL` - https://github.com/titon/db-postgresql
* `SQLite` - https://github.com/titon/db-sqlite
* `MongoDB` - https://github.com/titon/db-mongodb

### Features ###

* `Repository` - Queries drivers, maps relations and returns entities
* `Behavior` - Executes logic during database events
* `Relation` - Object relation mapper
* `Entity` - Single record of data
* `Mapper` - Modify data before save and after find
* `Connection` - Driver manager
* `Driver` - Interacts with a database or remote service
    * `Dialect` - Driver specific SQL formatting
    * `Finder` - Select query formatting
    * `Schema` - Repository schema
    * `Type` - Data type mapping
* `Query` - Object oriented query builder
    * `Expr` - Expression builder
    * `Func` - Function builder
    * `Join` - Join builder
    * `Predicate` - Clause builder
    * `Result` - Result set mapper

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

* Different configurations for read and writes / master and slaves
* Built-in aggregate methods
* Select unions
* Locking and unlocking
* Polymorphic relations
* Refactored lazy/eager loading of relations