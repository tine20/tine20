<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * controller for Admin application
 *
 * @package     Admin
 */
class Admin_Controller
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
     * @var Admin_Controller
     */
    private static $instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Admin_Controller
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Admin_Controller;
        }
        
        return self::$instance;
    }

    /**
     * get list of accounts
     *
     * @param string $_filter string to search accounts for
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Account_Model_Account
     */
    public function getAccounts($_filter, $_sort, $_dir, $_start = NULL, $_limit = NULL)
    {
        $backend = Tinebase_Account::getInstance();

        $result = $backend->getAccounts($_filter, $_sort, $_dir, $_start, $_limit);
        
        return $result;
    }

    /**
     * get list of full accounts
     *
     * @param string $_filter string to search accounts for
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Account_Model_FullAccount
     */
    public function getFullAccounts($_filter, $_sort, $_dir, $_start = NULL, $_limit = NULL)
    {
        $backend = Tinebase_Account::getInstance();

        $result = $backend->getFullAccounts($_filter, $_sort, $_dir, $_start, $_limit);
        
        return $result;
    }
    
    /**
     * get account
     *
     * @param   int $_accountId account id to get
     * @return  Tinebase_Account_Model_Account
     */
    public function getAccount($_accountId)
    {        
        return Tinebase_Account::getInstance()->getAccountById($_accountId);
    }
    

    /**
     * set account status
     *
     * @param   string $_accountId  account id
     * @param   string $_status     status to set
     * @return  array with success flag
     */
    public function setAccountStatus($_accountId, $_status)
    {
        $result = Tinebase_Account::getInstance()->setStatus($_accountId, $_status);
        
        return $result;
    }

    /**
     * set the password for a given account
     *
     * @param Tinebase_Account_Model_FullAccount $_account the account
     * @param string $_password1 the new password
     * @param string $_password2 the new password again
     * @return unknown
     */
    public function setAccountPassword(Tinebase_Account_Model_FullAccount $_account, $_password1, $_password2)
    {
        if($_password1 != $_password2) {
            throw new Exception("passwords don't match");
        }
        
        $result = Tinebase_Auth::getInstance()->setPassword($_account->accountLoginName, $_password1, $_password2);
        
        return $result;
    }

    /**
     * save or update account
     *
     * @param Tinebase_Account_Model_FullAccount $_account the account
     * @param string $_password1 the new password
     * @param string $_password2 the new password again
     * @return Tinebase_Account_Model_FullAccount
     */
    public function updateAccount(Tinebase_Account_Model_FullAccount $_account, $_password1, $_password2)
    {
        $account = Tinebase_Account::getInstance()->updateAccount($_account);
        Tinebase_Group::getInstance()->addGroupMember($account->accountPrimaryGroup, $account);
        
        // fire needed events
        $event = new Admin_Event_UpdateAccount;
        $event->account = $account;
        Tinebase_Events::fireEvent($event);
        
        if(!empty($_password1) && !empty($_password2)) {
            Tinebase_Auth::getInstance()->setPassword($_account->accountLoginName, $_password1, $_password2);
        }
        
        return $account;
    }
    
    /**
     * save or update account
     *
     * @param Tinebase_Account_Model_FullAccount $_account the account
     * @param string $_password1 the new password
     * @param string $_password2 the new password again
     * @return Tinebase_Account_Model_FullAccount
     */
    public function addAccount(Tinebase_Account_Model_FullAccount $_account, $_password1, $_password2)
    {
        $account = Tinebase_Account::getInstance()->addAccount($_account);
        Tinebase_Group::getInstance()->addGroupMember($account->accountPrimaryGroup, $account);
        
        $event = new Admin_Event_AddAccount;
        $event->account = $account;
        Tinebase_Events::fireEvent($event);
        
        if(!empty($_password1) && !empty($_password2)) {
            Tinebase_Auth::getInstance()->setPassword($_account->accountLoginName, $_password1, $_password2);
        }
        
        return $account;
    }

    
    /**
     * delete accounts
     *
     * @param   array $_accountIds  array of account ids
     * @return  array with success flag
     */
    public function deleteAccounts(array $_accountIds)
    {
        return Tinebase_Account::getInstance()->deleteAccounts($_accountIds);
    }
    
    /**
     * get list of access log entries
     *
     * @param string $_filter string to search accounts for
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_RecordSet_AccessLog set of matching access log entries
     */
    public function getAccessLogEntries($_filter = NULL, $_sort = 'li', $_dir = 'ASC', $_start = NULL, $_limit = NULL, $_from = NULL, $_to = NULL)
    {
        $tineAccessLog = Tinebase_AccessLog::getInstance();

        $result = $tineAccessLog->getEntries($_filter, $_sort, $_dir, $_start, $_limit, $_from, $_to);
        
        return $result;
    }

    /**
     * returns the total number of access logs
     * 
     * @param Zend_Date $_from the date from which to fetch the access log entries from
     * @param Zend_Date $_to the date to which to fetch the access log entries to
     * @param string $_filter OPTIONAL search parameter
     * 
     * @return int
     */
    public function getTotalAccessLogEntryCount($_from, $_to, $_filter)
    {
        return Tinebase_AccessLog::getInstance()->getTotalCount($_from, $_to, $_filter);
    }
    
    /**
     * delete access log entries
     *
     * @param   array $_logIds list of logIds to delete
     */
    public function deleteAccessLogEntries($_logIds)
    {
        Tinebase_AccessLog::getInstance()->deleteEntries($_logIds);
    }
    
    /**
     * get list of applications
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_RecordSet_Application
     */
    public function getApplications($filter, $sort, $dir, $start, $limit)
    {
        $tineApplications = Tinebase_Application::getInstance();
        
        return $tineApplications->getApplications($filter, $sort, $dir, $start, $limit);
    }    

    /**
     * get application
     *
     * @param   int $_applicationId application id to get
     * @return  Tinebase_Model_Application
     */
    public function getApplication($_applicationId)
    {
        $tineApplications = Tinebase_Application::getInstance();
        
        return $tineApplications->getApplicationById($_applicationId);
    }
    
    /**
     * returns the total number of applications installed
     * 
     * @param string $_filter
     * @return int
     */
    public function getTotalApplicationCount($_filter)
    {
        $tineApplications = Tinebase_Application::getInstance();
        
        return $tineApplications->getTotalApplicationCount($_filter);
    }
    
    /**
     * set application state
     *
     * @param   array $_applicationIds  array of application ids
     * @param   string $_state           state to set
     */
    public function setApplicationState($_applicationIds, $_state)
    {
        $tineApplications = Tinebase_Application::getInstance();
        
        return $tineApplications->setApplicationState($_applicationIds, $_state);
    }
    
    /**
     * get list of groups
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Group_Model_Group
     */
    public function getGroups($filter, $sort, $dir, $start, $limit)
    {
   	    return Tinebase_Group::getInstance()->getGroups($filter, $sort, $dir, $start, $limit);
    }
   
    /**
     * fetch one group identified by groupid
     *
     * @param int $_groupId
     * @return Tinebase_Group_Model_Group
     */
    public function getGroup($_groupId)
    {
        $group = Tinebase_Group::getInstance()->getGroupById($_groupId);

        /*if(!Zend_Registry::get('currentAccount')->hasGrant($contact->owner, Tinebase_Container::GRANT_READ)) {
            throw new Exception('read access to contact denied');
        }*/
        
        return $group;            
    }  

   /**
     * add new group
     *
     * @param Tinebase_Group_Model_Group $_group
     * @param array $_groupMembers
     * 
     * @return Tinebase_Group_Model_Group
     */
    public function AddGroup(Tinebase_Group_Model_Group $_group, array $_groupMembers = array ())
    {
        $group = Tinebase_Group::getInstance()->addGroup($_group);
        
        if ( !empty($_groupMembers) ) {
            Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_groupMembers);
        }

        return $group;            
    }  

   /**
     * update existing group
     *
     * @param Tinebase_Group_Model_Group $_group
     * @param array $_groupMembers
     * 
     * @return Tinebase_Group_Model_Group
     */
    public function UpdateGroup(Tinebase_Group_Model_Group $_group, array $_groupMembers = array ())
    {
        $group = Tinebase_Group::getInstance()->updateGroup($_group);
        
        Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_groupMembers);

        return $group;            
    }  
    
    /**
     * delete multiple groups
     *
     * @param   array $_groupIds
     * @return  array with success flag
     */
    public function deleteGroups($_groupIds)
    {        
        return Tinebase_Group::getInstance()->deleteGroups($_groupIds);
    }    
    
    /**
     * get list of groupmembers
     *
     * @param int $_groupId
     * @return array with Tinebase_A
     */
    public function getGroupMembers($_groupId)
    {
        $accountIds = Tinebase_Group::getInstance()->getGroupMembers($_groupId);
        
        $result = array ();
        foreach ( $accountIds as $accountId ) {
            //$result[] = Tinebase_Account::getInstance()->getFullAccountById($accountId)->toArray();
            $result[] = Tinebase_Account::getInstance()->getAccountById($accountId)->toArray();
        }
        
        return $result;
    }
    
}
