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
    public function getFullAccounts($_filter = NULL, $_sort = NULL, $_dir = NULL, $_start = NULL, $_limit = NULL)
    {
        $result = $this->_backend->getAccounts($_filter, $_sort, $_dir, $_start, $_limit, $_accountClass = 'Tinebase_Account_Model_FullAccount');
        
        return $result;
    }
    
    /**
     * get account by id
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
     * get full account by id
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
     * @return 	Tinebase_Account_Model_Account full account
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
     * blocks the account (calls backend class with the same name)
     *
     * @param 	int $_accountId
     * @todo 	make it work!
    */
	public function setBlocked($_accountId /* more params ? */)
    {
        /*$result = $this->_backend->setBlocked($_accountId);
        
        return $result;*/
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
    
    /**
     * converts a int, string or Tinebase_Account_Model_Account to an accountid
     *
     * @param int|string|Tinebase_Account_Model_Account $_accountId the accountid to convert
     * @return int
     */
    static public function convertAccountIdToInt($_accountId)
    {
        if($_accountId instanceof Tinebase_Account_Model_Account) {
            if(empty($_accountId->accountId)) {
                throw new Exception('no accountId set');
            }
            $accountId = (int) $_accountId->accountId;
        } else {
            $accountId = (int) $_accountId;
        }
        
        if($accountId === 0) {
            throw new Exception('accountId can not be 0');
        }
        
        return $accountId;
    }
}