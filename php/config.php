<?php
if (! defined ( 'ENTRY_POINT_CHECK' ))
	die ( 'You are not allowed to execute this file directly' );

define ( "ERROR_LOG", __DIR__ . "/error.log" );

define ( "CONFIG_PASSWORD", "Frogs123!" ); // set to empty string to disable config script
define ( "CONFIG_SESSION_KEY", "123456789" ); // only used until session_info table created

define ( "MYSQL_ENGINE", "mysql" );
define ( "MYSQL_HOST", "127.0.0.1" );
define ( "MYSQL_DB", "your database" );
define ( "MYSQL_USER", "your username" );
define ( "MYSQL_PASS", "your password" );

ini_set ( "date.timezone", "America/Los_Angeles" );

error_reporting ( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );

ini_set ( "log_errors", "1" );
// ini_set("error_log" , "error.log");
ini_set ( "display_errors", "0" );

ini_set ( "memory_limit", "400M" );
