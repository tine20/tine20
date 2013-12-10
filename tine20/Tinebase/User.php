<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * User Class
 *
 * @package     Tinebase
 * @subpackage  User
 */
class Tinebase_User
{
    /**
     * backend constants
     * 
     * @var string
     */
    const ACTIVEDIRECTORY = 'ActiveDirectory';
    const LDAP   = 'Ldap';
    const SQL    = 'Sql';
    const TYPO3  = 'Typo3';
    
    /**
     * user status constants
     * 
     * @var string
     * 
     * @todo use constants from model
     */
    const STATUS_BLOCKED  = 'blocked';
    const STATUS_DISABLED = 'disabled';
    const STATUS_ENABLED  = 'enabled';
    const STATUS_EXPIRED  = 'expired';
    
    /**
     * Key under which the default user group name setting will be stored/retrieved
     *
     */
    const DEFAULT_USER_GROUP_NAME_KEY = 'defaultUserGroupName';
    
    /**
     * Key under which the default admin group name setting will be stored/retrieved
     *
     */
    const DEFAULT_ADMIN_GROUP_NAME_KEY = 'defaultAdminGroupName';

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * don't clone. Use the singleton.
     */
    private function __clone() {}

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_User_Interface
     */
    private static $_instance = NULL;

    /**
     * Holds the accounts backend type (e.g. Ldap or Sql.
     * Property is lazy loaded on first access via getter {@see getConfiguredBackend()}
     * 
     * @var array | optional
     */
    private static $_backendType;
    
    /**
     * Holds the backend configuration options.
     * Property is lazy loaded from {@see Tinebase_Config} on first access via
     * getter {@see getBackendConfiguration()}
     * 
     * @var array | optional
     */
    private static $_backendConfiguration;
    
    /**
     * Holds the backend configuration options.
     * Property is lazy loaded from {@see Tinebase_Config} on first access via
     * getter {@see getBackendConfiguration()}
     * 
     * @var array | optional
     */
    private static $_backendConfigurationDefaults = array(
        self::SQL => array(
            self::DEFAULT_USER_GROUP_NAME_KEY  => Tinebase_Group::DEFAULT_USER_GROUP,
            self::DEFAULT_ADMIN_GROUP_NAME_KEY => Tinebase_Group::DEFAULT_ADMIN_GROUP,
        ),
        self::LDAP => array(
            'host' => 'localhost',
            'username' => '',
            'password' => '',
            'bindRequiresDn' => true,
            'useRfc2307bis' => false,
            'userDn' => '',
            'userFilter' => 'objectclass=posixaccount',
            'userSearchScope' => Zend_Ldap::SEARCH_SCOPE_SUB,
            'groupsDn' => '',
            'groupFilter' => 'objectclass=posixgroup',
            'groupSearchScope' => Zend_Ldap::SEARCH_SCOPE_SUB,
            'pwEncType' => 'SSHA',
            'minUserId' => '10000',
            'maxUserId' => '29999',
            'minGroupId' => '11000',
            'maxGroupId' => '11099',
            'groupUUIDAttribute' => 'entryUUID',
            'userUUIDAttribute' => 'entryUUID',
            self::DEFAULT_USER_GROUP_NAME_KEY  => Tinebase_Group::DEFAULT_USER_GROUP,
            self::DEFAULT_ADMIN_GROUP_NAME_KEY => Tinebase_Group::DEFAULT_ADMIN_GROUP,
            'readonly' => false,
        ),
        self::ACTIVEDIRECTORY => array(
            'host' => 'localhost',
            'username' => '',
            'password' => '',
            'bindRequiresDn' => true,
            'useRfc2307' => false,
            'userDn' => '',
            'userFilter' => 'objectclass=user',
            'userSearchScope' => Zend_Ldap::SEARCH_SCOPE_SUB,
            'groupsDn' => '',
            'groupFilter' => 'objectclass=group',
            'groupSearchScope' => Zend_Ldap::SEARCH_SCOPE_SUB,
            'minUserId' => '10000',
            'maxUserId' => '29999',
            'minGroupId' => '11000',
            'maxGroupId' => '11099',
            'groupUUIDAttribute' => 'objectGUID',
            'userUUIDAttribute' => 'objectGUID',
            self::DEFAULT_USER_GROUP_NAME_KEY  => 'Domain Users',
            self::DEFAULT_ADMIN_GROUP_NAME_KEY => 'Domain Admins',
            'readonly' => false,
         )
    );
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_User_Abstract
     */
    public static function getInstance() 
    {
        $backendType = self::getConfiguredBackend();
        
        if (self::$_instance === NULL) {
            $backendType = self::getConfiguredBackend();
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' accounts backend: ' . $backendType);
            
            self::$_instance = self::factory($backendType);
        }
        
