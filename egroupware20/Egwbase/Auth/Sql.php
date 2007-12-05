<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */ 

/**
 * SQL authentication backend
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
	
/*	public function authenticate()
	{
	    $result = parent::authenticate();
	    
	    return $result;
	} */
}
?>