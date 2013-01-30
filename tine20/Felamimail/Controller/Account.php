<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Account controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Account extends Tinebase_Controller_Record_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * we need this for the searchCount -> set to true if default account has been added
     *
     * @var boolean
     */
    protected $_addedDefaultAccount = FALSE;
    
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
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_modelName = 'Felamimail_Model_Account';
        $this->_doContainerACLChecks = FALSE;
        $this->_doRightChecks = TRUE;
        $this->_purgeRecords = FALSE;
        
        $this->_backend = new Felamimail_Backend_Account();
        
        $this->_imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        $this->_useSystemAccount = (array_key_exists('useSystemAccount', $this->_imapConfig) && $this->_imapConfig['useSystemAccount']);
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

    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param boolean $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        if ($_filter === NULL) {
            $_filter = new Felamimail_Model_AccountFilter(array());
        }
        
        $result = parent::search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
        
        // check preference / config if we should add system account with tine user credentials or from config.inc.php
        if ($this->_useSystemAccount && ! $_onlyIds) {
            $systemAccountFound = FALSE;
            // check if resultset contains system account and add config values
            foreach($result as $account) {
                if ($account->type == Felamimail_Model_Account::TYPE_SYSTEM) {
                    $this->_addSystemAccountConfigValues($account);
                    $systemAccountFound = TRUE;
                }
            }
            if (! $systemAccountFound) {
                $this->_addSystemAccount($result);
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
        $count = $this->_backend->searchCount($_filter);
        
        if ($this->_addedDefaultAccount) {
            $count++;
        }
        return $count;
    }
    
    /**
     * get by id
     *
     * @param string $_id
     * @param int $_containerId
     * @return Felamimail_Model_Account
     */
    public function get($_id, $_containerId = NULL)
    {
        $record = parent::get($_id, $_containerId);
        
        if ($record->type == Felamimail_Model_Account::TYPE_SYSTEM) {
            $this->_addSystemAccountConfigValues($record);
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
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        $userFilter = $_filter->getFilter('user_id');
        
        // force a $userFilter filter (ACL)
        if ($userFilter === NULL || $userFilter->getOperator() !== 'equals' || $userFilter->getValue() !== Tinebase_Core::getUser()->getId()) {
            $userFilter = $_filter->createFilter('user_id', 'equals', Tinebase_Core::getUser()->getId());
            $_filter->addFilter($userFilter);
        }
    }

    /**
     * inspect creation of one record
     * - add credentials and user id here
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        // add user id
        $_record->user_id = Tinebase_Core::getUser()->getId();
        
        // use the imap host as smtp host if empty
        if (! $_record->smtp_hostname) {
            $_record->smtp_hostname = $_record->host;
        }
        
        if (! $_record->user || ! $_record->password) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No username or password given for new account.');
            return;
        }
        
        // add imap & smtp credentials
        $_record->credentials_id = $this->_createCredentials($_record->user, $_record->password);
        if ($_record->smtp_user && $_record->smtp_password) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Create SMTP credentials.');
            $_record->smtp_credentials_id = $this->_createCredentials($_record->smtp_user, $_record->smtp_password);
        } else {
            $_record->smtp_credentials_id = $_record->credentials_id;
        }
        
        $this->_checkSignature($_record);
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
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        // set as default account if it is the only account
        $accountCount = $this->searchCount(new Felamimail_Model_AccountFilter(array()));
        if ($accountCount == 1) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Set account ' . $_createdRecord->name . ' as new default email account.');
            Tinebase_Core::getPreference($this->_applicationName)->{Felamimail_Preference::DEFAULTACCOUNT} = $_createdRecord->getId();
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
        if ($_record->type == Felamimail_Model_Account::TYPE_SYSTEM) {
            $this->_beforeUpdateSystemAccount($_record, $_oldRecord);
        } else {
            $this->_beforeUpdateStandardAccount($_record, $_oldRecord);
        }
        
        $this->_checkSignature($_record);
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
            'signature',
            'signature_position',
            'display_format',
            'has_children_support',
            'delimiter',
            'ns_personal',
            'ns_other',
            'ns_shared',
            'last_modified_time',
            'last_modified_by',
        );
        $diff = $_record->diff($_oldRecord)->diff;
        foreach ($diff as $key => $value) {
            if (! in_array($key, $allowedFields)) {
                // setting old value
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
        $this->_beforeUpdateStandardAccountCredentials($_record, $_oldRecord);
        
        $diff = $_record->diff($_oldRecord)->diff;
        
        // delete message body cache because display format has changed
        if (array_key_exists('display_format', $diff)) {
            Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('getMessageBody'));
        }
        
        // reset capabilities if imap host / port changed
        if (isset(Tinebase_Core::getSession()->Felamimail) && (array_key_exists('host', $diff) || array_key_exists('port', $diff))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Resetting capabilities for account ' . $_record->name);
            unset(Tinebase_Core::getSession()->Felamimail[$_record->getId()]);
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
        // get old credentials
        $credentialsBackend = Tinebase_Auth_CredentialCache::getInstance();
        $userCredentialCache = Tinebase_Core::get(Tinebase_Core::USERCREDENTIALCACHE);
        
        if ($userCredentialCache !== NULL) {
            $credentialsBackend->getCachedCredentials($userCredentialCache);
        } else {
            Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ 
                . ' Something went wrong with the CredentialsCache / use given username/password instead.'
            );
            return;
        }
        
        if ($_oldRecord->credentials_id) {
            $credentials = $credentialsBackend->get($_oldRecord->credentials_id);
            $credentials->key = substr($userCredentialCache->password, 0, 24);
            $credentialsBackend->getCachedCredentials($credentials);
        } else {
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
        $this->_setRightChecks(FALSE);
        $this->update($account);
        $this->_setRightChecks(TRUE);
        
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
     * @param Felamimail_Model_Account $_account
     * @return array capabilities
     */
    public function updateCapabilities(Felamimail_Model_Account $_account, Felamimail_Backend_ImapProxy $_imapBackend = NULL)
    {
        if (isset(Tinebase_Core::getSession()->Felamimail) && array_key_exists($_account->getId(), Tinebase_Core::getSession()->Felamimail)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting capabilities of account ' . $_account->name . ' from SESSION.');
            return Tinebase_Core::getSession()->Felamimail[$_account->getId()];
        }
        
        $imapBackend = ($_imapBackend !== NULL) ? $_imapBackend : $this->_getIMAPBackend($_account, TRUE);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting capabilities of account ' . $_account->name);
        
        // get imap server capabilities and save delimiter / personal namespace in account
        $capabilities = $imapBackend->getCapabilityAndNamespace();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($capabilities, TRUE));
        
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
        
        // save capabilities in SESSION
        Tinebase_Core::getSession()->Felamimail[$_account->getId()] = $capabilities;
        
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
        $delimiter = (! empty($_capabilities['namespace']['personal']) && strlen($_capabilities['namespace']['personal']['delimiter']) === 1) 
            ? $_capabilities['namespace']['personal']['delimiter'] : '';
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
                
                $splitFolderName = Felamimail_Model_Folder::extractLocalnameAndParent($_systemFolder, $_account->delimiter);
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
        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Folder not found: ' . $_systemFolder . '. Trying to add it.');
        
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
     * add system account with tine user credentials (from config.inc.php or config db) 
     *
     * @param Tinebase_Record_RecordSet $_accounts of Felamimail_Model_Account
     */
    protected function _addSystemAccount(Tinebase_Record_RecordSet $_accounts)
    {
        $userId = Tinebase_Core::getUser()->getId();
        $fullUser = Tinebase_User::getInstance()->getFullUserById($userId);
        $email = $this->_getAccountEmail($fullUser);
        
        // only create account if email address is set
        if ($email) {
            $systemAccount = new Felamimail_Model_Account(NULL, TRUE);
            
            $this->_addSystemAccountConfigValues($systemAccount);
            
            $systemAccount->type = Felamimail_Model_Account::TYPE_SYSTEM;
            $systemAccount->user_id = $userId;
            $this->_addUserValues($systemAccount, $fullUser, $email);
            
            $this->_addFolderDefaults($systemAccount, TRUE);
            
            // create new account and update capabilities
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($systemAccount, 'create');
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($systemAccount->toArray(), TRUE));
            
            $systemAccount = $this->_backend->create($systemAccount);
            $_accounts->addRecord($systemAccount);
            $this->_addedDefaultAccount = TRUE;
            
            // set as default account preference
            Tinebase_Core::getPreference($this->_applicationName)->{Felamimail_Preference::DEFAULTACCOUNT} = $systemAccount->getId();
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Created new system account "' . $systemAccount->name . '".');
            
        } else {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Could not create system account for user ' . $fullUser->accountLoginName . '. No email address given.');
        }
    }
    
    /**
     * add folder defaults
     * 
     * @param Felamimail_Model_Account $_account
     * @param boolean $_force
     * @return boolean
     */
    protected function _addFolderDefaults($_account, $_force = FALSE)
    {
        // set some default settings if not set
        $folderDefaults = array(
            'sent_folder'       => 'Sent',
            'trash_folder'      => 'Trash',
            'drafts_folder'     => 'Drafts',
            'templates_folder'  => 'Templates',
        );
        
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
     * returns email address used for the account by checking the user data and imap config
     * 
     * @param Tinebase_Model_FullUser $_user
     * @return string
     */
    protected function _getAccountEmail(Tinebase_Model_FullUser $_user)
    {
        $email = ((! $_user->accountEmailAddress || empty($_user->accountEmailAddress)) && array_key_exists('user', $this->_imapConfig)) 
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
        
        if (Tinebase_Core::isRegistered(Tinebase_Core::USERCREDENTIALCACHE)) {
            $userCredentialCache = Tinebase_Core::get(Tinebase_Core::USERCREDENTIALCACHE);
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
     * @return void
     */
    protected function _addSystemAccountConfigValues(Felamimail_Model_Account $_account)
    {
        $configs = array(
            Tinebase_Config::IMAP     => array(
                'keys'      => array('host', 'port', 'ssl'),
                'defaults'  => array('port' => 143),
            ),
            Tinebase_Config::SMTP     => array(
                'keys'      => array('hostname', 'port', 'ssl', 'auth'),
                'defaults'  => array('port' => 25),
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
        
        $this->_addUserValues($_account);
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_account->toArray(), TRUE));
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
        $config = ($_configKey == Tinebase_Config::IMAP) ? $this->_imapConfig : Tinebase_Config::getInstance()->get($_configKey, new Tinebase_Config_Struct($_defaults))->toArray();
        $prefix = ($_configKey == Tinebase_Config::IMAP) ? '' : strtolower($_configKey) . '_';
        if (! is_array($config)) {
            throw new Felamimail_Exception('Invalid config found for ' . $_configKey);
        }
        
        foreach ($config as $key => $value) {
            if (in_array($key, $_keysOverwrite) && ! empty($value)) {
                $_account->{$prefix . $key} = $value;
            }
        }
    }
    
    /**
     * add user account/contact data
     * 
     * @param Felamimail_Model_Account $_account
     * @param Tinebase_Model_FullUser $_user
     * @param string $_email
     * @return void
     */
    protected function _addUserValues(Felamimail_Model_Account $_account, Tinebase_Model_FullUser $_user = NULL, $_email = NULL)
    {
        if ($_user === NULL) {
            $_user = Tinebase_User::getInstance()->getFullUserById(Tinebase_Core::getUser()->getId());
        }
        
        if ($_email === NULL) {
            $_email = $this->_getAccountEmail($_user);
        }
        
        // add user data
        $_account->user   = $_user->accountLoginName;
        $_account->email  = $_email;
        $_account->name   = $_email;
        $_account->from   = $_user->accountFullName;
        
        // add contact data (if available)
        try {
            $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($_user->getId(), TRUE);
            $_account->organization = $contact->org_name;
        } catch (Addressbook_Exception_NotFound $aenf) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Could not get system account user contact: ' . $aenf->getMessage());
        }
    }
}