        return self::$_instance;
    }
        
    /**
     * return an instance of the current user backend
     *
     * @param   string $backendType name of the user backend
     * @return  Tinebase_User_Abstract
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public static function factory($backendType) 
    {
        $options = self::getBackendConfiguration();
        
        // this is a dangerous TRACE as there might be passwords in here!
        //if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' 
        //    . print_r($options, TRUE));
        
        $options['plugins'] = array();
        
        // manage email user settings
        if (Tinebase_EmailUser::manages(Tinebase_Config::IMAP)) {
            try {
                $options['plugins'][] = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Could not add IMAP EmailUser plugin: ' . $e);
            }
        }
        if (Tinebase_EmailUser::manages(Tinebase_Config::SMTP)) {
            try {
                $options['plugins'][] = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
                        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Could not add SMTP EmailUser plugin: ' . $e);
            }
        }
        
        switch ($backendType) {
            case self::ACTIVEDIRECTORY:
                $result  = new Tinebase_User_ActiveDirectory($options);
                
                break;
                
            case self::LDAP:
                // manage samba sam?
                if (isset(Tinebase_Core::getConfig()->samba) && Tinebase_Core::getConfig()->samba->get('manageSAM', FALSE) == true) {
                    $options['plugins'][] = new Tinebase_User_Plugin_Samba(Tinebase_Core::getConfig()->samba->toArray());
                }
                
                $result  = new Tinebase_User_Ldap($options);
                
                break;
                
            case self::SQL:
                $result = new Tinebase_User_Sql($options);
                
                break;
            
            case self::TYPO3:
                $result = new Tinebase_User_Typo3($options);
                
                break;
                
            default:
                throw new Tinebase_Exception_InvalidArgument("User backend type $backendType not implemented.");
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Created user backend of type ' . $backendType);
        
        return $result;
    }
    
    /**
     * returns the configured rs backend
     * 
     * @return string
     */
    public static function getConfiguredBackend()
    {
        if (!isset(self::$_backendType)) {
            if (Setup_Controller::getInstance()->isInstalled('Tinebase')) {
                self::setBackendType(Tinebase_Config::getInstance()->get(Tinebase_Config::USERBACKENDTYPE, self::SQL));
            } else {
                self::setBackendType(self::SQL);
            }
        }
        
        return self::$_backendType;
    }
    
    /**
     * setter for {@see $_backendType}
     * 
     * @todo persist in db
     * 
     * @param string $backendType
     * @return void
     */
    public static function setBackendType($backendType)
    {
        if (empty($backendType)) {
            throw new Tinebase_Exception_InvalidArgument('Backend type can not be empty!');
        }
        
        $newBackendType = ucfirst($backendType);
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
            ' Setting backend type to ' . $newBackendType);
        
        self::$_backendType = $newBackendType;
    }
    
    /**
     * Setter for {@see $_backendConfiguration}
     * 
     * NOTE:
     * Setting will not be written to Database or Filesystem.
     * To persist the change call {@see saveBackendConfiguration()}
     * 
     * @param mixed $_value
     * @param string $_key
     * @param boolean $_applyDefaults
     * @return void
     * 
     * @todo generalize this (see Tinebase_Auth::setBackendConfiguration)
     */
    public static function setBackendConfiguration($_value, $_key = null, $_applyDefaults = false)
    {
        $defaultValues = self::$_backendConfigurationDefaults[self::getConfiguredBackend()];
        
        if (is_null($_key) && !is_array($_value)) {
            throw new Tinebase_Exception_InvalidArgument('To set backend configuration either a key and value '
                . 'parameter are required or the value parameter should be a hash');
        } elseif (is_null($_key) && is_array($_value)) {
            $configToSet = $_applyDefaults ? array_merge($defaultValues, $_value) : $_value;
            foreach ($configToSet as $key => $value) {
                self::setBackendConfiguration($value, $key);
            }
        } else {
            if ( ! (isset($defaultValues[$_key]) || array_key_exists($_key, $defaultValues))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
                    " Cannot set backend configuration option '$_key' for accounts storage " . self::getConfiguredBackend());
                return;
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' Setting backend key ' . $_key . ' to ' . (preg_match('/password|pwd|pass|passwd/i', $_key) ? '********' : $_value));
            
            self::$_backendConfiguration[$_key] = $_value;
        }
    }
    
    /**
     * Delete the given config setting or all config settings if {@param $_key} is not specified
     * 
     * @param string | optional $_key
     * @return void
     */
    public static function deleteBackendConfiguration($_key = null)
    {
        if (is_null($_key)) {
            self::$_backendConfiguration = array();
        } elseif ((isset(self::$_backendConfiguration[$_key]) || array_key_exists($_key, self::$_backendConfiguration))) {
            unset(self::$_backendConfiguration[$_key]);
        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' configuration option does not exist: ' . $_key);
        }
    }
    
    /**
     * Write backend configuration setting {@see $_backendConfigurationSettings} and {@see $_backendType} to
     * db config table.
     * 
     * @return void
     */
    public static function saveBackendConfiguration()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USERBACKEND, self::getBackendConfiguration());
        Tinebase_Config::getInstance()->set(Tinebase_Config::USERBACKENDTYPE, self::getConfiguredBackend());
    }
    
    /**
     * Getter for {@see $_backendConfiguration}
     * 
     * @param String | optional $_key
     * @return mixed [If {@param $_key} is set then only the specified option is returned, otherwise the whole options hash]
     */
    public static function getBackendConfiguration($_key = null, $_default = null)
    {
        //lazy loading for $_backendConfiguration
        if (!isset(self::$_backendConfiguration)) {
            if (Setup_Controller::getInstance()->isInstalled('Tinebase')) {
                $rawBackendConfiguration = Tinebase_Config::getInstance()->get(Tinebase_Config::USERBACKEND, new Tinebase_Config_Struct())->toArray();
            } else {
                $rawBackendConfiguration = array();
            }
            self::$_backendConfiguration = is_array($rawBackendConfiguration) ? $rawBackendConfiguration : Zend_Json::decode($rawBackendConfiguration);
        }

        if (isset($_key)) {
            return (isset(self::$_backendConfiguration[$_key]) || array_key_exists($_key, self::$_backendConfiguration)) ? self::$_backendConfiguration[$_key] : $_default;
        } else {
            return self::$_backendConfiguration;
        }
    }
    
    /**
     * Returns default configuration for all supported backends 
     * and overrides the defaults with concrete values stored in this configuration 
     * 
     * @param boolean $_getConfiguredBackend
     * @return mixed [If {@param $_key} is set then only the specified option is returned, otherwise the whole options hash]
     */
    public static function getBackendConfigurationWithDefaults($_getConfiguredBackend = TRUE)
    {
        $config = array();
        $defaultConfig = self::getBackendConfigurationDefaults();
        foreach ($defaultConfig as $backendType => $backendConfig) {
            $config[$backendType] = ($_getConfiguredBackend && $backendType == self::getConfiguredBackend() ? self::getBackendConfiguration() : array());
            if (is_array($config[$backendType])) {
                foreach ($backendConfig as $key => $value) {
                    if (! (isset($config[$backendType][$key]) || array_key_exists($key, $config[$backendType]))) {
                        $config[$backendType][$key] = $value;
                    }
                }
            } else {
                $config[$backendType] = $backendConfig;
            }
        }
        return $config;
    }
    
    /**
     * Getter for {@see $_backendConfigurationDefaults}
     * @param String | optional $_backendType
     * @return array
     */
    public static function getBackendConfigurationDefaults($_backendType = null) {
        if ($_backendType) {
            if (!(isset(self::$_backendConfigurationDefaults[$_backendType]) || array_key_exists($_backendType, self::$_backendConfigurationDefaults))) {
                throw new Tinebase_Exception_InvalidArgument("Unknown backend type '$_backendType'");
            }
            return self::$_backendConfigurationDefaults[$_backendType];
        } else {
            return self::$_backendConfigurationDefaults;
        }
    }
    
    /**
     * syncronize user from syncbackend to local sql backend
     * 
     * @param  mixed  $username  the login id of the user to synchronize
     * @param  array $options
     * @return Tinebase_Model_FullUser
     * @throws Tinebase_Exception
     * 
     * @todo make use of dbmail plugin configurable (should be false by default)
     * @todo switch to new primary group if it could not be found
     */
    public static function syncUser($username, $options = array())
    {
        if ($username instanceof Tinebase_Model_FullUser) {
            $username = $username->accountLoginName;
        } else {
            $username = $username;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . "  sync user data for: " . $username);
        
        $userBackend  = Tinebase_User::getInstance();
        if (isset($options['ldapplugins']) && is_array($options['ldapplugins'])) {
            foreach ($options['ldapplugins'] as $plugin) {
                $userBackend->registerLdapPlugin($plugin);
            }
        }
        
        $groupBackend = Tinebase_Group::getInstance();
        
        $user = $userBackend->getUserByPropertyFromSyncBackend('accountLoginName', $username, 'Tinebase_Model_FullUser');
        $user->accountPrimaryGroup = $groupBackend->resolveGIdNumberToUUId($user->accountPrimaryGroup);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($user->toArray(), TRUE));
        
        // make sure primary group exists
        try {
            $group = $groupBackend->getGroupById($user->accountPrimaryGroup);
        } catch (Tinebase_Exception_Record_NotDefined $tern) {
            try {
                $group = $groupBackend->getGroupByIdFromSyncBackend($user->accountPrimaryGroup);
            } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                throw new Tinebase_Exception('Primary group ' . $user->accountPrimaryGroup . ' not found in sync backend.');
            }
            try {
                $sqlGgroup = $groupBackend->getGroupByName($group->name);
                throw new Tinebase_Exception('Group already exists but it has a different ID: ' . $group->name);
                
            } catch (Tinebase_Exception_Record_NotDefined $tern) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Adding group " . $group->name);
                $group = $groupBackend->addGroupInSqlBackend($group);
            }
        }
        
        try {
            $currentUser = $userBackend->getUserByProperty('accountId', $user, 'Tinebase_Model_FullUser');
        
            $currentUser->accountLoginName          = $user->accountLoginName;
            $currentUser->accountLastPasswordChange = $user->accountLastPasswordChange;
            $currentUser->accountExpires            = $user->accountExpires;
            $currentUser->accountPrimaryGroup       = $user->accountPrimaryGroup;
            $currentUser->accountDisplayName        = $user->accountDisplayName;
            $currentUser->accountLastName           = $user->accountLastName;
            $currentUser->accountFirstName          = $user->accountFirstName;
            $currentUser->accountFullName           = $user->accountFullName;
            $currentUser->accountEmailAddress       = $user->accountEmailAddress;
            $currentUser->accountHomeDirectory      = $user->accountHomeDirectory;
            $currentUser->accountLoginShell         = $user->accountLoginShell;
        
            $syncedUser = $userBackend->updateUserInSqlBackend($currentUser);
            $userBackend->updatePluginUser($syncedUser, $user);
            
        } catch (Tinebase_Exception_NotFound $ten) {
            try {
                $invalidUser = $userBackend->getUserByPropertyFromSqlBackend('accountLoginName', $username, 'Tinebase_Model_FullUser');
                if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . " remove invalid user: " . $username);
                $userBackend->deleteUserInSqlBackend($invalidUser);
            } catch (Tinebase_Exception_NotFound $ten) {
                // do nothing
            }
        
            self::syncContact($user);
            $syncedUser = $userBackend->addUserInSqlBackend($user);
            $userBackend->addPluginUser($syncedUser, $user);
        }
        
        // import contactdata(phone, address, fax, birthday. photo)
        if (isset($options['syncContactData']) && $options['syncContactData'] && Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
            $addressbook = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
            
            try {
                $contact = $addressbook->getByUserId($syncedUser->getId());
                
                $userBackend->updateContactFromSyncBackend($syncedUser, $contact);
                $addressbook->update($contact);
            } catch (Addressbook_Exception_NotFound $aenf) {
                // do nothing => user has no contact in addressbook
            }
        }
        
        // sync group memberships
        Tinebase_Group::syncMemberships($syncedUser);
        
        return $syncedUser;
    }
    
    /**
     * create contact in addressbook
     * 
     * @param Tinebase_Model_FullUser $user
     */
    public static function syncContact($user)
    {
        if (! Tinebase_Application::getInstance()->isInstalled('Addressbook')) {
            return;
        }
        
        $addressbook = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        $internalAddressbook = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Internal Contacts', Tinebase_Model_Container::TYPE_SHARED);
    
        $contact = new Addressbook_Model_Contact(array(
            'n_family'      => $user->accountLastName,
            'n_given'       => $user->accountFirstName,
            'n_fn'          => $user->accountFullName,
            'n_fileas'      => $user->accountDisplayName,
            'email'         => $user->accountEmailAddress,
            'type'          => Addressbook_Model_Contact::CONTACTTYPE_USER,
            'container_id'  => $internalAddressbook->getId()
        ));
    
        // add modlog info
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($contact, 'create');
    
        $contact = $addressbook->create($contact);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . " Added contact " . $contact->n_given);
    
        $user->contact_id = $contact->getId();
    }
    
    /**
     * import users from sync backend
     * 
     * @param array $options
     */
    public static function syncUsers($options)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' start synchronizing users');
        
        $users = Tinebase_User::getInstance()->getUsersFromSyncBackend(NULL, NULL, 'ASC', NULL, NULL, 'Tinebase_Model_FullUser');

        foreach($users as $user) {
            try {
                $user = self::syncUser($user, $options);
            } catch (Tinebase_Exception_NotFound $ten) {
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . " User {$user->accountLoginName} not synced: "
                    . $ten->getMessage());
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' finished synchronizing users');
    }

    /**
     * get all user passwords from ldap
     * - set pw for user (in sql and sql plugins)
     * - do not encrypt the pw again as it is encrypted in LDAP
     * 
     * @throws Tinebase_Exception_Backend
     */
    public static function syncLdapPasswords()
    {
        $userBackend = Tinebase_User::getInstance();
        if (! $userBackend instanceof Tinebase_User_Ldap) {
            throw new Tinebase_Exception_Backend('Needs LDAP accounts backend');
        }
        
        $result = $userBackend->getUserAttributes(array('entryUUID', 'userPassword'));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' About to sync ' . count($result) . ' user passwords from LDAP to Tine 2.0.');
        
        $sqlBackend = Tinebase_User::factory(self::SQL);
        foreach ($result as $user) {
            try {
                $sqlBackend->setPassword($user['entryUUID'], $user['userPassword'], FALSE);
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Could not find user with id ' . $user['entryUUID'] . ' in SQL backend.');
            }
        }
    }
    
    /**
     * create initial admin account
     * 
     * Method is called during Setup Initialization
     *
     * $_options may contain the following keys:
     * <code>
     * $options = array(
     *  'adminLoginName'    => 'admin',
     *  'adminPassword'     => 'lars',
     *  'adminFirstName'    => 'Tine 2.0',
     *  'adminLastName'     => 'Admin Account',
     *  'adminEmailAddress' => 'admin@tine20domain.org',
     *  'expires'            => Tinebase_DateTime object
     * );
     * </code>
     *
     * @param array $_options [hash that may contain override values for admin user name and password]
     * @return void
     */
    public static function createInitialAccounts($_options)
    {
        if (! isset($_options['adminPassword']) || ! isset($_options['adminLoginName'])) {
            throw new Tinebase_Exception_InvalidArgument('Admin password and login name have to be set when creating initial account.', 503);
        }
        
        $adminLoginName     = $_options['adminLoginName'];
        $adminPassword      = $_options['adminPassword'];
        $adminFirstName     = isset($_options['adminFirstName'])    ? $_options['adminFirstName'] : 'Tine 2.0';
        $adminLastName      = isset($_options['adminLastName'])     ? $_options['adminLastName']  : 'Admin Account';
        $adminEmailAddress  = ((isset($_options['adminEmailAddress']) || array_key_exists('adminEmailAddress', $_options))) ? $_options['adminEmailAddress'] : NULL;

        // get admin & user groups
        $userBackend   = Tinebase_User::factory(Tinebase_User::SQL);
        $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);
        
        $adminGroup = $groupsBackend->getDefaultAdminGroup();
        $userGroup  = $groupsBackend->getDefaultGroup();
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating initial admin user (login: ' . $adminLoginName . ' / email: ' . $adminEmailAddress . ')');

        $user = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => $adminLoginName,
            'accountStatus'         => 'enabled',
            'accountPrimaryGroup'   => $userGroup->getId(),
            'accountLastName'       => $adminLastName,
            'accountDisplayName'    => $adminLastName . ', ' . $adminFirstName,
            'accountFirstName'      => $adminFirstName,
            'accountExpires'        => (isset($_options['expires'])) ? $_options['expires'] : NULL,
            'accountEmailAddress'   => $adminEmailAddress
        ));
        
        if ($adminEmailAddress !== NULL) {
            $user->imapUser = new Tinebase_Model_EmailUser(array(
                'emailPassword' => $adminPassword
            ));
            $user->smtpUser = new Tinebase_Model_EmailUser(array(
                'emailPassword' => $adminPassword
            ));
        }

        // update or create user in local sql backend
        try {
            $userBackend->getUserByProperty('accountLoginName', $adminLoginName);
            $user = $userBackend->updateUserInSqlBackend($user);
        } catch (Tinebase_Exception_NotFound $ten) {
            // call addUser here to make sure, sql user plugins (email, ...) are triggered
            $user = $userBackend->addUser($user);
        }
        
        // set the password for the account
        Tinebase_User::getInstance()->setPassword($user, $adminPassword);

        // add the admin account to all groups
        Tinebase_Group::getInstance()->addGroupMember($adminGroup, $user);
        Tinebase_Group::getInstance()->addGroupMember($userGroup, $user);
        
    }
}
