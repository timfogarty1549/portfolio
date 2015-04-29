<?php
define ( 'ENTRY_POINT_CHECK', 1 );
define ( 'DEBUG_MODE', 1 );

require_once 'config.php';
require_once 'functions.php';
require_once 'JSONSlim.php';

use Slim\Extras\Log\DateTimeFileWriter;
use \Slim\Middleware\HttpBasicAuthentication\AuthenticatorInterface;

$config = array (
		'Slim' => array (
				'debug' => true,
				'templates.path' => __DIR__ . '/php/templates',
				'log.level' => 4,
				'log.enabled' => true,
				'log.writer' => new DateTimeFileWriter ( array (
						'path' => __DIR__ . '/logs',
						'name_format' => 'y-m-d' 
				) ) 
		) 
);

$app = new JSONSlim ( $config ['Slim'] );
// $app->response->headers->set('Content-Type', 'application/json');

$app->error ( function (Exception $e) use($app) {
	// $app->render('error.php');
	echo "<pre>";
	print_r ( $e );
} );

$app->notFound ( function () use($app) {
	// $app->render('404.html');
	echo "<h3>Path not found</h3>";
	echo "<pre>";
	print_r ( $_SERVER );
} );


/**
 * see http://www.appelsiini.net/projects/slim-jwt-auth for other types of authorization
 */
$app->add ( new \Slim\Middleware\HttpBasicAuthentication ( [ 
		"path" => "/admin",
		"realm" => "Protected",
		"authenticator" => new UserAuthenticator () 
] ) );

/**
 * just a few restful calls.  All the POSTS and DELETES are in admin.php
 */

$app->get ( "/mags", function () use($app) {
	$app->render ( 200, Magazine::fetchSummary () );
} );

$app->get ( "/mags/count", function () use($app) {
	$app->render ( 200, MagazineCover::fetchAthleteCount () );
} );

$app->get ( "/mags/:mag", function ($mag_id) use($app) {
	$app->render ( 200, MagazineIssue::fetchByMagazine ( $mag_id ) );
} );

$app->get ( "/mag_issue/:mag_issue_id", function ($mag_issue_id) use($app) {
	$app->render ( 200, MagazineIssue::fetchIssue ( $mag_issue_id ) );
} );

$app->post ( "/login/cookie", function () use($app) {
	$body = json_decode ( $app->request ()->getBody () );
	list ( $status, $results ) = Users::loginFromToken ( $body->token );
	$app->render ( $status, $results );
} );

$app->post ( "/login", function () use($app) {
	$body = json_decode ( $app->request ()->getBody () ); // why isn't is showing up in request()->params() ?
	list ( $status, $results ) = Users::login ( $body->email, $body->password );
	$app->render ( $status, $results );
} );

$app->run ();

class UserAuthenticator implements AuthenticatorInterface {
	public function authenticate($user, $pass) {
		return Users::validate( $user, $pass );
	}
}
