<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Accounts
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * sql implementation of the eGW SQL accounts interface
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
    private static $instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Egwbase_Account_Sql
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Egwbase_Account_Sql;
        }
        
        return self::$instance;
    }

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
     * return the list of group members
     *
     * @param int $groupId
     * @todo the group info do not belong into the ACL table, there should be a separate group table
     * @deprecated 
     * @return array list of group members
     */
    public function getGroupMembers($groupId)
    {
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
     * get list of accounts
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return array
     */
    public function getAccounts($_filter, $_sort, $_dir, $_start = NULL, $_limit = NULL)
    {
        //$right = (int)$_right;
        //if($right != $_right) {
        //    throw new InvalidArgumentException('$_right must be integer');
        //}
        //$accountId   = Zend_Registry::get('currentAccount')->account_id;
        //$application = Egwbase_Application::getInstance()->getApplicationByName($_application);

        $db = Zend_Registry::get('dbAdapter');
        
        $select = $db->select()
            ->from('egw_accounts')
            ->join(
                'egw_addressbook',
                'egw_accounts.account_id = egw_addressbook.account_id'
            )
            ->limit($_limit, $_start)
            ->order($_sort . ' ' . $_dir);

        //error_log("getAccounts:: " . $select->__toString());

        $stmt = $db->query($select);

        $result = array();
        
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        foreach($rows as $account) {
            if($account['account_lastlogin'] !== NULL) {
                $account['account_lastlogin'] = new Zend_Date($account['account_lastlogin'], Zend_Date::TIMESTAMP);
            }
            
            if($account['account_lastpwd_change'] !== NULL) {
                $account['account_lastpwd_change'] = new Zend_Date($account['account_lastpwd_change'], Zend_Date::TIMESTAMP);
            }
            
            if($account['account_expires'] > 0) {
                $account['account_expires'] = new Zend_Date($account['account_expires'], Zend_Date::TIMESTAMP);
            } else {
                $account['account_expires'] = NULL;
            }
            
            $result[] = $account;
        }
        //$result = new Egwbase_Record_RecordSet($stmt->fetchAll(Zend_Db::FETCH_ASSOC), 'Egwbase_Record_Container');
        
        return $result;
    }
    
    /**
     * read the account from acl depending on the where query and value
     *
     * @param string $_whereQuery the where query 
     * @param int|string $_whereValue the where value
     * @return Egwbase_Record_Account the account object
     */
    protected function _getAccountFromSQL($_whereQuery, $_whereValue)
    {
        $db = Zend_Registry::get('dbAdapter');
        
        $select = $db->select()
            ->from('egw_accounts', array(
                'account_id', 
                'account_lid', 
                'account_lastlogin', 
                'account_lastloginfrom', 
                'account_lastpwd_change', 
                'account_status', 
                'account_expires', 
                'account_primary_group')
            )
            ->join(
                'egw_addressbook',
                'egw_accounts.account_id = egw_addressbook.account_id', 
                array()
            )
            ->where($_whereQuery, $_whereValue);

        //error_log("getAccountByLoginName:: " . $select->__toString());

        $stmt = $db->query($select);
    
        $accountArray = $stmt->fetch(Zend_Db::FETCH_ASSOC);

        if($accountArray['account_lastlogin'] !== NULL) {
            $accountArray['account_lastlogin'] = new Zend_Date($account['account_lastlogin'], Zend_Date::TIMESTAMP);
        }
            
        if($accountArray['account_lastpwd_change'] !== NULL) {
            $accountArray['account_lastpwd_change'] = new Zend_Date($account['account_lastpwd_change'], Zend_Date::TIMESTAMP);
        }
            
        if($accountArray['account_expires'] > 0) {
            $accountArray['account_expires'] = new Zend_Date($account['account_expires'], Zend_Date::TIMESTAMP);
        } else {
            $accountArray['account_expires'] = NULL;
        }
        
        $account = new Egwbase_Record_Account($accountArray, true);
        
        return $account;
        
    }

    /**
     * get eGW account by login name
     *
     * @param string $_loginName the loginname of the account
     * @return Egwbase_Record_Account the account object
     */
    public function getAccountByLoginName($_loginName)
    {
        $result = $this->_getAccountFromSQL('egw_accounts.account_lid = ?', $_loginName);
        
        return $result;
    }
    
    /**
     * get eGW account by accountId
     *
     * @param int $_accountId the account id
     * @return Egwbase_Record_Account the account object
     */
    public function getAccountById($_accountId)
    {
        $accountId = (int)$_accountId;
        if($accountId != $_accountId) {
            throw new InvalidArgumentException('$_accountId must be integer');
        }
        
        $result = $this->_getAccountFromSQL('egw_accounts.account_id = ?', $accountId);
        
        return $result;
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