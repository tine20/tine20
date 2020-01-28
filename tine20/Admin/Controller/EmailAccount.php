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

        $currentAccount = $this->get($_record->getId());

        $this->_inspectBeforeUpdate($_record, $currentAccount);
        $account = $this->_backend->update($_record);
        $this->_inspectAfterUpdate($account, $_record, $currentAccount);
        
        return $account;
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
        if ($_record->email !== $_oldRecord->email && $_record->type === Felamimail_Model_Account::TYPE_SYSTEM) {
            // change user email address
            $user = Admin_Controller_User::getInstance()->get($_record->user_id);
            $user->accountEmailAddress = $_record->email;
            Admin_Controller_User::getInstance()->update($user);
        }
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
        if ($this->_backend->doConvertToShared($updatedRecord, $currentRecord, false)) {
            // update user (don't delete email account!)
            $userId = is_array($currentRecord->user_id) ? $currentRecord->user_id['accountId'] :  $currentRecord->user_id;
            $user = Admin_Controller_User::getInstance()->get($userId);
            $user->accountEmailAddress = '';
            Admin_Controller_User::getInstance()->updateUserWithoutEmailPluginUpdate($user);
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
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
            . __LINE__ . ' Account id: ' . $_accountId);

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
}
