<?php
/**
 * Tine 2.0
 * 
 * @package     Egwbase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */ 

/**
 * SQL authentication backend
 * 
 * @package     Egwbase
 * @subpackage  Auth 
 */
class Egwbase_Auth_Sql extends Zend_Auth_Adapter_DbTable
{
	public function __construct()
	{
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