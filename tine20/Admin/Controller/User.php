<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        change exceptions to PermissionDeniedException
 */

/**
 * User Controller for Admin application
 *
 * @package     Admin
 */
class Admin_Controller_User extends Admin_Controller_Abstract
{
    /**
     * holdes the instance of the singleton
     *
     * @var Admin_Controller_User
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Admin_Controller_User
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_User;
        }
        
        return self::$_instance;
    }
    
    /**
     * get list of accounts
     *
     * @param string $_filter string to search accounts for
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_User
     */
    public function getUsers($_filter, $_sort, $_dir, $_start = NULL, $_limit = NULL)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        $backend = Tinebase_User::getInstance();

        $result = $backend->getUsers($_filter, $_sort, $_dir, $_start, $_limit);
        
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
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_FullUser
     */
    public function getFullUsers($_filter, $_sort, $_dir, $_start = NULL, $_limit = NULL)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        $backend = Tinebase_User::getInstance();

        $result = $backend->getFullUsers($_filter, $_sort, $_dir, $_start, $_limit);
        
        return $result;
    }
    
    /**
     * get account
     *
     * @param   int $_accountId account id to get
     * @return  Tinebase_Model_User
     */
    public function getAccount($_accountId)
    {        
        $this->checkRight('VIEW_ACCOUNTS');
        
        return Tinebase_User::getInstance()->getUserById($_accountId);
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
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $result = Tinebase_User::getInstance()->setStatus($_accountId, $_status);
        
        return $result;
    }

    /**
     * set the password for a given account
     *
     * @param Tinebase_Model_FullUser $_account the account
     * @param string $_password the new password
     * @param string $_passwordRepeat the new password again
     * @return unknown
     */
    public function setAccountPassword(Tinebase_Model_FullUser $_account, $_password, $_passwordRepeat)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        if ($_password != $_passwordRepeat) {
            throw new Exception("passwords don't match");
        }
        
        $result = Tinebase_Auth::getInstance()->setPassword($_account->accountLoginName, $_password, $_passwordRepeat);
        
        return $result;
    }

    /**
     * save or update account
     *
     * @param Tinebase_Model_FullUser $_account the account
     * @param string $_password the new password
     * @param string $_passwordRepeat the new password again
     * @return Tinebase_Model_FullUser
     */
    public function updateUser(Tinebase_Model_FullUser $_account, $_password, $_passwordRepeat)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $account = Tinebase_User::getInstance()->updateUser($_account);
        Tinebase_Group::getInstance()->addGroupMember($account->accountPrimaryGroup, $account);
        
        // fire needed events
        $event = new Admin_Event_UpdateAccount;
        $event->account = $account;
        Tinebase_Events::fireEvent($event);
        
        if (!empty($_password) && !empty($_passwordRepeat)) {
            Tinebase_Auth::getInstance()->setPassword($_account->accountLoginName, $_password, $_passwordRepeat);
        }
        
        return $account;
    }
    
    /**
     * save or update account
     *
     * @param Tinebase_Model_FullUser $_account the account
     * @param string $_password the new password
     * @param string $_passwordRepeat the new password again
     * @return Tinebase_Model_FullUser
     */
    public function addUser(Tinebase_Model_FullUser $_account, $_password, $_passwordRepeat)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $account = Tinebase_User::getInstance()->addUser($_account);
        Tinebase_Group::getInstance()->addGroupMember($account->accountPrimaryGroup, $account);
        
        $event = new Admin_Event_AddAccount;
        $event->account = $account;
        Tinebase_Events::fireEvent($event);
        
        if (!empty($_password) && !empty($_passwordRepeat)) {
            Tinebase_Auth::getInstance()->setPassword($_account->accountLoginName, $_password, $_passwordRepeat);
        }
        
        return $account;
    }

    
    /**
     * delete accounts
     *
     * @param   array $_accountIds  array of account ids
     * @return  array with success flag
     */
    public function deleteUsers(array $_accountIds)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        return Tinebase_User::getInstance()->deleteUsers($_accountIds);
    }
}
