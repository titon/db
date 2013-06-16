# Model [![Build Status](https://travis-ci.org/titon/Model.png)](https://travis-ci.org/titon/Model) #

The Titon model package provides a basic database abstraction layer and an object relational mapper.
Requires the PDO extension for database connections.

### Features ###

* `Model` - Queries drivers, maps relations and returns entities
* `Relation` - Object relation mapper (ORM)
* `Entity` - Single record of data
* `Connection` - Driver manager
* `Driver` - Interacts with a database or remote service
	* `Dialect` - Driver specific SQL formatting
	* `Type` - Data type mapping
	* `Schema` - Table schema
* `Query` - Object oriented query builder
	* `Func` - Database function builder
	* `Predicate` - Advanced clause builder
	* `Result` - Result set mapper
	* `Log` - Query logging

### Dependencies ###

* `Common`
* `Cache` (optional)
* `Psr\Log` (optional)

### Requirements ###

* PHP 5.4.0
	* PDO

### To Do ###

* Unit Testing
* Behaviors
* Transactions
* Improved Operator Support
* Fetch Neighbors/Threaded
* Pagination