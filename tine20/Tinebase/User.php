<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * User Class
 *
 * @package     Tinebase
 * @subpackage  User
 */
class Tinebase_User
{
    const SQL = 'Sql';
    
    const LDAP = 'Ldap';
    
    const TYPO3 = 'Typo3';
    
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
            'changepw' => true,
            self::DEFAULT_USER_GROUP_NAME_KEY => 'Users',
            self::DEFAULT_ADMIN_GROUP_NAME_KEY => 'Administrators',
        ),
        self::LDAP => array(
            'host' => '',
            'username' => '',
            'password' => '',
            'bindRequiresDn' => true,
            'useRfc2307bis' => true,
            'userDn' => '',
            'userFilter' => 'objectclass=posixaccount',
            'userSearchScope' => Zend_Ldap::SEARCH_SCOPE_SUB,
            'groupsDn' => '',
            'groupFilter' => 'objectclass=posixgroup',
            'groupSearchScope' => Zend_Ldap::SEARCH_SCOPE_SUB,
            'pwEncType' => 'CRYPT',
            'minUserId' => '10000',
            'maxUserId' => '29999',
            'minGroupId' => '11000',
            'maxGroupId' => '11099',
            'groupUUIDAttribute' => 'entryUUID',
            'userUUIDAttribute' => 'entryUUID',
            self::DEFAULT_USER_GROUP_NAME_KEY => 'Users',
            self::DEFAULT_ADMIN_GROUP_NAME_KEY => 'Administrators',
            'changepw' => true
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
     * return an instance of the current rs backend
     *
     * @param   string $_backendType name of the rs backend
     * @return  Tinebase_User_Abstract
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public static function factory($_backendType) 
    {
        switch ($_backendType) {
            case self::LDAP:
                $options = self::getBackendConfiguration();
                
                $options['plugins'] = array();
                
		        // manage samba sam?
		        if (isset(Tinebase_Core::getConfig()->samba) && Tinebase_Core::getConfig()->samba->get('manageSAM', FALSE) == true) {
	                $options['plugins'][] = Tinebase_User_Ldap::PLUGIN_SAMBA;
	                $options[Tinebase_User_Ldap::PLUGIN_SAMBA] = Tinebase_Core::getConfig()->samba->toArray(); 
		        }
                
                $result  = new Tinebase_User_Ldap($options);
                
                break;
                
            case self::SQL:
                $result = new Tinebase_User_Sql();
                
                break;
            
            case self::TYPO3:
                $result = new Tinebase_User_Typo3();
                
                break;
                
            default:
                throw new Tinebase_Exception_InvalidArgument("User backend type $_backendType not implemented.");
        }
        
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
                self::setBackendType(Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::USERBACKENDTYPE, null, self::SQL)->value);
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
     * @param string $_backendType
     * @return void
     */
    public static function setBackendType($_backendType)
    {
        self::$_backendType = ucfirst($_backendType);
    }
    
    /**
     * Setter for {@see $_backendConfiguration}
     * 
     * NOTE:
     * Setting will not be written to Database or Filesystem.
     * To persist the change call {@see saveBackendConfiguration()}
     * 
     * @param mixed $_value
     * @param string  optional $_key
     * @return void
     */
    public static function setBackendConfiguration($_value, $_key = null)
    {
        $defaultValues = self::$_backendConfigurationDefaults[self::getConfiguredBackend()];

        if (is_null($_key) && !is_array($_value)) {
            throw new Tinebase_Exception_InvalidArgument('To set backend configuration either a key and value parameter are required or the value parameter should be a hash');
        } elseif (is_null($_key) && is_array($_value)) {
            foreach ($_value as $key=> $value) {
                self::setBackendConfiguration($value, $key);
            }
        } else {
            if ( ! array_key_exists($_key, $defaultValues)) {
                throw new Tinebase_Exception_InvalidArgument("Cannot set backend configuration option '$_key' for accounts storage " . self::getConfiguredBackend());
            }
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
        } elseif (array_key_exists($_key, self::$_backendConfiguration)) {
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
        Tinebase_Config::getInstance()->setConfigForApplication(Tinebase_Model_Config::USERBACKEND, Zend_Json::encode(self::getBackendConfiguration()));
        Tinebase_Config::getInstance()->setConfigForApplication(Tinebase_Model_Config::USERBACKENDTYPE, self::getConfiguredBackend());
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
                $rawBackendConfiguration = Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::USERBACKEND, null, array())->value;
            } else {
                $rawBackendConfiguration = array();
            }
            self::$_backendConfiguration = is_array($rawBackendConfiguration) ? $rawBackendConfiguration : Zend_Json::decode($rawBackendConfiguration);
        }

        if (isset($_key)) {
            return array_key_exists($_key, self::$_backendConfiguration) ? self::$_backendConfiguration[$_key] : $_default; 
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
                    if (! array_key_exists($key, $config[$backendType])) {
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
            if (!array_key_exists($_backendType, self::$_backendConfigurationDefaults)) {
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
     * @param  mixed  $_username  the login id of the user to synchronize
     * return Tinebase_Model_FullUser
     */
    public static function syncUser($_username, $_syncContactData = false)
    {
        if($_username instanceof Tinebase_Model_FullUser) {
            $username = $_username->accountLoginName;
        } else {
            $username = $_username;
        }
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . "  sync user data for: " . $username);
        
        $userBackend  = Tinebase_User::getInstance();
        $groupBackend = Tinebase_Group::getInstance();
        
        $user = $userBackend->getUserByPropertyFromSyncBackend('accountLoginName', $username, 'Tinebase_Model_FullUser');
        
        $user->accountPrimaryGroup = $groupBackend->resolveGIdNumberToUUId($user->accountPrimaryGroup);
        
        // make sure primary group exists
        try {
            $group = $groupBackend->getGroupById($user->accountPrimaryGroup);
        } catch (Tinebase_Exception_Record_NotDefined $tern) {
            $group = $groupBackend->getGroupByIdFromSyncBackend($user->accountPrimaryGroup);
            $group = $groupBackend->addGroupInSqlBackend($group);
        }
        
        // update or create user in local sql backend
        try {
            $currentUser = $userBackend->getUserByProperty('accountId', $user, 'Tinebase_Model_FullUser');
            $user->openid = $currentUser->openid;
            
            $user = $userBackend->updateUserInSqlBackend($user);
        } catch (Tinebase_Exception_NotFound $ten) {
            try {
                $invalidUser = $userBackend->getUserByPropertyFromSqlBackend('accountLoginName', $username, 'Tinebase_Model_FullUser');
                if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . " remove invalid user: " . $username);
                $userBackend->deleteUserInSqlBackend($invalidUser);
            } catch (Tinebase_Exception_NotFound $ten) {
                // do nothing
            }
            $user = $userBackend->addUserInSqlBackend($user);
        }
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  synced user object: " . print_r($user->toArray(), true));

        // import contactdata(phone, address, fax, birthday. photo)
        if($_syncContactData === true) {
            $addressbook = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
            
            $contact = $addressbook->getByUserId($user->getId());
            $userBackend->updateContactFromSyncBackend($user, $contact);
            $addressbook->update($contact);
        }
        
        // sync group memberships
        Tinebase_Group::syncMemberships($user);
        
        return $user;
    }
    
    /**
     * import users from sync backend
     *
     */
    public static function syncUsers($_syncContactData = false)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' start synchronizing users');
        
        $users = Tinebase_User::getInstance()->getUsersFromSyncBackend(NULL, NULL, 'ASC', NULL, NULL, 'Tinebase_Model_FullUser');

        foreach($users as $user) {
            try {
                $user = self::syncUser($user, $_syncContactData);
            } catch (Tinebase_Exception_NotFound $ten) {
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . " User {$user->accountLoginName} not synced: " . $ten->getMessage());
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' finnished synchronizing users');
    }
    
    /**
     * create initial admin account
     * 
     * Method is called during Setup Initialization
     *
     * @param array $_options [hash that may contain override values for admin user name and password]
     * 
     * @example $_options may contain the following keys:
     * <pre>
     * $options = array(
     *  'adminLoginName'    => 'admin',
     *  'adminPassword'     => 'lars',
     *  'adminFirstName'    => 'Tine 2.0',
     *  'adminLastName'     => 'Admin Account',
     * );
     * </pre>
     * 
     * @return void
     */
    public static function createInitialAccounts($_options)
    {
        if (! isset($_options['adminPassword'])) {
            throw new Tinebase_Exception_InvalidArgument('Admin password has to be set when creating initial accont.');
        }
        
        $_options['adminFirstName']     = isset($_options['adminFirstName'])    ? $_options['adminFirstName'] : 'Tine 2.0';
        $_options['adminLastName']      = isset($_options['adminLastName'])     ? $_options['adminLastName']  : 'Admin Account';
        $_options['adminLoginName']     = isset($_options['adminLoginName'])    ? $_options['adminLoginName'] : 'tine20admin';

        // get admin & user groups
        $userBackend   = Tinebase_User::factory(Tinebase_User::SQL);
        $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);
        
        $adminGroup = $groupsBackend->getDefaultAdminGroup();
        $userGroup  = $groupsBackend->getDefaultGroup();
        
        print_r($_options);
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating initial admin user(' . $_options['adminLoginName'] . ')');

        $user = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => $_options['adminLoginName'],
            'accountStatus'         => 'enabled',
            'accountPrimaryGroup'   => $userGroup->getId(),
            'accountLastName'       => $_options['adminLastName'],
            'accountDisplayName'    => $_options['adminLastName'] . ', ' . $_options['adminFirstName'],
            'accountFirstName'      => $_options['adminFirstName'],
            'accountExpires'        => NULL,
            'accountEmailAddress'   => NULL,
        ));

        #$groupsBackend->addOrUpdateUser($account);
        // update or create user in local sql backend
        try {
            $userBackend->getUserByProperty('accountId', $_options['adminLoginName']);
            $user = $userBackend->updateUserInSqlBackend($user);
        } catch (Tinebase_Exception_NotFound $ten) {
            $user = $userBackend->addUserInSqlBackend($user);
        }
        
        Tinebase_Core::set(Tinebase_Core::USER, $user);
        // set the password for the account
        Tinebase_User::getInstance()->setPassword(Tinebase_Core::getUser(), $_options['adminPassword']);

        // add the admin account to all groups
        Tinebase_Group::getInstance()->addGroupMember($adminGroup, $user);
        Tinebase_Group::getInstance()->addGroupMember($userGroup, $user);
        
    }
}
