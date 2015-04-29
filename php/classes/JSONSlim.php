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