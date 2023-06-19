<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * User Class
 *
 * @package     Tinebase
 * @subpackage  User
 */
class Tinebase_User implements Tinebase_Controller_Interface
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
     * Key under which the default anonymous group name setting will be stored/retrieved
     *
     */
    const DEFAULT_ANONYMOUS_GROUP_NAME_KEY = 'defaultAnonymousGroupName';

    const SYSTEM_USER_CRON = 'cronuser';
    const SYSTEM_USER_REPLICATION = 'replicationuser';
    const SYSTEM_USER_ANONYMOUS = 'anonymoususer';
    const SYSTEM_USER_CALENDARSCHEDULING = 'calendarscheduling';
    const SYSTEM_USER_SETUP = 'setupuser';

    /**
     * Do the user sync with the options as configured in the config.
     * see Tinebase_Config:: TODO put key here
     * for details and default behavior
     */
    const SYNC_WITH_CONFIG_OPTIONS = 'sync_with_config_options';

    /**
     * Key under which the default replication group name setting will be stored/retrieved
     */
    const DEFAULT_REPLICATION_GROUP_NAME_KEY = 'defaultReplicationGroupName';

    protected static $_contact2UserMapping = array(
        'n_family'      => 'accountLastName',
        'n_given'       => 'accountFirstName',
        'n_fn'          => 'accountFullName',
        'n_fileas'      => 'accountDisplayName',
        'email'         => 'accountEmailAddress',
        'container_id'  => 'container_id',
    );
    
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
     * @var array|null
     */
    private static $_backendType;
    
    /**
     * Holds the backend configuration options.
     * Property is lazy loaded from {@see Tinebase_Config} on first access via
     * getter {@see getBackendConfiguration()}
     * 
     * @var array|null
     */
    private static $_backendConfiguration;
    
    /**
     * Holds the backend configuration options.
     * Property is lazy loaded from {@see Tinebase_Config} on first access via
     * getter {@see getBackendConfiguration()}
     * 
     * @var array|null
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
            'useStartTls' => false,
            'useSsl' => false,
            'port' => 389,
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
            'emailAttribute' => 'mail',
            self::DEFAULT_USER_GROUP_NAME_KEY  => Tinebase_Group::DEFAULT_USER_GROUP,
            self::DEFAULT_ADMIN_GROUP_NAME_KEY => Tinebase_Group::DEFAULT_ADMIN_GROUP,
            'readonly' => false,
        ),
        self::ACTIVEDIRECTORY => array(
            'host' => 'localhost',
            'username' => '',
            'password' => '',
            'bindRequiresDn' => true,
            'useStartTls' => false,
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
        if (self::$_instance === NULL) {
            $backendType = self::getConfiguredBackend();
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
                __METHOD__ . '::' . __LINE__ .' accounts backend: ' . $backendType);
            
            self::$_instance = self::factory($backendType);
        }
        
        return self::$_instance;
    }

    public static function destroyInstance()
    {
        self::$_instance = null;
        self::$_backendConfiguration = null;
        self::$_backendType = null;
        Tinebase_EmailUser::destroyInstance();
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
        
        $options['plugins'] = array(
            Addressbook_Controller_Contact::getInstance(),
        );
        
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
                $result = new Tinebase_User_Typo3();
                
                break;
                
            default:
                throw new Tinebase_Exception_InvalidArgument("User backend type $backendType not implemented.");
        }

        if ($result instanceof Tinebase_User_Interface_SyncAble) {
            // turn off replicable feature for Tinebase_Model_User
            Tinebase_Model_User::setReplicable(false);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Created user backend of type ' . get_class($result));

        return $result;
    }
    
    /**
     * returns the configured backend
     * 
     * @return string
     */
    public static function getConfiguredBackend()
    {
        if (! isset(self::$_backendType)) {
            if (Tinebase_Application::getInstance()->isInstalled('Tinebase')) {
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
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function setBackendType($backendType)
    {
        if (empty($backendType)) {
            throw new Tinebase_Exception_InvalidArgument('Backend type can not be empty!');
        }
        
        $newBackendType = ucfirst($backendType);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
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
     * @throws Tinebase_Exception_InvalidArgument
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
     * @param string|null $_key
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
     * @param string|null $_key
     * @param string|null $_default
     * @return mixed [If {@param $_key} is set then only the specified option is returned, otherwise the whole options hash]
     */
    public static function getBackendConfiguration($_key = null, $_default = null)
    {
        //lazy loading for $_backendConfiguration
        if (!isset(self::$_backendConfiguration)) {
            if (Tinebase_Application::getInstance()->isInstalled('Tinebase')) {
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
     * @param string|null $_backendType
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
     * @return Tinebase_Group_Ldap
     */
    protected static function _getLdapGroupController()
    {
        return Tinebase_Group::getInstance();
    }

    /**
     * @return Tinebase_User_Ldap
     */
    protected static function _getLdapUserController()
    {
        return Tinebase_User::getInstance();
    }

    /**
     * synchronize user from syncbackend to local sql backend
     * 
     * @param  mixed  $username  the login id of the user to synchronize
     * @param  array $options
     * @return Tinebase_Model_FullUser|null
     * @throws Tinebase_Exception
     * 
     * @todo switch to new primary group if it could not be found
     */
    public static function syncUser($username, $options = array())
    {
        if ($username instanceof Tinebase_Model_FullUser) {
            $username = $username->accountLoginName;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Sync options: ' . print_r($options, true));

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . " Sync user data for: " . $username);

        if (! Tinebase_Core::getUser() instanceof Tinebase_Model_User) {
            $setupUser = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
            Tinebase_Core::set(Tinebase_Core::USER, $setupUser);
        }

        $userBackend  = self::_getLdapUserController();
        if (isset($options['ldapplugins']) && is_array($options['ldapplugins'])) {
            foreach ($options['ldapplugins'] as $plugin) {
                $userBackend->registerLdapPlugin($plugin);
            }
        }

        try {
            $user = $userBackend->getUserByPropertyFromSyncBackend('accountLoginName', $username, 'Tinebase_Model_FullUser');
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Sync User: ' . print_r($user->toArray(), true));
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' '
                . $tenf->getMessage());
            return null;
        }

        $user->accountPrimaryGroup = self::_getLdapGroupController()->resolveGIdNumberToUUId($user->accountPrimaryGroup);
        
        $userProperties = method_exists($userBackend, 'getLastUserProperties') ? $userBackend->getLastUserProperties() : array();

        $hookResult = self::_syncUserHook($user, $userProperties);
        if (! $hookResult) {
            return null;
        }

        $oldContainerAcl = Addressbook_Controller_Contact::getInstance()->doContainerACLChecks(false);
        $oldRequestContext = Addressbook_Controller_Contact::getInstance()->getRequestContext();

        $requestContext = array();
        if (!isset($options['syncContactPhoto']) || !$options['syncContactPhoto']) {
            $requestContext[Addressbook_Controller_Contact::CONTEXT_NO_SYNC_PHOTO] = true;
        }
        if (!isset($options['syncContactData']) || !$options['syncContactData']) {
            $requestContext[Addressbook_Controller_Contact::CONTEXT_NO_SYNC_CONTACT_DATA] = true;
        }
        Addressbook_Controller_Contact::getInstance()->setRequestContext($requestContext);

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {

            self::getPrimaryGroupForUser($user);

            try {

                // this will $userBackend->updatePluginUser
                // the addressbook is registered as a plugin
                $syncedUser = self::_syncDataAndUpdateUser($user, $options);

            } catch (Tinebase_Exception_NotFound $ten) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' ' . $ten->getMessage());
                try {
                    $invalidUser = $userBackend->getUserByPropertyFromSqlBackend('accountLoginName', $username, 'Tinebase_Model_FullUser');
                    if (isset($options['deleteUsers']) && $options['deleteUsers']) {
                        // handle removed users differently with "sync deleted users" config
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . " Skipping user: " . $username . '. Do not remove as it might be the same user as before with different ID.');
                        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                        $transactionId = null;
                        return null;
                    }

                    if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__
                        . " Remove invalid user: " . $username);
                    // this will fire a delete event
                    $userBackend->deleteUserInSqlBackend($invalidUser);
                } catch (Tinebase_Exception_NotFound $ten) {
                    // do nothing
                }

                $visibility = $user->visibility;
                if ($visibility === null) {
                    $visibility = Tinebase_Model_FullUser::VISIBILITY_DISPLAYED;
                }

                Tinebase_Timemachine_ModificationLog::setRecordMetaData($user, 'create');
                $syncedUser = $userBackend->addUserInSqlBackend($user);

                // fire event to make sure all user data is created in the apps
                try {
                    // TODO convert to Tinebase event?
                    $event = new Admin_Event_AddAccount(array(
                        'account' => $syncedUser
                    ));
                    Tinebase_Event::fireEvent($event);
                } catch (Exception $e) {
                    // we continue with user creation - even if the event failed
                    // otherwise we would get lots of duplicate contacts in the addressbook
                    Tinebase_Exception::log($e);
                }

                // the addressbook is registered as a plugin and will take care of the create
                // see \Addressbook_Controller_Contact::inspectUpdateUser
                try {
                    $userBackend->addPluginUser($syncedUser, $user);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                        __METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
                    $transactionId = null;
                    return null;
                }

                $contactId = $syncedUser->contact_id;
                if (!empty($contactId) && $visibility != $syncedUser->visibility) {
                    $syncedUser->visibility = $visibility;
                    $syncedUser = $userBackend->updateUserInSqlBackend($syncedUser);
                    $userBackend->updatePluginUser($syncedUser, $user);
                }
            }

            Tinebase_Group::syncMemberships($syncedUser);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        Addressbook_Controller_Contact::getInstance()->setRequestContext($oldRequestContext === null ? array() : $oldRequestContext);
        Addressbook_Controller_Contact::getInstance()->doContainerACLChecks($oldContainerAcl);

        return $syncedUser;
    }

    /**
     * sync account data and update
     *
     * @param Tinebase_Model_FullUser $user
     * @param array $options
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected static function _syncDataAndUpdateUser($user, $options)
    {
        $currentUser = self::_getLdapUserController()->getUserByProperty('accountId', $user, 'Tinebase_Model_FullUser', true);

        if (self::_checkAndUpdateCurrentUser($currentUser, $user, $options)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Record needs an update');
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($currentUser, 'update');
            $syncedUser = self::_getLdapUserController()->updateUserInSqlBackend($currentUser);
        } else {
            $syncedUser = $currentUser;
        }
        if (! empty($user->container_id)) {
            $syncedUser->container_id = $user->container_id;
        }

        // Addressbook is registered as plugin and will take care of the update
        self::_getLdapUserController()->updatePluginUser($syncedUser, $user);

        return $syncedUser;
    }

    /**
     * @param Tinebase_Model_FullUser $currentUser
     * @param Tinebase_Model_FullUser $user
     * @param array $options
     * @return bool
     * @throws Tinebase_Exception_Backend
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_Validation
     */
    protected static function _checkAndUpdateCurrentUser(Tinebase_Model_FullUser $currentUser, Tinebase_Model_FullUser $user, array $options = [])
    {
       $fieldsToSync = [
            // true = REQUIRED, may be empty; false = OPTIONAL, omit if empty/missing; null IGNORE always
            'accountLoginName' => true, 
            'accountLastPasswordChange' => false, 
            'accountExpires' => false, 
            'accountPrimaryGroup' => true,
            'accountDisplayName' => false, 
            'accountLastName' => true, 
            'accountFirstName' => true, 
            'accountFullName' => false, 
            'accountEmailAddress' => true,
            'accountHomeDirectory' => false, 
            'accountLoginShell' => false, 
            'visibility' => false, 
            'accountStatus' => call_user_func(function() use ($options) {
                if (isset($options['syncAccountStatus'])) {
                    return (bool) $options['syncAccountStatus'];
                }
                return null;
            }),
        ];

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Fields to sync (bool: sync empty?): ' . print_r($fieldsToSync, true));

        $recordNeedsUpdate = false;
        foreach ($fieldsToSync as $field => $syncRequired) {
            // IGNORE field even if not empty
            if ($syncRequired === null) {
                continue;
            }
            // Ignore OPTIONAL fields if empty or missing
            else if (($syncRequired === false) && empty($user->{$field})) {
                continue;
            }
            // ldap might not have time information on datetime fields, so we ignore these, if the date matches
            else if ($user->{$field} instanceof Tinebase_DateTime && $currentUser->{$field} instanceof Tinebase_DateTime
                    && $user->{$field}->hasSameDate($currentUser->{$field})
            ) {
                continue;
            }
            // SYNC NON-EMPTY OPTIONAL or REQUIRED fields
            else if ($currentUser->{$field} !== $user->{$field}) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Diff found in field ' . $field  . ' current: ' . $currentUser->{$field} . ' new: ' . $user->{$field});
                $currentUser->{$field} = $user->{$field};
                $recordNeedsUpdate = true;
            }
        }

        if ($currentUser->is_deleted) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' Undeleteting user ' . $user->accountLoginName);
            $currentUser->visibility = Tinebase_Model_FullUser::VISIBILITY_DISPLAYED;
            $currentUser->accountStatus = Tinebase_Model_FullUser::ACCOUNT_STATUS_ENABLED;
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($currentUser, 'undelete');
            $updatedUser = self::_getLdapUserController()->updateUserInSqlBackend($currentUser, true);
            $currentUser->seq = $updatedUser->seq;
            $currentUser->last_modified_by = $updatedUser->last_modified_by;
            $currentUser->last_modified_time = $updatedUser->last_modified_time;
            $currentUser->deleted_time = $updatedUser->deleted_time;
            $currentUser->deleted_by = $updatedUser->deleted_by;
            $recordNeedsUpdate = false;
        }

        return $recordNeedsUpdate;
    }
    
    /**
     * get primary group for user and make sure that group exists
     * 
     * @param Tinebase_Model_FullUser $user
     * @throws Tinebase_Exception
     * @return Tinebase_Model_Group
     */
    protected static function getPrimaryGroupForUser(Tinebase_Model_FullUser $user): Tinebase_Model_Group
    {
        try {
            $group = Tinebase_Group::getInstance()->getGroupById($user->accountPrimaryGroup);
        } catch (Tinebase_Exception_Record_NotDefined $tern) {
            $group = self::_getPrimaryGroupFromSyncBackend($user);
        }
        
        return $group;
    }

    /**
     * @param Tinebase_Model_FullUser $user
     * @return Tinebase_Model_Group
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_Record_Validation
     */
    protected static function _getPrimaryGroupFromSyncBackend(Tinebase_Model_FullUser $user): Tinebase_Model_Group
    {
        $groupBackend = Tinebase_Group::getInstance();

        if (! $groupBackend instanceof Tinebase_Group_Ldap || $groupBackend->isDisabledBackend()) {
            // groups are sql only
            $group = $groupBackend->getDefaultGroup();
            $user->accountPrimaryGroup = $group->getId();
        } else {
            try {
                $group = $groupBackend->getGroupByIdFromSyncBackend($user->accountPrimaryGroup);
            } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                throw new Tinebase_Exception('Primary group ' . $user->accountPrimaryGroup . ' not found in sync backend.');
            }
            try {
                $groupBackend->getGroupByName($group->name);
                throw new Tinebase_Exception('Group already exists but it has a different ID: ' . $group->name);

            } catch (Tinebase_Exception_Record_NotDefined $tern) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " Adding group " . $group->name);

                $transactionId = Tinebase_TransactionManager::getInstance()
                    ->startTransaction(Tinebase_Core::getDb());
                try {
                    if (Tinebase_Application::getInstance()->isInstalled('Addressbook')) {
                        // here it should be ok to create the list without members
                        Addressbook_Controller_List::getInstance()->createOrUpdateByGroup($group);
                    }
                    $group = $groupBackend->addGroupInSqlBackend($group);

                    Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                    $transactionId = null;
                } finally {
                    if (null !== $transactionId) {
                        Tinebase_TransactionManager::getInstance()->rollBack();
                    }
                }
            }
        }

        return $group;
    }

    /**
     * call configured hooks for adjusting synced user data
     * 
     * @param Tinebase_Model_FullUser $user
     * @param array $userProperties
     * @return boolean if false, user is skipped
     */
    protected static function _syncUserHook(Tinebase_Model_FullUser $user, $userProperties)
    {
        $result = true;
        $hook = Tinebase_Config::getInstance()->getHookClass(Tinebase_Config::SYNC_USER_HOOK_CLASS, 'syncUser');
        if ($hook) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Calling ' . get_class($hook) . '::syncUser() ...');

            try {
                $result = call_user_func_array(array($hook, 'syncUser'), array($user, $userProperties));
            } catch (Tinebase_Exception $te) {
                Tinebase_Exception::log($te);
                return false;
            }
        }

        return $result;
    }

    /**
     * sync user data to contact
     * 
     * @param Tinebase_Model_FullUser $user
     * @param Addressbook_Model_Contact $contact
     * @return Addressbook_Model_Contact
     */
    public static function user2Contact($user, $contact = null)
    {
        if ($contact === null) {
            $contact = new Addressbook_Model_Contact(array(), true);
        }
        
        $contact->type = Addressbook_Model_Contact::CONTACTTYPE_USER;
        
        foreach (self::$_contact2UserMapping as $contactKey => $userKey) {
            if (! empty($contact->{$contactKey}) && $contact->{$contactKey} == $user->{$userKey}) {
                continue;
            }
            
            switch ($contactKey) {
                case 'container_id':
                    $contact->container_id = (! empty($user->container_id)) ? $user->container_id : Admin_Controller_User::getInstance()->getDefaultInternalAddressbook();
                    break;
                default:
                    $contact->{$contactKey} = $user->{$userKey};
            }
        }

        if ($contact->n_fn !== $user->accountFullName) {
            // use accountFullName overwrites contact n_fn
            $contact->n_fn = $user->accountFullName;
        }

        $contact->account_id = $user->getId();

        return $contact;
    }
    
    /**
     * import users from sync backend
     * 
     * @param array $options
     * @return bool
     */
    public static function syncUsers($options = array())
    {
        if (isset($options[self::SYNC_WITH_CONFIG_OPTIONS]) && $options[self::SYNC_WITH_CONFIG_OPTIONS]) {
            $syncOptions = Tinebase_Config::getInstance()->get(Tinebase_Config::USERBACKEND)->{Tinebase_Config::SYNCOPTIONS};
            if (!isset($options['deleteUsers'])) {
                $options['deleteUsers'] = $syncOptions->{Tinebase_Config::SYNC_DELETED_USER};
            }
            if (!isset($options['syncContactPhoto'])) {
                $options['syncContactPhoto'] = $syncOptions->{Tinebase_Config::SYNC_USER_CONTACT_PHOTO};
            }
            if (!isset($options['syncContactData'])) {
                $options['syncContactData'] = $syncOptions->{Tinebase_Config::SYNC_USER_CONTACT_DATA};
            }
            if (!isset($options['syncAccountStatus'])) {
                $options['syncAccountStatus'] = $syncOptions->{Tinebase_Config::SYNC_USER_ACCOUNT_STATUS};
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Start synchronizing users with options ' . print_r($options, true));

        if (! self::_getLdapUserController() instanceof Tinebase_User_Interface_SyncAble) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' User backend is not instanceof Tinebase_User_Ldap, nothing to sync');
            return true;
        }

        $userIdsInSqlBackend = [];
        if (isset($options['deleteUsers']) && $options['deleteUsers']) {
            $userIdsInSqlBackend = self::_getLdapUserController()->getAllUserIdsFromSqlBackend();
        }
        
        $users = self::_getLdapUserController()->getUsersFromSyncBackend(NULL, NULL, 'ASC', NULL, NULL, 'Tinebase_Model_FullUser');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' About to sync ' . count($users) . ' users from sync backend ...');

        $result = true;
        foreach ($users as $user) {
            try {
                self::syncUser($user, $options);

                Tinebase_Lock::keepLocksAlive();
            } catch (Exception $e) {
                $result = false;
                Tinebase_Exception::log($e, null, $user->toArray());
            }
        }

        if (isset($options['deleteUsers']) && $options['deleteUsers']) {
            self::_syncDeletedUsers($users, $userIdsInSqlBackend);
        }

        self::_getLdapGroupController()->resetClassCache();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Finished synchronizing users.');

        return $result;
    }

    /**
     * deletes user in tine20 db that no longer exist in sync backend
     *
     * @param Tinebase_Record_RecordSet $usersInSyncBackend
     * @param array $userIdsInSqlBackend
     */
    protected static function _syncDeletedUsers(Tinebase_Record_RecordSet $usersInSyncBackend, array $userIdsInSqlBackend)
    {
        $oldContainerAcl = Addressbook_Controller_Contact::getInstance()->doContainerACLChecks(false);

        $deletedInSyncBackend = array_diff($userIdsInSqlBackend, $usersInSyncBackend->getArrayOfIds());

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' About to delete / expire ' . count($deletedInSyncBackend) . ' users in SQL backend...');

        foreach ($deletedInSyncBackend as $userToDelete) {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            try {
                $user = self::_getLdapUserController()->getUserByPropertyFromSqlBackend('accountId', $userToDelete, 'Tinebase_Model_FullUser');

                if (in_array($user->accountLoginName, self::getSystemUsernames())) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Skipping system user ' . $user->accountLoginName);
                    Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                    $transactionId = null;
                    continue;
                }

                // at first, we expire+deactivate the user
                $now = Tinebase_DateTime::now();
                if (! $user->accountExpires || $user->accountStatus !== Tinebase_Model_User::ACCOUNT_STATUS_DISABLED) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Disable user and set expiry date of ' . $user->accountLoginName . ' to ' . $now);
                    $user->accountExpires = $now;
                    $user->accountStatus = Tinebase_Model_User::ACCOUNT_STATUS_DISABLED;
                    self::_getLdapUserController()->updateUserInSqlBackend($user);
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' User already expired ' . print_r($user->toArray(), true));

                    $deleteAfterMonths = Tinebase_Config::getInstance()->get(Tinebase_Config::SYNC_USER_DELETE_AFTER);
                    if ($user->accountExpires->isEarlier($now->subMonth($deleteAfterMonths))) {
                        // if he or she is already expired longer than configured expiry, we remove them!
                        // this will trigger the plugin Addressbook which will make a soft delete and especially runs the addressbook sync backends if any configured
                        self::_getLdapUserController()->deleteUser($userToDelete);

                        // now we make the addressbook hard delete, which is ok, because we went through the addressbook_controller_contact::delete already
                        if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true && ! empty($user->contact_id)) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                                . ' Deleting user contact of ' . $user->accountLoginName);

                            $contactsBackend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
                            $contactsBackend->delete($user->contact_id);
                        }
                    } else {
                        // keep user in expiry state
                    }
                }

                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                $transactionId = null;
            } finally {
                if (null !== $transactionId) {
                    Tinebase_TransactionManager::getInstance()->rollBack();
                }
            }

            Tinebase_Lock::keepLocksAlive();
        }

        Addressbook_Controller_Contact::getInstance()->doContainerACLChecks($oldContainerAcl);
    }

    /**
     * returns login_names of system users
     *
     * @return array
     */
    public static function getSystemUsernames()
    {
        return [self::SYSTEM_USER_CRON, self::SYSTEM_USER_CALENDARSCHEDULING, self::SYSTEM_USER_SETUP,
            self::SYSTEM_USER_REPLICATION, self::SYSTEM_USER_ANONYMOUS];
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
        $userBackend = self::_getLdapUserController();
        if (! $userBackend instanceof Tinebase_User_Ldap) {
            throw new Tinebase_Exception_Backend('Needs LDAP accounts backend');
        }
        
        $result = $userBackend->getUserAttributes(array('entryUUID', 'userPassword'));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' About to sync ' . count($result) . ' user passwords from LDAP');
        
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
     *  'adminPassword'     => 'adminpw',
     *  'adminFirstName'    => 'tine',
     *  'adminLastName'     => 'Admin',
     *  'adminEmailAddress' => 'admin@tinedomain.org',
     *  'expires'            => Tinebase_DateTime object
     * );
     * </code>
     *
     * @param array $_options [hash that may contain override values for admin user name and password]
     * @param boolean $onlyAdmin
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function createInitialAccounts($_options, $onlyAdmin = false)
    {
        if (! isset($_options['adminPassword']) || ! isset($_options['adminLoginName'])) {
            throw new Tinebase_Exception_InvalidArgument('Admin password and login name have to be set when creating initial account.', 503);
        }

        $addressBookController = Addressbook_Controller_Contact::getInstance();

        // make sure we have a setup user:
        $setupUser = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
        if (! Tinebase_Core::getUser() instanceof Tinebase_Model_User && $setupUser) {
            Tinebase_Core::set(Tinebase_Core::USER, $setupUser);
        }

        $groupsBackend = Tinebase_Group::getInstance();
        $userBackend = Tinebase_User::getInstance();

        if (! $onlyAdmin) {
            // create the replication user
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Creating new replication user.');

            $replicationUser = static::createSystemUser(Tinebase_User::SYSTEM_USER_REPLICATION,
                $groupsBackend->getDefaultReplicationGroup());
            if (null !== $replicationUser) {
                $replicationMasterConf = Tinebase_Config::getInstance()->get(Tinebase_Config::REPLICATION_MASTER);
                if (empty(($password = $replicationMasterConf->{Tinebase_Config::REPLICATION_USER_PASSWORD}))) {
                    $password = Tinebase_Record_Abstract::generateUID(12);
                }
                $userBackend->setPassword($replicationUser, $password);
            }

            static::createSystemUser(Tinebase_User::SYSTEM_USER_ANONYMOUS);
        }

        $oldAcl = $addressBookController->doContainerACLChecks(false);
        $oldRequestContext = $addressBookController->getRequestContext();
        $requestContext = array(
            Addressbook_Controller_Contact::CONTEXT_ALLOW_CREATE_USER => true,
            Addressbook_Controller_Contact::CONTEXT_NO_ACCOUNT_UPDATE => true,
        );
        $addressBookController->setRequestContext($requestContext);

        $adminLoginName     = $_options['adminLoginName'];
        $adminPassword      = $_options['adminPassword'];
        $adminFirstName     = $_options['adminFirstName'] ?? Tinebase_Config::getInstance()->{Tinebase_Config::BRANDING_TITLE};
        $adminLastName      = $_options['adminLastName'] ?? 'Admin';
        $adminEmailAddress  = $_options['adminEmailAddress'] ?? null;

        // get admin & user groupss
        $adminGroup = $groupsBackend->getDefaultAdminGroup();
        $userGroup  = $groupsBackend->getDefaultGroup();
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Creating initial admin user (login: ' . $adminLoginName . ' / email: ' . $adminEmailAddress . ')');

        $user = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => $adminLoginName,
            'accountStatus'         => Tinebase_Model_User::ACCOUNT_STATUS_ENABLED,
            'accountPrimaryGroup'   => $userGroup->getId(),
            'accountLastName'       => $adminLastName,
            'accountDisplayName'    => $adminLastName . ', ' . $adminFirstName,
            'accountFirstName'      => $adminFirstName,
            'accountExpires'        => (isset($_options['expires'])) ? $_options['expires'] : NULL,
            'accountEmailAddress'   => $adminEmailAddress,
            'groups'                => $adminGroup->getId(),
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
            $existingUser = $userBackend->getUserByProperty('accountLoginName', $adminLoginName);
            $user->setId($existingUser->getId());
            $user->contact_id = $existingUser->contact_id;
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($user, 'update');
            $user = $userBackend->updateUserInSqlBackend($user);
            // Addressbook is registered as plugin and will take care of the update
            $userBackend->updatePluginUser($user, $user);
            // set the password for the account
            // empty password triggers password change dialogue during first login
            if (!empty($adminPassword)) {
                $userBackend->setPassword($user, $adminPassword);
            }
            // add the admin account to all groups
            $groupsBackend->addGroupMember($adminGroup, $user);
            $groupsBackend->addGroupMember($userGroup, $user);
        } catch (Tinebase_Exception_NotFound $ten) {
            Admin_Controller_User::getInstance()->create($user, $adminPassword, $adminPassword, true);
        }

        $addressBookController->doContainerACLChecks($oldAcl);
        $addressBookController->setRequestContext($oldRequestContext === null ? array() : $oldRequestContext);
    }

    /**
     * create new system user
     *
     * @param string $accountLoginName
     * @param Tinebase_Model_Group|null $defaultGroup
     * @return Tinebase_Model_FullUser|Tinebase_Model_User|null
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    static public function createSystemUser(string $accountLoginName, Tinebase_Model_Group $defaultGroup = null): ?Tinebase_Model_User
    {
        $userBackend = Tinebase_User::getInstance();

        try {
            $systemUser = $userBackend->getFullUserByLoginName($accountLoginName);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Use existing system user ' . $accountLoginName);
            return $systemUser;
        } catch (Tinebase_Exception_NotFound $tenf) {
            // continue
        }

        $groupsBackend = Tinebase_Group::getInstance();

        if (! $defaultGroup && $accountLoginName === Tinebase_User::SYSTEM_USER_ANONYMOUS) {
            $defaultGroup = $groupsBackend->getDefaultAnonymousGroup();
        }

        // disable modlog stuff
        $oldGroupValue = $groupsBackend->modlogActive(false);
        $oldUserValue = $userBackend->modlogActive(false);
        $oldAdbValue = Addressbook_Controller_Contact::getInstance()->modlogActive(false);
        if (Tinebase_User::SYSTEM_USER_SETUP === $accountLoginName) {
            $plugin = $userBackend->removePlugin(Addressbook_Controller_Contact::getInstance());
        } else {
            $plugin = null;
        }

        if (null === $defaultGroup) {
            $defaultGroup = $groupsBackend->getDefaultAdminGroup();
        }
        $systemUser = new Tinebase_Model_FullUser(array(
            'accountLoginName' => $accountLoginName,
            'accountStatus' => Tinebase_Model_User::ACCOUNT_STATUS_DISABLED,
            'visibility' => Tinebase_Model_FullUser::VISIBILITY_HIDDEN,
            'accountPrimaryGroup' => $defaultGroup->getId(),
            'accountLastName' => $accountLoginName,
            'accountDisplayName' => $accountLoginName,
            'accountExpires' => NULL,
        ));

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Creating new system user ' . $accountLoginName);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' ' . print_r($systemUser->toArray(), true));

        try {
            $systemUser = $userBackend->addUser($systemUser);
            $groupsBackend->addGroupMember($systemUser->accountPrimaryGroup, $systemUser->getId());
        } catch (Zend_Ldap_Exception $zle) {
            Tinebase_Exception::log($zle);
            if (stripos($zle->getMessage(), 'Already exists') !== false) {
                try {
                    /** @var Tinebase_User_Ldap $userBackend */
                    $user = $userBackend->getUserByPropertyFromSyncBackend(
                        'accountLoginName',
                        $accountLoginName,
                        'Tinebase_Model_FullUser'
                    );
                    Tinebase_Timemachine_ModificationLog::setRecordMetaData($user, 'create');
                    $systemUser->merge($user);
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                        ' Creating new (sql) system user ' . print_r($systemUser->toArray(), true));
                    $systemUser = $userBackend->addUserInSqlBackend($systemUser);
                    $groupsBackend->addGroupMember($systemUser->accountPrimaryGroup, $systemUser->getId());
                } catch(Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ .
                        ' no system user could be created');
                    // TODO we should try to fetch an admin user here (see Sales_Setup_Update_Release8::_updateContractsFields)
                    Tinebase_Exception::log($e);
                    $systemUser = null;
                }
            } else {
                try {
                    $systemUser = $userBackend->addUserInSqlBackend($systemUser);
                    $groupsBackend->addGroupMember($systemUser->accountPrimaryGroup, $systemUser->getId());
                } catch(Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ .
                        ' no system user could be created');
                    // TODO we should try to fetch an admin user here (see Sales_Setup_Update_Release8::_updateContractsFields)
                    Tinebase_Exception::log($e);
                    $systemUser = null;
                }
            }

        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                ' No system user could be created: ' . $e->getMessage());

            // TODO we should try to fetch an admin user here (see Sales_Setup_Update_Release8::_updateContractsFields)

            try {
                if ($e instanceof Zend_Db_Statement_Exception && Tinebase_Exception::isDbDuplicate($e)) {
                    // user might have been deleted -> undelete
                    try {
                        $systemUser = $userBackend->getUserByLoginName($accountLoginName);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        $userBackend->undelete($accountLoginName);
                        $systemUser = $userBackend->getUserByLoginName($accountLoginName);
                    }
                } else {
                    $systemUser = $userBackend->addUserInSqlBackend($systemUser);
                    $groupsBackend->addGroupMember($systemUser->accountPrimaryGroup, $systemUser->getId());
                }
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ .
                    ' no system user could be created: ' . $e->getMessage());
                // TODO we should try to fetch an admin user here (see Sales_Setup_Update_Release8::_updateContractsFields)

                if (! ($e instanceof Zend_Db_Statement_Exception && Tinebase_Exception::isDbDuplicate($e))) {
                    Tinebase_Exception::log($e);
                }
                $systemUser = null;
            }
        }

        if (null !== $systemUser && Tinebase_User::SYSTEM_USER_SETUP === $accountLoginName &&
                empty($systemUser->contact_id)) {
            $contact = Addressbook_Controller_Contact::getInstance()->getBackend()
                ->create(self::user2Contact($systemUser));
            $systemUser->contact_id = $contact->getId();
            $userBackend->updateUserInSqlBackend($systemUser);
        }

        // re-enable modlog stuff
        $groupsBackend->modlogActive($oldGroupValue);
        $userBackend->modlogActive($oldUserValue);
        Addressbook_Controller_Contact::getInstance()->modlogActive($oldAdbValue);
        if (null !== $plugin) {
            $userBackend->registerPlugin($plugin);
        }

        return $systemUser;
    }

    /**
     * generate random password
     *
     * @param int $length
     * @param boolean $useSpecialChar
     * @return string
     */
    public static function generateRandomPassword($length = 10, $useSpecialChar = true)
    {
        $symbolsGeneral = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $symbolsSpecialChars = '!?~@#-_+<>[]{}';

        $used_symbols = $symbolsGeneral;
        $symbols_length = strlen($used_symbols) - 1; //strlen starts from 0 so to get number of characters deduct 1

        $pass = '';

        for ($i = 0; $i < $length; $i++) {
            $pass .= $used_symbols[rand(0, $symbols_length)];
        }

        if ($useSpecialChar) {
            $pass = substr($pass, 1) ;
            $pass .= $symbolsSpecialChars[rand(0, strlen($symbolsSpecialChars) - 1)];

        }

        return str_shuffle($pass);
    }
}
