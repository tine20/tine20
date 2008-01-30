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
			SQL_TABLE_PREFIX . 'accounts',
			'account_lid',
			'account_pwd',
			'MD5(?)'
		);
	}
	
	    /**
     * authenticate() - defined by Zend_Auth_Adapter_Interface.
     *
     * @throws Zend_Auth_Adapter_Exception if answering the authentication query is impossible
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $result = parent::authenticate();
        
        if($result->isValid()) {
            // username and password are correct, let's do some additional tests
            
            if($this->_resultRow['account_status'] != 'A') {
                // account is disabled
                $authResult['code'] = Zend_Auth_Result::FAILURE_UNCATEGORIZED;
                $authResult['messages'][] = 'Account disabled.';
                return new Zend_Auth_Result($authResult['code'], $result->getIdentity(), $authResult['messages']);
            }
            
            if($this->_resultRow['account_expires'] !== NULL && $this->_resultRow['account_expires'] < Zend_Date::now()->getTimestamp()) {
                // account is expired
                $authResult['code'] = Zend_Auth_Result::FAILURE_UNCATEGORIZED;
                $authResult['messages'][] = 'Account expired.';
                return new Zend_Auth_Result($authResult['code'], $result->getIdentity(), $authResult['messages']);
            }
        }
        
        return $result;
    }
    
    /**
     * set the password for given account
     *
     * @param int $_accountId
     * @param string $_password
     * @return void
     */
    public function setPassword($_loginName, $_password)
    {
        if(empty($_loginName)) {
            throw new InvalidArgumentException('$_loginName can not be empty');
        }
        
        $accountsTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        
        $accountData['account_pwd'] = md5($_password);
        $accountData['account_lastpwd_change'] = Zend_Date::now()->getTimestamp();
        
        $where = array(
            $accountsTable->getAdapter()->quoteInto('account_lid = ?', $_loginName)
        );
        
        $result = $accountsTable->update($accountData, $where);
        if ($result != 1) {
            throw new Exception('Unable to update password! account not found in authentication backend.');
        }
        
        return $result;
    }
    
}