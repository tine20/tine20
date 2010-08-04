<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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

    /******************************** overwritten funcs *********************************/
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param boolean $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     * 
     * @todo move creation of system account to another place
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        if ($_filter === NULL) {
            $_filter = new Felamimail_Model_AccountFilter(array());
        }
        
        $this->_checkRight($_action);
        $this->checkFilterACL($_filter, $_action);
        $result = $this->_backend->search($_filter, $_pagination, $_onlyIds);
        
        // check preference / config if we should add system account with tine user credentials or from config.inc.php
        if ($this->_useSystemAccount && ! $_onlyIds) {
            if (count($result) == 0) { 
                $result = $this->_addSystemAccount();
            } else {
                // check if resultset contains system account and add config values
                foreach($result as $account) {
                    if ($account->type == Felamimail_Model_Account::TYPE_SYSTEM) {
                        $this->_addSystemAccountConfigValues($account);
                    }
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
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * 
     * @todo do we really want to add new account as default account pref?
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        $result = parent::create($_record);
        
        // set as default account
        Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT} = $result->getId();
        
        // update account capabilities
        return $this->updateCapabilities($result);
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $result = parent::update($_record);
        
        // update account capabilities
        return $this->updateCapabilities($result);
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
        foreach ($_filter->getFilterObjects() as $filter) {
            if ($filter->getField() === 'user_id') {
                $userFilter = $filter;
                $userFilter->setValue($this->_currentAccount->getId());
            }
        }
        
        if (! isset($userFilter)) {
            // force a $userFilter filter (ACL)
            $userFilter = $_filter->createFilter('user_id', 'equals', $this->_currentAccount->getId());
            $_filter->addFilter($userFilter);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding user_id filter.');
        }
    }

    /**
     * inspect creation of one record
     * - add credentials and user id here
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectCreate(Tinebase_Record_Interface $_record)
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
     * inspect update of one record
     * - update credentials here / only allow to update certain fields of system accounts
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectUpdate($_record, $_oldRecord)
    {
        if ($_record->type == Felamimail_Model_Account::TYPE_SYSTEM) {
            // only allow to update some values for system accounts
            $allowedFields = array(
                'name',
                'signature',
                'intelligent_folders',
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
    
    /******************************** public funcs ************************************/
    
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
     * get imap server capabilities and save delimiter / personal namespace in account
     *
     * @param Felamimail_Model_Account $_account
     * @param Felamimail_Backend_ImapProxy $_imapBackend
     * @param string $_delimiter
     * @return Felamimail_Model_Account
     * 
	 * @todo only get all capabilities once (the first time this account connects) / only update namespaces and delimiter later
     */
    public function updateCapabilities($_account, Felamimail_Backend_ImapProxy $_imapBackend = NULL, $_delimiter = NULL)
    {
        if ($_imapBackend === NULL) {
            $_imapBackend = Felamimail_Backend_ImapFactory::factory($_account);
        }
        
        // get imap server capabilities and save delimiter / personal namespace in account
        $capabilities = $_imapBackend->getCapabilityAndNamespace();
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($capabilities, TRUE));
        
        if (isset($capabilities['namespace'])) {
            $_account->delimiter     = $capabilities['namespace']['personal']['delimiter'];
            $_account->ns_personal   = (! empty($capabilities['namespace']['personal'])) ? $capabilities['namespace']['personal']['name']: '';
            $_account->ns_other      = (! empty($capabilities['namespace']['other']))    ? $capabilities['namespace']['other']['name']   : '';
            $_account->ns_shared     = (! empty($capabilities['namespace']['shared']))   ? $capabilities['namespace']['shared']['name']  : '';
            
            if ($_account->ns_personal == 'NIL') {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' No personal namespace available!');
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting personal namespace: "' . $_account->ns_personal . '"');
                // update sent/trash folders
                if (! empty($_account->ns_personal) && ! preg_match('/$' . $_account->ns_personal . '/', $_account->sent_folder) && ! preg_match('/$' . $_account->ns_personal . '/', $_account->trash_folder)) {
                    $_account->sent_folder = $_account->ns_personal . $_account->sent_folder;
                    $_account->trash_folder = $_account->ns_personal . $_account->trash_folder;
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Updated sent/trash folder names: ' . $_account->sent_folder .' / ' . $_account->trash_folder);
                }
            }
            
        } else if ($_delimiter !== NULL) {
            // get delimiter from params
            if ($_delimiter != $_account->delimiter) {
                $_account->delimiter = $_delimiter;
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting new delimiter: ' . $_delimiter);
            }
        }
        
        // check if server has 'CHILDREN' support
        $_account->has_children_support = (in_array('CHILDREN', $capabilities['capabilities'])) ? 1 : 0;
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating capabilities for account: ' . $_account->name);
        
        if ($_account->delimiter) {
            $_account->delimiter = substr($_account->delimiter, 0, 1);
        }
        
        $result = $this->_backend->update($_account);
        return $this->get($result->getId());
    }
    
    /******************************** protected funcs *********************************/

    /**
     * add system account with tine user credentials (from config.inc.php or config db) 
     *
     * @return Tinebase_Record_RecordSet
     */
    protected function _addSystemAccount()
    {
        $result = new Tinebase_Record_RecordSet('Felamimail_Model_Account');
        
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
            
            // set some default settings if not set
            if (empty($systemAccount->sent_folder)) {
                $systemAccount->sent_folder = 'Sent';
            }
            if (empty($systemAccount->trash_folder)) {
                $systemAccount->trash_folder = 'Trash';
            }
            
            // create new account and update capabilities
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($systemAccount, 'create');
            $systemAccount = $this->_backend->create($systemAccount);
            $systemAccount = $this->updateCapabilities($systemAccount);
            $result->addRecord($systemAccount);
            $this->_addedDefaultAccount = TRUE;
            
            // set as default account preference
            Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT} = $systemAccount->getId();
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Created new system account "' . $systemAccount->name . '".');
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($systemAccount->toArray(), TRUE));
            
        } else {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Could not create system account for user ' . $fullUser->accountLoginName . '. No email address given.');
        }
                
        return $result;
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
            
        if (! preg_match('/@/', $email)) {
            $email .= '@' . $this->_imapConfig['host'];
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
    protected function _createCredentials($_username = NULL, $_password = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Create new account credentials for username ' . $_username);
        
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
                'defaults'  => array(),
            ),
        );
        
        foreach ($configs as $configKey => $values) {
            $this->_addConfigValuesToAccount($_account, $configKey, $values['keys'], $values['defaults']);
        }
        
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
        
        foreach ($config as $key => $value) {
            if (in_array($key, $_keysOverwrite)) {
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
    protected function _addUserValues(Felamimail_Model_Account $_account, Tinebase_Model_FullUser $_user, $_email = NULL)
    {
        // add user data
        $_account->user   = $_user->accountLoginName;
        $_account->email  = ($_email !== NULL) ? $_email : $_user->accountEmailAddress;
        $_account->name   = ($_email !== NULL) ? $_email : $_user->accountEmailAddress;
        $_account->from   = $_user->accountFullName;
        
        // add contact data
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($_user->getId());
        $_account->organization = $contact->org_name;
    }
}
