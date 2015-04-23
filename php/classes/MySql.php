<?php
// require_once( 'settings.php' );
define ( 'MYSQL_DATETIME', "Y-m-d H:i:s" );
class MySql {
	const LIMIT = 9999;
	const SINGLE_FIELD = '__single_field__';
	private static $mysqli;
	public static $logQuery = false;
	static public function setLogging($flag) {
		self::$logQuery = $flag;
	}
	public function __construct() {
		self::$mysqli = new mysqli ( MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB );
		if (self::$mysqli->connect_errno) {
			printf ( "Connect failed: %s\n", self::$mysqli->connect_error );
			exit ();
		}
	}
	static public function getInstance() {
		if (self::$mysqli == null)
			new MySql ();
		return self::$mysqli;
	}
	static public function errorMsg() {
		return self::getInstance ()->error;
	}
	static public function selectDB($dbName) {
		return self::getInstance ()->select_db ( $dbName );
	}
	static public function resetDB() {
		self::selectDB ( OBJECT_DB );
	}
	static public function close() {
		if (self::$mysqli != null) {
			self::$mysqli->close ();
			self::$mysqli = null;
		}
	}
	static public function datetime($val) {
		if (! empty ( $val )) {
			if (strlen ( $val ) == 4)
				return $val . '-00-00';
			$date = new DateTime ( $val );
			return $date->format ( MYSQL_DATETIME );
		} else {
			return '';
		}
	}
	static public function doQuery($query) {
		if (self::$logQuery)
			echo $query . "\n";
		$result = self::getInstance ()->query ( $query );
		if ($result === false)
			logError ( self::$mysqli->error );
		return $result;
	}
	static public function queryList($query, $full = false) {
		$list = array ();
		$result = self::doQuery ( $query );
		if ($result->num_rows > 0) {
			while ( $row = $result->fetch_row () ) {
				$list [] = $full ? $row : $row [0];
			}
		}
		$result->free ();
		return $list;
	}
	static public function queryIdKeyList($query) {
		$list = array ();
		$result = self::doQuery ( $query );
		if ($result->num_rows > 0) {
			while ( $row = $result->fetch_row () ) {
				if (count ( $row ) == 2) {
					$list [$row [0]] = $row [1];
				} else {
					$id = $row [0];
					unset ( $row [0] );
					$list [$id] = array_values ( $row );
				}
			}
		}
		$result->free ();
		return $list;
	}
	static public function querySingleRow($query) {
		if (is_array ( $query )) { // append multiple queries into a single row
			$row = array ();
			foreach ( $query as $q ) {
				$result = self::doQuery ( $q );
				if ($result->num_rows > 0)
					$row += $result->fetch_assoc (); // don't we really want to merge arrays ?
			}
		} else {
			$result = self::doQuery ( $query );
			if ($result->num_rows > 0)
				return false;
			$row = $result->fetch_assoc ();
		}
		$result->free ();
		return $row;
	}
	static public function querySingleField($query) {
		$result = self::doQuery ( $query );
		if ($result === false || $result->num_rows == 0)
			return false;
		$row = $result->fetch_row ();
		$result->free ();
		return $row [0];
	}
	static public function fetchAssoc($query) {
		$result = self::doQuery ( $query );
		if ($result === false || $result->num_rows == 0)
			return false;
		$list = array ();
		while ( $row = $result->fetch_assoc () ) {
			$list [] = $row;
		}
		$result->free ();
		return $list;
	}
	static public function fetchSingleFieldList($query) {
		$result = self::doQuery ( $query );
		if ($result === false || $result->num_rows == 0)
			return false;
		$list = array ();
		while ( $row = $result->fetch_row () ) {
			$list [] = $row [0];
		}
		$result->free ();
		return $list;
	}
	static public function fetchObject($query, $class, $singleOK = true) {
		$result = self::doQuery ( $query );
		if ($result === false || $result->num_rows == 0) {
			$vo = self::getInstance ()->error;
			if (empty ( $vo ))
				$vo = array ();
		} else if ($result->num_rows == 1 && $singleOK) {
			$vo = $result->fetch_object ( $class );
		} else {
			$vo = array ();
			for($ii = 0; $ii < $result->num_rows; $ii ++) {
				$vo [] = $result->fetch_object ( $class );
			}
		}
		if ($result !== false)
			$result->free ();
		return $vo;
	}
	static public function fetchPair($query) {
		$result = self::doQuery ( $query );
		if ($result->num_rows == 0)
			return false;
		$row = $result->fetch_row ();
		$result->free ();
		return array (
				$row [0] => $row [1] 
		);
	}
	static public function fetchPairList($query, $array = null) {
		$result = self::doQuery ( $query );
		if ($result === false || $result->num_rows == 0) {
			$array = self::getInstance ()->error;
			if (empty ( $array ))
				$array = array ();
		} else {
			if ($array == null)
				$array = array ();
			for($ii = 0; $ii < $result->num_rows; $ii ++) {
				$row = $result->fetch_row ();
				$array [$row [0]] = $row [1];
			}
		}
		if ($result !== false)
			$result->free ();
		return $array;
	}
	static public function fetchPairListOfObjects($query, $field, $array = null) {
		$result = self::doQuery ( $query );
		if ($result === false || $result->num_rows == 0) {
			$array = self::getInstance ()->error;
			if (empty ( $array ))
				$array = array ();
		} else {
			if ($array == null)
				$array = array ();
			for($ii = 0; $ii < $result->num_rows; $ii ++) {
				$row = $result->fetch_row ();
				if (! array_key_exists ( $row [0], $array ))
					$array [$row [0]] = new stdClass ();
				$array [$row [0]]->{$field} = $row [1];
			}
		}
		if ($result !== false)
			$result->free ();
		return $array;
	}
	static public function fetchPairListToVO($query, $vo = null) {
		$result = self::doQuery ( $query );
		if ($result === false || $result->num_rows == 0) {
			$vo = self::getInstance ()->error;
			if (empty ( $vo ))
				$vo = array ();
		} else {
			if ($vo == null)
				$vo = new stdClass ();
			for($ii = 0; $ii < $result->num_rows; $ii ++) {
				$row = $result->fetch_row ();
				$vo->{$row [0]} = $row [1];
			}
		}
		if ($result !== false)
			$result->free ();
		return $vo;
	}
	static public function simpleQuery($query) { // remember only executes query, doesnt fetch
	                                               // $result = self::doQuery( $query );
	                                               // if( $result === false ) return self::getInstance()->error;
	                                               // $result->free();
	                                               // return false;
		return self::doQuery ( $query ) === true ? false : self::getInstance ()->error;
	}
	static public function complexQuery($complex) {
		$list = explode ( ';', $complex );
		foreach ( $list as $query ) {
			if (! empty ( $query )) {
				if (! self::doQuery ( $query ))
					return self::getInstance ()->error;
			}
		}
		return false;
	}
	static public function htmlentities($string) {
		return htmlentities ( $string, ENT_QUOTES, "ISO-8859-15" );
	}
	
