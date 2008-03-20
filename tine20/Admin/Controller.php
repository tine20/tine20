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
    
    public function setAccountStatus($_accountId, $_status)
    {
        $backend = Tinebase_Account::getInstance();
        
        $result = $backend->setStatus($_accountId, $_status);
        
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
    
    public function getAccessLogEntries($_filter = NULL, $_sort = 'li', $_dir = 'ASC', $_limit = NULL, $_start = NULL, $_from = NULL, $_to = NULL)
    {
        $tineAccessLog = Tinebase_AccessLog::getInstance();

        $result = $tineAccessLog->getEntries($_filter, $_sort, $_dir, $_start, $_limit, $_from, $_to);
        
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
    public function saveAccount(Tinebase_Account_Model_FullAccount $_account, $_password1, $_password2)
    {
        if(empty($_account->accountId)) {
            $account = Tinebase_Account::getInstance()->addAccount($_account);
        } else {
            $account = Tinebase_Account::getInstance()->updateAccount($_account);
        }
        Tinebase_Group::getInstance()->addGroupMember($account->accountPrimaryGroup, $account);
        
        // fire needed events
        if(isset($_account->accountId)) {
            $event = new Admin_Event_UpdateAccount;
            $event->account = $account;
        } else {
            $event = new Admin_Event_AddAccount;
            $event->account = $account;
        }
        Tinebase_Events::fireEvent($event);
        
        if(!empty($_password1) && !empty($_password2)) {
            Tinebase_Auth::getInstance()->setPassword($_account->accountLoginName, $_password1, $_password2);
        }
        
        return $account;
    }

    public function deleteAccounts(array $_accountIds)
    {
        return Tinebase_Account::getInstance()->deleteAccounts($_accountIds);
    }
}
