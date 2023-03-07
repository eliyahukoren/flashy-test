## flashy-test
A PHP Class that reads JSON file as a database

#### Docker compose
```
make start
```

###### Or
#### Docker build and run
```
make build
make run
```

#### Initialize
```php
<?php 

// __DIR__ Or passing the directory of your json files.
// E.g. new JSONDB( '/usr/src/flashy/db' )
$jsonDb = new JSONDB( __DIR__ ); 
```

#### Inserting
Insert into new JSON file. Using *users.json* as example here

**NB:** *Columns inserted first will be the only allowed column on other inserts*

```php
<?php
$jsonDb->insert( 'users', 
	[ 
    'id' => 1,
    'user_name' => 'un0001',
		'first_name' => 'Mike', 
		'last_name' => 'Brown', 
	]
);
```

#### Get 
Get back data, just like SQL in PHP

##### All columns:
```php
<?php
// Or $users = $jsonDb->select('*')
$allFields = $jsonDb->select()
	->from( 'users' )
	->get();

print_r( $allFields );
```

##### Custom Columns:
```php
<?php 
$customFields = $jsonDb->select( 'first_name, last_name'  )
	->from( 'users' )
	->get();

print_r( $customFields );
	
```

##### Where Statement:
This WHERE works as AND Operator at the moment or OR
```php
<?php 
$whereUsers = $jsonDb->select( 'first_name, last_name'  )
	->from( 'users' )
	->where( [ 'first_name' => 'Mike' ] )
	->get();

print_r( $whereUsers );

// Where first_name OR user_name
$whereUsers = $jsonDb->select( 'id, first_name'  )
	->from( 'users' )
	->where( [ 'first_name' => 'Mike', 'user_name' => 'dummy' ] )
	->get();

print_r( $whereUsers );  
	
// Where first_name AND user_name 
$whereUsers = $jsonDb->select( 'name, state'  )
	->from( 'users.json' )
	->where( [ 'first_name' => 'Mike', 'user_name' => 'un0001' ], JSONDB::AND )
	->get();

print_r( $whereUsers );  	
```

##### Order By:
```php
<?php 
$sortedUsers = $jsonDb->select( 'id, first_name, last_name'  )
	->from( 'users' )
	->order_by( 'first_name', JSONDB::ASC )
	->get();

print_r( $sortedUsers );
```

##### Updating Row
*Without the **where()** method, it will update all rows*
```php
<?php 
$jsonDb->update( [ 'first_name' => 'John', 'last_name' => 'Smith' ] )
	->from( 'users' )
	->where( [ 'first_name' => 'Mike', 'last_name' => 'Brown' ], JSONDB::AND )
	->flush();
	
```

#### Deleting Row
*Without the **where()** method, it will deletes all rows*
```php
<?php
$jsonDb->delete()
	->from( 'users.json' )
	->where( [ 'name' => 'Thomas' ] )
	->flush();

```
