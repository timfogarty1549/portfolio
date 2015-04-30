<?php
abstract class DatabaseTemplate {
	
	/**
	 * each child must declare
	 * static protected $TABLE_NAME = '';
	 * static protected $CLASS_NAME = '';
	 * static public $fields = array();
	 * static protected $keys = array();
	 * 
	 * note the use of LATE STATIC BINDING   http://php.net/language.oop5.late-static-bindings
	 */
	protected $_key_pair;
	public $_selected;
	
	/**
	 * 
	 * @param string $data Optional associative array used to populate fields in the class
	 */
	public function __construct($data = null) {
		if ($data != null) {
			foreach ( static::$fields as $field ) {
				if (array_key_exists ( $field, $data )) {
					$this->$field = $data [$field];
				}
			}
		}
	}
	
	/**
	 * Create an object of the child class from a JSON string
	 * 
	 * @param string $string        
	 * 	
	 * @return object of child class
	 */
	static public function fromJSON( $jsonString ) {
		$obj = new static::$CLASS_NAME ();
		$obj->setJSON ( json_decode ( $jsonString, true ) );
		return $obj;
	}
	
	/**
	 *
	 * @param assoc array $data
	 * @param object $obj        	
	 */
	private function setJSON($data, $obj = null) {
		if ($obj == null)
			$obj = $this;
		foreach ( $data as $key => $value ) {
			if (is_array ( $value )) {
				$sub = new stdClass ();
				$this->setJSON ( $value, $sub );
				$value = $sub;
			}
			$obj->{$key} = $value;
		}
	}
	
	/**
	 * 
	 *
	 * @param string|regular array|associativeArray $values
	 *        	string - single value to be combined with single key
	 *        	regular array - keys to be combined with keys
	 *        	associative array - keys to use as is
	 *        
	 * @return NULL multitype:
	 */
	static public function buildKeyPair($values) {
		if ($values == null)
			return null;
		if (is_string ( $values ))
			$values = array (
					$values 
			);
		if (is_array ( $values ) && count ( $values ) == 1 && is_array ( $values [0] )) { // is this a hack or what I did at deluxe?
			$values = $values [0];
		}
		if (is_assoc ( $values ))
			$key_pair = $values;
		else
			$key_pair = array_combine ( static::$keys, $values );
		return $key_pair;
	}
	
	/**
	 * 
	 * @return unknown|Ambigous <multitype:, multitype:NULL >
	 */
	static public function fetchRecord() { // $key0, $key1, ...
		$args = func_get_args ();
		if ($args == null || count ( $args ) == 0) {
			return new static::$CLASS_NAME ();
		} else if (count ( $args ) == 1 && is_string ( $args [0] ) && strtoupper ( substr ( trim ( $args [0] ), 0, 6 ) ) == 'SELECT') {
			return MySql::fetchObject ( $args [0], static::$CLASS_NAME );
		} else {
			$key_pair = self::buildKeyPair ( $args );
			$obj = MySql::fetchRecord ( static::$TABLE_NAME, $key_pair, static::$CLASS_NAME );
			if ($obj)
				$obj->_key_pair = $key_pair;
			return $obj;
		}
	}
	/**
	 * 
	 * @return multitype:boolean number
	 */
	public function createRec() {
		return static::createRecord ( ( array ) $this, static::$_encode_exclude );
	}
	
	/**
	 * 
	 * @param associativeArray $data
	 * @param array $exclude
	 * @param boolean $ignore
	 * @return multitype:boolean number
	 */
	static public function createRecord($data, $exclude = null, $ignore = false) {
		return MySql::newRecord ( static::$TABLE_NAME, static::$fields, $data, $exclude, $ignore ? static::$keys [0] : null );
	}
	
	/**
	 * 
	 * @return Ambigous <string, boolean>
	 */
	public function updateRec() {
		return MySql::updateRecord ( static::$TABLE_NAME, static::$fields, static::$keys, $this, static::$_encode_exclude );
	}
	
	/**
	 * 
	 * @param array $data
	 * @return Ambigous <string, boolean>
	 */
	static public function updateRecord($data = null) {
		if ($data == null)
			$data = static::$this;
		return MySql::updateRecord ( static::$TABLE_NAME, static::$fields, static::$keys, $data, static::$_encode_exclude );
	}
	
