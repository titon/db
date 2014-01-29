# Queries #

A Query is an object oriented approach to SQL query building. A query can be instantiated through the table as so:

```php
$query = $this->query(Query::UPDATE); // specify the type of query
$query = $this->select(); // returns a QUERY::SELECT object
```

### Standard ###

Set the fields to fetch, or the data to insert/update.

```php
$query->fields('id', 'username'); // select
$query->fields(['created'], true); // select merge or overwrite fields
$query->fields(['username' => 'foo']); // update or insert
```

Set the table to query. Defaults to the table name.

```php
$query->from('users');
$query->from('users', 'User'); // aliased
```

Set the order by.

```php
$query->orderBy('id', 'asc');
$query->orderBy(['id' => 'asc', 'created' => 'desc']);
```

Set the group by.

```php
$query->groupBy('id');
```

Set the limit and offset.

```php
$query->limit(10);
$query->offset(5);
$query->limit(10, 5); // both
```

Include related records.

```php
$query->with('RelationAlias');
```

### Where and Having ###

Where and having clauses support the following group operators: or, and and xor.
They can be called with the methods: `where()`, `orWhere()`, `xorWhere()`, `having()`, `orHaving()`, `xorHaving()`. Each method works exactly the same.

Call the method multiple times to add multiple expressions.

```php
$query
    ->where('active', true) // active = true
    ->where('age', '>', 25); // AND age > 25
```

Use advanced operators as the second argument.

```php
$query
    ->orWhere('color', 'in', ['red', 'green', 'blue']) // color IN ('red', 'green', 'blue')
    ->orWhere('color', 'like', '%black%'); // OR color LIKE '%black%'
```

The following operators are supported: =, !=, >, >=, <, <=, <>, isNull, isNotNull, like, notLike, in, notIn, between, notBetween, regexp, notRegexp.

Advanced predicates can be built when a closure is provided. This also allows for nested predicates.

```php
$query->where(function() {
    // Represents a Predicate object
    $this
        ->in('color', ['red', 'green', 'blue'])
        ->notLike('size', '%small%')
        ->either(function() {
            $this->gte('quantity', 5)->notEq('soldOut', 0);
        });
}); // color IN ('red', 'green', 'blue') AND size NOT LIKE '%small%' AND (quantity >= 5 OR soldOut != 0)
```

To generate sub-groupings, call the following methods. `also()` will create an AND group. `either()` will create an OR group. `neither()` will create a NOR group. `maybe()` will create a XOR group.

### Joins ###

Data from other tables can be joined in. This is different than using `with()` to include relationships.
The following methods can be used to join data: `leftJoin()`, `rightJoin()`, `innerJoin()`, `outerJoin()`, `straightJoin()`. Each method works exactly the same.

Join tables through ON declarations. The second argument is the field list, leave blank to include all.

```php
$query->rightJoin('profiles', [], ['id' => 'profiles.id']);
$query->rightJoin(['profiles', 'Profile'], [], ['id' => 'Profile.id']); // with aliasing
```

Join relations or just use `with()` instead.

```php
$query->leftJoin($this->getRelation('Profile'));
```

When data is fetched, all joined records will be indexed in a sub-array using the alias.

### Advanced ###

Expressions can be used by calling `expr()`.

```php
$query->fields([
    'foo' => 'bar', // foo = 'bar'
    'count' => Query::expr('count', '+', 1) // count = count + 1
]);
```

Functions can be used by calling `func()`. Function literals, column fields and nested functions are all supported.

```php
$query->fields([
    'id',
    'username',
    Query::func('SUBSTRING', ['username' => Func::FIELD, 5])->asAlias('shortName') // SUBSTRING(username, 5) AS shortName
]);
```

Sub-queries can be used by calling `subQuery()`. Only select queries are supported.

```php
$query->where('id', $query->subQuery('id')->from('tableName')); // id = (SELECT id FROM tableName)
```

### Executing ###

After a query is built, it needs to be executed to return data or the affected rows.

```php
// Select queries
$query->first(); // return single row
$query->all(); // return multiple rows
$query->lists(); // return rows as a key value list
$query->count(); // return count

// Custom finders
$query->find('threaded');

// Other queries
$query->save(); // return affected row count
```