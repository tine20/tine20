<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 *
 * @todo        add Voipmanager_Server_Snom
 */

/**
 * dispatcher and initialisation class (functions are static)
 * - dispatchRequest() function
 * - initXYZ() functions 
 * - has registry and config
 * 
 * @package     Tinebase
 */
class Tinebase_Core
{
    /**
     * constant for config registry index
     *
     */
    const CONFIG = 'configFile';    

    /**
     * constant for logger registry index
     *
     */
    const LOGGER = 'logger';    
    
    /**
     * constant for cache registry index
     *
     */
    const CACHE = 'cache';    

    /**
     * constant for session namespace (tinebase) registry index
     *
     */
    const SESSION = 'session';    
    
    /**
     * constant for current account/user
     *
     */
    const USER = 'currentAccount';    
    
    /**
     * const PDO_MYSQL
     *
     */
    const PDO_MYSQL = 'Pdo_Mysql';
    
    /**
     * const PDO_OCI
     *
     */
    const PDO_OCI = 'Pdo_Oci';
    
    /******************************* DISPATCH *********************************/
    
    /**
     * dispatch request
     *
     * @todo add ActiveSync
     */
    public static function dispatchRequest()
    {
        $server = NULL;
        
        /**************************** JSON API *****************************/

        if (( (isset($_SERVER['HTTP_X_TINE20_REQUEST_TYPE']) && $_SERVER['HTTP_X_TINE20_REQUEST_TYPE'] == 'JSON')  || 
              (isset($_POST['requestType']) && $_POST['requestType'] == 'JSON')
            ) && isset($_REQUEST['method'])) {
            $server = new Tinebase_Server_Json();

        /**************************** SNOM API *****************************/
            
        } elseif(preg_match('/^Mozilla\/4\.0 \(compatible; (snom...)\-SIP (\d+\.\d+\.\d+)/i', $_SERVER['HTTP_USER_AGENT'])) {
            $server = new Voipmanager_Server_Snom();
            
        /**************************** CLI API *****************************/
        
        } elseif (php_sapi_name() == 'cli') {
            $server = new Tinebase_Server_Cli();
            
        /**************************** HTTP API ****************************/
        
        } else {
            $server = new Tinebase_Server_Http();
        }        
        
        $server->handle();
    }
    
    /******************************* APPLICATION ************************************/
    
    /**
     * returns an instance of the controller of an application
     *
     * @param string $_applicationName
     * @param string $_modelName
     * @return object the controller of the application
     * 
     */
    public static function getApplicationInstance($_applicationName, $_modelName = '')
    {
        $controllerName = ucfirst((string) $_applicationName) . '_Controller';

        // check for model controller
        if (!empty($_modelName)) {
            $controllerNameModel = $controllerName . '_' . $_modelName;
            if (!class_exists($controllerNameModel)) {
    
                // check for generic app controller
                if (!class_exists($controllerName)) {            
                    throw new Exception('No Controller found (checked classes '. $controllerName . ' and ' . $controllerNameModel . ')!');
                } 
            } else {
                $controllerName = $controllerNameModel;
            }
        } else {
            if (!class_exists($controllerName)) {            
                throw new Exception('No Application Controller found (checked class ' . $controllerName . ')!');
            }             
        }
        
        $controller = call_user_func(array($controllerName, 'getInstance'));
        
        return $controller;
    }
    
    /******************************* SETUP ************************************/
    
    /**
     * initializes the config
     *
     */
    public static function setupConfig()
    {
        if(file_exists(dirname(__FILE__) . '/../config.inc.php')) {
            $config = new Zend_Config(require dirname(__FILE__) . '/../config.inc.php');
        } else {
            try {
                $config = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');
            } catch (Zend_Config_Exception $e) {
                die ('central configuration file ' . $_SERVER['DOCUMENT_ROOT'] . '/../config.ini not found');
            }
        }
        self::set(self::CONFIG, $config);  
    }
    