	/**
	 *
	 * @param array $keys        	
	 * @param array $data=null        	
	 * @return string WHERE x='a' AND y='b' ...
	 *        
	 *         if $keys is an associative array of $key=>$value then the where clause becomes
	 *         WHERE $key = '$value' ...
	 *         if $keys is not an associative array and $data is present, then the where clause becomes
	 *         WHERE $key = '$data[$key]
	 *         for lookups, the key name is extracted out of any function. for example
	 *         $keys = array( "MD5(user_name)" ), and $data['user_name'] = 'tim then where clause becomes
	 *         WHERE MD5(user_name) = 'tim'
	 */
	static private function buildWhereClause($keys, $data = null) {
		if (is_string ( $keys )) {
			return "WHERE " . $keys;
		} else if (is_array ( $keys )) {
			$where = array ();
			foreach ( $keys as $key => $value ) {
				if (is_string ( $key )) {
					$where [] = "$key = '$value'";
				} else {
					if (preg_match ( '/(?<=\()(.*?)(?=\))/', $value, $match )) {
						$tag = $match [0];
					} else {
						$tag = $value;
					}
					$where [] = "$value = '" . $data [trim ( $tag )] . "'";
				}
			}
			return "WHERE " . implode ( ' AND ', $where );
		} else {
			return "";
		}
	}
	static private function buildQuerySet($key, $fields, $data, $noHE = array()) {
		if (is_object ( $data ))
			$data = ( array ) $data;
		if ($fields == null)
			$fields = array_keys ( $data );
		$set = array ();
		foreach ( $fields as $tag ) {
			if (array_key_exists ( $tag, $data ) && $tag != $key) {
				$once = true;
				$val = $noHE != null && array_search ( $tag, $noHE ) !== false ? $data [$tag] : self::htmlentities ( $data [$tag] );
				// if( $noHE != null && array_search($tag,$noHE) !== false ) echo "....$val";
				$set [] = "$tag='$val'";
			}
		}
		return count ( $set ) > 0 ? "SET " . implode ( ', ', $set ) : "";
	}
	static public function updateField($table, $key, $field, $value) {
		$where = self::buildWhereClause ( $key );
		$query = "UPDATE $table SET $field = '$value' $where LIMIT 1";
		return self::simpleQuery ( $query );
	}
	static public function updateRecord($table, $fields, $key, $data, $noHE = null) {
		$where = self::buildWhereClause ( $key );
		$setString = self::buildQuerySet ( $key, $fields, $data, $noHE );
		if (empty ( $setString ))
			return "Nothing to update";
		$query = "UPDATE $table $setString $where LIMIT 1";
		return self::simpleQuery ( $query );
	}
	static public function fetchRecord($table, $key, $class) {
		$where = self::buildWhereClause ( $key );
		return self::fetchObject ( "SELECT * FROM $table $where", $class );
	}
	static public function newRecord($table, $fields, $data, $noHE = array(), $ignore = null) {
		$query = "INSERT INTO $table " . self::buildQuerySet ( null, $fields, $data, $noHE );
		if (! empty ( $ignore ))
			$query .= " ON DUPLICATE KEY UPDATE $ignore = $ignore ";
		$msg = self::simpleQuery ( $query );
		$id = empty ( $msg ) ? self::getInstance ()->insert_id : 0;
		return array (
				$msg,
				$id 
		);
	}
	static public function replaceRecord($table, $fields, $data, $noHE = array()) {
		$setString = self::buildQuerySet ( null, $fields, $data, $noHE );
		if (empty ( $setString ))
			return "Nothing to update";
		$query = "REPLACE INTO $table $setString";
		return self::simpleQuery ( $query );
	}
	static public function deleteRecord($table, $key) {
		$where = self::buildWhereClause ( $key );
		$query = "DELETE FROM $table $where LIMIT 1";
		return self::simpleQuery ( $query );
	}
	
