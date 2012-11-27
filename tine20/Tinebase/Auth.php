<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * main authentication class
 * 
 * @todo 2010-05-20 cweiss: the default option handling looks like a big mess -> someone needs to tidy up here!
 * 
 * @package     Tinebase
 * @subpackage  Auth 
 */

class Tinebase_Auth
{
    /**
     * constant for Sql auth
     *
     */
    const SQL = 'Sql';
    
    /**
     * constant for LDAP auth
     *
     */
    const LDAP = 'Ldap';

    /**
     * constant for IMAP auth
     *
     */
    const IMAP = 'Imap';

    /**
     * General Failure
     */
    const FAILURE                       =  Zend_Auth_Result::FAILURE;

    /**
     * Failure due to identity not being found.
     */
    const FAILURE_IDENTITY_NOT_FOUND    = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;

    /**
     * Failure due to identity being ambiguous.
     */
    const FAILURE_IDENTITY_AMBIGUOUS    = Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS;

    /**
     * Failure due to invalid credential being supplied.
     */
    const FAILURE_CREDENTIAL_INVALID    = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;

    /**
     * Failure due to uncategorized reasons.
     */
    const FAILURE_UNCATEGORIZED         = Zend_Auth_Result::FAILURE_UNCATEGORIZED;
    
    /**
     * Failure due the account is disabled
     */
    const FAILURE_DISABLED              = -100;

    /**
     * Failure due the account is expired
     */
    const FAILURE_PASSWORD_EXPIRED      = -101;
    
    /**
     * Failure due the account is temporarly blocked
     */
    const FAILURE_BLOCKED               = -102;
        
    /**
     * database connection failure
     */
    const FAILURE_DATABASE_CONNECTION   = -103;
        
    /**
     * Authentication success.
     */
    const SUCCESS                        =  Zend_Auth_Result::SUCCESS;

    /**
     * the name of the authenticationbackend
     *
     * @var string
     */
    protected static $_backendType;
    
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
            'tryUsernameSplit' => '1',
            'accountCanonicalForm' => '2',
            'accountDomainName' => '',
            'accountDomainNameShort' => '',
        ),
        self::LDAP => array(
            'host' => '',
            'username' => '',
            'password' => '',
            'bindRequiresDn' => true,
            'baseDn' => '',
            'accountFilterFormat' => NULL,
            'accountCanonicalForm' => '2',
            'accountDomainName' => '',
            'accountDomainNameShort' => '',
         ),
         self::IMAP => array(
            'host'      => '',
            'port'      => 143,
            'ssl'       => 'tls',
            'domain'    => '',
         ),
     );
    
    /**
     * the instance of the authenticationbackend
     *
     * @var Tinebase_Auth_Interface
     */
    protected $_backend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->setBackend();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Auth
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Auth
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Auth;
        }
        
        return self::$_instance;
    }
    
    /**
     * authenticate user
     *
     * @param string $_username
     * @param string $_password
     * @return Zend_Auth_Result
     */
    public function authenticate($_username, $_password)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Trying to authenticate '. $_username);
        
        $this->_backend->setIdentity($_username);
        $this->_backend->setCredential($_password);
        
        if (Zend_Session::isStarted()) {
            Zend_Auth::getInstance()->setStorage(new Zend_Auth_Storage_Session());
        } else {
            Zend_Auth::getInstance()->setStorage(new Zend_Auth_Storage_NonPersistent());
        }
        $result = Zend_Auth::getInstance()->authenticate($this->_backend);
        
        if($result->isValid()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Authentication of '. $_username . ' succeeded');
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Authentication of '. $_username . ' failed');
        }
        
        return $result;
    }
    
    /**
     * check if password is valid
     *
     * @param string $_username
     * @param string $_password
     * @return boolean
     */
    public function isValidPassword($_username, $_password)
    {
        $this->_backend->setIdentity($_username);
        $this->_backend->setCredential($_password);
        
        $result = $this->_backend->authenticate();

        if ($result->isValid()) {
            return true;
        }
        
        return false;
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
                self::setBackendType(Tinebase_Config::getInstance()->get(Tinebase_Config::AUTHENTICATIONBACKENDTYPE, self::SQL));
            } else {
                self::setBackendType(self::SQL);
            }
        }
        
        return self::$_backendType;
    }
    
    /**
     * set the auth backend
     */
    public function setBackend()
    {
        $backendType = self::getConfiguredBackend();
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' authentication backend: ' . $backendType);
        $this->_backend = Tinebase_Auth_Factory::factory($backendType);
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
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
                    "Cannot set backend configuration option '$_key' for accounts storage " . self::getConfiguredBackend());
                return;
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
        Tinebase_Config::getInstance()->set(Tinebase_Config::AUTHENTICATIONBACKEND, self::getBackendConfiguration());
        Tinebase_Config::getInstance()->set(Tinebase_Config::AUTHENTICATIONBACKENDTYPE, self::getConfiguredBackend());
    }
    
    /**
     * Getter for {@see $_backendConfiguration}
     * 
     * @param boolean $_getConfiguredBackend
     * @return mixed [If {@param $_key} is set then only the specified option is returned, otherwise the whole options hash]
     */
    public static function getBackendConfiguration($_key = null, $_default = null)
    {
        //lazy loading for $_backendConfiguration
        if (!isset(self::$_backendConfiguration)) {
            if (Setup_Controller::getInstance()->isInstalled('Tinebase')) {
                $rawBackendConfiguration = Tinebase_Config::getInstance()->get(Tinebase_Config::AUTHENTICATIONBACKEND, new Tinebase_Config_Struct())->toArray();
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
     * @param String | optional $_key
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
                    // 2010-05-20 cweiss Zend_Ldap changed and does not longer throw exceptions
                    // on unsupported values, we might skip this cleanup here.
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
    
}