    /**
     * initializes the logger
     *
     */
    public static function setupLogger()
    {
        $config = self::getConfig();
        $logger = new Zend_Log();
        
        if (isset($config->logger)) {
            try {
                $loggerConfig = $config->logger;
                
                $filename = $loggerConfig->filename;
                $priority = (int)$loggerConfig->priority;
    
                $writer = new Zend_Log_Writer_Stream($filename);
                $logger->addWriter($writer);
    
                $filter = new Zend_Log_Filter_Priority($priority);
                $logger->addFilter($filter);
            } catch (Exception $e) {
                error_log("Tine 2.0 can't setup the configured logger! The Server responded: $e");
                $writer = new Zend_Log_Writer_Null;
                $logger->addWriter($writer);
            }
        } else {
            $writer = new Zend_Log_Writer_Null;
            $logger->addWriter($writer);
        }

        self::set(self::LOGGER, $logger);

        $logger->debug(__METHOD__ . '::' . __LINE__ .' logger initialized');
    }
    
    /**
     * setup the cache and add it to zend registry
     *
     */
    public static function setupCache()
    {
        $config = self::getConfig();        
        
        // create zend cache
        if ($config->caching && $config->caching->active) {
            $frontendOptions = array(
                'cache_id_prefix' => SQL_TABLE_PREFIX,
                'lifetime' => ($config->caching->lifetime) ? $config->caching->lifetime : 7200,
                'automatic_serialization' => true // turn that off for more speed
            );
                        
            $backendType = ($config->caching->backend) ? ucfirst($config->caching->backend) : 'File';
            
            switch ($backendType) {
                case 'File':
                    $backendOptions = array(
                        'cache_dir' => ($config->caching->path) ? $config->caching->path : session_save_path()  // Directory where to put the cache files
                    );
                break;
                case 'Memcached':                        
                    $backendOptions = array(
                        'servers' => array(
                            'host' => ($config->caching->host) ? $config->caching->host : 'localhost',
                            'port' => ($config->caching->port) ? $config->caching->port : 11211,
                            'persistent' => TRUE
                    ));
                break;
            }
        } else {
            $backendType = 'File';
            $frontendOptions = array(
                'caching' => false
            );
            $backendOptions = array(
                'cache_dir' => session_save_path()
            );
        }    

        // getting a Zend_Cache_Core object
        $cache = Zend_Cache::factory('Core', $backendType, $frontendOptions, $backendOptions);
        
        self::set(self::CACHE, $cache);
    }
    
    /**
     * initializes the session
     *
     */
    public static function setupSession()
    {
        $config = self::getConfig();
        
        Zend_Session::setOptions(array(
            'name'              => 'TINE20SESSID',
            'cookie_httponly'   => true,
            'hash_function'     => 1,
        
        ));
        if(isset($_SERVER['HTTPS'])) {
            Zend_Session::setOptions(array(
                'cookie_secure'     => true,
            ));
        }
        Zend_Session::start();
        
        define('TINE20_BUILDTYPE',     strtoupper($config->get('buildtype', 'DEVELOPMENT')));
        define('TINE20_CODENAME',      'trunk');
        define('TINE20_PACKAGESTRING', 'none');
        define('TINE20_RELEASETIME',   Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG));
        
        $session = new Zend_Session_Namespace('tinebase');
        
        if (!isset($session->jsonKey)) {
            $session->jsonKey = Tinebase_Record_Abstract::generateUID();
        }
        self::set('jsonKey', $session->jsonKey);

        if (isset($session->currentAccount)) {
            self::set(self::USER, $session->currentAccount);
        }
        
