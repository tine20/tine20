<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        catch error (and show alert) if postfix email already exists
 * @todo        extend Tinebase_Controller_Record_Abstract
 */

/**
 * User Controller for Admin application
 *
 * @package     Admin
 */
class Admin_Controller_User extends Tinebase_Controller_Abstract
{
	/**
	 * @var Tinebase_User_Abstract
	 */
	protected $_userBackend = NULL;
	
	/**
	 * @var Tinebase_SambaSAM_Ldap
	 */
	protected $_samBackend = NULL;

    /**
     * @var bool
     */
    protected $_manageImapEmailUser = FALSE;
    
    /**
     * @var bool
     */
    protected $_manageSmtpEmailUser = FALSE;
    
    /**
     * @var Tinebase_EmailUser_Abstract
     */
    protected $_imapUserBackend = NULL;

    /**
     * @var Tinebase_EmailUser_Abstract
     */
    protected $_smtpUserBackend = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_currentAccount = Tinebase_Core::getUser();        
        $this->_applicationName = 'Admin';
		
        $this->_userBackend = Tinebase_User::getInstance();

        // manage email user settings
        if (Tinebase_EmailUser::manages(Tinebase_Model_Config::IMAP)) {
            $this->_manageImapEmailUser = TRUE; 
            $this->_imapUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Model_Config::IMAP);
        }

        if (Tinebase_EmailUser::manages(Tinebase_Model_Config::SMTP)) {
            $this->_manageSmtpEmailUser = TRUE; 
            $this->_smtpUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Model_Config::SMTP);
        }
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }

    /**
     * holds the instance of the singleton
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
     * get list of full accounts -> renamed to search full users
     *
     * @param string $_filter string to search accounts for
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_FullUser
     */
    public function searchFullUsers($_filter, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        $result = $this->_userBackend->getUsers($_filter, $_sort, $_dir, $_start, $_limit, 'Tinebase_Model_FullUser');
        
        return $result;
    }
    
    /**
     * count users
     *
     * @param string $_filter string to search user accounts for
     * @return int total user count
     */
    public function searchCount($_filter)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        $users = $this->_userBackend->getUsers($_filter);
        $result = count($users);
        
        return $result;
    }
    
    /**
     * get account
     *
     * @param   int $_accountId account id to get
     * @return  Tinebase_Model_FullUser
     */
    public function get($_accountId)
    {        
        $this->checkRight('VIEW_ACCOUNTS');
        
        $user = $this->_userBackend->getUserById($_accountId, 'Tinebase_Model_FullUser');
        
        // add email user data here
        $user->emailUser = $this->_getEmailUser($user);
        
        return $user;
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
        
        $result = $this->_userBackend->setStatus($_accountId, $_status);
        
        return $result;
    }
    
    /**
     * set the password for a given account
     *
     * @param Tinebase_Model_FullUser $_account the account
     * @param string $_password the new password
     * @param string $_passwordRepeat the new password again
     * @param bool $_mustChange
     * @return void
     * 
     * @todo add must change pwd info to normal tine user accounts
     * @todo add Admin_Event_ChangePassword?
     */
    public function setAccountPassword(Tinebase_Model_FullUser $_account, $_password, $_passwordRepeat, $_mustChange = FALSE)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        if ($_password != $_passwordRepeat) {
            throw new Admin_Exception("Passwords don't match.");
        }
        
        $this->_userBackend->setPassword($_account->accountLoginName, $_password);
        
        Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . 
            ' Set new password for user ' . $_account->accountLoginName . '. Must change:' . $_mustChange
        );
        
        $this->_setEmailUserPassword($_account->getId(), $_password);
        
        // fire change password event
        /*
        $event = new Admin_Event_ChangePassword();
        $event->userId = $_account->getId();
        $event->password = $_password;
        Tinebase_Event::fireEvent($event);
        */
    }
    
    /**
     * save or update account
     *
     * @param Tinebase_Model_FullUser $_account the account
     * @param string $_password the new password
     * @param string $_passwordRepeat the new password again
     * @return Tinebase_Model_FullUser
     */
    public function update(Tinebase_Model_FullUser $_account, $_password, $_passwordRepeat)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $oldAccount = $this->_userBackend->getUserByProperty('accountId', $_account, 'Tinebase_Model_FullUser');
        $account    = $this->_userBackend->updateUser($_account);

        // remove user from primary group, if primary group changes
        if($oldAccount->accountPrimaryGroup != $account->accountPrimaryGroup) {
            Tinebase_Group::getInstance()->removeGroupMember($oldAccount->accountPrimaryGroup, $account);
        }
        // always add user to primary group
        Tinebase_Group::getInstance()->addGroupMember($account->accountPrimaryGroup, $account);
        
        // fire needed events
        $event = new Admin_Event_UpdateAccount;
        $event->account = $account;
        Tinebase_Event::fireEvent($event);
        
        // update email user settings
        $this->_updateEmailUser($account, $_account->emailUser);
        
        if (!empty($_password) && !empty($_passwordRepeat)) {
            $this->setAccountPassword($_account, $_password, $_passwordRepeat);
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
    public function create(Tinebase_Model_FullUser $_account, $_password, $_passwordRepeat)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        // avoid forging accountId, get's created in backend
        unset($_account->accountId);
        
        $account = $this->_userBackend->addUser($_account);
        Tinebase_Group::getInstance()->addGroupMember($account->accountPrimaryGroup, $account);
        
        $event = new Admin_Event_AddAccount();
        $event->account = $account;
        Tinebase_Event::fireEvent($event);
        
        // create email user data here
        $this->_createEmailUser($account, $_account->emailUser);
        
        if (!empty($_password) && !empty($_passwordRepeat)) {
            $this->setAccountPassword($account, $_password, $_passwordRepeat);
        }

        return $account;
    }
    
    /**
     * delete accounts
     *
     * @param   array $_accountIds  array of account ids
     * @return  array with success flag
     */
    public function delete(array $_accountIds)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $groupsBackend = Tinebase_Group::getInstance();
        foreach ((array)$_accountIds as $accountId) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " about to remove user with id: {$accountId}");
            
            $memberships = $groupsBackend->getGroupMemberships($accountId);
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " removing user from groups: " . print_r($memberships, true));
            
            foreach ((array)$memberships as $groupId) {
                $groupsBackend->removeGroupMember($groupId, $accountId);
            }
            
            if ($this->_manageImapEmailUser) {
                $this->_imapUserBackend->deleteUser($accountId);
            }
            if ($this->_manageSmtpEmailUser) {
                $this->_smtpUserBackend->deleteUser($accountId);
            }
            
            $this->_userBackend->deleteUser($accountId);
        }
    }
    
    /**
     * get email user settings
     * 
     * @param Tinebase_Model_FullUser $_user
     * @return Tinebase_Model_EmailUser
     */
    protected function _getEmailUser($_user)
    {
        if (! $this->_manageImapEmailUser && ! $this->_manageSmtpEmailUser) {
            return new Tinebase_Model_EmailUser();
        }
        
        try {
            $imapUser = ($this->_manageImapEmailUser) ? $this->_imapUserBackend->getUserById($_user->getId()) : NULL;
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' No imap email user settings yet');
            $imapUser = NULL;
        }            
        
        try {
            $smtpUser = ($this->_manageSmtpEmailUser) ? $this->_smtpUserBackend->getUserById($_user->getId()) : NULL;
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' No smtp email user settings yet');
            $smtpUser = NULL;
        }
            
        // merge
        $result = Tinebase_EmailUser::merge($imapUser, $smtpUser);
        if ($result === NULL) {
            // no email settings yet
            $result = ($this->_manageImapEmailUser) ? $this->_imapUserBackend->getNewUser($_user) : $this->_smtpUserBackend->getNewUser($_user);
        }
        
        return $result;
    }

    /**
     * create email user settings
     * 
     * @param Tinebase_Model_FullUser $_user
     * @param Tinebase_Model_EmailUser $_emailUser
     * @return void
     */
    protected function _createEmailUser($_user, $_emailUser)
    {
        if (! $_emailUser) {
            $_emailUser = new Tinebase_Model_EmailUser();
        }
        
        // update email user data here
        if ($this->_manageImapEmailUser) {
            $this->_imapUserBackend->addUser($_user, $_emailUser);
        }
        if ($this->_manageSmtpEmailUser) {
            $this->_smtpUserBackend->addUser($_user, $_emailUser);
        }
        
        $_user->emailUser = $this->_getEmailUser($_user);
    }
    
    /**
     * update email user settings
     * 
     * @param Tinebase_Model_FullUser $_user
     * @param Tinebase_Model_EmailUser $_emailUser
     * @return void
     */
    protected function _updateEmailUser($_user, $_emailUser)
    {
        if (! $_emailUser) {
            $_emailUser = new Tinebase_Model_EmailUser();
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_user->emailUser->toArray(), true));
        
        // update email user data here
        if ($this->_manageImapEmailUser) {
            if ($_emailUser->emailUsername) {
                $this->_imapUserBackend->updateUser($_user, $_emailUser);
            } else {
                $this->_imapUserBackend->addUser($_user, $_emailUser);
            }
        }
        if ($this->_manageSmtpEmailUser) {
            if ($_emailUser->emailAddress) {
                $this->_smtpUserBackend->updateUser($_user, $_emailUser);
            } else {
                $this->_smtpUserBackend->addUser($_user, $_emailUser);
            }
        }

        $_user->emailUser = $this->_getEmailUser($_user);
    }
    
    /**
     * set email user password
     * 
     * @param string $_accountId
     * @param string $_password
     * @return void
     */
    protected function _setEmailUserPassword($_accountId, $_password)
    {
        try {
            if ($this->_manageImapEmailUser) {
                $this->_imapUserBackend->setPassword($_accountId, $_password);
            }
    
            if ($this->_manageSmtpEmailUser) {
                $this->_smtpUserBackend->setPassword($_accountId, $_password);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Tried to set password of non-existant email user. user id: ' . $_accountId);
        }
    }
}
