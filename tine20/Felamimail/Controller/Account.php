<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Account controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Account extends Tinebase_Controller_Record_Grants
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * if imap config useSystemAccount is active
     *
     * @var boolean
     */
    protected $_useSystemAccount = FALSE;
    
    /**
     * imap config
     * 
     * @var array
     */
    protected $_imapConfig = array();
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Account
     */
    private static $_instance = NULL;
    
    /**
     * @var Felamimail_Backend_Account
     */
    protected $_backend;

    const ACCOUNT_CAPABILITIES_CACHEID = 'Felamimail_Account_Capabilities';

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_modelName = Felamimail_Model_Account::class;
        $this->_doContainerACLChecks = true;
        $this->_doRightChecks = true;
        $this->_purgeRecords = false;
        
        $this->_backend = new Felamimail_Backend_Account();
        
        $this->setImapConfig();
        $this->_useSystemAccount = isset($this->_imapConfig[Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT])
            && $this->_imapConfig[Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT];

        $this->_grantsModel = Felamimail_Model_AccountGrants::class;
        $this->_grantsBackend = new Tinebase_Backend_Sql_Grants([
            'modelName' => $this->_grantsModel,
            'tableName' => 'felamimail_account_acl',
            'recordTable' => $this->_backend->getTableName(),
        ]);
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_Account
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Account();
        }
        
        return self::$_instance;
    }

    public static function destroyInstance()
    {
        self::$_instance = null;
    }

    public function setImapConfig()
    {
        $this->_imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
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
        if ($_filter === NULL) {
            $_filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class);
        }
        
        $result = parent::search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);

        // check preference / config if we should add system account with tine user credentials or from config.inc.php
        if ($this->_useSystemAccount && ! $_onlyIds) {
            // check if resultset contains system account and add config values
            foreach ($result as $account) {
                if ($account->type === Felamimail_Model_Account::TYPE_SYSTEM) {
                    $this->addSystemAccountConfigValues($account);
                }
            }
        }
        
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
        $this->checkFilterACL($_filter, $_action);
        return $this->_backend->searchCount($_filter);
    }
    
    /**
     * get by id
     *
     * @param string $_id
     * @param int $_containerId
     * @return Felamimail_Model_Account
     */
    public function get($_id, $_containerId = NULL, $_getRelatedData = true, $_getDeleted = false)
    {
        /** @var Felamimail_Model_Account $record */
        $record = parent::get($_id, $_containerId, $_getRelatedData, $_getDeleted);
        
        if ($record->type === Felamimail_Model_Account::TYPE_SYSTEM) {
            $this->addSystemAccountConfigValues($record);
        }

        return $record;
    }

    /**
     * Deletes a set of records.
     * 
     * @param   array array of record identifiers
     * @return  void
     */
    public function delete($_ids)
    {
        parent::delete($_ids);
        
        // check if default account got deleted and set new default account
        if (in_array(Tinebase_Core::getPreference($this->_applicationName)->{Felamimail_Preference::DEFAULTACCOUNT}, (array) $_ids)) {
            $accounts = $this->search();
            $defaultAccountId = (count($accounts) > 0) ? $accounts->getFirstRecord()->getId() : '';
            
            Tinebase_Core::getPreference($this->_applicationName)->{Felamimail_Preference::DEFAULTACCOUNT} = $defaultAccountId;
        }
    }
    
    /**
     * Removes accounts where current user has no access to
     * 
     * @param Felamimail_Model_AccountFilter $_filter
     * @param string $_action get|update
     * @throws Tinebase_Exception_AccessDenied
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        if (! $_filter instanceof Felamimail_Model_AccountFilter) {
            throw new Tinebase_Exception_UnexpectedValue('expected filter of class ' .
                Felamimail_Model_AccountFilter::class);
        }
        
        if (! $this->doContainerACLChecks()) {
            $_filter->doIgnoreAcl(true);
            return;
        }

        $_filter->doIgnoreAcl(false);

        parent::checkFilterACL($_filter, $_action);
    }

    /**
     * inspect creation of one record
     * - add credentials and user id here
     * 
     * @param   Felamimail_Model_Account $_record
     * @return  void
     * @throws Tinebase_Exception_UnexpectedValue
     * @throws Tinebase_Exception_PasswordPolicyViolation
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        // add user id
        if (empty($_record->user_id) && ($_record->type === Felamimail_Model_Account::TYPE_USER ||
                $_record->type === Felamimail_Model_Account::TYPE_SYSTEM)) {
            $_record->user_id = Tinebase_Core::getUser()->getId();
        } elseif (is_array($_record->user_id)) {
            // TODO move to converter
            $_record->user_id = $_record->user_id['accountId'];
        }
        
        // use the imap host as smtp host if empty
        if (! $_record->smtp_hostname) {
            $_record->smtp_hostname = $_record->host;
        }

        $this->_checkSignature($_record);

        switch ($_record->type) {
            case Felamimail_Model_Account::TYPE_SYSTEM:
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                    __LINE__ . ' system account, no credential cache needed');
                return;
            case Felamimail_Model_Account::TYPE_SHARED:
            case Felamimail_Model_Account::TYPE_ADB_LIST:
                $this->_createSharedEmailUserAndCredentials($_record);
                break;
            case Felamimail_Model_Account::TYPE_USER_INTERNAL:
                $this->_createUserInternalEmailUser($_record);
                // we dont need a credential cache here neither
                return;
            default:
                if (! $_record->user || ! $_record->password) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__
                        . '::' . __LINE__ . ' No username or password given for new account.');
                    return;
                }
                $this->_createUserCredentials($_record);
        }
    }

    protected function _createSharedEmailUserAndCredentials($_record)
    {
        $translation = Tinebase_Translation::getTranslation($this->_applicationName);

        if (! $_record->password) {
            throw new Felamimail_Exception_PasswordMissing($translation->_('shared / adb_list accounts need to have a password set'));
        }
        if (! $_record->email) {
            throw new Tinebase_Exception_UnexpectedValue($translation->_('shared / adb_list accounts need to have an email set'));
        }

        $this->_createSharedEmailUser($_record);
        $this->_createOrUpdateSharedCredentials($_record);
    }

    protected function _createOrUpdateSharedCredentials($account)
    {
        $account->credentials_id = $this->_createSharedCredentials($account->user, $account->password);
        if ($account->smtp_user && $account->smtp_password) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Create SMTP credentials.');
            }
            $account->smtp_credentials_id = $this->_createSharedCredentials($account->smtp_user, $account->smtp_password);
        } else {
            $account->smtp_credentials_id = $account->credentials_id;
        }
    }

    /**
     * @param Felamimail_Model_Account $_record
     * @throws Tinebase_Exception_SystemGeneric
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _createSharedEmailUser($_record)
    {
        $userId = $_record->user_id ? $_record->user_id : Tinebase_Record_Abstract::generateUID();
        if (Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS}) {
            Tinebase_EmailUser_XpropsFacade::setXprops($_record, $userId);
        } else {
            if ($_record->type === Felamimail_Model_Account::TYPE_ADB_LIST) {
                throw new Tinebase_Exception_InvalidArgument(
                    'it is not possible to create MAILINGLIST accounts without EMAIL_USER_ID_IN_XPROPS config');
            }
            $_record->user_id = $userId;
        }

        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $_record->user = $emailUserBackend->getLoginName($userId, $_record->email, $_record->email);

        Felamimail_Controller_Account::getInstance()->addSystemAccountConfigValues($_record);

        $user = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($_record);
        $this->_checkIfEmailUserExists($user);

        $emailUserBackend->inspectAddUser($user, $user);
        Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP)->inspectAddUser($user, $user);
    }

    /**
     * @param Felamimail_Model_Account $_record
     * @throws Tinebase_Exception_NotImplemented
     */
    protected function _createUserInternalEmailUser($_record)
    {
        $this->_createUserInternalEmailUserCheckPreconditions($_record);
        $this->_createUserInternalEmailUserFromSystemUser($_record);
    }

    /**
     * @param Felamimail_Model_Account $_record
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _createUserInternalEmailUserCheckPreconditions($_record)
    {
        $translation = Tinebase_Translation::getTranslation($this->_applicationName);

        if (!Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS}) {
            // this leads to major problems otherwise ...
            throw new Tinebase_Exception_UnexpectedValue(
                $translation->_('userInternal accounts are only allowed with Tinebase_Config::EMAIL_USER_ID_IN_XPROPS'));
        }
        if (!$_record->email) {
            throw new Tinebase_Exception_UnexpectedValue($translation->_('userInternal accounts need to have an email set'));
        }
        if (!$_record->user_id) {
            throw new Tinebase_Exception_UnexpectedValue($translation->_('userInternal accounts need to have an user_id set'));
        }

        $user = Tinebase_User::getInstance()->getFullUserById($_record->user_id);
        if ($user->accountEmailAddress === $_record->email) {
            throw new Tinebase_Exception_UnexpectedValue(
                $translation->_('Please choose a new email address for userInternal accounts'));
        }
    }

    /**
     * @param Tinebase_Model_FullUser|string $user
     * @return Tinebase_Model_FullUser
     */
    protected function _getEmailSystemUser($user)
    {
        $user = is_string($user) ? Tinebase_User::getInstance()->getFullUserById($user) : $user;
        $systemEmailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($user);
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);

        // make sure that system account exists before copy
        if (! $emailUserBackend->userExists($systemEmailUser)) {
            throw new Tinebase_Exception_UnexpectedValue($translation->_('system account for user does not exist'));
        }

        return $systemEmailUser;
    }

    /**
     * @param Felamimail_Model_Account $_record
     * @throws Tinebase_Exception
     * @throws Zend_Db_Statement_Exception
     */
    protected function _createUserInternalEmailUserFromSystemUser($_record)
    {
        $user = Tinebase_User::getInstance()->getFullUserById($_record->user_id);

        $systemEmailUser = $this->_getEmailSystemUser($user);

        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $newEmailAddress = $_record->email;

        Tinebase_EmailUser_XpropsFacade::setXprops($_record);
        $newEmailUserId = self::getUserInternalEmailUserId($_record);
        $newEmailUserUsername = $emailUserBackend->getLoginName($newEmailUserId, $user->accountLoginName, $_record->email);
        $newEmailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($user, [
            'user_id' => $newEmailUserId,
        ]);
        // need to set new email address + login name for copyUser
        $systemEmailUser->accountEmailAddress = $newEmailAddress;
        $systemEmailUser->accountLoginName = $newEmailUser->accountLoginName = $newEmailUserUsername;
        $newEmailUser->accountEmailAddress = $_record->email;
        Felamimail_Controller_Account::getInstance()->addSystemAccountConfigValues($_record, $newEmailUser);
        // FIXME email is overwritten by addSystemAccountConfigValues
        $_record->email = $newEmailAddress;

        // copy emailuser accounts (with new email address)
        /** @var Tinebase_EmailUser_Sql $emailUserBackend */
        $emailUserBackend->copyUser($systemEmailUser, $newEmailUserId);
        $smtpEmailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
        try {
            $smtpEmailUserBackend->copyUser($systemEmailUser, $newEmailUserId);
        } catch (Exception $e) {
            if (Tinebase_Exception::isDbDuplicate($e)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                }
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param $account
     * @param string $xprop
     * @return string
     */
    public static function getUserInternalEmailUserId($account, $xprop = Tinebase_EmailUser_XpropsFacade::XPROP_EMAIL_USERID_IMAP)
    {
        if (Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS}) {
            return Tinebase_EmailUser_XpropsFacade::getEmailUserId($account, $xprop);
        } else {
            return substr($account->user_id, 0, 32) . '#~#' . substr($account->getId(), 0, 5);
        }
    }

    /**
     * delete linked objects (notes, relations, attachments, alarms) of record
     *
     * @param Felamimail_Model_Account $_record
     */
    protected function _deleteLinkedObjects(Tinebase_Record_Interface $_record)
    {
        parent::_deleteLinkedObjects($_record);

        if ($_record->type === Felamimail_Model_Account::TYPE_ADB_LIST || $_record->type ===
                Felamimail_Model_Account::TYPE_SHARED || $_record->type ===
                Felamimail_Model_Account::TYPE_USER_INTERNAL) {
            $_record->resolveCredentials(false);
            Tinebase_EmailUser_XpropsFacade::deleteEmailUsers($_record);
        }
    }

    protected function _createUserCredentials($_record)
    {
        if ($_record->user_id === Tinebase_Core::getUser()->getId()) {
            $_record->credentials_id = $this->_createCredentials($_record->user, $_record->password);
            if ($_record->smtp_user && $_record->smtp_password) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Create SMTP credentials.');
                }
                $_record->smtp_credentials_id = $this->_createCredentials($_record->smtp_user, $_record->smtp_password);
            } else {
                $_record->smtp_credentials_id = $_record->credentials_id;
            }
        } else if (! $this->doContainerACLChecks()) {
            // created shared credentials in admin mode
            $this->_createOrUpdateSharedCredentials($_record);
        } else {
            throw new Tinebase_Exception_AccessDenied('it is not allowed to change user account credentials');
        }
    }

    /**
     * add default grants
     *
     * @param   Tinebase_Record_Interface $record
     * @param   boolean $addDuringSetup -> let admin group have all rights instead of user
     */
    protected function _setDefaultGrants(Tinebase_Record_Interface $record, $addDuringSetup = false)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Setting default grants ...');

        $record->grants = new Tinebase_Record_RecordSet($this->_grantsModel);
        $userId = (in_array($record->type , [
            Felamimail_Model_Account::TYPE_SYSTEM,
            Felamimail_Model_Account::TYPE_USER,
            Felamimail_Model_Account::TYPE_USER_INTERNAL
        ])) ? $record->user_id : Tinebase_Core::getUser()->getId();
        /** @var Tinebase_Model_Grants $grant */
        $grant = new $this->_grantsModel([
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'account_id'   => $userId,
            'record_id'    => $record->getId(),
        ]);
        $grant->sanitizeAccountIdAndFillWithAllGrants();
        $record->grants->addRecord($grant);
    }
    
    /**
     * convert signature to text to remove all html tags and spaces/linebreaks, if the remains are empty -> set empty signature
     * 
     * @param Felamimail_Model_Account $account
     */
    protected function _checkSignature($account)
    {
        if (empty($account->signature)) {
            return;
        }
        
        $plainTextSignature = Felamimail_Message::convertFromHTMLToText($account->signature, "\n");
        if (! preg_match('/[^\s^\\n]/', $plainTextSignature, $matches)) {
            $account->signature = '';
        }
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
        if ($_createdRecord->type === Tinebase_EmailUser_Model_Account::TYPE_USER) {
            // set as default account if it is the only account
            $accountCount = $this->searchCount(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class));
            if ($accountCount == 1) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Set account ' . $_createdRecord->name . ' as new default email account.');
                }
                Tinebase_Core::getPreference($this->_applicationName)->{Felamimail_Preference::DEFAULTACCOUNT} = $_createdRecord->getId();
            }
        } else if ($_record->type === Felamimail_Model_Account::TYPE_ADB_LIST) {
            Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(function($listId, $account) {
                $sieveRule = Felamimail_Sieve_AdbList::createFromList(
                    Addressbook_Controller_List::getInstance()->get($listId))->__toString();

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                    __LINE__ . ' add sieve script: ' . $sieveRule);

                Felamimail_Controller_Sieve::getInstance()->setAdbListScript($account,
                    Felamimail_Model_Sieve_ScriptPart::createFromString(
                        Felamimail_Model_Sieve_ScriptPart::TYPE_ADB_LIST, $listId, $sieveRule));
            }, [$_record->user_id, $_createdRecord]);

        }
    }

    protected function _checkIfEmailUserExists($user)
    {
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
        if ($emailUserBackend->emailAddressExists($user)) {
            throw new Tinebase_Exception_SystemGeneric('email account already exists');
        }
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
        // TODO move to converter
        if (is_array($_record->user_id)) {
            $_record->user_id = $_record->user_id['accountId'];
        }

        switch ($_record->type) {
            case Felamimail_Model_Account::TYPE_SYSTEM:
                $this->_beforeUpdateSystemAccount($_record, $_oldRecord);
                break;
            case Felamimail_Model_Account::TYPE_SHARED:
            case Felamimail_Model_Account::TYPE_ADB_LIST:
                $this->_beforeUpdateSharedAccount($_record, $_oldRecord);
                break;
            case Felamimail_Model_Account::TYPE_USER_INTERNAL:
                $this->_beforeUpdateUserInternalAccount($_record, $_oldRecord);
                break;
            default:
                $this->_beforeUpdateStandardAccount($_record, $_oldRecord);
        }
        $this->_checkSignature($_record);
    }

    /**
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface $_oldRecord
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _beforeUpdateSharedAccount($_record, $_oldRecord)
    {
        if ($this->doConvertToShared($_record, $_oldRecord)) {
            $this->_convertToShared($_record, $_oldRecord);
        } else if (empty($_record->user_id)) {
            // prevent overwriting of user_id - client might send user_id = null for shared accounts
            // as the user_id is not a real user ...
            $_record->user_id = $_oldRecord->user_id;
        }

        if ($_oldRecord->email !== $_record->email) {
            // check only with email address!
            $user = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($_record, [], false);
            $this->_checkIfEmailUserExists($user);
            Tinebase_EmailUser_XpropsFacade::updateEmailUsers($_record);
        }

        if (! empty($_record->password)) {
            $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
            $user = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($_record);
            // always set defined username
            $_record->user = $emailUserBackend->getLoginName($user->getId(), $_record->email, $_record->email);
            $this->_beforeUpdateSharedAccountCredentials($_record, $_oldRecord);
            Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP)->inspectSetPassword($user->getId(), $_record->password);
        }
    }

    protected function _beforeUpdateUserInternalAccount($_record, $_oldRecord)
    {
        if ($this->doConvertToUserInternal($_record, $_oldRecord)) {
            $this->_convertToUserInternal($_record, $_oldRecord);
        }
    }

    public function doConvertToShared($_record, $_oldRecord, $_throw = true)
    {
        return $this->_doConvertTo(
            $_record,
            $_oldRecord,
            $_throw,
            [
                Felamimail_Model_Account::TYPE_SYSTEM,
                Felamimail_Model_Account::TYPE_USER_INTERNAL,
            ],
            Felamimail_Model_Account::TYPE_SHARED
        );
    }

    public function doConvertToUserInternal($_record, $_oldRecord, $_throw = true)
    {
        return $this->_doConvertTo(
            $_record,
            $_oldRecord,
            $_throw,
            [
                Felamimail_Model_Account::TYPE_SHARED,
            ],
            Felamimail_Model_Account::TYPE_USER_INTERNAL
        );
    }

    /**
     * @param $_record
     * @param $_oldRecord
     * @param $_throw
     * @param $_from
     * @param $_to
     * @return bool
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _doConvertTo($_record, $_oldRecord, $_throw, $_from, $_to)
    {
        $result = ($_record->type === $_to && in_array($_oldRecord->type, $_from));

        if ($_throw && $_record->type !== $_oldRecord->type && ! $result) {
            throw new Tinebase_Exception_UnexpectedValue('type can not change');
        }

        if ($result) {
            if (! Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS}) {
                $translate = Tinebase_Translation::getTranslation('Felamimail');
                throw new Tinebase_Exception_SystemGeneric(
                    $translate->_('Config EMAIL_USER_ID_IN_XPROPS is not enabled! Please check if your email user backend supports it and run CLI method Tinebase.emailUserIdInXprops'));
            }
        }

        return $result;
    }

    /**
     * @param $_record
     * @param $_oldRecord
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _convertToShared($_record, $_oldRecord)
    {
        if (! $_record->migration_approved) {
            $translate = Tinebase_Translation::getTranslation('Felamimail');
            throw new Tinebase_Exception_SystemGeneric($translate->_('Migration of this account has not been approved!'));
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Convert account id ' . $_record->getId() . ' to ' . $_record->type
            . ' ... Set new shared credential cache and update email user password');

        $this->_transferEmailUserIdXprops($_record, $_oldRecord);

        if (! isset($_record->xprops()[Felamimail_Model_Account::XPROP_EMAIL_USERID_IMAP])) {
            throw new Tinebase_Exception_SystemGeneric($translate->_('Could not find email user xprops!'));
        }

        /** @var Tinebase_EmailUser_Sql $emailUserBackend */
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $emailUserId = Tinebase_EmailUser_XpropsFacade::getEmailUserId($_record);
        $emailUserBackend->inspectSetPassword($emailUserId, $_record->password);

        $_record->user = $emailUserBackend->getLoginName($emailUserId, $_record->email, $_record->email);
        $_record->credentials_id = $this->_createSharedCredentials($_record->user, $_record->password);
        $_record->smtp_credentials_id = $_record->credentials_id;
        $_record->migration_approved = 0;

        // remove user_id if present
        $_record->user_id = null;
    }

    /**
     * get user (xprops) if missing
     *
     * @param $_record
     * @param $_oldRecord
     */
    protected function _transferEmailUserIdXprops($_record, $_oldRecord)
    {
        if (isset($_record->xprops()[Felamimail_Model_Account::XPROP_EMAIL_USERID_IMAP])) {
            return;
        }

        if (isset($_oldRecord->xprops()[Felamimail_Model_Account::XPROP_EMAIL_USERID_IMAP])) {
            Tinebase_EmailUser_XpropsFacade::setXprops($_record,
                $_oldRecord->xprops()[Tinebase_Model_FullUser::XPROP_EMAIL_USERID_IMAP], false);
        } else if ($_record->user_id) {
            $user = Tinebase_User::getInstance()->getFullUserById($_record->user_id);
            Tinebase_EmailUser_XpropsFacade::setXprops($_record,
                $user->xprops()[Tinebase_Model_FullUser::XPROP_EMAIL_USERID_IMAP], false);
        }
    }

    /**
     * @param $_record
     * @param $_oldRecord
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _convertToUserInternal($_record, $_oldRecord)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Convert account id ' . $_record->getId() . ' to ' . $_record->type
            . ' ... Set new user credential cache and remove given grants not belonging to the user');

        if (! $_record->user_id) {
            $translation = Tinebase_Translation::getTranslation('Felamimail');
            throw new Tinebase_Exception_UnexpectedValue($translation->_('userInternal accounts need to have an user_id set'));
        }

        $this->_transferEmailUserIdXprops($_record, $_oldRecord);

        // copy password from system user account
        if ($_oldRecord->type !== Felamimail_Model_Account::TYPE_SYSTEM) {
            $systemEmailUser = $this->_getEmailSystemUser($_record->user_id);
            $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($_record);
            $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
            $emailUserBackend->copyPassword($systemEmailUser, $emailUser);
        }

        // reset credential cache
        $_record->credentials_id = null;
        $_record->smtp_credentials_id = null;

        $this->_setDefaultGrants($_record);
    }
    
    /**
     * inspect update of system account
     * - only allow to update certain fields of system accounts
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _beforeUpdateSystemAccount($_record, $_oldRecord)
    {
        // only allow to update some values for system accounts
        $allowedFields = array(
            'name',
            'signatures',
            'signature_position',
            'display_format',
            'compose_format',
            'preserve_format',
            'reply_to',
            'has_children_support',
            'delimiter',
            'ns_personal',
            'ns_other',
            'ns_shared',
            'last_modified_time',
            'last_modified_by',
            'sieve_notification_email',
            'sent_folder',
            'trash_folder',
            'drafts_folder',
            'templates_folder',
            'migration_approved',
        );
        $diff = $_record->diff($_oldRecord)->diff;
        foreach ($diff as $key => $value) {
            if (! in_array($key, $allowedFields)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Setting old value in system account for key ' . $key);
                $_record->$key = $_oldRecord->$key;
            }
        } 
    }
    
    /**
     * inspect update of normal user account
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _beforeUpdateStandardAccount($_record, $_oldRecord)
    {
        if ($_record->type !== Felamimail_Model_Account::TYPE_USER_INTERNAL) {
            if ($_record->type === Felamimail_Model_Account::TYPE_USER) {
                $this->_beforeUpdateStandardAccountCredentials($_record, $_oldRecord);
            } else {
                $this->_beforeUpdateSharedAccountCredentials($_record, $_oldRecord);
            }
        }
        
        $diff = $_record->diff($_oldRecord)->diff;
        
        // delete message body cache because display format has changed
        if ((isset($diff['display_format']) || array_key_exists('display_format', $diff))) {
            Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('getMessageBody'));
        }

        // reset capabilities if imap host / port changed
        if (isset($diff['host']) || isset($diff['port'])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Resetting capabilities for account ' . $_record->name);
            $cacheId = Tinebase_Helper::convertCacheId(self::ACCOUNT_CAPABILITIES_CACHEID . '_' . $_record->getId());
            Tinebase_Core::getCache()->remove($cacheId);
        }
    }

    /**
     * update shared / adb list account credentials
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _beforeUpdateSharedAccountCredentials($_record, $_oldRecord)
    {
        // get old credentials
        $credentialsBackend = Tinebase_Auth_CredentialCache::getInstance();

        $credentials = null;
        if ($_oldRecord->credentials_id) {
            $credentials = $credentialsBackend->get($_oldRecord->credentials_id);
            $credentials->key = Tinebase_Config::getInstance()->{Tinebase_Config::CREDENTIAL_CACHE_SHARED_KEY};
            try {
                $credentialsBackend->getCachedCredentials($credentials);
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' Creating new credentials (' . $tenf->getMessage() . ')');
                $credentials = null;
            }
        }

        if (! $credentials) {
            $credentials = new Tinebase_Model_CredentialCache(array(
                'username'  => '',
                'password'  => ''
            ));
        }

        // check if something changed
        if (
            ! $_oldRecord->credentials_id
            ||  (! empty($_record->user) && $_record->user !== $credentials->username)
            ||  (! empty($_record->password) && $_record->password !== $credentials->password)
        ) {
            $newPassword = ($_record->password) ? $_record->password : $credentials->password;
            $newUsername = ($_record->user) ? $_record->user : $credentials->username;

            $_record->credentials_id = $this->_createSharedCredentials($newUsername, $newPassword);
            $imapCredentialsChanged = true;
        } else {
            $imapCredentialsChanged = false;
        }

        if ($_record->smtp_user && $_record->smtp_password) {
            // create extra smtp credentials
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Update/create SMTP credentials.');
            $_record->smtp_credentials_id = $this->_createSharedCredentials($_record->smtp_user, $_record->smtp_password);

        } else if (
            $imapCredentialsChanged
            && (! $_record->smtp_credentials_id || $_record->smtp_credentials_id == $_oldRecord->credentials_id)
        ) {
            // use imap credentials for smtp auth as well
            $_record->smtp_credentials_id = $_record->credentials_id;
        }
    }

    /**
     * update user account credentials
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _beforeUpdateStandardAccountCredentials($_record, $_oldRecord)
    {
        if ($_record->user_id !== Tinebase_Core::getUser()->getId()) {
            if ($this->doContainerACLChecks()) {
                throw new Tinebase_Exception_AccessDenied('no access to user account');
            } else if ($_record->user && $_record->password) {
                // created shared credentials in admin mode
                $this->_createOrUpdateSharedCredentials($_record);
                return;
            }
        }

        // get old credentials
        $credentialsBackend = Tinebase_Auth_CredentialCache::getInstance();
        $userCredentialCache = Tinebase_Core::getUserCredentialCache();
        
        if ($userCredentialCache !== NULL) {
            $credentialsBackend->getCachedCredentials($userCredentialCache);
        } else {
            Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ 
                . ' Something went wrong with the CredentialsCache / use given username/password instead.'
            );
            return;
        }

        $credentials = null;
        if ($_oldRecord->credentials_id) {
            $credentials = $credentialsBackend->get($_oldRecord->credentials_id);
            $credentials->key = substr($userCredentialCache->password, 0, 24);
            try {
                $credentialsBackend->getCachedCredentials($credentials);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // create new credentials in this case
                Tinebase_Exception::log($tenf);
                $credentials = null;
            }
        }

        if (! $credentials) {
            $credentials = new Tinebase_Model_CredentialCache(array(
                'username'  => '',
                'password'  => ''
            ));
        }
        
        // check if something changed
        if (
            ! $_oldRecord->credentials_id
            ||  (! empty($_record->user) && $_record->user !== $credentials->username)
            ||  (! empty($_record->password) && $_record->password !== $credentials->password)
        ) {
            $newPassword = ($_record->password) ? $_record->password : $credentials->password;
            $newUsername = ($_record->user) ? $_record->user : $credentials->username;

            $_record->credentials_id = $this->_createCredentials($newUsername, $newPassword);
            $imapCredentialsChanged = TRUE;
        } else {
            $imapCredentialsChanged = FALSE;
        }
        
        if ($_record->smtp_user && $_record->smtp_password) {
            // create extra smtp credentials
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Update/create SMTP credentials.');
            $_record->smtp_credentials_id = $this->_createCredentials($_record->smtp_user, $_record->smtp_password);
            
        } else if (
            $imapCredentialsChanged 
            && (! $_record->smtp_credentials_id || $_record->smtp_credentials_id == $_oldRecord->credentials_id)
        ) {
            // use imap credentials for smtp auth as well
            $_record->smtp_credentials_id = $_record->credentials_id;
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
        if ($updatedRecord->sieve_notification_email != $currentRecord->sieve_notification_email) {
            Felamimail_Controller_Sieve::getInstance()->setNotificationEmail($updatedRecord->getId(),
                $updatedRecord->sieve_notification_email);
        }
    }
    
    /**
     * check if user has the right to manage accounts
     * 
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }
        
        switch ($_action) {
            case 'create':
                if (! Tinebase_Core::getUser()->hasRight($this->_applicationName, Felamimail_Acl_Rights::ADD_ACCOUNTS)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to add accounts!");
                }
                break;
            case 'update':
            case 'delete':
                if (! Tinebase_Core::getUser()->hasRight($this->_applicationName, Felamimail_Acl_Rights::MANAGE_ACCOUNTS)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to manage accounts!");
                }
                break;
            default;
               break;
        }

        parent::_checkRight($_action);
    }

    /**
     * check grant for action (CRUD)
     *
     * @param Tinebase_Record_Interface $record
     * @param string $action
     * @param boolean $throw
     * @param string $errorMessage
     * @param Tinebase_Record_Interface $oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkGrant($record, $action, $throw = true, $errorMessage = 'No Permission.', $oldRecord = null)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }

        if (Tinebase_Core::getUser()->hasRight($this->_applicationName, Felamimail_Acl_Rights::MANAGE_ACCOUNTS)) {
            return true;
        }

        return parent::_checkGrant($record, $action, $throw, $errorMessage, $oldRecord);
    }
    
    /**
     * change account password
     *
     * @param string $_accountId
     * @param string $_username
     * @param string $_password
     * @return boolean
     */
    public function changeCredentials($_accountId, $_username, $_password)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Changing credentials for account id ' . $_accountId);
        
        // get account and set pwd
        $account = $this->get($_accountId);
        
        $account->user = $_username;
        $account->password = $_password;
        
        // update account
        $this->doRightChecks(FALSE);
        $this->update($account);
        $this->doRightChecks(TRUE);
        
        return TRUE;
    }
    
    /**
     * updates all credentials of user accounts with new password
     * 
     * @param Tinebase_Model_CredentialCache $_oldUserCredentialCache old user credential cache
     */
    public function updateCredentialsOfAllUserAccounts(Tinebase_Model_CredentialCache $_oldUserCredentialCache)
    {
        Tinebase_Auth_CredentialCache::getInstance()->getCachedCredentials($_oldUserCredentialCache);
        $accounts = $this->search();
        
        foreach ($accounts as $account) {
            if ($account->type === Felamimail_Model_Account::TYPE_USER) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Updating credentials for account ' . $account->name);
                
                $imapAndSmtpAreEqual = ($account->credentials_id == $account->smtp_credentials_id);
                $credentialIdKeys = array('credentials_id', 'smtp_credentials_id');
                foreach ($credentialIdKeys as $idKey) {
                    if (! empty($account->{$idKey})) {
                        if ($idKey == 'smtp_credentials_id' && $imapAndSmtpAreEqual) {
                            $account->smtp_credentials_id = $account->credentials_id;
                        } else {
                            $oldCredentialCache = Tinebase_Auth_CredentialCache::getInstance()->get($account->{$idKey});
                            $oldCredentialCache->key = $_oldUserCredentialCache->password;
                            Tinebase_Auth_CredentialCache::getInstance()->getCachedCredentials($oldCredentialCache);
                            $account->{$idKey} = $this->_createCredentials($oldCredentialCache->username, $oldCredentialCache->password);
                        }
                    }
                }
                $this->_backend->update($account);
            }
        }
    }
    
    /**
     * get imap server capabilities and save delimiter / personal namespace in account
     *
     * - capabilities are saved in the cache
     *
     * @param Felamimail_Model_Account $_account
     * @return array capabilities
     */
    public function updateCapabilities(Felamimail_Model_Account $_account, $_imapBackend = NULL)
    {
        $cacheId = Tinebase_Helper::convertCacheId(self::ACCOUNT_CAPABILITIES_CACHEID . '_' . $_account->getId());
        $cache = Tinebase_Core::getCache();

        if ($cache->test($cacheId)) {
            return $cache->load($cacheId);
        }

        $imapBackend = ($_imapBackend !== NULL) ? $_imapBackend : $this->_getIMAPBackend($_account, TRUE);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Getting capabilities of account ' . $_account->name);
        
        // get imap server capabilities and save delimiter / personal namespace in account
        $capabilities = $imapBackend->getCapabilityAndNamespace();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Capabilities: ' . print_r($capabilities, TRUE));
        
        $this->_updateNamespacesAndDelimiter($_account, $capabilities);

        // check if server has 'CHILDREN' support
        $_account->has_children_support = (in_array('CHILDREN', $capabilities['capabilities'])) ? 1 : 0;
        
        try {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating capabilities for account: ' . $_account->name);
            $this->_backend->update($_account);
        } catch (Zend_Db_Statement_Exception $zdse) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not update account: ' . $zdse->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $zdse->getTraceAsString());
        }
        
        // save capabilities in cache
        $cache->save($capabilities, $cacheId);
        
        return $capabilities;
    }
    
    /**
     * update account namespaces from capabilities
     * 
     * @param Felamimail_Model_Account $_account
     * @param array $_capabilities
     */
    protected function _updateNamespacesAndDelimiter(Felamimail_Model_Account $_account, $_capabilities)
    {
        if (! isset($_capabilities['namespace'])) {
            return;
        }
        
        // update delimiter
        $delimiter = (! empty($_capabilities['namespace']['personal'])) 
            ? $_capabilities['namespace']['personal']['delimiter'] : '/';

        // care for multiple backslashes (for example from Domino IMAP server)
        if ($delimiter == '\\\\') {
            $delimiter = '\\';
        }

        if (strlen($delimiter) > 1) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Got long delimiter: ' . $delimiter . ' Fall back to default (/)');
            $delimiter = '/';
        }

        if ($delimiter && $delimiter != $_account->delimiter) {
            $_account->delimiter = $delimiter;
        }
    
        // update namespaces
        $_account->ns_personal   = (! empty($_capabilities['namespace']['personal'])) ? $_capabilities['namespace']['personal']['name']: '';
        $_account->ns_other      = (! empty($_capabilities['namespace']['other']))    ? $_capabilities['namespace']['other']['name']   : '';
        $_account->ns_shared     = (! empty($_capabilities['namespace']['shared']))   ? $_capabilities['namespace']['shared']['name']  : '';

        $this->_addNamespaceToFolderConfig($_account);
    }
    
    /**
     * add namespace to account system folder names
     * 
     * @param Felamimail_Model_Account $_account
     * @param string $_namespace
     * @param array $_folders
     */
    protected function _addNamespaceToFolderConfig($_account, $_namespace = 'ns_personal', $_folders = array())
    {
        $folders = (empty($_folders)) ? array(
            'sent_folder',
            'trash_folder',
            'drafts_folder',
            'templates_folder',
        ) : $_folders;
        
        if ($_account->{$_namespace} === 'NIL') {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' No ' . $_namespace . ' namespace available for account ' . $_account->name);
            return;
        }
        
        if (empty($_account->{$_namespace})) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Setting ' . $_namespace . ' namespace: "' . $_account->{$_namespace} . '" for systemfolders of account ' . $_account->name);
        
        foreach ($folders as $folder) {
            if (! preg_match('/^' . preg_quote($_account->{$_namespace}, '/') . '/', $_account->{$folder})) {
                $_account->{$folder} = $_account->{$_namespace} . $_account->{$folder};
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Updated system folder name: ' . $folder .' -> ' . $_account->{$folder});
            }
        }
    }
    
    /**
     * get imap backend and catch exceptions
     * 
     * @param Felamimail_Model_Account $_account
     * @param boolean $_throwException
     * @return boolean|Felamimail_Backend_ImapProxy
     * @throws Felamimail_Exception_IMAP|Felamimail_Exception_IMAPInvalidCredentials
     */
    protected function _getIMAPBackend(Felamimail_Model_Account $_account, $_throwException = FALSE)
    {
        $result = FALSE;
        try {
            $result = Felamimail_Backend_ImapFactory::factory($_account);
        } catch (Zend_Mail_Storage_Exception $zmse) {
            $message = 'Wrong user credentials (' . $zmse->getMessage() . ')';
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            $message =  'No connection to imap server (' . $zmpe->getMessage() . ')';
        } catch (Felamimail_Exception_IMAPInvalidCredentials $feiic) {
            $message = 'Wrong user credentials (' . $feiic->getMessage() . ')';
        }
        
        if (! $result) {
            $message .= ' for account ' . $_account->name;
            
            if ($_throwException) {
                throw (isset($feiic)) ? $feiic : new Felamimail_Exception_IMAP($message);
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $message);
            }
        }
        
        return $result;
    }
    
    /**
     * get system folder for account
     * 
     * @param string|Felamimail_Model_Account $_account
     * @param string $_systemFolder
     * @return NULL|Felamimail_Model_Folder
     */
    public function getSystemFolder($_account, $_systemFolder)
    {
        $account = ($_account instanceof Felamimail_Model_Account) ? $_account : $this->get($_account);
        $changed = $this->_addFolderDefaults($account);
        if ($changed) {
            // need to use backend update because we prohibit the change of some fields in _inspectBeforeUpdate()
            $account = $this->_backend->update($account);
        }
        
        $systemFolderField = $this->_getSystemFolderField($_systemFolder);
        $folderName = $account->{$systemFolderField};
        
        if (empty($folderName)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' No ' . $_systemFolder . ' folder set in account.');
            return NULL;
        }
        
        // check if folder exists on imap server
        $imapBackend = $this->_getIMAPBackend($account);
        if ($imapBackend && $imapBackend->getFolderStatus(Felamimail_Model_Folder::encodeFolderName($folderName)) === false) {
            $systemFolder = $this->_createSystemFolder($account, $folderName);
            if ($systemFolder->globalname !== $folderName) {
                $account->{$systemFolderField} = $systemFolder->globalname;
                $this->_backend->update($account);
            }
        } else {
            try {
                $systemFolder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($account->getId(), $folderName);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Found system folder: ' . $folderName);
                                
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' 
                    . $tenf->getMessage());
                
                $splitFolderName = Felamimail_Model_Folder::extractLocalnameAndParent($_systemFolder, $account->delimiter);
                Felamimail_Controller_Cache_Folder::getInstance()->update($account, $splitFolderName['parent'], TRUE);
                $systemFolder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($account->getId(), $folderName);
            }
        }
        
        return $systemFolder;
    }
    
    /**
     * map folder constant to account model field
     * 
     * @param string $_systemFolder
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getSystemFolderField($_systemFolder)
    {
        switch ($_systemFolder) {
            case Felamimail_Model_Folder::FOLDER_TRASH:
                $field = 'trash_folder';
                break;
            case Felamimail_Model_Folder::FOLDER_SENT:
                $field = 'sent_folder';
                break;
            case Felamimail_Model_Folder::FOLDER_TEMPLATES:
                $field = 'templates_folder';
                break;
            case Felamimail_Model_Folder::FOLDER_DRAFTS:
                $field = 'drafts_folder';
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('No system folder: ' . $_systemFolder);
        }
        
        return $field;
    }
    
    /**
     * create new system folder
     * 
     * @param Felamimail_Model_Account $_account
     * @param string $_systemFolder
     * @return Felamimail_Model_Folder
     */
    protected function _createSystemFolder(Felamimail_Model_Account $_account, $_systemFolder)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Folder not found: ' . $_systemFolder . '. Trying to add it.');
        
        $splitFolderName = Felamimail_Model_Folder::extractLocalnameAndParent($_systemFolder, $_account->delimiter);
        
        try {
            $result = Felamimail_Controller_Folder::getInstance()->create($_account, $splitFolderName['localname'], $splitFolderName['parent']);
        } catch (Felamimail_Exception_IMAPServiceUnavailable $feisu) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' 
                . $feisu->getMessage());
            // try again with INBOX as parent because some IMAP servers can not handle namespaces correctly
            $result = Felamimail_Controller_Folder::getInstance()->create($_account, $splitFolderName['localname'], 'INBOX');
        }
        
        return $result;
    }
    
    /**
     * set vacation active field for account
     * 
     * @param string|Felamimail_Model_Account $_account
     * @param boolean $_vacationEnabled
     * @return Felamimail_Model_Account
     */
    public function setVacationActive(Felamimail_Model_Account $_account, $_vacationEnabled)
    {
        $account = $this->get($_account->getId());
        if ($account->sieve_vacation_active != $_vacationEnabled) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Updating sieve_vacation_active = ' . $_vacationEnabled . ' for account: ' . $account->name);
            
            $account->sieve_vacation_active = (bool) $_vacationEnabled;
            // skip all special update handling
            $account = $this->_backend->update($account);
        }
        
        return $account;
    }

    /**
     * @param Tinebase_Model_User|string|null $_accountId
     * @return Felamimail_Model_Account|null
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function getSystemAccount($_accountId = null)
    {
        if (null === $_accountId) {
            $_accountId = Tinebase_Core::getUser()->getId();
        } elseif ($_accountId instanceof Tinebase_Model_User) {
            $_accountId = $_accountId->getId();
        }
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, [
            ['field' => 'user_id', 'operator' => 'equals', 'value' => $_accountId],
            ['field' => 'type', 'operator' => 'equals', 'value' => Felamimail_Model_Account::TYPE_SYSTEM],
        ]);
        return $this->search($filter)->getFirstRecord();
    }

    /**
     * add system account with tine user credentials
     *
     * @param Tinebase_Model_FullUser
     * @return Felamimail_Model_Account|null
     */
    public function addSystemAccount(Tinebase_Model_FullUser $_account, $pwd)
    {
        $email = $this->_getAccountEmail($_account);
        
        // only create account if email address is set
        if ($email && $_account->imapUser instanceof Tinebase_Model_EmailUser) {
            if (null !== ($systemAccount = $this->getSystemAccount($_account->getId()))) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' system account "' . $systemAccount->name . '" already exists.');
                return $systemAccount;
            }

            $oldACLValue = $this->doContainerACLChecks(false);
            $oldRightValue = $this->doRightChecks(false);
            $raii = new Tinebase_RAII(function() use ($oldACLValue, $oldRightValue) {
                Felamimail_Controller_Account::getInstance()->doContainerACLChecks($oldACLValue);
                Felamimail_Controller_Account::getInstance()->doRightChecks($oldRightValue);
            });

            $systemAccount = new Felamimail_Model_Account([
                'type'      => Felamimail_Model_Account::TYPE_SYSTEM,
                'user_id'   => $_account->getId(),
            ], true);

            $this->addSystemAccountConfigValues($systemAccount, $_account);

            $this->_addFolderDefaults($systemAccount, true);

            // create new account and update capabilities
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($systemAccount, 'create');
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' ' . print_r($systemAccount->toArray(), true));
            }

            try {
                /** @var Felamimail_Model_Account $systemAccount */
                $systemAccount = $this->create($systemAccount);
            } catch (Tinebase_Exception_AccessDenied $tead) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tead->getMessage());
                return null;
            }

            if (Felamimail_Config::getInstance()
                    ->featureEnabled(Felamimail_Config::FEATURE_SYSTEM_ACCOUNT_AUTOCREATE_FOLDERS)) {
                $emailUser = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
                $systemAccount->user = $emailUser->getEmailUserName($_account);
                $systemAccount->password = $pwd;
                $this->_autoCreateSystemAccountFolders($systemAccount);
            }
            
            // set as default account preference
            Tinebase_Core::getPreference($this->_applicationName)->setValueForUser(
                Felamimail_Preference::DEFAULTACCOUNT, $systemAccount->getId(), $_account->getId(), true);

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Created new system account "' . $systemAccount->name . '".');

            // just for unused variable check:
            unset($raii);

            return $systemAccount;
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Could not create system account for user ' . $_account->accountLoginName
                . '. No email address or imapUser given.');
        }

        return null;
    }
    
    /**
     * add folder defaults
     * 
     * @param Felamimail_Model_Account $_account
     * @param boolean $_force
     * @return boolean
     */
    protected function _addFolderDefaults(Felamimail_Model_Account $_account, $_force = FALSE)
    {
        // set some default settings if not set
        $folderDefaults = Felamimail_Config::getInstance()->get(Felamimail_Config::SYSTEM_ACCOUNT_FOLDER_DEFAULTS, array(
            'sent_folder'       => 'Sent',
            'trash_folder'      => 'Trash',
            'drafts_folder'     => 'Drafts',
            'templates_folder'  => 'Templates',
        ));
        
        $changed = FALSE;
        foreach ($folderDefaults as $key => $value) {
            if ($_force || ! isset($_account->{$key}) || empty($_account->{$key})) {
                $_account->{$key} = $value;
                $changed = TRUE;
            }
        }
        
        $this->_addNamespaceToFolderConfig($_account);
        
        return $changed;
    }

    /**
     * @param Felamimail_Model_Account $_account
     */
    protected function _autoCreateSystemAccountFolders(Felamimail_Model_Account $_account)
    {
        try {
            foreach ([
                         Felamimail_Model_Folder::FOLDER_DRAFTS,
                         Felamimail_Model_Folder::FOLDER_SENT,
                         Felamimail_Model_Folder::FOLDER_TEMPLATES,
                         Felamimail_Model_Folder::FOLDER_TRASH
                     ] as $folder) {
                $systemFolderField = $this->_getSystemFolderField($folder);
                $folderName = $_account->{$systemFolderField};
                $this->_createSystemFolder($_account, $folderName);
            }
        } catch (Felamimail_Exception_IMAPInvalidCredentials $feiic) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $feiic->getMessage());
        } catch (Exception $e) {
            // skip creation at this point
            Tinebase_Exception::log($e);
        }
    }
    
    /**
     * returns email address used for the account by checking the user data and imap config
     * 
     * @param Tinebase_Model_User $_user
     * @return string
     */
    protected function _getAccountEmail(Tinebase_Model_User $_user)
    {
        $email = ((! $_user->accountEmailAddress || empty($_user->accountEmailAddress))
            && (isset($this->_imapConfig['user']) || array_key_exists('user', $this->_imapConfig))
        )
            ? $this->_imapConfig['user']
            : $_user->accountEmailAddress;
            
        if (empty($email)) {
            $email = $_user->accountLoginName;
        }
            
        if (! preg_match('/@/', $email)) {
            if (isset($this->_imapConfig['domain'])) {
                $email .= '@' . $this->_imapConfig['domain'];
            } else {
                $email .= '@' . $this->_imapConfig['host'];
            }
        }
        
        return $email;
    }

    /**
     * create a shared credential cache and return new credentials id
     *
     * @param string $_username
     * @param string $_password
     * @return string
     */
    protected function _createSharedCredentials($_username, $_password)
    {
        $cc = Tinebase_Auth_CredentialCache::getInstance();
        $adapter = explode('_', get_class($cc->getCacheAdapter()));
        $adapter = end($adapter);
        try {
            $cc->setCacheAdapter('Shared');
            $sharedCredentials = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials($_username, $_password,
                null, true /* save in DB */, Tinebase_DateTime::now()->addYear(100));

            return $sharedCredentials->getId();
        } finally {
            $cc->setCacheAdapter($adapter);
        }
    }

    /**
     * create account credentials and return new credentials id
     *
     * @param string $_username
     * @param string $_password
     * @return string
     */
    protected function _createCredentials($_username = NULL, $_password = NULL, $_userCredentialCache = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
            $message = 'Create new account credentials';
            if ($_username !== NULL) {
                $message .= ' for username ' . $_username;
            } 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $message);
        }

        $userCredentialCache = Tinebase_Core::getUserCredentialCache();
        if ($userCredentialCache !== null) {
            Tinebase_Auth_CredentialCache::getInstance()->getCachedCredentials($userCredentialCache);
        } else {
            Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ 
                . ' Something went wrong with the CredentialsCache / use given username/password instead.'
            );
            $userCredentialCache = new Tinebase_Model_CredentialCache(array(
                'username' => $_username,
                'password' => $_password,
            ));
        }
        
        $accountCredentials = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials(
            ($_username !== NULL) ? $_username : $userCredentialCache->username,
            ($_password !== NULL) ? $_password : $userCredentialCache->password,
            $userCredentialCache->password,
            TRUE // save in DB
        );
        
        return $accountCredentials->getId();
    }
    
    /**
     * add settings/values from system account
     * 
     * @param Felamimail_Model_Account $_account
     * @param Tinebase_Model_User $_user
     * @return void
     */
    public function addSystemAccountConfigValues(Felamimail_Model_Account $_account, $_user = null)
    {
        $configs = array(
            Tinebase_Config::IMAP     => array(
                'keys'      => array('host', 'port', 'ssl'),
                'defaults'  => array(), // @todo remove when not needed for sieve anymore
            ),
            Tinebase_Config::SMTP     => array(
                'keys'      => array('hostname', 'port', 'ssl', 'auth'),
                'defaults'  => array(), // @todo remove when not needed for sieve anymore
            ),
            Tinebase_Config::SIEVE    => array(
                'keys'      => array('hostname', 'port', 'ssl'),
                'defaults'  => array('port' => 2000, 'ssl' => Felamimail_Model_Account::SECURE_NONE),
            ),
        );
        
        foreach ($configs as $configKey => $values) {
            try {
                $this->_addConfigValuesToAccount($_account, $configKey, $values['keys'], $values['defaults']);
            } catch (Felamimail_Exception $fe) {
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' Could not get system account config values: ' . $fe->getMessage());
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $fe->getTraceAsString());
            }
        }

        if ($_account->type === Felamimail_Model_Account::TYPE_USER || $_account->type ===
                Felamimail_Model_Account::TYPE_SYSTEM || $_account->type ===
                Felamimail_Model_Account::TYPE_USER_INTERNAL) {
            if (null === $_user || $_user->getId() !== $_account->user_id) {
                if (Tinebase_Core::getUser()->getId() === $_account->user_id) {
                    $_user = Tinebase_Core::getUser();
                } else {
                    try {
                        $_user = Tinebase_User::getInstance()->getUserById($_account->user_id);
                    } catch (Tinebase_Exception_NotFound $e) {
                        $_user = null;
                    }
                }
            }

            if (null !== $_user) {
                $this->_addUserValues($_account, $_user);
            }
        }
    }
    
    /**
     * add config values to account
     * 
     * @param Felamimail_Model_Account $_account
     * @param string $_configKey for example Tinebase_Config::IMAP for imap settings 
     * @param array $_keysOverwrite keys to overwrite
     * @param array $_defaults
     */
    protected function _addConfigValuesToAccount(Felamimail_Model_Account $_account, $_configKey, $_keysOverwrite = array(), $_defaults = array())
    {
        switch ($_configKey) {
            case Tinebase_Config::IMAP:
                /** @var Tinebase_EmailUser_Sql $emailUserBackend */
                $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
                $systemDefaults = $emailUserBackend->_getConfiguredSystemDefaults();

                foreach ([
                            'emailHost'     => 'host',
                            'emailPort'     => 'port',
                            'emailSecure'   => 'ssl',
                        ] as $key => $value) {
                    if (isset($systemDefaults[$key])) {
                        $_account->$value = $systemDefaults[$key];
                    }
                }
                
                break;
                
            case Tinebase_Config::SMTP:
                /** @var Tinebase_EmailUser_Sql $emailUserBackend */
                $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
                $systemDefaults = $emailUserBackend->_getConfiguredSystemDefaults();
                
                foreach ([
                            'emailHost'     => 'smtp_hostname',
                            'emailPort'     => 'smtp_port',
                            'emailSecure'   => 'smtp_ssl',
                            'emailAuth'     => 'smtp_auth',
                        ] as $key => $value) {
                    if (isset($systemDefaults[$key])) {
                        $_account->$value = $systemDefaults[$key];
                    }
                }
                
                break;
                
            case Tinebase_Config::SIEVE:
                $config = Tinebase_Config::getInstance()->get($_configKey, new Tinebase_Config_Struct($_defaults))->toArray();
                $prefix = strtolower($_configKey) . '_';
                
                if (! is_array($config)) {
                    throw new Felamimail_Exception('Invalid config found for ' . $_configKey);
                }
                
                foreach ($config as $key => $value) {
                    if (in_array($key, $_keysOverwrite) && ! empty($value)) {
                        $_account->{$prefix . $key} = $value;
                    }
                }
                
                break;
        }
    }
    
    /**
     * add user account/contact data
     * 
     * @param Felamimail_Model_Account $_account
     * @param Tinebase_Model_User $_user
     * @param string $_email
     * @return void
     */
    protected function _addUserValues(Felamimail_Model_Account $_account, Tinebase_Model_User $_user, $_email = NULL)
    {
        if ($_email === NULL) {
            $_email = $this->_getAccountEmail($_user);
        }
        
        // add user data
        $_account->email  = $_email;
        if (empty($_account->name) && $_account->type !== Felamimail_Model_Account::TYPE_USER_INTERNAL) {
            $_account->name = $_email;
        }
        $_account->from = $_user->accountFullName;
        
        // add contact data (if available)
        try {
            $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($_user->getId(), TRUE);
            $_account->organization = $contact->org_name;
        } catch (Addressbook_Exception_NotFound $aenf) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Could not get system account user contact: ' . $aenf->getMessage());
        }
    }

    /**
     * Returns a set of records identified by their id's
     *
     * @param   array $_ids array of record identifiers
     * @param   bool $_ignoreACL don't check acl grants
     * @param Tinebase_Record_Expander $_expander
     * @param   bool $_getDeleted
     * @return Tinebase_Record_RecordSet of $this->_modelName
     */
    public function getMultiple($_ids, $_ignoreACL = false, Tinebase_Record_Expander $_expander = null, $_getDeleted = false)
    {
        // TODO fix me! system account resolving is mssing, add it here?!
        throw new Tinebase_Exception_NotImplemented('do not use this function');
    }

    public function getAccountForList(Addressbook_Model_List $list)
    {
        $account = Felamimail_Controller_Account::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
                ['field' => 'user_id', 'operator' => 'equals', 'value' => $list->getId()],
                ['field' => 'type', 'operator' => 'equals', 'value' => Felamimail_Model_Account::TYPE_ADB_LIST],
            ]))->getFirstRecord();

        if (null === $account) {
            $e = new Tinebase_Exception('no felamimail account found for list ' . $list->getId());
            Tinebase_Exception::log($e);
            return false;
        }

        return $account;
    }

    public static function getVisibleAccountsFilterForUser($user = null)
    {
        if (null === $user) {
            $user = Tinebase_Core::getUser();
        }
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class);
        $filter->addFilterGroup(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Felamimail_Model_Account::class, [
            ['field' => 'type', 'operator' => 'equals', 'value' => Felamimail_Model_Account::TYPE_SHARED],
            ['field' => 'user_id', 'operator' => 'equals', 'value' => $user->getId()],
        ], Tinebase_Model_Filter_FilterGroup::CONDITION_OR));

        return $filter;
    }

    public function updateSystemAccount(Tinebase_Model_FullUser $user, Tinebase_Model_FullUser $oldUser)
    {
        $checks = Felamimail_Controller_Account::getInstance()->doContainerACLChecks(false);
        $systemaccount = null;
        $updateSystemAccount = false;
        if ($user->accountFullName !== $oldUser->accountFullName) {
            $systemaccount = Felamimail_Controller_Account::getInstance()->getSystemAccount($user);
            if ($systemaccount) {
                $systemaccount->from = $user->accountFullName;
                $updateSystemAccount = true;
            }
        }
        if ($user->accountEmailAddress !== $oldUser->accountEmailAddress) {
            if (! $systemaccount) {
                $systemaccount = Felamimail_Controller_Account::getInstance()->getSystemAccount($user);
            }
            if ($systemaccount && $systemaccount->name === $oldUser->accountEmailAddress) {
                $systemaccount->name = $user->accountEmailAddress;
                $updateSystemAccount = true;
            }
        }

        if ($updateSystemAccount) {
            Felamimail_Controller_Account::getInstance()->update($systemaccount);
        }
        Felamimail_Controller_Account::getInstance()->doContainerACLChecks($checks);
    }

    /**
     * @param $accountId
     * @return Felamimail_Model_Account
     * @throws Tinebase_Exception_AccessDenied
     */
    public function approveMigration($accountId)
    {
        $account = $this->get($accountId);
        if (! in_array($account->type, [
            Felamimail_Model_Account::TYPE_USER_INTERNAL,
            Felamimail_Model_Account::TYPE_SYSTEM
        ]) && $account->user_id === Tinebase_Core::getUser()->getId()) {
            throw new Tinebase_Exception_AccessDenied('you can only approve the migration of your own system accounts');
        }

        $account->migration_approved = 1;
        $this->update($account);

        return $account;
    }

    /**
     *  add email userid xprops to shared email accounts
     */
    public function convertAccountsToSaveUserIdInXprops()
    {
        $checks = $this->doContainerACLChecks(false);
        // get all shared email accounts
        $sharedAccounts = $this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Felamimail_Model_Account::class, [
            ['field' => 'type', 'operator' => 'in', 'value' => [
                Felamimail_Model_Account::TYPE_SHARED,
                Felamimail_Model_Account::TYPE_ADB_LIST
            ]]
        ]));
        $this->doContainerACLChecks($checks);

        $listBackend = new Addressbook_Backend_List();
        foreach ($sharedAccounts as $account) {
            $emailUserId = $account->user_id;
            if (empty($emailUserId)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' .
                    __LINE__ . ' user_id not set ... skipping account');
                continue;
            }
            if ($account->type === Felamimail_Model_Account::TYPE_SHARED) {
                $account->user_id = null;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' .
                __LINE__ . ' update xprops of account ' . $account->name);

            $account->xprops()[Felamimail_Model_Account::XPROP_EMAIL_USERID_IMAP] = $emailUserId;
            $account->xprops()[Felamimail_Model_Account::XPROP_EMAIL_USERID_SMTP] = $emailUserId;
            $this->_backend->update($account);
        }
    }

    /**
     * @param Felamimail_Model_Account $account
     * @return Tinebase_Model_FullUser
     */
    public function getSharedAccountEmailUser(Felamimail_Model_Account $account)
    {
        return Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($account, ['user_id' => 'user_id']);
    }
}
