<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        make it possible to switch back to smtp creds = imap creds even if extra smtp creds have been created
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
    private function __construct() {
        $this->_modelName = 'Felamimail_Model_Account';
        $this->_doContainerACLChecks = FALSE;
        $this->_doRightChecks = TRUE;
        $this->_backend = new Felamimail_Backend_Account();
        
        $this->_currentAccount = Tinebase_Core::getUser();
        
        $this->_imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
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
     * @return Tinebase_Record_Interface
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
        if (in_array(Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT}, (array) $_ids)) {
            $accounts = $this->search();
            $defaultAccountId = (count($accounts) > 0) ? $accounts->getFirstRecord()->getId() : '';
            
            Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT} = $defaultAccountId;
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
        if ($userFilter === NULL || $userFilter->getOperator() !== 'equals' || $userFilter->getValue() !== $this->_currentAccount->getId()) {
            $userFilter = $_filter->createFilter('user_id', 'equals', $this->_currentAccount->getId());
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
        $_record->user_id = $this->_currentAccount->getId();
        
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
            Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT} = $_createdRecord->getId();
        }
    }
    
    /**
     * inspect update of one record
     * - update credentials here / only allow to update certain fields of system accounts
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     * 
     * @todo split this into separate functions
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if ($_record->type == Felamimail_Model_Account::TYPE_SYSTEM) {
            // only allow to update some values for system accounts
            $allowedFields = array(
                'name',
                'signature',
                'has_children_support',
                'delimiter',
                'ns_personal',
                'ns_other',
                'ns_shared',
                'last_modified_time',
                'last_modified_by',
            );
            $diff = $_record->diff($_oldRecord);
            foreach ($diff as $key => $value) {
                if (! in_array($key, $allowedFields)) {
                    // setting old value
                    $_record->$key = $_oldRecord->$key;
                }
            } 
        } else {
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
            
            $diff = $_record->diff($_oldRecord);
            if (array_key_exists('display_format', $diff)) {
                // delete message body cache because display format has changed
                Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('getMessageBody'));
            }
        }
        
        // @todo reset capabilities if imap host / port changed
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
                if (! Tinebase_Core::getUser()->hasRight('Felamimail', Felamimail_Acl_Rights::ADD_ACCOUNTS)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to add accounts!");
                }
                break;                
            case 'update':
            case 'delete':
                if (! Tinebase_Core::getUser()->hasRight('Felamimail', Felamimail_Acl_Rights::MANAGE_ACCOUNTS)) {
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
    public function updateCapabilities(Felamimail_Model_Account $_account, Felamimail_Backend_ImapProxy $_imapBackend)
    {
        if (isset($_SESSION['Felamimail']) && array_key_exists($_account->getId(), $_SESSION['Felamimail'])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting capabilities of account ' . $_account->name . ' from SESSION.');
            return $_SESSION['Felamimail'][$_account->getId()];
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting capabilities of account ' . $_account->name);
        
        // get imap server capabilities and save delimiter / personal namespace in account
        $capabilities = $_imapBackend->getCapabilityAndNamespace();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($capabilities, TRUE));
        
        if (isset($capabilities['namespace'])) {
            $_account->delimiter     = $capabilities['namespace']['personal']['delimiter'];
            if ($_account->delimiter) {
                $_account->delimiter = substr($_account->delimiter, 0, 1);
            }
        
            $_account->ns_personal   = (! empty($capabilities['namespace']['personal'])) ? $capabilities['namespace']['personal']['name']: '';
            $_account->ns_other      = (! empty($capabilities['namespace']['other']))    ? $capabilities['namespace']['other']['name']   : '';
            $_account->ns_shared     = (! empty($capabilities['namespace']['shared']))   ? $capabilities['namespace']['shared']['name']  : '';

            if ($_account->ns_personal !== 'NIL') {
                // update sent/trash folders
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting personal namespace: "' . $_account->ns_personal . '"');
                if (! empty($_account->ns_personal) && ! preg_match('/^' . $_account->ns_personal . '/', $_account->sent_folder) && ! preg_match('/^' . $_account->ns_personal . '/', $_account->trash_folder)) {
                    $_account->sent_folder = $_account->ns_personal . $_account->sent_folder;
                    $_account->trash_folder = $_account->ns_personal . $_account->trash_folder;
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Updated sent/trash folder names: ' . $_account->sent_folder .' / ' . $_account->trash_folder);
                }
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' No personal namespace available!');
            }            
        }

//         else if ($_delimiter !== NULL) {
//            // get delimiter from params
//            if ($_delimiter != $_account->delimiter) {
//                $_account->delimiter = $_delimiter;
//                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting new delimiter: ' . $_delimiter);
//            }
//        }
        
        // check if server has 'CHILDREN' support
        $_account->has_children_support = (in_array('CHILDREN', $capabilities['capabilities'])) ? 1 : 0;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating capabilities for account: ' . $_account->name);
        
        $result = $this->_backend->update($_account);
        
        // save capabilities in SESSION
        $_SESSION['Felamimail'][$_account->getId()] = $capabilities;
        
        return $capabilities;
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
     * check system folders and create them if not found
     * 
     * @param $_account
     * 
     * @todo add more?
     */
    public function checkSystemFolders($_account)
    {
        $systemFoldersToCheck = array(Felamimail_Model_Folder::FOLDER_TRASH, Felamimail_Model_Folder::FOLDER_SENT);
        foreach($systemFoldersToCheck as $folder) {
            $this->getSystemFolder($_account, $folder);
        }
    }
    
    /**
     * get system folder for account
     * 
     * @param $_account
     * @param $_systemFolder
     * @return NULL|Felamimail_Model_Folder
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function getSystemFolder($_account, $_systemFolder)
    {
        $account = ($_account instanceof Felamimail_Model_Account) ? $_account : $this->get($_account);
        $changed = $this->_addFolderDefaults($account);
        if ($changed) {
            // need to use backend update because we prohibit the change of some fields in _inspectBeforeUpdate()
            $account = $this->_backend->update($account);            
        }
        
        $imapBackend = $this->_getIMAPBackend($account);
        
        switch ($_systemFolder) {
            case Felamimail_Model_Folder::FOLDER_TRASH:
                $folderName = $account->trash_folder;
                break;
            case Felamimail_Model_Folder::FOLDER_SENT:
                $folderName = $account->sent_folder;
                break;
            case Felamimail_Model_Folder::FOLDER_TEMPLATES:
                $folderName = $account->templates_folder;
                break;
            case Felamimail_Model_Folder::FOLDER_DRAFTS:
                $folderName = $account->drafts_folder;
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('No system folder: ' . $_systemFolder);
        }
        
        if (empty($folderName)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' No ' . $_systemFolder . ' folder set in account.');
            return NULL;
        }
        
        // check if folder exists on imap server
        if ($imapBackend->getFolderStatus(Felamimail_Model_Folder::encodeFolderName($folderName)) === false) {
            $systemFolder = $this->_createSystemFolder($account, $folderName);
        } else {
            try {
                $systemFolder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($account->getId(), $folderName);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Found system folder: ' . $folderName);
                                
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' 
                    . $tenf->getMessage());
                
                $splitFolderName = Felamimail_Model_Folder::extractLocalnameAndParent($_systemFolder, $_account->delimiter);
                Felamimail_Controller_Cache_Folder::getInstance()->update($account, $splitFolderName['parent'], TRUE, FALSE);
                $systemFolder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($account->getId(), $folderName);
            }
        }
        
        return $systemFolder;
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
        
        return Felamimail_Controller_Folder::getInstance()->create($_account, $splitFolderName['localname'], $splitFolderName['parent']);
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
            $account = $this->update($account);
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
        $userId = $this->_currentAccount->getId();
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
            Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT} = $systemAccount->getId();
            
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
            $userCredentialCache->password
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
            Tinebase_Model_Config::IMAP     => array(
                'keys'      => array('host', 'port', 'ssl'),
                'defaults'  => array('port' => 143),
            ),
            Tinebase_Model_Config::SMTP     => array(
                'keys'      => array('hostname', 'port', 'ssl', 'auth'),
                'defaults'  => array('port' => 25),
            ),
            Tinebase_Model_Config::SIEVE    => array(
                'keys'      => array('hostname', 'port', 'ssl'),
                'defaults'  => array('port' => 2000),
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
     * @param string $_configKey for example Tinebase_Model_Config::IMAP for imap settings 
     * @param array $_keysOverwrite keys to overwrite
     * @param array $_defaults
     */
    protected function _addConfigValuesToAccount(Felamimail_Model_Account $_account, $_configKey, $_keysOverwrite = array(), $_defaults = array())
    {
        $config = ($_configKey == Tinebase_Model_Config::IMAP) ? $this->_imapConfig : Tinebase_Config::getInstance()->getConfigAsArray($_configKey, 'Tinebase', $_defaults);
        $prefix = ($_configKey == Tinebase_Model_Config::IMAP) ? '' : strtolower($_configKey) . '_';
        
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
            $_user = Tinebase_User::getInstance()->getFullUserById($this->_currentAccount->getId());
        }
        
        if ($_email === NULL) {
            $_email = $this->_getAccountEmail($_user);
        }
        
        // add user data
        $_account->user   = $_user->accountLoginName;
        $_account->email  = $_email;
        $_account->name   = $_email;
        $_account->from   = $_user->accountFullName;
        
        // add contact data
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($_user->getId(), TRUE);
        $_account->organization = $contact->org_name;
    }
}