	/**
	 * 
	 * @param string|array|associativeArray $key_values
	 * @param array $data
	 * @return Ambigous <string, boolean>
	 */
	static public function updateFields($key_values, $data) {
		$key_pair = self::buildKeyPair ( $key_values );
		return MySql::updateRecord ( static::$TABLE_NAME, static::$fields, $key_pair, $data );
	}
	
	/**
	 * 
	 * @param unknown $key_values
	 * @param unknown $field
	 * @param unknown $value
	 * @return boolean
	 */
	static public function updateField($key_values, $field, $value) {
		$key_pair = self::buildKeyPair ( $key_values );
		return MySql::updateField ( static::$TABLE_NAME, $key_pair, $field, $value );
	}
	
	/**
	 * 
	 * @return boolean|string
	 */
	static public function deleteRecord() {
		$values = func_get_args ();
		if (count ( $values ) == count ( static::$keys )) {
			if (is_array ( $values [0] ))
				$key_pair = $values;
			else
				$key_pair = array_combine ( static::$keys, $values );
			return MySql::deleteRecord ( static::$TABLE_NAME, $key_pair );
		} else {
			return "Key mismatch";
		}
	}
	
	/**
	 * 
	 * @param string $keys
	 * @return Ambigous <boolean, unknown>
	 */
	static public function fetchCount($keys = null) {
		$key_pair = self::buildKeyPair ( $keys );
		return MySql::fetchCount ( static::$TABLE_NAME, $key_pair );
	}
	
	/**
	 * 
	 * @param string $keys
	 * @return boolean
	 */
	static public function recordExists($keys = null) {
		$key_pair = self::buildKeyPair ( $keys );
		return MySql::recordExists ( static::$TABLE_NAME, $key_pair );
	}
	
	/**
	 *
	 * @var array $join
	 * @var string, array key or key pair
	 * @return Ambigous <boolean, multitype:NULL >
	 */
	static public function fetchListJoin($join, $keys = null, $fields = "*", $offset = 0, $limit = MySql::LIMIT) {
		$key_pair = self::buildKeyPair ( $keys );
		return MySql::fetchListJoin ( static::$CLASS_NAME, static::$TABLE_NAME, $join, $key_pair, $fields, $offset, $limit );
	}
	
	/**
	 *
	 * @param string $where
	 *        	| array $key_pair | string $query
	 * @param string $fields
	 *        	= '*'
	 * @param integer $offset
	 *        	= 0
	 * @param integer $limit
	 *        	= MySql::LIMIT
	 * @return multitype:NULL
	 */
	static public function fetchList($keys = null, $fields = "*", $offset = 0, $limit = MySql::LIMIT) {
		if (is_string ( $keys ) && strtoupper ( substr ( trim ( $keys ), 0, 6 ) ) == 'SELECT') {
			return MySql::fetchList ( null, $keys, static::$CLASS_NAME );
		} else {
			$key_pair = self::buildKeyPair ( $keys );
			return MySql::fetchList ( static::$TABLE_NAME, $key_pair, static::$CLASS_NAME, $fields, $offset, $limit );
		}
	}
	
	/**
	 *
	 * @param string|array $args        	
	 * @param
	 *        	array of objects $selectedItems
	 * @param string $key0        	
	 * @param string $key1
	 *        	= null
	 * @param string $fields
	 *        	= *
	 * @param integer $offset
	 *        	= 0
	 * @param integer $limit
	 *        	= MySql::LIMIT
	 * @return Ambigous <multitype:NULL, multitype:NULL >
	 */
	static public function fetchListSelected($args, $selectedItems, $key0, $key1 = null, $fields = "*", $offset = 0, $limit = MySql::LIMIT) {
		if ($key1 == null)
			$key1 = $key0;
		$items = self::fetchList ( $args, $fields, $offset, $limit );
		foreach ( $items as &$item ) {
			$item->_selected = false;
		}
		if ($selectedItems !== false && is_array ( $selectedItems ) && count ( $selectedItems ) > 0) {
			foreach ( $selectedItems as $selItem ) {
				if (is_object ( $selItem )) {
					foreach ( $items as &$item ) {
						if ($selItem->$key1 == $item->$key0) {
							$item->_selected = true;
							break;
						}
					}
				} else {
					echo "DatabaseTemplate fetchListSelected - invalid type " . print_r ( $selItem, true ) . "<br/>";
				}
			}
		}
		return $items;
	}
}