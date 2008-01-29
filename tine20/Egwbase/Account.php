<?php
/**
 * Tine 2.0
 * 
 * @package     Egwbase
 * @subpackage  Accounts
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Account Class
 *
 * @package     Egwbase
 * @subpackage  Accounts
 */
class Egwbase_Account
{
    /**
     * the name of the accountsbackend
     *
     * @var string
     */
    protected $_backendType = Egwbase_Account_Factory::SQL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend = Egwbase_Account_Factory::getBackend($this->_backendType);
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Egwbase_Account
     */
    private static $_instance = NULL;
    
    
    /**
     * the singleton pattern
     *
     * @return Egwbase_Account
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Egwbase_Account;
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
    
    public function getAccounts($_filter, $_sort, $_dir, $_start = NULL, $_limit = NULL)
    {
        $result = $this->_backend->getAccounts($_filter, $_sort, $_dir, $_start, $_limit, $_accountClass = 'Egwbase_Account_Model_Account');
        
        return $result;
    }
    
    public function getFullAccounts($_filter, $_sort, $_dir, $_start = NULL, $_limit = NULL)
    {
        $result = $this->_backend->getAccounts($_filter, $_sort, $_dir, $_start, $_limit, $_accountClass = 'Egwbase_Account_Model_FullAccount');
        
        return $result;
    }
    
    public function getAccountByLoginName($_loginName)
    {     
        $result = $this->_backend->getAccountByLoginName($_loginName, $_accountClass = 'Egwbase_Account_Model_Account');
        
        return $result;
    }

    public function getFullAccountByLoginName($_loginName)
    {
        $result = $this->_backend->getAccountByLoginName($_loginName, $_accountClass = 'Egwbase_Account_Model_FullAccount');
        
        return $result;
    }
    
    public function getAccountById($_accountId)
    {
        $result = $this->_backend->getAccountById($_accountId, 'Egwbase_Account_Model_Account');
        
        return $result;
    }

    public function getFullAccountById($_accountId)
    {
        $result = $this->_backend->getAccountById($_accountId, 'Egwbase_Account_Model_FullAccount');
        
        return $result;
    }
    
    public function setStatus($_accountId, $_status)
    {
        $result = $this->_backend->setStatus($_accountId, $_status);
        
        return $result;
    }
    
    public function setPassword($_accountId, $_password)
    {
        $result = $this->_backend->setPassword($_accountId, $_password);
      
        return $result;
    }
    
    public function setLoginTime($_accountId, $_ipAddress) 
    {
        $result = $this->_backend->setLoginTime($_accountId, $_ipAddress);
        
        return $result;
    }
    
    public function saveAccount(Egwbase_Account_Model_FullAccount $_account)
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