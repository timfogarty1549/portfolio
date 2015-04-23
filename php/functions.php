<?php
if (! defined ( 'ENTRY_POINT_CHECK' ))
	die ( 'You are not allowed to execute this file directly' );

define ( "MYSQL_TIME_FMT", "Y-m-d H:i:s" );

spl_autoload_register ( function ($class_name) {
	$fname = __DIR__ . "/classes/$class_name.php";
	if (file_exists ( $fname ))
		include_once $fname;
} );

spl_autoload_register ( function ($class_name) {
	$fname = __DIR__ . "/VO/$class_name.php";
	if (file_exists ( $fname ))
		include_once $fname;
} );
function is_debugMode() {
	return defined ( 'DEBUG_MODE' ) && DEBUG_MODE == 1;
}
function debug($obj, $label = null) {
	echo "<pre>$label\n";
	print_r ( $obj );
	echo "</pre>\n";
}
function logError() {
	$array = func_get_args ();
	$str = "";
	foreach ( $array as $item ) {
		if (is_string ( $item ))
			$str .= "\n" . $item;
		else
			$str .= "\n" . var_export ( $item, true );
	}
	error_log ( $str, 3, ERROR_LOG );
}
function getTimestamp($t0 = 0) {
	$t = $t0 > 1000000 ? $t0 : time () + $t0;
	return date ( MYSQL_TIME_FMT, $t );
}
function getRequest($l, $default = null) {
	return array_key_exists ( $l, $_REQUEST ) ? $_REQUEST [$l] : $default;
}
function getIntRequest($l, $default = 0) {
	return intval ( getRequest ( $l, $default ) );
}
function getRequestClean($l, $default = null) {
	return htmlentities ( getRequest ( $l, $default ), ENT_QUOTES );
}
function getValue($tag, $array, $def = "") {
	return array_key_exists ( $tag, $array ) ? $array [$tag] : $def;
}
function checkEmail($str) {
	return preg_match ( "/^[\.A-z0-9_\-\+]+[@][A-z0-9_\-]+([.][A-z0-9_\-]+)+[A-z]{1,4}$/", $str );
}
/*
 * function send_mail($from, $to, $subject, $body) {
 * $headers = '';
 * $headers .= "From: $from\n";
 * $headers .= "Reply-to: $from\n";
 * $headers .= "Return-Path: $from\n";
 * $headers .= "Message-ID: <" . md5 ( uniqid ( time () ) ) . "@" . $_SERVER ['SERVER_NAME'] . ">\n";
 * $headers .= "MIME-Version: 1.0\n";
 * $headers .= "Date: " . date ( 'r', time () ) . "\n";
 *
 * mail ( $to, $subject, $body, $headers );
 * }
 */
function is_assoc($v) {
	if (! is_array ( $v ))
		return false;
	return count ( array_diff_key ( $v, array_keys ( array_keys ( $v ) ) ) ) > 0;
}
function osort(&$array, $prop, $desc = false) {
	$one = $desc ? - 1 : 1;
	usort ( $array, function ($a, $b) use($prop, $one) {
		if ($a->$prop == $b->$prop)
			return 0;
		return $a->$prop > $b->$prop ? $one : - $one;
	} );
}
/**
 * define( 'JSON_FLAGS', JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK );
 *
 * function jsonResults( $result ) {
 * $flags = is_debugMode() ? JSON_PRETTY_PRINT : 0;
 * //	header("Content-Type: application/json");
 * echo json_encode($result, $flags | JSON_FLAGS );
 * //	exit();
 * }
 */
function charset_decode_utf_8($string) {
	/* Only do the slow convert if there are 8-bit characters */
	/* avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that */
	// if (! ereg("[\200-\237]", $string) and ! ereg("[\241-\377]", $string))
	if (! preg_match ( "/[\200-\237]/", $string ) and ! preg_match ( "/[\241-\377]/", $string ))
		return $string;
		
		// decode three byte unicode characters
	$string = preg_replace ( "/([\340-\357])([\200-\277])([\200-\277])/e", "'&#'.((ord('\\1')-224)*4096 + (ord('\\2')-128)*64 + (ord('\\3')-128)).';'", $string );
	
	// decode two byte unicode characters
	$string = preg_replace ( "/([\300-\337])([\200-\277])/e", "'&#'.((ord('\\1')-192)*64+(ord('\\2')-128)).';'", $string );
	
	return $string;
}
function listFiles($dir, &$list = null) {
	if ($list == null)
		$list = array ();
	foreach ( glob ( $dir . '/*' ) as $file ) {
		if (is_dir ( $file )) {
			$list = listFiles ( $file, $list );
		} else {
			$list [] = $file;
		}
	}
	return $list;
}
function camelCaseToWords($ccWord, $asString = true) {
	$re = '/(?#! splitCamelCase Rev:20140412)
    # Split camelCase "words". Two global alternatives. Either g1of2:
      (?<=[a-z])      # Position is after a lowercase,
      (?=[A-Z])       # and before an uppercase letter.
    | (?<=[A-Z])      # Or g2of2; Position is after uppercase,
      (?=[A-Z][a-z])  # and before upper-then-lower case.
    /x';
	$array = preg_split ( $re, $ccWord );
	return $asString ? implode ( ' ', $array ) : $array;
}

$trackProgress = false;
function trackProgress($flag) {
	global $trackProgress;
	$trackProgress = $flag;
}
function showProgress() {
	global $trackProgress;
	if ($trackProgress) {
		$array = func_get_args ();
		$buf = array ();
		foreach ( $array as $item ) {
			if (is_string ( $item )) {
				$buf [] = $item;
			} else {
				$buf [] = var_export ( $item, true );
			}
		}
		echo getTimestamp () . ' ' . implode ( "\n", $buf ) . "\n";
	}
}