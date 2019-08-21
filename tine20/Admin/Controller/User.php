<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        catch error (and show alert) if postfix email already exists
 * @todo        extend Tinebase_Controller_Record_Abstract
 */

/**
 * User Controller for Admin application
 *
 * @package     Admin
 * @subpackage  Controller
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
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_applicationName = 'Admin';
        
        $this->_userBackend = Tinebase_User::getInstance();
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

        $emailUser = null;
        try {
            $emailUser = Tinebase_EmailUser::getInstance();
        } catch (Tinebase_Exception_NotFound $tenf) {}

        // FIXME if you want quota info: in Tinebase_User_Plugin_LdapInterface, inspectGetUserByProperty has a second param!
        if (null !== $emailUser && ! $emailUser instanceof Tinebase_User_Plugin_LdapInterface ) {
            foreach ($result as $user) {
                $emailUser->inspectGetUserByProperty($user);
            }
        }
        
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
        
        $result = $this->_userBackend->getUsersCount($_filter);
        
        return $result;
    }
    
    /**
     * get account
     *
     * @param   string  $_accountId  account id to get
     * @return  Tinebase_Model_FullUser
     */
    public function get($_userId)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        $user = $this->_userBackend->getUserById($_userId, 'Tinebase_Model_FullUser');
        
        return $user;
    }
    
    /**
     * set account status
     *
     * @param   string $_accountId  account id
     * @param   string $_status     status to set
     * @return  integer (0 for no change, 1 for changed status)
     */
    public function setAccountStatus($_accountId, $_status)
    {
        $this->checkRight('MANAGE_ACCOUNTS');

        $user = $this->get($_accountId);
        if ($user->accountStatus !== $_status) {
            $user->accountStatus = $_status;
            $this->update($user);
            return 1;
        }

        return 0;
    }
    
    /**
     * set the password for a given account
     *
     * @param  Tinebase_Model_FullUser  $_account the account
     * @param  string                   $_password the new password
     * @param  string                   $_passwordRepeat the new password again
     * @param  bool                     $_mustChange
     * @return void
     * 
     * @todo add must change pwd info to normal tine user accounts
     */
    public function setAccountPassword(Tinebase_Model_FullUser $_account, $_password, $_passwordRepeat, $_mustChange = null)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        if ($_password != $_passwordRepeat) {
            throw new Admin_Exception("Passwords don't match.");
        }
        
        $this->_userBackend->setPassword($_account, $_password, true, $_mustChange);
        
        Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . 
            ' Set new password for user ' . $_account->accountLoginName . '. Must change:' . $_mustChange
        );
    }

    /**
     * set the pin for a given account
     *
     * @param  Tinebase_Model_FullUser  $_account the account
     * @param  string                   $_password the new password
     * @return void
     */
    public function setAccountPin(Tinebase_Model_FullUser $_account, $_password)
    {
        $this->checkRight('MANAGE_ACCOUNTS');

        $this->_userBackend->setPin($_account, $_password);

        Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ .
            ' Set new pin for user ' . $_account->accountLoginName);
    }

    /**
     * update user
     *
     * @param  Tinebase_Model_FullUser    $_user            the user
     * @param  string                     $_password        the new password
     * @param  string                     $_passwordRepeat  the new password again
     * @throws Tinebase_Exception_Backend_Database_LockTimeout
     * @throws Exception
     * 
     * @return Tinebase_Model_FullUser
     */
    public function update(Tinebase_Model_FullUser $_user, $_password = null, $_passwordRepeat = null)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $oldUser = $this->_userBackend->getUserByProperty('accountId', $_user, 'Tinebase_Model_FullUser');
        
        if ($oldUser->accountLoginName !== $_user->accountLoginName) {
            $this->_checkLoginNameExistance($_user);
        }
        $this->_checkLoginNameLength($_user);
        $this->_checkPrimaryGroupExistance($_user);
        $deactivated = false;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Update user ' . $_user->accountLoginName);
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            
            if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
                $_user->contact_id = $oldUser->contact_id;
                $contact = $this->createOrUpdateContact($_user);
                $_user->contact_id = $contact->getId();
            }
            
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($_user, 'update', $oldUser);

            $deactivated = $this->_checkAccountStatus($_user, $oldUser);
            $user = $this->_userBackend->updateUser($_user);

            // make sure primary groups is in the list of group memberships
            $currentGroups = ! isset($_user->groups)
                ? Admin_Controller_Group::getInstance()->getGroupMemberships($user->getId())
                : (array) $_user->groups;
            $groups = array_unique(array_merge(array($user->accountPrimaryGroup), $currentGroups));
            Admin_Controller_Group::getInstance()->setGroupMemberships($user, $groups);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e);
            
            if ($e instanceof Zend_Db_Statement_Exception && preg_match('/Lock wait timeout exceeded/', $e->getMessage())) {
                throw new Tinebase_Exception_Backend_Database_LockTimeout($e->getMessage());
            }
            
            throw $e;
        }

        if ($deactivated) {
            // TODO send this for blocked/expired, too? allow to configure this?
            Tinebase_User::getInstance()->sendDeactivationNotification($user);
        }
        
        // fire needed events
        $event = new Admin_Event_UpdateAccount;
        $event->account = $user;
        $event->oldAccount = $oldUser;
        Tinebase_Event::fireEvent($event);
        
        if (!empty($_password) && !empty($_passwordRepeat)) {
            $this->setAccountPassword($_user, $_password, $_passwordRepeat, FALSE);
        }

        $this->_updateCurrentUser($user);

        return $user;
    }

    /**
     * update account status if changed to enabled/disabled
     *
     * @param $_user
     * @param $_oldUser
     * @return boolean true if user is deactivated
     */
    protected function _checkAccountStatus($_user, $_oldUser)
    {
        if ($_oldUser->accountStatus !== $_user->accountStatus && in_array($_user->accountStatus, array(
                Tinebase_Model_FullUser::ACCOUNT_STATUS_ENABLED,
                Tinebase_Model_FullUser::ACCOUNT_STATUS_DISABLED,
            ))) {

            if ($_user->accountStatus === Tinebase_Model_FullUser::ACCOUNT_STATUS_DISABLED) {
                return true;
            }
        }

        return false;
    }

    /**
     * update current user in session if changed
     *
     * @param $updatedUser
     */
    protected function _updateCurrentUser($updatedUser)
    {
        $currentUser = Tinebase_Core::getUser();
        if ($currentUser->getId() === $updatedUser->getId()) {
            // update current user in session!
            Tinebase_Core::set(Tinebase_Core::USER, $updatedUser);
            Tinebase_Session::getSessionNamespace()->currentAccount = $updatedUser;
        }
    }
    
    /**
     * create user
     *
     * @param  Tinebase_Model_FullUser  $_account           the account
     * @param  string                     $_password           the new password
     * @param  string                     $_passwordRepeat  the new password again
     * @param  boolean                    $_ignorePwdPolicy
     * @return Tinebase_Model_FullUser
     */
    public function create(Tinebase_Model_FullUser $_user, $_password, $_passwordRepeat, $_ignorePwdPolicy = false)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        // avoid forging accountId, gets created in backend
        unset($_user->accountId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Create new user ' . $_user->accountLoginName);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_user->toArray(), TRUE));

        if ($_password != $_passwordRepeat) {
            throw new Admin_Exception("Passwords don't match.");
        } else if (empty($_password)) {
            $_password = '';
            $_passwordRepeat = '';
        }
        if (!$_ignorePwdPolicy) {
            Tinebase_User::getInstance()->checkPasswordPolicy($_password, $_user);
        }

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

            $this->_checkLoginNameExistance($_user);
            $this->_checkLoginNameLength($_user);
            $this->_checkPrimaryGroupExistance($_user);
        } catch (Tinebase_Exception_SystemGeneric $sytemEx) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

            throw $sytemEx;
        }  catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Exception::log($e);
            throw $e;
        }

        try {
            if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
                    $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Addressbook_Model_Contact', [
                        ['field' => 'n_fileas', 'operator' => 'equals', 'value' => $_user['accountDisplayName']]]);

                    $userContact = Addressbook_Controller_Contact::getInstance()->search($filter)->getFirstRecord();
                  
                    if($userContact === null)
                    {
                        $userContact = $this->createOrUpdateContact($_user);
                        $_user->contact_id = $userContact->getId();
                    } else{
                        $_user->contact_id = $userContact->getId();
                        $this->createOrUpdateContact($_user);
                    }
                }

                      
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($_user, 'create');
            
            $user = $this->_userBackend->addUser($_user);
            $user->imapUser = $_user->imapUser;
            
            // make sure primary groups is in the list of groupmemberships
            $groups = array_unique(array_merge(array($user->accountPrimaryGroup), (array) $_user->groups));
            Admin_Controller_Group::getInstance()->setGroupMemberships($user, $groups);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Exception::log($e);
            throw $e;
        }
        
        $event = new Admin_Event_AddAccount(array(
            'account' => $user,
            'pwd'     => $_password,
        ));
        Tinebase_Event::fireEvent($event);

        if (!empty($_password)) {
            $this->setAccountPassword($user, $_password, $_passwordRepeat);
        }

        return $user;
    }
    
    /**
     * look for user with the same login name
     * 
     * @param Tinebase_Model_FullUser $user
     * @return boolean
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _checkLoginNameExistance(Tinebase_Model_FullUser $user)
    {
        try {
            $existing = Tinebase_User::getInstance()->getUserByLoginName($user->accountLoginName);
            if ($user->getId() === NULL || $existing->getId() !== $user->getId()) {
                throw new Tinebase_Exception_SystemGeneric('Login name already exists. Please choose another one.');
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
        }
        
        return TRUE;
    }
    
    /**
     * Check login name length
     *
     * @param Tinebase_Model_FullUser $user
     * @return boolean
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _checkLoginNameLength(Tinebase_Model_FullUser $user)
    {
        $maxLoginNameLength = Tinebase_Config::getInstance()->get(Tinebase_Config::MAX_USERNAME_LENGTH);
        if (!empty($maxLoginNameLength) && strlen($user->accountLoginName) > $maxLoginNameLength) {
            throw new Tinebase_Exception_SystemGeneric('The login name exceeds the maximum length of  ' . $maxLoginNameLength . ' characters. Please choose another one.');
        }
        return TRUE;
    }
    
    /**
     * look for primary group, if it does not exist, fallback to default user group
     * 
     * @param Tinebase_Model_FullUser $user
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _checkPrimaryGroupExistance(Tinebase_Model_FullUser $user)
    {
        try {
            $group = Tinebase_Group::getInstance()->getGroupById($user->accountPrimaryGroup);
        } catch (Tinebase_Exception_Record_NotDefined $ternd) {
            $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Group with id ' . $user->accountPrimaryGroup . ' not found. Use default group (' . $defaultUserGroup->name
                . ') as primary group for ' . $user->accountLoginName);
            
            $user->accountPrimaryGroup = $defaultUserGroup->getId();
        }
    }
    
    /**
     * delete accounts
     *
     * @param   mixed $_accountIds  array of account ids
     * @return  array with success flag
     * @throws  Tinebase_Exception_Record_NotAllowed
     */
    public function delete($_accountIds)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $groupsController = Admin_Controller_Group::getInstance();
        
        foreach ((array)$_accountIds as $accountId) {
            if ($accountId === Tinebase_Core::getUser()->getId()) {
                $message = 'You are not allowed to delete yourself!';
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $message);
                throw new Tinebase_Exception_AccessDenied($message);
            }
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " about to remove user with id: {$accountId}");
            
            $oldUser = $this->get($accountId);

            $eventBefore = new Admin_Event_BeforeDeleteAccount();
            $eventBefore->account = $oldUser;
            Tinebase_Event::fireEvent($eventBefore);

            $memberships = $groupsController->getGroupMemberships($accountId);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " removing user from groups: " . print_r($memberships, true));
            
            foreach ((array)$memberships as $groupId) {
                $groupsController->removeGroupMember($groupId, $accountId);
            }
            
            $this->_userBackend->deleteUser($accountId);
        }
    }
    
    /**
     * returns all shared addressbooks
     * 
     * @return Tinebase_Record_RecordSet of shared addressbooks
     * 
     * @todo do we need to fetch ALL shared containers here (even if admin user has NO grants for them)?
     */
    public function searchSharedAddressbooks()
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        return Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), Addressbook_Model_Contact::class, Tinebase_Model_Grants::GRANT_READ, TRUE);
    }
    
    /**
     * returns default internal addressbook container
     * 
     * @return string|int ID
     */
    public static function getDefaultInternalAddressbook()
    {
        $appConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();
        if (empty($appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK])) {
            $internalAdb = Addressbook_Setup_Initialize::setDefaultInternalAddressbook();
            $internalAdbId = $internalAdb->getId();
        } else {
            $internalAdbId = $appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK];
        }
        return $internalAdbId;
    }
    
    /**
     * create or update contact in addressbook backend
     * 
     * @param Tinebase_Model_FullUser $_user
     * @param boolean $_setModlog
     * @return Addressbook_Model_Contact
     */
    public function createOrUpdateContact(Tinebase_Model_FullUser $_user, $_setModlog = true)
    {
        $contactsBackend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        $contactsBackend->setGetDisabledContacts(true);
        
        if (empty($_user->container_id)) {
            $_user->container_id = $this->getDefaultInternalAddressbook();
        }
        
        try {
            if (empty($_user->contact_id)) { // jump to catch block
                throw new Tinebase_Exception_NotFound('contact_id is empty');
            }

            /** @var Addressbook_Model_Contact $contact */
            $contact = $contactsBackend->get($_user->contact_id);
            
            // update exisiting contact
            $contact->n_family   = $_user->accountLastName;
            $contact->n_given    = $_user->accountFirstName;
            $contact->n_fn       = $_user->accountFullName;
            $contact->n_fileas   = $_user->accountDisplayName;
            $contact->email      = $_user->accountEmailAddress;
            $contact->type       = Addressbook_Model_Contact::CONTACTTYPE_USER;
            $contact->container_id = $_user->container_id;

            unset($contact->jpegphoto);

            if ($_setModlog) {
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($contact, 'update');
            }
            
            $contact = $contactsBackend->update($contact);
            
        } catch (Tinebase_Exception_NotFound $tenf) {
            // add new contact
            $contact = new Addressbook_Model_Contact(array(
                'n_family'      => $_user->accountLastName,
                'n_given'       => $_user->accountFirstName,
                'n_fn'          => $_user->accountFullName,
                'n_fileas'      => $_user->accountDisplayName,
                'email'         => $_user->accountEmailAddress,
                'type'          => Addressbook_Model_Contact::CONTACTTYPE_USER,
                'container_id'  => $_user->container_id
            ));

            if ($_setModlog) {
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($contact, 'create');
            }
    
            $contact = $contactsBackend->create($contact);
        }
        
        return $contact;
    }
}
