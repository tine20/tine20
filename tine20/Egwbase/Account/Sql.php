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
 * sql implementation of the eGW SQL accounts interface
 * 
 * @package     Egwbase
 * @subpackage  Accounts
 */

class Egwbase_Account_Sql implements Egwbase_Account_Interface
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
     * @var Egwbase_Account_Sql
     */
    private static $_instance = NULL;
    
    protected $rowNameMapping = array(
        'accountId'             => 'account_id',
        'accountDisplayName'    => 'n_fileas',
        'accountFullName'       => 'n_fn',
        'accountFirstName'      => 'n_given',
        'accountLastName'       => 'n_family',
        'accountLoginName'      => 'account_lid',
        'accountLastLogin'      => 'account_lastlogin',
        'accountLastLoginfrom'  => 'account_lastloginfrom',
        'accountLastPasswordChange' => 'account_lastpwd_change',
        'accountStatus'         => 'account_status',
        'accountExpires'        => 'account_expires',
        'accountPrimaryGroup'   => 'account_primary_group'
    
    );
    
    
    /**
     * the singleton pattern
     *
     * @return Egwbase_Account_Sql
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Egwbase_Account_Sql;
        }
        
        return self::$_instance;
    }
    
    /**
     * 
     * groupmembership handling
     * 
     */

    /**
     * return the group ids a account is member of
     *
     * @param int $accountId the accountid of a account
     * @todo the group info do not belong into the ACL table, there should be a separate group table
     * @return array list of group ids
     */
    public function getGroupMemberships($_accountId)
    {
        $accountId = (int)$_accountId;
        if($accountId != $_accountId) {
            throw new InvalidArgumentException('$_accountId must be integer');
        }
        
        $aclTable = new Egwbase_Db_Table(array('name' => 'egw_acl'));
        
        $groupMemberShips = array();
        
        $where = array(
            $aclTable->getAdapter()->quoteInto('acl_appname = ?', 'phpgw_group'),
            $aclTable->getAdapter()->quoteInto('acl_account = ?', $_accountId)
        );
        
        $rowSet = $aclTable->fetchAll($where);
        
        foreach($rowSet as $row) {
            $groupMemberShips[] = $row->acl_location;
        }
        
        return $groupMemberShips;
    }
    
    /**
     * return a list of group members account id's
     *
     * @param int $groupId
     * @todo the group info do not belong into the ACL table, there should be a separate group table
     * @deprecated 
     * @return array list of group members account id's
     */
    public function getGroupMembers($_groupId)
    {
        $groupId = (int)$_groupId;
        if($groupId != $_groupId) {
            throw new InvalidArgumentException('$_groupId must be integer');
        }
        
        $aclTable = new Egwbase_Acl_Sql();
        $members = array();
        
        $where = array(
            "acl_appname = 'phpgw_group'",
            $aclTable->getAdapter()->quoteInto('acl_location = ?', $groupId)
        );
        
        $rowSet = $aclTable->fetchAll($where);
        
        foreach($rowSet as $row) {
            $members[] = $row->acl_account;
        }
        
        return $members;
    }
    
    /**
     * 
     * public account data handling
     * 
     */
    
    /**
     * get list of accounts with NO internal informations
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Egwbase_Record_RecordSet with record class Egwbase_Account_Model_Account
     */
    public function getAccounts($_filter, $_sort, $_dir, $_start = NULL, $_limit = NULL, $_accountClass = 'Egwbase_Account_Model_Account')
    {        
        $select = $this->_getAccountSelectObject()
            ->where('(n_family LIKE ? OR n_given LIKE ?)', '%' . $_filter . '%')
            ->where('account_status != ?', 'D')
            ->limit($_limit, $_start)
            ->order($this->rowNameMapping[$_sort] . ' ' . $_dir);

        //error_log("getAccounts:: " . $select->__toString());

        $stmt = $select->query();

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        $result = new Egwbase_Record_RecordSet($rows, $_accountClass);
        
        return $result;
    }
    
    /**
     * get eGW account by login name
     *
     * @param string $_loginName the loginname of the account
     * @return Egwbase_Account_Model_Account the account object
     */
    public function getAccountByLoginName($_loginName, $_accountClass = 'Egwbase_Account_Model_Account')
    {
        $select = $this->_getAccountSelectObject()
            ->where('egw_accounts.account_lid = ?', $_loginName);

        //error_log("getAccounts:: " . $select->__toString());

        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);

        try {
            $account = new $_accountClass();
            $account->setFromUserData($row);
        } catch (Exception $e) {
            $validation_errors = $account->getValidationErrors();
            Zend_Registry::get('logger')->debug( 'Egwbase_Account_Sql::getAccountByLoginName: ' . $e->getMessage() . "\n" .
                "Egwbase_Account_Model_Account::validation_errors: \n" .
                print_r($validation_errors,true));
            throw ($e);
        }
        
        return $account;
    }
    
    /**
     * get eGW account by accountId
     *
     * @param int $_accountId the account id
     * @return Egwbase_Account_Model_Account the account object
     */
    public function getAccountById($_accountId, $_accountClass = 'Egwbase_Account_Model_Account')
    {
        $accountId = (int)$_accountId;
        if($accountId != $_accountId) {
            throw new InvalidArgumentException('$_accountId must be integer');
        }
        
        $select = $this->_getAccountSelectObject()
            ->where('egw_accounts.account_id = ?', $accountId);

        //error_log("getAccounts:: " . $select->__toString());

        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);

        try {
            $account = new $_accountClass();
            $account->setFromUserData($row);
        } catch (Exception $e) {
            $validation_errors = $account->getValidationErrors();
            Zend_Registry::get('logger')->debug( 'Egwbase_Account_Sql::_getAccountFromSQL: ' . $e->getMessage() . "\n" .
                "Egwbase_Account_Model_Account::validation_errors: \n" .
                print_r($validation_errors,true));
            throw ($e);
        }
        
        return $account;
    }
    
    protected function _getAccountSelectObject()
    {
        $db = Zend_Registry::get('dbAdapter');
        
        $select = $db->select()
            ->from('egw_accounts', 
                array(
                    'accountId' => $this->rowNameMapping['accountId'],
                    'accountLoginName' => $this->rowNameMapping['accountLoginName'],
                    'accountLastLogin' => 'FROM_UNIXTIME(`egw_accounts`.`account_lastlogin`)',
                    'accountLastLoginfrom' => $this->rowNameMapping['accountLastLoginfrom'],
                    'accountLastPasswordChange' => 'FROM_UNIXTIME(`egw_accounts`.`account_lastpwd_change`)',
                    'accountStatus' => $this->rowNameMapping['accountStatus'],
                    'accountExpires' => 'FROM_UNIXTIME(`egw_accounts`.`account_expires`)',
                    'accountPrimaryGroup' => $this->rowNameMapping['accountPrimaryGroup']
                )
            )
            ->join(
                'egw_addressbook',
                'egw_accounts.account_id = egw_addressbook.account_id',
                array(
                    'accountDisplayName'    => $this->rowNameMapping['accountDisplayName'],
                    'accountFullName'       => $this->rowNameMapping['accountFullName'],
                    'accountFirstName'      => $this->rowNameMapping['accountFirstName'],
                    'accountLastName'       => $this->rowNameMapping['accountLastName']
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
        $accountId = (int)$_accountId;
        if($accountId != $_accountId) {
            throw new InvalidArgumentException('$_accountId must be integer');
        }
        
        switch($_status) {
            case 'enabled':
                $accountData['account_status'] = 'A';
                break;
                
            case 'disabled':
                $accountData['account_status'] = 'D';
                break;
                
            case 'expired':
                $accountData['account_expires'] = Zend_Date::getTimestamp();
                break;
            
            default:
                throw new InvalidArgumentException('$_status can be only enabled, disabled or epxired');
                break;
        }
        
        $accountsTable = new Egwbase_Db_Table(array('name' => 'egw_accounts'));

        $where = array(
            $accountsTable->getAdapter()->quoteInto('account_id = ?', $accountId)
        );
        
        $result = $accountsTable->update($accountData, $where);
        
        return $result;
    }
    
    /**
     * set the password for given account
     *
     * @param int $_accountId
     * @param string $_password
     * @return void
     */
    public function setPassword($_accountId, $_password)
    {
        $accountId = (int)$_accountId;
        if($accountId != $_accountId) {
            throw new InvalidArgumentException('$_accountId must be integer');
        }
        
        $accountsTable = new Egwbase_Db_Table(array('name' => 'egw_accounts'));
        
        $accountData['account_pwd'] = md5($_password);
        $accountData['account_lastpwd_change'] = Zend_Date::now()->getTimestamp();
        
        $where = array(
            $accountsTable->getAdapter()->quoteInto('account_id = ?', $accountId)
        );
        
        $result = $accountsTable->update($accountData, $where);
        if ($result != 1) {
            throw new Exception('Unable to update password');
        }
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
        $accountId = (int)$_accountId;
        if($accountId != $_accountId) {
            throw new InvalidArgumentException('$_accountId must be integer');
        }
        
        $accountsTable = new Egwbase_Db_Table(array('name' => 'egw_accounts'));
        
        $accountData['account_lastloginfrom'] = $_ipAddress;
        $accountData['account_lastlogin'] = Zend_Date::now()->getTimestamp();
        
        $where = array(
            $accountsTable->getAdapter()->quoteInto('account_id = ?', $accountId)
        );
        
        $result = $accountsTable->update($accountData, $where);
        
        return $result;
    }
}