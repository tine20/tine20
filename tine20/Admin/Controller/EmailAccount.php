<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * EmailAccount Controller for Admin application
 *
 * just a wrapper for Felamimail_Controller_Account with additional admin acl
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_EmailAccount extends Tinebase_Controller_Record_Abstract
{
    /**
     * application backend class
     *
     * @var Felamimail_Controller_Account
     */
    protected $_backend;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_applicationName       = 'Admin';
        $this->_modelName             = 'Felamimail_Model_Account';
        $this->_purgeRecords          = false;

        // we need to avoid that anybody else gets this instance ... as it has acl turned off!
        Felamimail_Controller_Account::destroyInstance();
        $this->_backend = Felamimail_Controller_Account::getInstance();
        $this->_backend->doContainerACLChecks(false);
        // unset internal reference to prevent others to get instance without acl
        Felamimail_Controller_Account::destroyInstance();
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
     * @var Admin_Controller_EmailAccount
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Admin_Controller_EmailAccount
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_EmailAccount;
        }
        
        return self::$_instance;
    }

    /**
     * get by id
     *
     * @param string $_id
     * @param int $_EmailAccountId
     * @param bool         $_getRelatedData
     * @param bool $_getDeleted
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_AccessDenied
     */
    public function get($_id, $_EmailAccountId = NULL, $_getRelatedData = TRUE, $_getDeleted = FALSE, $_aclProtect = true)
    {
        $this->_checkRight('get');
        
        return $this->_backend->get($_id);
    }

    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $this->_checkRight('get');

        $result = $this->_backend->search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
        // we need to unset the accounts grants to make the admin grid actions work for all accounts
        $result->account_grants = null;
        $this->resolveAccountEmailUsers($result);

        return $result;
    }

    /**
     * Gets total count of search with $_filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        $this->_checkRight('get');

        return $this->_backend->searchCount($_filter, $_action);
    }

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record, $_duplicateCheck = true)
    {
        $this->_checkRight('create');

        $account = $this->_backend->create($_record);
        $this->_inspectAfterCreate($account, $_record);
        
        return $account;
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   array $_additionalArguments
     * @return  Tinebase_Record_Interface
     */
    public function update(Tinebase_Record_Interface $_record, $_additionalArguments = array(), $_updateDeleted = false)
    {
        $this->_checkRight('update');

        $currentAccount = $this->get($_record->getId(), null, true, $_updateDeleted);

        $raii = false;
        if (Tinebase_EmailUser::sieveBackendSupportsMasterPassword($_record)) {
            $raii = Tinebase_EmailUser::prepareAccountForSieveAdminAccess($_record->getId());
        }

        $this->_inspectBeforeUpdate($_record, $currentAccount);
        $account = $this->_backend->update($_record);
        $this->_inspectAfterUpdate($account, $_record, $currentAccount);

        if ($raii && Tinebase_EmailUser::sieveBackendSupportsMasterPassword($_record)) {
            Tinebase_EmailUser::removeSieveAdminAccess();
            unset($raii);
        }

        return $account;
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param   Felamimail_Model_Account $_createdRecord
     * @param   Felamimail_Model_Account $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        $this->updateAccountEmailUsers($_record);
        $this->resolveAccountEmailUsers($_createdRecord);
        Felamimail_Controller_Account::getInstance()->checkEmailAccountContact($_createdRecord);
    }

    /**
     * inspect update of one record
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        // if user of email account changes and if migration checkbox is checked, it needs to be unchecked
        if ($_record->user_id !== $_oldRecord->user_id && $_record->type === Felamimail_Model_Account::TYPE_USER) {
            $_record->migration_approved = false;
        }
        
        if ($_record->email !== $_oldRecord->email && $_record->type === Felamimail_Model_Account::TYPE_SYSTEM) {
            // change user email address
            $user = Admin_Controller_User::getInstance()->get($_record->user_id);
            $user->accountEmailAddress = $_record->email;
            Admin_Controller_User::getInstance()->update($user);
        }
        $this->updateAccountEmailUsers($_record);
    }

    /**
     * inspect update of one record (after update)
     *
     * @param   Felamimail_Model_Account $updatedRecord   the just updated record
     * @param   Felamimail_Model_Account $record          the update record
     * @param   Felamimail_Model_Account $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        if ($currentRecord->type === Felamimail_Model_Account::TYPE_SYSTEM
            && (   $this->_backend->doConvertToShared($updatedRecord, $currentRecord, false)
                || $this->_backend->doConvertToUserInternal($updatedRecord, $currentRecord, false)
            )
        ) {
            // update user (don't delete email account!)
            $userId = is_array($currentRecord->user_id) ? $currentRecord->user_id['accountId'] :  $currentRecord->user_id;
            $user = Admin_Controller_User::getInstance()->get($userId);
            $user->accountEmailAddress = '';
            // remove xprops from user
            Tinebase_EmailUser_XpropsFacade::setXprops($user, null, false);
            Admin_Controller_User::getInstance()->updateUserWithoutEmailPluginUpdate($user);
        } else {
            $this->resolveAccountEmailUsers($updatedRecord);
        }
    }

    /**
     * Deletes a set of records.
     * 
     * If one of the records could not be deleted, no record is deleted
     * 
     * @param   array array of record identifiers
     * @return void
     */
    public function delete($_ids)
    {
        $this->_checkRight('delete');

        $this->_deleteEmailAccountContact($_ids);
        $records = $this->_backend->delete($_ids);
        foreach ($records as $record) {
            if ($record->type === Tinebase_EmailUser_Model_Account::TYPE_ADB_LIST) {
                $event = new Admin_Event_DeleteMailingList();
                $event->listId = $record->user_id;
                Tinebase_Event::fireEvent($event);
            }
        }
    }

    /**
     * check if user has the right to manage EmailAccounts
     * 
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        switch ($_action) {
            case 'get':
                $this->checkRight(Admin_Acl_Rights::VIEW_EMAILACCOUNTS);
                break;
            case 'create':
            case 'update':
            case 'delete':
                $this->checkRight(Admin_Acl_Rights::MANAGE_EMAILACCOUNTS);
                break;
            default;
               break;
        }

        parent::_checkRight($_action);
    }

    /**
     * @param Tinebase_Model_User|string|null $user
     * @return Felamimail_Model_Account|null
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function getSystemAccount($user)
    {
        return $this->_backend->getSystemAccount($user);
    }

    /**
     * @param Felamimail_Model_Account $account
     */
    public function updateAccountEmailUsers(Felamimail_Model_Account $account)
    {
        $this->checkRight('MANAGE_ACCOUNTS');

        // set emailUserId im xprops if not set
        if (! Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS}) {
            return;
        }

        if (isset($account['email_imap_user'])) {
            $fullUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($account);
            $newFullUser = clone($fullUser);

            $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
            $emailUserBackend->updateUser($fullUser, $newFullUser);
        }
    }

    /**
     * set emailUserId im xprops if not set
     *
     * @param Tinebase_Record_RecordSet|Felamimail_Model_Account $_records
     */
    public function resolveAccountEmailUsers($_records)
    {
        if (! Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS}) {
            return;
        }

        $_records = $_records instanceof Tinebase_Record_RecordSet ? $_records : [$_records];

        foreach ($_records as $_record) {
            if (!isset($_record->xprops()[Felamimail_Model_Account::XPROP_EMAIL_USERID_IMAP])) {
                try {
                    $user = Tinebase_User::getInstance()->getFullUserById($_record->user_id);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                        __METHOD__ . '::' . __LINE__ . ' ' . $tenf);
                    continue;
                }
                if (!isset($user->xprops()[Tinebase_Model_FullUser::XPROP_EMAIL_USERID_IMAP])) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                        __METHOD__ . '::' . __LINE__ . ' User has no XPROP_EMAIL_USERID_IMAP ...');
                    continue;
                }
                Tinebase_EmailUser_XpropsFacade::setXprops($_record,
                    $user->xprops()[Tinebase_Model_FullUser::XPROP_EMAIL_USERID_IMAP], false);
            }

            $fullUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($_record);

            $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
            $smtpUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);

            if (method_exists($emailUserBackend, 'getEmailuser')) {
                $_record->email_imap_user = $emailUserBackend->getEmailuser($fullUser)->toArray();
            }

            if (method_exists($smtpUserBackend, 'getEmailuser')) {
                $_record->email_smtp_user = $smtpUserBackend->getEmailuser($fullUser)->toArray();
            }
        }
    }

    /**
     * remove one groupmember from the group
     *
     * @return void
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_AccessDenied
     */
    public function _deleteEmailAccountContact($ids)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        try {
            $ids = is_array($ids) ? $ids : [$ids];

            foreach ($ids as $id) {
                $emailAccount = $id instanceof Felamimail_Model_Account ? $id : $this->get($id);

                if ($emailAccount->type === Felamimail_Model_Account::TYPE_SHARED ||
                    $emailAccount->type === Felamimail_Model_Account::TYPE_USER ||
                    $emailAccount->type === Felamimail_Model_Account::TYPE_USER_INTERNAL ||
                    $emailAccount->type === Felamimail_Model_Account::TYPE_ADB_LIST) {

                    if (!empty($emailAccount->contact_id)) {
                        try {
                            $contact = Addressbook_Controller_Contact::getInstance()->get($emailAccount->contact_id);
                            // hard delete contact in admin module
                            $contactsBackend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
                            $contactsBackend->delete($contact->getId());
                        } catch (Exception $e) {
                            continue;
                        }
                    }
                }
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
    }
}
