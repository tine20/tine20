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
    protected $_masterUser = null;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_applicationName       = 'Admin';
        $this->_modelName             = 'Felamimail_Model_Account';
        $this->_doEmailAccountACLChecks = false;
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
    public function get($_id, $_EmailAccountId = NULL, $_getRelatedData = TRUE, $_getDeleted = FALSE)
    {
        $this->_checkRight('get');
        
        $account = $this->_backend->get($_id);

        return $account;
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

        return $this->_backend->search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
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

        if ($_record->type === Felamimail_Model_Account::TYPE_USER) {
            // remove password for "user" accounts
            unset($_record->password);
        }

        $account = $this->_backend->create($_record);

        return $account;
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   array $_additionalArguments
     * @return  Tinebase_Record_Interface
     */
    public function update(Tinebase_Record_Interface $_record, $_additionalArguments = array())
    {
        $this->_checkRight('update');

        if ($_record->type === Felamimail_Model_Account::TYPE_USER) {
            // remove password for "user" accounts
            unset($_record->password);
        }

        $account = $this->_backend->update($_record);
        
        return $account;
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

        $this->_backend->delete($_ids);
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
     * @param string $_accountId
     * @param string $_rightToCheck
     * @return Tinebase_RAII
     */
    public function prepareAccountForSieveAdminAccess($_accountId, $_rightToCheck = Admin_Acl_Rights::VIEW_EMAILACCOUNTS)
    {
        Admin_Controller_EmailAccount::getInstance()->checkRight($_rightToCheck);

        $oldAccountAcl = Felamimail_Controller_Account::getInstance()->doContainerACLChecks(false);
        $oldSieveAcl = Felamimail_Controller_Sieve::getInstance()->doAclCheck(false);

        $raii = new Tinebase_RAII(function() use($oldAccountAcl, $oldSieveAcl) {
            Felamimail_Controller_Account::getInstance()->doContainerACLChecks($oldAccountAcl);
            Felamimail_Controller_Sieve::getInstance()->doAclCheck($oldSieveAcl);
        });

        $account = $this->get($_accountId);

        // create sieve master user account here
        try {
            $this->_setSieveMasterPassword($account);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::'
                . __LINE__ . ' ' . $tenf->getMessage());
        }

        // sieve login
        Felamimail_Backend_SieveFactory::factory($account);

        return $raii;
    }

    protected function _setSieveMasterPassword(Felamimail_Model_Account $account)
    {
        $this->_masterUser = Tinebase_Record_Abstract::generateUID(8);
        $account->user = $this->_getAccountUsername($account);
        $account->password = Tinebase_Record_Abstract::generateUID(20);
        $imapEmailBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        if (method_exists($imapEmailBackend, 'setMasterPassword')) {
            $imapEmailBackend->setMasterPassword($this->_masterUser, $account->password);
        }
    }

    protected function _getAccountUsername($account)
    {
        $imapEmailBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $imapLoginname = $imapEmailBackend->getLoginName($account->user_id, null, $account->email);
        return $imapLoginname . '*' . $this->_masterUser;
    }

    public function removeSieveAdminAccess()
    {
        $imapEmailBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        if (method_exists($imapEmailBackend, 'removeMasterPassword')) {
            $imapEmailBackend->removeMasterPassword($this->_masterUser);
        }
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
     * @param Felamimail_Model_Account $_account
     * @param $_to
     * @throws Tinebase_Exception_SystemGeneric
     * @return Tinebase_Record_Interface
     */
    public function convertEmailAccount(Felamimail_Model_Account $_account, $_to = Felamimail_Model_Account::TYPE_SHARED)
    {
        if (! in_array($_account->type, [
            Felamimail_Model_Account::TYPE_SYSTEM,
            Felamimail_Model_Account::TYPE_USER_INTERNAL
        ])) {
            throw new Tinebase_Exception_SystemGeneric('It is only allowed to convert SYSTEM email accounts');
        }

        $userId = is_array($_account->user_id) ? $_account->user_id['accountId'] :  $_account->user_id;
        $user = Admin_Controller_User::getInstance()->get($userId);

        // convert account
        $_account->type = $_to;
        // keep old user grants
        // $account->grants = [];

        // make sure, shared credential cache is created - password is needed!
        $account = $this->_backend->update($_account);

        // update user (don't delete email account!)
        $user->accountEmailAddress = '';
        Admin_Controller_User::getInstance()->updateUserWithoutEmailPluginUpdate($user);

        return $account;
    }
}
