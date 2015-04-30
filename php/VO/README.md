### VOs (Value Object)

In Flex/Actionscript, classes in Actionscript can be mapped to a Remote Object through software such as AMFPHP.  For browser side Javascript AJAX calls, the class is transmitted via JSON.

Create one VO for each database table.  You can also create VOs for complex Left Joins, however updates can be done only on $TABLE_NAME.

For AMFPHP, you'll need to modify gateway.php to parse the namespace correctly, or create a directory structure to match the value in $_explicitType. (/com/myNamespace/VO/)


```php

<?php
class ClassName extends DatabaseTemplate {

	var $_explicitType = "com.myNamespace.VO.ClassName";	// a Flex/Actionscript thing

	public static $TABLE_NAME = 'database_table_name';

	public static $CLASS_NAME = 'ClassName';				// must match class name above

	/**
	 * list all fields that can be updated here as strings
	 * generally you don't include the auto incremented primary key
	 */
	public static $fields = array (
		'field1',
		'field2',
	);
	
	protected static $keys = array (
			'name_of_primary_key' 			// may be a list of fields if that makes up the unique primary key
	);
	
	/**
	 * list in an array of strings the names of any fields you don't want run through html_entities
	 * before being included in the SQL query
	 */
	public static $_encode_exclude = null;

	/**
	 * list each field as a variable
	 */
	 public $name_of_primary_key;
	 public $field1;
	 public $field2;


	public function __construct($data = null) {
		parent::__construct ( $data );
	}
	
	/**
	 * add your specialized methods here, or use the standars ones in DatabaseTemplate
	 */
}

```