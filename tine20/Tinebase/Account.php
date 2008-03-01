<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
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
    
    public function getGroupMemberships($_accountId)
    {
        $result = $this->_backend->getGroupMemberships($_accountId);
        
        return $result;
    }
    
    public function getGroupMembers($_groupId)
    {
        $result = $this->_backend->getGroupMembers($_groupId);
        
        return $result;
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
    public function getAccounts($_filter = NULL, $_sort = NULL, $_dir = NULL, $_start = NULL, $_limit = NULL)
    {
        $result = $this->_backend->getAccounts($_filter, $_sort, $_dir, $_start, $_limit, 'Tinebase_Account_Model_Account');
        
        return $result;
    }
    
    public function getFullAccounts($_filter = NULL, $_sort = NULL, $_dir = NULL, $_start = NULL, $_limit = NULL)
    {
        $result = $this->_backend->getAccounts($_filter, $_sort, $_dir, $_start, $_limit, $_accountClass = 'Tinebase_Account_Model_FullAccount');
        
        return $result;
    }
    
    public function getAccountByLoginName($_loginName)
    {     
        $result = $this->_backend->getAccountByLoginName($_loginName, $_accountClass = 'Tinebase_Account_Model_Account');
        
        return $result;
    }

    public function getFullAccountByLoginName($_loginName)
    {
        $result = $this->_backend->getAccountByLoginName($_loginName, $_accountClass = 'Tinebase_Account_Model_FullAccount');
        
        return $result;
    }
    
    public function getAccountById($_accountId)
    {
        $result = $this->_backend->getAccountById($_accountId, 'Tinebase_Account_Model_Account');
        
        return $result;
    }

    public function getFullAccountById($_accountId)
    {
        $result = $this->_backend->getAccountById($_accountId, 'Tinebase_Account_Model_FullAccount');
        
        return $result;
    }
    
    public function setStatus($_accountId, $_status)
    {
        $result = $this->_backend->setStatus($_accountId, $_status);
        
        return $result;
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $_accountId
     * @param unknown_type $_password
     * @deprecated moved to authentication class
     * @return unknown
     */
    private function setPassword($_accountId, $_password)
    {
        $result = $this->_backend->setPassword($_accountId, $_password);
      
        return $result;
    }
    
    public function setLoginTime($_accountId, $_ipAddress) 
    {
        $result = $this->_backend->setLoginTime($_accountId, $_ipAddress);
        
        return $result;
    }
    
    /**
     * add or updates an account
     *
     * @param Tinebase_Account_Model_FullAccount $_account
     * @return Tinebase_Account_Model_FullAccount
     */
    public function saveAccount(Tinebase_Account_Model_FullAccount $_account)
    {
        $result = $this->_backend->saveAccount($_account);
        
        return $result;
    }

    public function deleteAccount($_accountId)
    {
        $result = $this->_backend->deleteAccount($_accountId);
        
        return $result;
    }

    public function deleteAccounts(array $_accountIds)
    {
        foreach($_accountIds as $accountId) {
            $result = $this->_backend->deleteAccount($accountId);
        }
    }
}