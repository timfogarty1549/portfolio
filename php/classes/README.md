### Abstract Class DatabaseTemplate

- __construct( $data array )

````php
class User extends DatabaseTemplate { ... }

$user = new User( [ 'username'=>'timF', 'name'=>'Tim Fogarty' } );
````

- static function fromJSON( $string string ) 

````php
$user = User::fromJSON( $app->request->getBody() );
````

- static function fetchRecord( args... ) 
