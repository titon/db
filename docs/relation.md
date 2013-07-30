# Relation #

A Relation class represents a relationship between database tables. This provides support for RDBMS systems, but also works for schemaless systems like MongoDB since relational data doesn't use joins.

The following relationships are supported:

* OneToOne (Has one)
* OneToMany (Has many)
* ManyToOne (Belongs to)
* ManyToMany (Has and belongs to many)

Relationships can be defined in models by calling the `addRelation()` method. The method returns the relation object allowing for continued modification.

```php
use Titon\Model\Model;
use Titon\Model\Relation\OneToOne;

class User extends Model {
	// ...

	public function initialize() {
		parent::initialize();

		$this->addRelation(new OneToOne('Profile', 'App\Model\Profile'))
			->setRelatedForeignKey('user_id');
	}
}
```

The previous example set a one-to-one relationship between the App\Model\User and App\Model\Profile model, through the Profile alias, and using the user_id in the profiles table as the foreign key.

Once a relationship is defined, it can be accessed through the alias name on the model object. Nested relationships are also supported.

```php
$user = new User();
$user->Profile; // Profile model
```

### Usage ###

Each relation type must be defined in a certain manner. This assumes that foreign key and junction mapping is set correctly.

When mapping the foreign keys, there is `setForeignKey()` which sets the field within the current model, and `setRelatedForeignKey()` which sets the field within the external related model.

```php
// OneToOne - User has one Profile
$this->addRelation(new OneToOne('Profile', 'App\Model\Profile'))
	->setRelatedForeignKey('user_id'); // profiles.user_id

// OneToMany - User has many Posts
$this->addRelation(new OneToMany('Posts', 'App\Model\Post'))
	->setRelatedForeignKey('user_id'); // posts.user_id

// ManyToOne - User belongs to Country
$this->addRelation(new ManyToOne('Country', 'App\Model\Country'))
	->setForeignKey('country_id'); // users.country_id

// ManyToMany - User has and belongs to many Groups
$this->addRelation(new ManyToMany('Groups', 'App\Model\Group'))
	->setJunctionClass('App\Model\UserGroup')
	->setForeignKey('user_id') // user_groups.user_id
	->setRelatedForeignKey('group_id'); // user_groups.group_id
```

When defining ManyToMany relations, the junction model and both foreign keys must be defined.

Optional query conditions can be defined for each relation to filter the data. For example, only include active posts.

```php
$this->addRelation(new OneToMany('Posts', 'App\Model\Post'))
	->setRelatedForeignKey('user_id')
	->setConditions(function() {
		// Represents a query object
		$this->where('active', true);
	});
```

### CRUD ###

Relational data can be created, read, updated or deleted when the parent model executes a CRUD operation.

While creating parent records, define an array of data using the relation alias. ManyToOne relations will be ignored as they are technically the parent.

```php
$user->create([
	'country_id' => 1,
	'username' => 'foo',
	'Profile' => [
		'signature' => 'bar'
	]
]);

Include relational data using `with()` while reading parent records.

```php
$row = $user->select()
	->with('Country')
	->with('Profile', function() {
		// with custom conditions
	})
	->fetch();

// Or a list
$row = $user->select()
	->with(['Country', 'Profile'])
	->fetch();
```

Similar to creating, define an array of data using the relation alias that will be updated as well. The primary key is required else a new record will be created.

```php
$user->update(1, [
	'username' => 'bar',
	'Profile' => [
		'id' => 1,
		'signature' => 'foo'
	]
]);
```

When deleting, dependent records can be deleted by setting `cascade` to true.

```php
$user->delete(1, true); // delete all related records
$user->delete(1, false); // will not delete related records

// Or with option array
$user->delete(1, ['cascade' => true]);
```