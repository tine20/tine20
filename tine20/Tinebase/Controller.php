<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * php helpers
 */
require_once 'Helper.php';

/**
 * the class provides functions to handle applications
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Controller
{
    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Controller
     */
    private static $instance = NULL;
    
    /**
     * stores the tinebase session namespace
     *
     * @var Zend_Session_Namespace
     */
    protected $_session;
    
    /**
     * config
     *
     * @var unknown_type
     */
    protected $_config;
    
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
    
    /**
     * the constructor
     *
     */
    private function __construct() 
    {    
    }
    
    /**
     * initialize the framework
     *
     */
    protected function _initFramework()
    {
        Zend_Session::setOptions(array(
            'name'              => 'TINE20SESSID',
            //'cookie_httponly'   => true, // not supported by ZF as of 2008-08-13
            'hash_function'     => 1,
        
        ));
        if(isset($_SERVER['HTTPS'])) {
            Zend_Session::setOptions(array(
                'cookie_secure'     => true,
            ));
        }
        Zend_Session::start();
        
        if(file_exists(dirname(__FILE__) . '/../config.inc.php')) {
            $this->_config = new Zend_Config(require dirname(__FILE__) . '/../config.inc.php');
        } else {
            try {
                $this->_config = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');
            } catch (Zend_Config_Exception $e) {
                die ('central configuration file ' . $_SERVER['DOCUMENT_ROOT'] . '/../config.ini not found');
            }
        }
        Zend_Registry::set('configFile', $this->_config);
        
        define('TINE20_BUILDTYPE', $this->_config->get('buildtype', 'DEVELOPMENT'));
        
        $this->_session = new Zend_Session_Namespace('tinebase');
        
        if (!isset($this->_session->jsonKey)) {
            $this->_session->jsonKey = Tinebase_Record_Abstract::generateUID();
        }
        Zend_Registry::set('jsonKey', $this->_session->jsonKey);

        if (isset($this->_session->currentAccount)) {
            Zend_Registry::set('currentAccount', $this->_session->currentAccount);
        }
        
        // setup a temporary user locale. This will be overwritten from setupUserLocale, 
        // but we need to handle exceptions during initialisation process such as seesion timeout
        // @todo add fallback locale to config.ini
        Zend_Registry::set('locale', new Zend_Locale('en_US'));
        
        // Server Timezone must be setup before logger, as logger has timehandling!
        $this->setupServerTimezone();
        
        $this->setupLogger();
        
        $this->setupMailer();

        $this->setupDatabaseConnection();

        $this->setupUserTimezone();
        
        $this->setupUserLocale();
        
        $this->setupCache();
        
        header('X-API: http://www.tine20.org/apidocs/tine20/');
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Controller
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Controller;
        }
        
        return self::$instance;
    }
    
    /**
     * returns an instance of the controller of an application
     *
     * @param string $_applicationName
     * @return object the controller of the application
     */
    public static function getApplicationInstance($_applicationName)
    {
        $controllerName = ucfirst((string) $_applicationName) . '_Controller';
        
        if (!class_exists($controllerName)) {
            throw new Exception('class '. $controllerName . ' not found');
        }
        
        $controller = call_user_func(array($controllerName, 'getInstance'));
        
        return $controller;
    }
    
    /**
     * handler for HTTP api requests
     * @todo session expre handling
     * 
     * @return HTTP
     */
    public function handleHttp()
    {
        try {
            $this->_initFramework();
            Zend_Registry::get('logger')->debug('is http request. method: ' . (isset($_REQUEST['method']) ? $_REQUEST['method'] : 'EMPTY'));
            
            $server = new Tinebase_Http_Server();
            
            //NOTE: auth check for Tinebase HTTP api is done via Tinebase_Http::checkAuth  
            $server->setClass('Tinebase_Http', 'Tinebase');
    
            // register addidional HTTP apis only available for authorised users
            if (Zend_Auth::getInstance()->hasIdentity()) {
                $userApplications = Zend_Registry::get('currentAccount')->getApplications();
                
                foreach ($userApplications as $application) {
                    $applicationName = ucfirst((string) $application);
                    try {
                        $server->setClass($applicationName.'_Http', $applicationName);
                    } catch (Exception $e) {
                        // do nothing
                    }
                }
            } 
            
            if (empty($_REQUEST['method'])) {
                if (Zend_Auth::getInstance()->hasIdentity()) {
                    $_REQUEST['method'] = 'Tinebase.mainScreen';
                } else {
                    $_REQUEST['method'] = 'Tinebase.login';
                }
            }

            $server->handle($_REQUEST);
        } catch (Exception $exception) {
            $server = new Tinebase_Http_Server();
            $server->setClass('Tinebase_Http', 'Tinebase');
            if (! Zend_Registry::isRegistered('currentAccount')) {
                Zend_Registry::get('logger')->INFO('Attempt to request a privileged Http-API method without autorisation from "' . $_SERVER['REMOTE_ADDR'] . '". (seesion timeout?)');
                $server->handle(array('method' => 'Tinebase.sessionTimedOut'));
            } else {
                Zend_Registry::get('logger')->DEBUG(__FILE__ . '::' . __METHOD__ . ' (line' . __LINE__ .') Http-Api exception: ' . print_r($exception, true));
                $server->handle(array('method' => 'Tinebase.exception'));
            }
        }
    }

    /**
     * handler for JSON api requests
     * @todo session expre handling
     * 
     * @return JSON
     */
    public function handleJson()
    {
        try {
            $this->_initFramework();
            Zend_Registry::get('logger')->debug('is json request. method: ' . $_REQUEST['method']);
            
            $anonymnousMethods = array(
                'Tinebase.login',
                'Tinebase.getAvailableTranslations',
                'Tinebase.setLocale'
            );
            // check json key for all methods but some exceptoins
            if ( !(in_array($_POST['method'], $anonymnousMethods) || preg_match('/Tinebase_UserRegistration/', $_POST['method'])) 
                    && $_POST['jsonKey'] != Zend_Registry::get('jsonKey') ) { 
    
                if (! Zend_Registry::isRegistered('currentAccount')) {
                    Zend_Registry::get('logger')->INFO('Attempt to request a privileged Json-API method without autorisation from "' . $_SERVER['REMOTE_ADDR'] . '". (seesion timeout?)');
                    
                    throw new Exception('Not Autorised', 401);
                } else {
                    Zend_Registry::get('logger')->WARN('Fatal: got wrong json key! (' . $_POST['jsonKey'] . ') Possible CSRF attempt!' .
                        ' affected account: ' . print_r(Zend_Registry::get('currentAccount')->toArray(), true) .
                        ' request: ' . print_r($_REQUEST, true)
                    );
                    
                    throw new Exception('Possible CSRF attempt detected!');
                }
            }
    
            $server = new Zend_Json_Server();
            
            // add json apis which require no auth
            $server->setClass('Tinebase_Json', 'Tinebase');
            $server->setClass('Tinebase_Json_UserRegistration', 'Tinebase_UserRegistration');
            
            // register addidional Json apis only available for authorised users
            if (Zend_Auth::getInstance()->hasIdentity()) {
                // addidional Tinebase json apis
                $server->setClass('Tinebase_Json_Container', 'Tinebase_Container');
    
                // application apis
                $userApplications = Zend_Registry::get('currentAccount')->getApplications();
                foreach ($userApplications as $application) {
                    $applicationName = ucfirst((string) $application);
                    try {
                        $server->setClass($applicationName.'_Json', $applicationName);
                    } catch (Exception $e) {
                        // do nothing
                    }
                }
            }
        } catch (Exception $exception) {
            $server = new Zend_Json_Server();
            $server->fault($exception, $exception->getCode());
            exit;
        }
         
        $server->handle($_REQUEST);
    }
    
    /**
     * handler for SNOM api requests
     * 
     * @return xml
     */
    public function handleSnom()
    {
        if(isset($_REQUEST['TINE20SESSID'])) {
            Zend_Session::setId($_REQUEST['TINE20SESSID']);
        }
        
        $this->_initFramework();
        Zend_Registry::get('logger')->debug('is snom xml request. method: ' . (isset($_REQUEST['method']) ? $_REQUEST['method'] : 'EMPTY'));
        
        $server = new Tinebase_Http_Server();
        $server->setClass('Voipmanager_Snom', 'Voipmanager');
                    
        $server->handle($_REQUEST);
    }
    
    /**
     * initializes the logger
     *
     */
    protected function setupLogger()
    {
        $logger = new Zend_Log();
        
        if (isset($this->_config->logger)) {
            try {
                $loggerConfig = $this->_config->logger;
                
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

        Zend_Registry::set('logger', $logger);

        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' logger initialized');
    }
    
    /**
     * setup the cache and add it to zend registry
     *
     */
    protected function setupCache()
    {
        // create zend cache
        if ($this->_config->caching && $this->_config->caching->active) {
            $frontendOptions = array(
                'cache_id_prefix' => SQL_TABLE_PREFIX,
                'lifetime' => ($this->_config->caching->lifetime) ? $this->_config->caching->lifetime : 7200,
                'automatic_serialization' => true // turn that off for more speed
            );
                        
            $backendType = ($this->_config->caching->backend) ? ucfirst($this->_config->caching->backend) : 'File';
            
            switch ($backendType) {
                case 'File':
                    $backendOptions = array(
                        'cache_dir' => ($this->_config->caching->path) ? $this->_config->caching->path : session_save_path()  // Directory where to put the cache files
                    );
                break;
                case 'Memcached':                        
                    $backendOptions = array(
                        'servers' => array(
                            'host' => ($this->_config->caching->host) ? $this->_config->caching->host : 'localhost',
                            'port' => ($this->_config->caching->port) ? $this->_config->caching->port : 11211,
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
        Zend_Registry::set('cache', $cache);
    }
    
    /**
     * initializes the database connection
     *
     */
    protected function setupDatabaseConnection()
    {
        if (isset($this->_config->database)) {
            $dbConfig = $this->_config->database;
            
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
     * @param  string $_locale
     * @param  bool   $_saveaspreference
     * @return Zend_Locale
     */
    public function setupUserLocale($_localeString = 'auto', $_saveaspreference = FALSE)
    {
        if ($_localeString == 'auto' && isset($this->_session->currentAccount)) {
            // get locale from config/preferences
            $localeString = Tinebase_Config::getInstance()
                ->getPreference(Zend_Registry::get('currentAccount')->getId(), 'Locale')
                ->value;
        } else {
            $localeString = $_localeString;
        }
        
        if ($localeString == 'auto' && isset($this->_session->userLocale)) {
            $locale = $this->_session->userLocale;
        } else {
            try {
                $locale = new Zend_Locale($localeString);
            } catch (Zend_Locale_Exception $e) {
                $locale = new Zend_Locale('en_US');
            }
            if ($this->_session !== NULL) {
                $this->_session->userLocale = $locale;
            }
        }
        
        // save in registry
        Zend_Registry::set('locale', $locale);
        
        // save locale in config
        if ($_saveaspreference) {
            $preference = new Tinebase_Model_Config(array(
                'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
                'name' => 'Locale',
                'value' => $localeString
            ));
            Tinebase_Config::getInstance()->setPreference(Zend_Registry::get('currentAccount')->getId(), $preference);
        }
                
        return $locale;
    }
    
    /**
     * intializes the timezone handling
     *
     */
    protected function setupServerTimezone()
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
    public function setupUserTimezone($_timezone = NULL, $_saveaspreference = FALSE)
    {
        if ($_timezone === NULL) {
            // get timezone from config/preferences
            if (isset($this->_session->currentAccount)) {
                $timezone = Tinebase_Config::getInstance()
                    ->getPreference(Zend_Registry::get('currentAccount')->getId(), 'Timezone')
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
                Tinebase_Config::getInstance()->setPreference(Zend_Registry::get('currentAccount')->getId(), $preference);
            }
        }
        
        Zend_Registry::set('userTimeZone', $timezone);
        
        return $timezone;
    }
    
    /**
     * create new user seesion
     *
     * @param string $_username
     * @param string $_password
     * @param string $_ipAddress
     * @return bool
     */
    public function login($_username, $_password, $_ipAddress)
    {
        $authResult = Tinebase_Auth::getInstance()->authenticate($_username, $_password);
        
        if ($authResult->isValid()) {
            $accountsController = Tinebase_User::getInstance();
            try {
                $account = $accountsController->getFullUserByLoginName($authResult->getIdentity());
            } catch (Exception $e) {
                Zend_Session::destroy();
                
                throw new Exception('account ' . $authResult->getIdentity() . ' not found in account storage');
            }
            
            Zend_Registry::set('currentAccount', $account);

            $this->_session->currentAccount = $account;
            
            $account->setLoginTime($_ipAddress);
            
            Tinebase_AccessLog::getInstance()->addLoginEntry(
                session_id(),
                $authResult->getIdentity(),
                $_ipAddress,
                $authResult->getCode(),
                Zend_Registry::get('currentAccount')
           );
            
            return true;
        } else {
            Tinebase_AccessLog::getInstance()->addLoginEntry(
                session_id(),
                $_username,
                $_ipAddress,
                $authResult->getCode()
           );
            
            Tinebase_AccessLog::getInstance()->addLogoutEntry(
                session_id(),
                $_ipAddress
           );
            
            Zend_Session::destroy();
            
            sleep(2);
            
            return false;
        }
    }
    
    /**
     * change user password
     *
     * @param string $_oldPassword
     * @param string $_newPassword1
     * @param string $_newPassword2
     */
    public function changePassword($_oldPassword, $_newPassword1, $_newPassword2)
    {
        //error_log(print_r(Zend_Registry::get('currentAccount')->toArray(), true));
        $loginName = Zend_Registry::get('currentAccount')->accountLoginName;
        Zend_Registry::get('logger')->debug("change password for $loginName");
        
        if (!Tinebase_Auth::getInstance()->isValidPassword($loginName, $_oldPassword)) {
            throw new Exception('old password worng');
        }
        
        Tinebase_Auth::getInstance()->setPassword($loginName, $_newPassword1, $_newPassword2);
    }
    
    /**
     * destroy session
     *
     * @return void
     */
    public function logout($_ipAddress)
    {
        if (Zend_Registry::isRegistered('currentAccount')) {
            $currentAccount = Zend_Registry::get('currentAccount');
    
            Tinebase_AccessLog::getInstance()->addLogoutEntry(
                session_id(),
                $_ipAddress,
                $currentAccount->accountId
           );
        }
        
        Zend_Session::destroy();
    }   
    
    /**
     * function to initialize the smtp connection
     *
     */
    protected function setupMailer()
    {
        if (isset($this->_config->mail)) {
            $mailConfig = $this->_config->mail;
        } else {
            $mailConfig = new Zend_Config(array(
                'smtpserver' => 'localhost', 
                'port' => 25
           ));
        }
        
        $transport = new Zend_Mail_Transport_Smtp($mailConfig->smtpserver,  $mailConfig->toArray());
        Zend_Mail::setDefaultTransport($transport);
    }
    
    /**
     * gets image info and data
     * 
     * @param  string $_application application which manages the image
     * @param  string $_identifier identifier of image/record
     * @param  string $_location optional additional identifier
     * @return Tinebase_Model_Image
     */
    public function getImage($_application, $_identifier, $_location='')
    {
        $appController = $this->getApplicationInstance($_application);
        if (!method_exists($appController, 'getImage')) {
            throw new Exception("$_application has no getImage function");
        }
        $image = $appController->getImage($_identifier, $_location);
        
        if (!$image instanceof Tinebase_Model_Image) {
            throw new Exception("$_application returned invalid image");
        }
        return $image;
    }
}