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
 * sql implementation of the SQL accounts interface
 * 
 * @package     Tinebase
 * @subpackage  Account
 */
class Tinebase_Account_Sql implements Tinebase_Account_Interface
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Account_Sql
     */
    private static $_instance = NULL;
    
    protected $rowNameMapping = array(
        'accountId'             => 'id',
        'accountDisplayName'    => 'n_fileas',
        'accountFullName'       => 'n_fn',
        'accountFirstName'      => 'n_given',
        'accountLastName'       => 'n_family',
        'accountLoginName'      => 'login_name',
        'accountLastLogin'      => 'last_login',
        'accountLastLoginfrom'  => 'last_login_from',
        'accountLastPasswordChange' => 'last_password_change',
        'accountStatus'         => 'status',
        'accountExpires'        => 'expires_at',
        'accountPrimaryGroup'   => 'primary_group_id',
        'accountEmailAddress'   => 'email'
    );
    
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Account_Sql
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Account_Sql;
        }
        
        return self::$_instance;
    }
    
    /**
     * get list of accounts
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @param string $_accountClass the type of subclass for the Tinebase_Record_RecordSet to return
     * @return Tinebase_Record_RecordSet with record class Tinebase_Account_Model_Account
     */
    public function getAccounts($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL, $_accountClass = 'Tinebase_Account_Model_Account')
    {        
        $select = $this->_getAccountSelectObject()
            ->limit($_limit, $_start);
            
        if($_sort !== NULL) {
            $select->order($this->rowNameMapping[$_sort] . ' ' . $_dir);
        }

        if($_filter !== NULL) {
            $select->where('(n_family LIKE ? OR n_given LIKE ? OR login_name LIKE ?)', '%' . $_filter . '%');
        }
        // return only active accounts, when searching for simple accounts
        if($_accountClass == 'Tinebase_Account_Model_Account') {
            $select->where('status = ?', 'enabled');
        }
        //error_log("getAccounts:: " . $select->__toString());

        $stmt = $select->query();

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        $result = new Tinebase_Record_RecordSet($_accountClass, $rows);
        
        return $result;
    }
    
    /**
     * get account by login name
     *
     * @param string $_loginName the loginname of the account
     * @return Tinebase_Account_Model_Account the account object
     *
     * @throws Tinebase_Record_Exception_NotDefined when row is empty
     */
    public function getAccountByLoginName($_loginName, $_accountClass = 'Tinebase_Account_Model_Account')
    {
        $select = $this->_getAccountSelectObject()
            ->where(SQL_TABLE_PREFIX . 'accounts.login_name = ?', $_loginName);

        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
	   	// throw exception if data is empty (if the row is no array, the setFromArray function throws a fatal error 
	   	// because of the wrong type that is not catched by the block below)
    	if ( $row === false ) {
             throw new Exception('account not found');
    	}        

        try {
            $account = new $_accountClass();
            $account->setFromArray($row);
        } catch (Exception $e) {
        	$validation_errors = $account->getValidationErrors();
            Zend_Registry::get('logger')->debug( 'Tinebase_Account_Sql::getAccountByLoginName: ' . $e->getMessage() . "\n" .
                "Tinebase_Account_Model_Account::validation_errors: \n" .
                print_r($validation_errors,true));
            throw ($e);
        }
        
        return $account;
    }
    
    /**
     * get account by accountId
     *
     * @param int $_accountId the account id
     * @return Tinebase_Account_Model_Account the account object
     */
    public function getAccountById($_accountId, $_accountClass = 'Tinebase_Account_Model_Account')
    {
        $accountId = Tinebase_Account_Model_Account::convertAccountIdToIntv($_accountId);
        
        $select = $this->_getAccountSelectObject()
            ->where(SQL_TABLE_PREFIX . 'accounts.id = ?', $accountId);

        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        if($row === false) {
            throw new Exception('account not found');
        }

        try {
            $account = new $_accountClass();
            $account->setFromArray($row);
        } catch (Exception $e) {
            $validation_errors = $account->getValidationErrors();
            Zend_Registry::get('logger')->debug( 'Tinebase_Account_Sql::_getAccountFromSQL: ' . $e->getMessage() . "\n" .
                "Tinebase_Account_Model_Account::validation_errors: \n" .
                print_r($validation_errors,true));
            throw ($e);
        }
        
        return $account;
    }
    
    protected function _getAccountSelectObject()
    {
        $db = Zend_Registry::get('dbAdapter');
        
        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'accounts', 
                array(
                    'accountId'             => $this->rowNameMapping['accountId'],
                    'accountLoginName'      => $this->rowNameMapping['accountLoginName'],
                    'accountLastLogin'      => $this->rowNameMapping['accountLastLogin'],
                    'accountLastLoginfrom'  => $this->rowNameMapping['accountLastLoginfrom'],
                    'accountLastPasswordChange' => $this->rowNameMapping['accountLastPasswordChange'],
                    'accountStatus'         => $this->rowNameMapping['accountStatus'],
                    'accountExpires'        => $this->rowNameMapping['accountExpires'],
                    'accountPrimaryGroup'   => $this->rowNameMapping['accountPrimaryGroup']
                )
            )
            ->join(
                SQL_TABLE_PREFIX . 'addressbook',
                SQL_TABLE_PREFIX . 'accounts.id = ' . SQL_TABLE_PREFIX . 'addressbook.account_id',
                array(
                    'accountDisplayName'    => $this->rowNameMapping['accountDisplayName'],
                    'accountFullName'       => $this->rowNameMapping['accountFullName'],
                    'accountFirstName'      => $this->rowNameMapping['accountFirstName'],
                    'accountLastName'       => $this->rowNameMapping['accountLastName'],
                    'accountEmailAddress'   => $this->rowNameMapping['accountEmailAddress']
                )
            );
                
        return $select;
    }
    
    /**
     * set the status of the account
     *
     * @param int $_accountId
     * @param unknown_type $_status
     * @return unknown
     */
    public function setStatus($_accountId, $_status)
    {
        $accountId = Tinebase_Account_Model_Account::convertAccountIdToIntv($_accountId);
        
        switch($_status) {
            case 'enabled':
            case 'disabled':
                $accountData['status'] = $_status;
                break;
                
            case 'expired':
                $accountData['expires_at'] = Zend_Date::getTimestamp();
                break;
            
            default:
                throw new InvalidArgumentException('$_status can be only enabled, disabled or expired');
                break;
        }
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));

        $where = array(
            $accountsTable->getAdapter()->quoteInto('id = ?', $accountId)
        );
        
        $result = $accountsTable->update($accountData, $where);
        
        return $result;
    }

    /**
     * sets/unsets expiry date 
     *
     * @param 	int 		$_accountId
     * @param 	Zend_Date 	$_expiryDate set to NULL to disable expirydate
    */
    public function setExpiryDate($_accountId, $_expiryDate)
    {
        $accountId = Tinebase_Account_Model_Account::convertAccountIdToIntv($_accountId);
        
        if($_expiryDate instanceof Zend_Date) {
            $accountData['expires_at'] = $_expiryDate->getIso();
        } else {
            $accountData['expires_at'] = NULL;
        }
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));

        $where = array(
            $accountsTable->getAdapter()->quoteInto('id = ?', $accountId)
        );
        
        $result = $accountsTable->update($accountData, $where);
        
        return $result;
	}

    /**
     * sets blocked until date 
     *
     * @param 	int 		$_accountId
     * @param 	Zend_Date 	$_blockedUntilDate set to NULL to disable blockedDate
    */
    public function setBlockedDate($_accountId, $_blockedUntilDate)
    {
        $accountId = Tinebase_Account_Model_Account::convertAccountIdToIntv($_accountId);
        
        if($_blockedUntilDate instanceof Zend_Date) {
            $accountData['blocked_until'] = $_blockedUntilDate->getIso();
        } else {
            $accountData['blocked_until'] = NULL;
        }
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));

        $where = array(
            $accountsTable->getAdapter()->quoteInto('id = ?', $accountId)
        );
        
        $result = $accountsTable->update($accountData, $where);
        
        return $result;
	}	
    /**
     * update the lastlogin time of account
     *
     * @param int $_accountId
     * @param string $_ipAddress
     * @return void
     */
    public function setLoginTime($_accountId, $_ipAddress) 
    {
        $accountId = Tinebase_Account_Model_Account::convertAccountIdToIntv($_accountId);
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        
        $accountData['last_login_from'] = $_ipAddress;
        $accountData['last_login']      = Zend_Date::now()->getIso();
        
        $where = array(
            $accountsTable->getAdapter()->quoteInto('id = ?', $accountId)
        );
        
        $result = $accountsTable->update($accountData, $where);
        
        return $result;
    }
    
    /**
     * updates an account
     * 
     * this function updates an account 
     *
     * @param Tinebase_Account_Model_FullAccount $_account
     * @return Tinebase_Account_Model_FullAccount
     */
    public function updateAccount(Tinebase_Account_Model_FullAccount $_account)
    {
        if(!$_account->isValid()) {
            throw(new Exception('invalid account object'));
        }

        $accountId = Tinebase_Account_Model_Account::convertAccountIdToIntv($_account);

        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));

        $accountData = array(
            'login_name'        => $_account->accountLoginName,
            'status'            => $_account->accountStatus,
            'expires_at'        => ($_account->accountExpires instanceof Zend_Date ? $_account->accountExpires->getTimestamp() : NULL),
            'primary_group_id'  => $_account->accountPrimaryGroup
        );
        
        $contactData = array(
            'n_family'      => $_account->accountLastName,
            'n_given'       => $_account->accountFirstName,
            'n_fn'          => $_account->accountFullName,
            'n_fileas'      => $_account->accountDisplayName,
            'email'         => $_account->accountEmailAddress
        );

        try {
            Zend_Registry::get('dbAdapter')->beginTransaction();
            
            $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
            $contactsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'addressbook'));
            
            
            $where = array(
                Zend_Registry::get('dbAdapter')->quoteInto('id = ?', $accountId)
            );
            $accountsTable->update($accountData, $where);
            
            $where = array(
                Zend_Registry::get('dbAdapter')->quoteInto('account_id = ?', $accountId)
            );
            $contactsTable->update($contactData, $where);
            
            Zend_Registry::get('dbAdapter')->commit();
            
        } catch (Exception $e) {
            Zend_Registry::get('dbAdapter')->rollBack();
            throw($e);
        }
        
        return $this->getAccountById($accountId, 'Tinebase_Account_Model_FullAccount');
    }
    
    /**
     * add an account
     * 
     * this function adds an account 
     *
     * @param Tinebase_Account_Model_FullAccount $_account
     * @todo fix $contactData['owner'] = 1;
     * @return Tinebase_Account_Model_FullAccount
     */
    public function addAccount(Tinebase_Account_Model_FullAccount $_account)
    {
        if(!$_account->isValid()) {
            throw(new Exception('invalid account object'));
        }

        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        
        $accountData = array(
            'login_name'        => $_account->accountLoginName,
            'status'            => $_account->accountStatus,
            'expires_at'        => ($_account->accountExpires instanceof Zend_Date ? $_account->accountExpires->getIso() : NULL),
            'primary_group_id'  => $_account->accountPrimaryGroup,
        );
        if(!empty($_account->accountId)) {
            $accountData['id'] = $_account->accountId;
        }
        
        $contactData = array(
            'n_family'      => $_account->accountLastName,
            'n_given'       => $_account->accountFirstName,
            'n_fn'          => $_account->accountFullName,
            'n_fileas'      => $_account->accountDisplayName,
            'email'         => $_account->accountEmailAddress
        );

        try {
            Zend_Registry::get('dbAdapter')->beginTransaction();
            
            $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
            $contactsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'addressbook'));
            
            // add new account
            $accountId = $accountsTable->insert($accountData);
            
            // if we insert an account without an accountId, we need to get back one
            if(empty($_account->accountId) && $accountId == 0) {
                throw new Exception("returned accountId is 0");
            }
            
            // if the account had no accountId set, set the id now
            if(empty($_account->accountId)) {
                $_account->accountId = $accountId;
            }
            
            $contactData['account_id'] = $accountId;
            $contactData['tid'] = 'n';
            $contactData['owner'] = 1;
            
            $contactsTable->insert($contactData);
            
            Zend_Registry::get('dbAdapter')->commit();
            
        } catch (Exception $e) {
            Zend_Registry::get('dbAdapter')->rollBack();
            throw($e);
        }
        
        // add group membership (primary group)
        Tinebase_Group::getInstance()->addGroupMember($_account->accountPrimaryGroup,$accountId);
        
        return $this->getAccountById($accountId, 'Tinebase_Account_Model_FullAccount');
    }
    
    /**
     * delete a account
     *
     * @param int $_accountId
     */
    public function deleteAccount($_accountId)
    {
        $accountId = Tinebase_Account_Model_Account::convertAccountIdToIntv($_accountId);
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        $contactsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'addressbook'));
        $groupMembersTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'group_members'));
        
        
        try {
            Zend_Registry::get('dbAdapter')->beginTransaction();
            
            $where  = array(
                Zend_Registry::get('dbAdapter')->quoteInto('account_id = ?', $accountId),
            );
            $contactsTable->delete($where);

            $where  = array(
                Zend_Registry::get('dbAdapter')->quoteInto('account_id = ?', $accountId),
            );
            $groupMembersTable->delete($where);
            
            $where  = array(
                Zend_Registry::get('dbAdapter')->quoteInto('id = ?', $accountId),
            );
            $accountsTable->delete($where);
            
            Zend_Registry::get('dbAdapter')->commit();
        } catch (Exception $e) {
            Zend_Registry::get('dbAdapter')->rollBack();
            throw($e);
        }
    }
}