<?php
/**
 * SQL authentication backend
 * 
 * @author Lars Kneschke <l.kneschke@metaways.de>
 * @package Egwbase
 *
 */
class Egwbase_Auth_Sql extends Zend_Auth_Adapter_DbTable
{
	public function __construct()
	{
		#$dbOptions = Zend_Registry::get('dbConfig');
		
		#$db = Zend_Db::factory('PDO_MYSQL', $dbOptions->toArray());
		#error_log(print_r(Zend_Db_Table_Abstract::getDefaultAdapter($db), true));
		#$db = Zend_Db_Table_Abstract::getDefaultAdapter($db);
		$db = Zend_Registry::get('dbAdapter');
		parent::__construct(
			$db,
			'egw_accounts',
			'account_lid',
			'account_pwd'
		);
		
		$this->setCredentialTreatment('MD5(?)');
	}
}
?>