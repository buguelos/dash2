<?php

namespace GitGis\Whatsapp\Model;

use \PDO as PDO;

/**
 * Overrided default PDO class with config taken from config.php
 *
 */
class DBConnection extends PDO {
	
	/**
	 * Creates PDO connection with config taken from config.php
	 * 
	 */
	public function __construct() {
		global $config;
		parent::__construct("mysql:host=".$config['db']['host'].";dbname=".$config['db']['name']."", $config['db']['user'], $config['db']['pass']);
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * Get instance of DBConnection - static constructor
	 * 
	 * @return \GitGis\Whatsapp\Model\DBConnection
	 */
	public static function getInstance() {
		return new DBConnection();
	}
}