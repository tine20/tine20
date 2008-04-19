<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Account Class
 *
 * @package     Tinebase
 * @subpackage  Account
 */
class Tinebase_Account
{
    /**
     * the name of the accountsbackend
     *
     * @var string
     */
    protected $_backendType = Tinebase_Account_Factory::SQL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        try {
            if(isset(Zend_Registry::get('configFile')->accounts)) {
                $this->_backendType = Zend_Registry::get('configFile')->accounts->get('backend', Tinebase_Account_Factory::SQL);
                $this->_backendType = ucfirst($this->_backendType);
            }
            
        } catch (Zend_Config_Exception $e) {
            // do nothing
            // there is a default set for $this->_backendType
        }
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' accounts backend: ' . $this->_backendType);
        
        $this->_backend = Tinebase_Account_Factory::getBackend($this->_backendType);
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Account
     */
    private static $_instance = NULL;
    
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Account
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Account;
        }
        
        return self::$_instance;
    }
    
    /**
     * get list of accounts with NO internal informations
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Account_Model_Account
     */
    public function getAccounts($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
        $result = $this->_backend->getAccounts($_filter, $_sort, $_dir, $_start, $_limit, 'Tinebase_Account_Model_Account');
        
        return $result;
    }
    
    /**
     * get list of accounts
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Account_Model_FullAccount
     */
    public function getFullAccounts($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
        $result = $this->_backend->getAccounts($_filter, $_sort, $_dir, $_start, $_limit, $_accountClass = 'Tinebase_Account_Model_FullAccount');
        
        return $result;
    }
    
    /**
     * get account by login name
     *
     * @param 	string 		$_loginName
     * @return 	Tinebase_Account_Model_Account full account
     */
    public function getAccountByLoginName($_loginName)
    {     
        $result = $this->_backend->getAccountByLoginName($_loginName, $_accountClass = 'Tinebase_Account_Model_Account');
        
        return $result;
    }

    /**
     * get full account by login name
     *
     * @param 	string 		$_loginName
     * @return 	Tinebase_Account_Model_FullAccount full account
     */
    public function getFullAccountByLoginName($_loginName)
    {
        $result = $this->_backend->getAccountByLoginName($_loginName, $_accountClass = 'Tinebase_Account_Model_FullAccount');
        
        return $result;
    }
    
    /**
     * get account by id
     *
     * @param 	int 		$_accountId
     * @return 	Tinebase_Account_Model_Account account
     */
    public function getAccountById($_accountId)
    {
        $result = $this->_backend->getAccountById($_accountId, 'Tinebase_Account_Model_Account');
        
        return $result;
    }

    /**
     * get full account by id
     *
     * @param 	int 		$_accountId
     * @return 	Tinebase_Account_Model_FullAccount full account
     */
    public function getFullAccountById($_accountId)
    {
        $result = $this->_backend->getAccountById($_accountId, 'Tinebase_Account_Model_FullAccount');
        
        return $result;
    }
    
    /**
     * update account status
     *
     * @param 	int 		$_accountId
     * @param 	string 		$_status
    */
    public function setStatus($_accountId, $_status)
    {
        $result = $this->_backend->setStatus($_accountId, $_status);
        
        return $result;
    }

    /**
     * sets/unsets expiry date (calls backend class with the same name)
     *
     * @param 	int 		$_accountId
     * @param 	Zend_Date 	$_expiryDate
    */
    public function setExpiryDate($_accountId, $_expiryDate)
    {
        $result = $this->_backend->setExpiryDate($_accountId, $_expiryDate);
        
        return $result;
    }

    /**
     * blocks/unblocks the account (calls backend class with the same name)
     *
     * @param 	int $_accountId
     * @param 	Zend_Date 	$_blockedUntilDate
    */
	public function setBlockedDate($_accountId, $_blockedUntilDate)
    {
        $result = $this->_backend->setBlockedDate($_accountId, $_blockedUntilDate);
        
        return $result;
    }
    
    /**
     * set login time for account (with ip address)
     *
     * @param int $_accountId
     * @param string $_ipAddress
     */
    public function setLoginTime($_accountId, $_ipAddress) 
    {
        $result = $this->_backend->setLoginTime($_accountId, $_ipAddress);
        
        return $result;
    }
    
    /**
     * updates an existing account
     *
     * @param Tinebase_Account_Model_FullAccount $_account
     * @return Tinebase_Account_Model_FullAccount
     */
    public function updateAccount(Tinebase_Account_Model_FullAccount $_account)
    {
        $result = $this->_backend->updateAccount($_account);
        
        return $result;
    }

    /**
     * adds a new account
     *
     * @param Tinebase_Account_Model_FullAccount $_account
     * @return Tinebase_Account_Model_FullAccount
     */
    public function addAccount(Tinebase_Account_Model_FullAccount $_account)
    {
        $result = $this->_backend->addAccount($_account);
        
        return $result;
    }
    
    /**
     * delete an account
     *
     * @param int $_accountId
     */
    public function deleteAccount($_accountId)
    {
        $result = $this->_backend->deleteAccount($_accountId);
        
        return $result;
    }

    /**
     * delete multiple accounts
     *
     * @param array $_accountIds
     */
    public function deleteAccounts(array $_accountIds)
    {
        foreach($_accountIds as $accountId) {
            $result = $this->_backend->deleteAccount($accountId);
        }
    }
}