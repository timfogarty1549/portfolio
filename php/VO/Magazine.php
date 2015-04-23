<?php
class Magazine extends DatabaseTemplate {
	var $_explicitType = "com.musclememory.VO.Magazine";
	public static $TABLE_NAME = 'magazines';
	public static $CLASS_NAME = 'Magazine';
	
	// mag_code sometimes varies within a magazine, such as when there are different covers for the same issue,
	// so is included as part of magazine_issues
	public static $fields = array (
			'mag_name' 
	);
	protected static $keys = array (
			'mag_id' 
	);
	public static $_encode_exclude = null;
	public $mag_id;
	public $mag_name;
	public function __construct($data = null) {
		parent::__construct ( $data );
	}
	static public function fetchSummary() {
		$query = <<<Q1
select 
    mag_id,
    count(*) as count,
	count(code) as scanned,
    mag_name,
    MIN(year) as year0,
    MAX(year) as year1
from
    magazine_issues
        left join
    magazines USING (mag_id)
group by mag_id
Q1;
		return self::fetchList ( $query );
	}
	static public function fetchIdByName($name) {
		$rec = self::fetchRecord ( array (
				'mag_name' => $name 
		) );
		return is_string ( $rec ) || $rec == null ? 0 : $rec->mag_id;
	}
}