	/**
	 *
	 * @param string $class
	 *        	object cast as
	 * @param string $table
	 *        	SELECT * FROM $table
	 * @param array $join
	 *        	LEFT JOIN $table USING ($field)
	 *        	$join[$table] = $field
	 *        	LEFT JOIN $table USING ($field1, $field2, ...)
	 *        	$join[$table] = array( $field1, $field2, ... )
	 *        	LEFT JOIN $table ON ( $field1a = $field1b AND $field2a = $field2b ... )
	 *        	$join[$table] = array( $field1a=>$field1b, $field2a=>$field2b, ... )
	 * @param array $key
	 *        	WHERE $field1 = $value1 AND $field2 = $value2
	 *        	$key = array( $field1=>$value1, $field2=>$value2, ... )
	 */
	static public function fetchListJoin($class, $table, $join, $key = null, $fields = "*", $offset = 0, $limit = self::LIMIT) {
		$query = "SELECT $fields FROM $table ";
		foreach ( $join as $joinTable => $joinOn ) {
			if (! is_array ( $joinOn )) {
				$using = "USING ($joinOn)";
			} else if (key ( $joinOn ) == "0") {
				$using = "USING (" . implode ( ',', $joinOn ) . ")";
			} else {
				$list = array ();
				foreach ( $joinOn as $j0 => $j1 ) {
					$list [] = "$j0=$j1";
				}
				$using = "ON ( " . implode ( " AND ", $list ) . " )";
			}
			$query .= "LEFT JOIN $joinTable " . $using;
		}
		$query .= self::buildWhereClause ( $key );
		$query .= " LIMIT $offset, $limit";
		return $class == null ? self::fetchAssoc ( $query ) : self::fetchObject ( $query, $class );
	}
	
	/**
	 *
	 * @param string|null $table        	
	 * @param string $keys|$query        	
	 * @param string $class        	
	 * @param string $fields
	 *        	= *
	 * @param number $offset
	 *        	= 0
	 * @param int $limit
	 *        	= LIMIT
	 * @return Ambigous <boolean, multitype:array , multitype:NULL >
	 *        
	 *         if table is null then $key is treated as query, otherwise query is built from table, keys and field
	 *         resutls is array where each item in array is cast to class
	 *        
	 */
	static public function fetchList($table, $keys, $class = null, $fields = "*", $offset = 0, $limit = self::LIMIT) {
		if ($table == null) {
			$query = $keys;
		} else {
			$where = self::buildWhereClause ( $keys );
			$query = "SELECT {$fields} FROM $table $where LIMIT $offset, $limit";
		}
		if ($class == self::SINGLE_FIELD) {
			$list = self::fetchSingleFieldList ( $query );
		} else if ($class == null) {
			$list = self::fetchAssoc ( $query );
		} else {
			$list = self::fetchObject ( $query, $class );
		}
		return is_array ( $list ) ? $list : [ 
				$list 
		];
	}
	static public function fetchCount($table, $keys) {
		$where = self::buildWhereClause ( $keys );
		$query = "SELECT count(*) FROM $table $where";
		return self::querySingleField ( $query );
	}
	static public function recordExists($table, $keys) {
		$where = self::buildWhereClause ( $keys );
		$query = "select exists(select * from $table $where)";
		return self::querySingleField ( $query ) == 1;
	}
}