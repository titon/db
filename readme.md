# Model v0.6.0 [![Build Status](https://travis-ci.org/titon/Model.png)](https://travis-ci.org/titon/Model) #

The Titon model package provides a basic database abstraction layer and an object relational mapper.
Supported drivers are implemented in external packages that can be installed through composer.

### Drivers ###

* `MySQL` - https://github.com/titon/Model.MySQL
* `PostgreSQL` - https://github.com/titon/Model.PostgreSQL
* `SQLite` - https://github.com/titon/Model.SQLite
* `MongoDB` - https://github.com/titon/Model.MongoDB

### Features ###

* `Model` - Queries drivers, maps relations and returns entities
* `Behavior` - Executes logic during model callbacks
* `Relation` - Object relation mapper (ORM)
* `Entity` - Single record of data
* `Connection` - Driver manager
* `Driver` - Interacts with a database or remote service (DBAL)
	* `Dialect` - Driver specific SQL formatting
	* `Type` - Data type mapping
	* `Schema` - Table schema
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