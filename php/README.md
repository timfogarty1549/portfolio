## php
For most project that I've worked on, I do my best to not use the PHP script to create or manipulate HTML, that is to create the view.  Flex/Actionscript applications are already compiled, so there is not much the server could send anyway.  Angular applications have their own templating system that they can manipulate in javascript. Best practices are that the server side PHP code is used only to send and receive Remote Objects, to extract data out of a database or other system, wrap it in some protocol (such as JSON) and send it to the front end UI. Or receive an object from the server and pass it on to the database.  I use AMFPHP as the framework for Flex/Actionscript projects and SLIM for Angular. Both are used to route incoming URLs (RESTful or not) and return Remote Objects.  With AMFPHP and Flex, a server side PHP Class maps to a browser side Actionscript Class.  Create an object on one side and it shows up as that same object on the other. With SLIM, an object is sent out as JSON, which the Javascript can parse.  

The PHP standard API mysqli has the method fetch_object which allows you to pass it the name of a class.  The fields of the class are automatically assigned the values of the fields returned from the query, eliminating the need for a whole bunch of assignment statements placing the results of the query into the object being sent to the browser. With this in mind, I create a class, Mysql (perhaps I could have named it better) to perform all the standard CRUD functions on objects, and an abstract class, DatabaseTemplate, to define those objects.

####Example

Script receiving the restful calls, as defined in .htaccess

````php
<?php
require_once 'config.php';
require_once 'JSONSlim.php';

$app = new JSONSlim ();

$app->get ( "/user/:user_id", function ($user_id) use($app) {
	$app->render ( 200, User::fetchRecord( $user_id ) );
} );

$app->get ( "/users", function () use($app) {
	$app->render ( 200, User::fetchList() );
} );

$app->post('/user', function () use ($app) {
    $json = $app->request->getBody();
    $data = json_decode($json, true);
    $status = User::updateRecord( $data );
    $app->render ( 200, $status );
});

$app->get( "/user/:user_id/:field/:value", function( $user_id, $field, $value ) use($app) {
	$status = User::updateField( $user_id, $field, $value );
    $app->render ( 200, $status );
});

$app->run ();
````

config.php contains 

````php
<?php
define ( "MYSQL_HOST", "127.0.0.1" );
define ( "MYSQL_DB", "your db name" );
define ( "MYSQL_USER", "your db user" );
define ( "MYSQL_PASS", "your db password" );
````

JSONSlim.php is a class that extends Slim, wrapping the results in JSON. The structure of the returned array may vary depending on your front end script.  Note that Slim adds a status and an error field to the object.  Having a data field works equally well for both an object and a list of objects.

````php
<?php
require_once 'Slim/Slim.php';

use Slim\Slim;
use Slim\jsonAPI\JsonApiMiddleware;
use Slim\jsonAPI\JsonApiView;

Slim::registerAutoloader ();
class JSONSlim extends Slim {
	/**
	 * Constructor
	 *
	 * @param array $userSettings
	 *        	Associative array of application settings
	 */
	public function __construct(array $userSettings = array()) {
		parent::__construct ( $userSettings );
		$this->view ( new JsonApiView ( JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT ) );
		$this->add ( new JsonApiMiddleware () );
	}
	public function render($status = 200, $data = NULL) {
		if ($data == null) {
			$array = [ 
					'msg' => 'empty set',
					'data' => [ ] 
			];
		} else if (is_string ( $data )) {
			// need to set error to true
			$array = [ 
					'data' => [ ],
					'msg' => $data 
			];
		} else {
			$array = [ 
					'data' => $data 
			];
		}
		parent::render ( $status, $array );
	}
}
````

Then for each object that you want to send to or receive from the browser, create a class that extends DatabaseTemplate.  A class may represent a row in the database, or a row returned from a query with many joins.

####User class

````php
class User extends DatabaseTemplate {
	var $_explicitType = "com.musclememory.VO.User";
	public static $TABLE_NAME = 'users';
	public static $CLASS_NAME = 'User';

	public static $fields = array (
			'username',
			'name',
			'address',
			'city',
			'state',
			'zipcode'
	);
	public static $keys = array (
			'user_id' 
	);
	public $user_id;
	public $username;
	public $name;
	public $address;
	public $city;
	public $state;
	public $zipcode;

	public function __construct($data = null) {
		parent::__construct ( $data );
	}
}
````

This is all that is needed for a complete RESTful script. Making the connection to the database, building the queries and fetching the objects are all contained within DatabaseTemplate.php and Mysql.php.