        self::set(self::SESSION, $session);
    }
    
    /**
     * initializes the database connection
     *
     */
    public static function setupDatabaseConnection()
    {
        $config = self::getConfig();
        
        if (isset($config->database)) {
            $dbConfig = $config->database;
            
            define('SQL_TABLE_PREFIX', $dbConfig->get('tableprefix') ? $dbConfig->get('tableprefix') : 'tine20_');
        
            $dbBackend = constant('self::' . strtoupper($dbConfig->get('backend', self::PDO_MYSQL)));
            
            switch($dbBackend) {
                case self::PDO_MYSQL:
                    $db = Zend_Db::factory('Pdo_Mysql', $dbConfig->toArray());
                    break;
                case self::PDO_OCI:
                    $db = Zend_Db::factory('Pdo_Oci', $dbConfig->toArray());
                    break;
                default:
                    throw new Exception('Invalid database backend type defined. Please set backend to ' . self::PDO_MYSQL . ' or ' . self::PDO_OCI . ' in config.ini.');
                    break;
            }
            
            Zend_Db_Table_Abstract::setDefaultAdapter($db);

            Zend_Registry::set('dbAdapter', $db);
        } else {
            die ('database section not found in central configuration file');
        }
    }
    
    /**
     * sets the user locale
     * 
     * @param  string $_localeString
     * @param  bool   $_saveaspreference
     */
    public static function setupUserLocale($_localeString = 'auto', $_saveaspreference = FALSE)
    {
        $session = self::get(self::SESSION);
        
        self::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " given localeString '$_localeString'");
        $localeString = NULL;
        if ($_localeString == 'auto') {
            // if the session already has a locale, use this, otherwise take the preference
            // NOTE: the preference allways exists, cuase setup gives one!
            if (isset($session->userLocale)) {
                $localeString = $session->userLocale;
                self::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " session value '$localeString'");
            } elseif (isset($session->currentAccount)) {
                $localeString = Tinebase_Config::getInstance()
                    ->getPreference(self::getUser()->getId(), 'Locale')
                    ->value;
                    
                self::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " preference '$localeString'");
            }
        } 
        $locale = Tinebase_Translation::getLocale($localeString ? $localeString : $_localeString);
        
        // save in session and registry
        if ($session !== NULL) {
            $session->userLocale = (string)$locale;
        }
        self::set('locale', $locale);
        
        // save locale in config
        if ($_saveaspreference && Zend_Registry::isRegistered(self::USER)) {
            $preference = new Tinebase_Model_Config(array(
                'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
                'name' => 'Locale',
                'value' => (string)$locale
            ));
            Tinebase_Config::getInstance()->setPreference(self::getUser()->getId(), $preference);
        }
    }
    
    /**
     * intializes the timezone handling
     *
     */
    public static function setupServerTimezone()
    {
        // All server operations are done in UTC
        date_default_timezone_set('UTC');
    }

    /**
     * intializes the timezone handling
     * 
     * @param  string $_timezone
     * @param  bool   $_saveaspreference
     * @return string
     */
    public static function setupUserTimezone($_timezone = NULL, $_saveaspreference = FALSE)
    {
        $session = self::get(self::SESSION);
        
        if ($_timezone === NULL) {
            // get timezone from config/preferences
            if (isset($session->currentAccount)) {
                $timezone = Tinebase_Config::getInstance()
                    ->getPreference(self::getUser()->getId(), 'Timezone')
                    ->value;
            } else {
                $timezone = Tinebase_Config::getInstance()
                    ->getConfig('Timezone')
                    ->value;
            }
        } else {
            
            $timezone = $_timezone;

            // save locale in config
            if ($_saveaspreference) {
                $preference = new Tinebase_Model_Config(array(
                    'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
                    'name' => 'Timezone',
                    'value' => $timezone
                ));
                Tinebase_Config::getInstance()->setPreference(self::getUser()->getId(), $preference);
            }
        }
        
        self::set('userTimeZone', $timezone);
        
        return $timezone;
    }
    
    /**
     * function to initialize the smtp connection
     *
     */
    public static function setupMailer()
    {
        $config = self::getConfig();
        
        if (isset($config->mail)) {
            $mailConfig = $config->mail;
        } else {
            $mailConfig = new Zend_Config(array(
                'smtpserver' => 'localhost', 
                'port' => 25
           ));
        }
        
        $transport = new Zend_Mail_Transport_Smtp($mailConfig->smtpserver,  $mailConfig->toArray());
        Zend_Mail::setDefaultTransport($transport);
    }

    /******************************* REGISTRY ************************************/
    
    /**
     * get a value from the registry
     *
     */
    public static function get($index)
    {
        return Zend_Registry::get($index);
    }

    /**
     * set a registry value 
     *
     * @return mixed value
     */
    public static function set($index, $value)
    {
        Zend_Registry::set($index, $value);
    }

    /**
     * checks a registry value 
     *
     * @return boolean
     */
    public static function isRegistered($index)
    {
        return Zend_Registry::isRegistered($index);
    }

    /**
     * get config from the registry
     *
     * @return Zend_Config|Zend_Config_Ini
     */
    public static function getConfig()
    {
        return self::get(self::CONFIG);
    }

    /**
     * get config from the registry
     *
     * @return Zend_Log the logger
     */
    public static function getLogger()
    {
        return self::get(self::LOGGER);
    }

    /**
     * get session namespace from the registry
     *
     * @return Zend_Session_Namespace tinebase session namespace
     */
    public static function getSession()
    {
        return self::get(self::SESSION);
    }

    /**
     * get current user account
     *
     * @return Tinebase_Model_FullUser the user account record
     */
    public static function getUser()
    {
        return self::get(self::USER);
    }
}
