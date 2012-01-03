<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * php helpers
 */
require_once 'Helper.php';

/**
 * dispatcher and initialisation class (functions are static)
 * - dispatchRequest() function
 * - initXYZ() functions
 * - has registry and config
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Core
{
    /**************** registry indexes *************************/

    /**
     * constant for config registry index
     *
     */
    const CONFIG = 'configFile';

    /**
     * constant for locale registry index
     *
     */
    const LOCALE = 'locale';

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
     * const for current users credentialcache
     *
     */
    const USERCREDENTIALCACHE = 'usercredentialcache';

    /**
     * constant for database adapter
     *
     */
    const DB = 'dbAdapter';

    /**
     * constant for database adapter
     *
     */
    const USERTIMEZONE = 'userTimeZone';

    /**
     * constant for preferences registry
     *
     */
    const PREFERENCES = 'preferences';
    
    /**
     * constant for preferences registry
     *
     */
    const SCHEDULER = 'scheduler';
    
    /**
     * constant temp dir registry
     *
     */
    const TMPDIR = 'tmpdir';
    
    /**
     * constant temp dir registry
     *
     */
    const FILESDIR = 'filesdir';
    
    /**************** other consts *************************/

    /**
     * const PDO_MYSQL
     *
     */
    const PDO_MYSQL = 'Pdo_Mysql';
    
    /**
     * minimal version of MySQL supported
     */
    const MYSQL_MINIMAL_VERSION = '5.0.0';

    /**
     * const PDO_PGSQL
     *
     */
    const PDO_PGSQL = 'Pdo_Pgsql';
    
    /**
     * minimal version of PostgreSQL supported
     */
    const PGSQL_MINIMAL_VERSION = '8.4.8';

    /**
     * const PDO_OCI
     *
     */
    const PDO_OCI = 'Pdo_Oci';
    
    /**
     * const ORACLE
     * Zend_Db adapter name for the oci8 driver.
     *
     */
    const ORACLE = 'Oracle';

    /******************************* DISPATCH *********************************/

    /**
     * dispatch request
     *
     */
    public static function dispatchRequest()
    {
        // disable magic_quotes_runtime
        ini_set('magic_quotes_runtime', 0);

        // display errors we can't handle ourselves
        error_reporting(E_COMPILE_ERROR | E_CORE_ERROR | E_ERROR | E_PARSE);
        ini_set('display_errors', 1);

        ini_set('log_errors', 1);
        set_error_handler('Tinebase_Core::errorHandler', E_ALL | E_STRICT);

        // set default internal encoding
        ini_set('iconv.internal_encoding', 'utf-8');

        $server = NULL;
        
        /**************************** JSON API *****************************/
        if ( (isset($_SERVER['HTTP_X_TINE20_REQUEST_TYPE']) && $_SERVER['HTTP_X_TINE20_REQUEST_TYPE'] == 'JSON')  ||
            (isset($_SERVER['CONTENT_TYPE']) && substr($_SERVER['CONTENT_TYPE'],0,16) == 'application/json')  ||
            (isset($_POST['requestType']) && $_POST['requestType'] == 'JSON')
        ) {
            $server = new Tinebase_Server_Json();

            /**************************** SNOM API *****************************/
        } elseif(
            isset($_SERVER['HTTP_USER_AGENT']) &&
            preg_match('/^Mozilla\/4\.0 \(compatible; (snom...)\-SIP (\d+\.\d+\.\d+)/i', $_SERVER['HTTP_USER_AGENT'])
        ) {
            $server = new Voipmanager_Server_Snom();


            /**************************** ASTERISK API *****************************/
        } elseif(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == 'asterisk-libcurl-agent/1.0') {
            $server = new Voipmanager_Server_Asterisk();


            /**************************** ActiveSync API ****************************
             * RewriteRule ^/Microsoft-Server-ActiveSync(.*) /index.php?frontend=activesync [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]
             */
        } elseif((isset($_SERVER['REDIRECT_ACTIVESYNC']) && $_SERVER['REDIRECT_ACTIVESYNC'] == 'true') ||
                 (isset($_REQUEST['frontend']) && $_REQUEST['frontend'] == 'activesync')) {
            $server = new ActiveSync_Server_Http();


            /**************************** WebDAV / CardDAV / CalDAV API **********************************
             * RewriteCond %{REQUEST_METHOD} !^(GET|POST)$
             * RewriteRule ^/$            /index.php [E=WEBDAV:true,E=REDIRECT_WEBDAV:true,E=REMOTE_USER:%{HTTP:Authorization},L]
             *
             * RewriteRule ^/addressbooks /index.php [E=WEBDAV:true,E=REDIRECT_WEBDAV:true,E=REMOTE_USER:%{HTTP:Authorization},L]
             * RewriteRule ^/calendars    /index.php [E=WEBDAV:true,E=REDIRECT_WEBDAV:true,E=REMOTE_USER:%{HTTP:Authorization},L]
             * RewriteRule ^/principals   /index.php [E=WEBDAV:true,E=REDIRECT_WEBDAV:true,E=REMOTE_USER:%{HTTP:Authorization},L]
             * RewriteRule ^/webdav       /index.php [E=WEBDAV:true,E=REDIRECT_WEBDAV:true,E=REMOTE_USER:%{HTTP:Authorization},L]
             */
        } elseif(isset($_SERVER['REDIRECT_WEBDAV']) && $_SERVER['REDIRECT_WEBDAV'] == 'true') {
            $server = new Tinebase_Server_WebDAV();

            
            /**************************** CLI API *****************************/
        } elseif (php_sapi_name() == 'cli') {
            $server = new Tinebase_Server_Cli();


            /**************************** HTTP API ****************************/
        } else {

            /**************************** OpenID ****************************
             * RewriteRule ^/users/(.*)                      /index.php?frontend=openid&username=$1 [L,QSA]
             */
            if (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/xrds+xml') !== FALSE) {
                $_REQUEST['method'] = 'Tinebase.getXRDS';
            } elseif ((isset($_SERVER['REDIRECT_USERINFOPAGE']) && $_SERVER['REDIRECT_USERINFOPAGE'] == 'true') ||
                      (isset($_REQUEST['frontend']) && $_REQUEST['frontend'] == 'openid')) {
                $_REQUEST['method'] = 'Tinebase.userInfoPage';
            }
            
            if(!isset($_REQUEST['method']) && (isset($_REQUEST['openid_action']) || isset($_REQUEST['openid_assoc_handle'])) ) {
                $_REQUEST['method'] = 'Tinebase.openId';
            }

            $server = new Tinebase_Server_Http();
        }

        $server->handle();
        self::getDbProfiling();
    }

    /******************************* APPLICATION ************************************/

    /**
     * returns an instance of the controller of an application
     *
     * @param   string $_applicationName appname / modelname
     * @param   string $_modelName
     * @return  Tinebase_Controller_Abstract|Tinebase_Controller_Record_Abstract the controller of the application
     * @throws  Tinebase_Exception_NotFound
     * 
     * @todo    make getApplicationInstance work for Tinebase records (Tinebase_Model_User for example)
     */
    public static function getApplicationInstance($_applicationName, $_modelName = '', $_ignoreACL = FALSE)
    {
        if (strpos($_applicationName, '_')) {
            // got (complete) model name name as first param
            list($appName, $i, $modelName) = explode('_', $_applicationName, 3);
        } else {
            $appName = $_applicationName;
            $modelName = $_modelName;
        }
        
        $controllerName = ucfirst((string) $appName) . '_Controller';

        // check for model controller
        if (!empty($modelName)) {
            $modelName = preg_replace('/^' . $appName . '_' . 'Model_/', '', $modelName);

            $controllerNameModel = $controllerName . '_' . $modelName;
            if (! class_exists($controllerNameModel)) {

                // check for generic app controller
                if (! class_exists($controllerName)) {
                    throw new Tinebase_Exception_NotFound('No Controller found (checked classes '. $controllerName . ' and ' . $controllerNameModel . ')!');
                }
            } else {
                $controllerName = $controllerNameModel;
            }
        } else if (!@class_exists($controllerName)) {
            throw new Tinebase_Exception_NotFound('No Application Controller found (checked class ' . $controllerName . ')!');
        }

        if (! $_ignoreACL && is_object(Tinebase_Core::getUser()) && ! Tinebase_Core::getUser()->hasRight($appName, Tinebase_Acl_Rights_Abstract::RUN)) {
            throw new Tinebase_Exception_AccessDenied('No right to access application ' . $appName);
        }
        
        $controller = call_user_func(array($controllerName, 'getInstance'));

        return $controller;
    }

    /******************************* SETUP ************************************/

    /**
     * init tine framework
     */
    public static function initFramework()
    {
        Tinebase_Core::setupConfig();
        
        // Server Timezone must be setup before logger, as logger has timehandling!
        Tinebase_Core::setupServerTimezone();
        
        Tinebase_Core::setupLogger();
        
        // Database Connection must be setup before cache because setupCache uses constant "SQL_TABLE_PREFIX" 
        Tinebase_Core::setupDatabaseConnection();
        
        Tinebase_Core::setupTempDir();
        
        Tinebase_Core::setupStreamWrapper();
        
        //Cache must be setup before User Locale because otherwise Zend_Locale tries to setup 
        //its own cache handler which might result in a open_basedir restriction depending on the php.ini settings
        Tinebase_Core::setupCache();
        
        Tinebase_Core::setupSession();
        
        // setup a temporary user locale/timezone. This will be overwritten later but we 
        // need to handle exceptions during initialisation process such as session timeout
        // @todo add fallback locale to config file
        Tinebase_Core::set('locale', new Zend_Locale('en_US'));
        Tinebase_Core::set('userTimeZone', 'UTC');
        
//        Tinebase_Core::setupMailer();
        
        Tinebase_Core::setupUserCredentialCache();
        
        Tinebase_Core::setupUserTimezone();
        
        Tinebase_Core::setupUserLocale();
        
        header('X-API: http://www.tine20.org/apidocs/tine20/');
    }
    
    /**
     * tines error exception handler for catchable fatal errors
     *
     * NOTE: PHP < 5.3 don't throws exceptions for Catchable fatal errors per default,
     * so we convert them into exceptions manually
     *
     * @param integer $severity
     * @param string $errstr
     * @param string $errfile
     * @param integer $errline
     * @throws ErrorException
     */
    public static function errorHandler($severity, $errstr, $errfile, $errline)
    {
        if (error_reporting() == 0) {
            return;
        }

        switch ($severity) {
            case E_COMPILE_ERROR:
            case E_CORE_ERROR:
            case E_ERROR:
            case E_PARSE:
            case E_RECOVERABLE_ERROR:
            case E_USER_ERROR:
                throw new ErrorException($errstr, 0, $severity, $errfile, $errline);
                break;

            case E_COMPILE_WARNING:
            case E_CORE_WARNING:
            case E_USER_WARNING:
            case E_WARNING:
                if (Tinebase_Core::isRegistered(Tinebase_Core::LOGGER)) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " $errstr in {$errfile}::{$errline} ($severity)");
                } else {
                    error_log(" $errstr in {$errfile}::{$errline} ($severity)");
                }
                break;

            case E_NOTICE:
            case E_STRICT:
            case E_USER_NOTICE:
            default:
                if (Tinebase_Core::isRegistered(Tinebase_Core::LOGGER)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " $errstr in {$errfile}::{$errline} ($severity)");
                } else {
                    error_log(" $errstr in {$errfile}::{$errline} ($severity)");
                }
                break;
        }
    }

    /**
     * initializes the config
     *
     */
    public static function setupConfig()
    {
        self::set(self::CONFIG, Tinebase_Config::getInstance());
    }

    /**
     * setup temp dir registry setting retrieved by {@see _getTempDir()}
     *
     * @return void
     */
    public static function setupTempDir()
    {
        self::set(self::TMPDIR, self::guessTempDir());
    }

    /**
     * figure out temp directory:
     * config.inc.php > sys_get_temp_dir > session_save_path > /tmp
     *
     * @return String
     */
    public static function guessTempDir()
    {
        $config = self::getConfig();

        $tmpdir = $config->tmpdir;
        if ($tmpdir == Tinebase_Model_Config::NOTSET || !@is_writable($tmpdir)) {
            $tmpdir = sys_get_temp_dir();
            if (empty($tmpdir) || !@is_writable($tmpdir)) {
                $tmpdir = session_save_path();
                if (empty($tmpdir) || !@is_writable($tmpdir)) {
                    $tmpdir = '/tmp';
                }
            }
        }

        return $tmpdir;
    }

    /**
     * initializes the logger
     *
     * @param $_defaultWriter Zend_Log_Writer_Abstract default log writer
     */
    public static function setupLogger(Zend_Log_Writer_Abstract $_defaultWriter = NULL)
    {
        $config = self::getConfig();
        $logger = new Zend_Log();

        if (isset($config->logger) && $config->logger->active) {
            try {
                $loggerConfig = $config->logger;

                $filename = $loggerConfig->filename;
                $priority = (int)$loggerConfig->priority;

                $writer = new Zend_Log_Writer_Stream($filename);
                $formatter = new Tinebase_Log_Formatter_Session();
                $writer->setFormatter($formatter);
                $logger->addWriter($writer);

                $filter = new Zend_Log_Filter_Priority($priority);
                $logger->addFilter($filter);

                // add more filters here
                if (isset($loggerConfig->filter->user)) {
                    $logger->addFilter(new Tinebase_Log_Filter_User($loggerConfig->filter->user));
                }

                if (isset($loggerConfig->filter->message)) {
                    $logger->addFilter(new Zend_Log_Filter_Message($loggerConfig->filter->message));
                }

            } catch (Exception $e) {
                error_log("Tine 2.0 can't setup the configured logger! The Server responded: $e");
                $writer = ($_defaultWriter === NULL) ? new Zend_Log_Writer_Null() : $_defaultWriter;
                $logger->addWriter($writer);
            }
        } else {
            $writer = new Zend_Log_Writer_Syslog;
            $logger->addWriter($writer);
        }

        self::set(self::LOGGER, $logger);

        $logger->info(__METHOD__ . '::' . __LINE__ .' logger initialized');
    }

    /**
     * setup the cache and add it to zend registry
     *
     * @param bool $_enabled disabled cache regardless what's configured in config.inc.php
     * 
     * @todo use the same config keys as Zend_Cache (backend + frontend) to simplify this
     */
    public static function setupCache($_enabled = true)
    {
        $config = self::getConfig();
        
        // create zend cache
        if ($_enabled === true && $config->caching && $config->caching->active) {
            $frontendOptions = array(
                'lifetime'                  => ($config->caching->lifetime) ? $config->caching->lifetime : 7200,
                'automatic_serialization'   => true, // turn that off for more speed
                'caching'                   => true,
                'automatic_cleaning_factor' => 0,    // no garbage collection as this is done by a scheduler task
                'write_control'             => false, // don't read cache entry after it got written
                'logging'                   => (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)),
                'logger'                    => self::getLogger(),
            );

            $backendType = ($config->caching->backend) ? ucfirst($config->caching->backend) : 'File';
            $backendOptions = ($config->caching->backendOptions) ? $config->caching->backendOptions->toArray() : false;

            if (! $backendOptions) {
                switch ($backendType) {
                    case 'File':
                        $backendOptions = array(
                            'cache_dir'              => ($config->caching->path)     ? $config->caching->path     : Tinebase_Core::getTempDir(),
                            'hashed_directory_level' => ($config->caching->dirlevel) ? $config->caching->dirlevel : 4, 
                            'logging'                => (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)),
                            'logger'                 => self::getLogger(),
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
                        
                    case 'Redis':
                        $backendOptions = array(
                            'servers' => array(
                                'host'   => ($config->caching->host) ? $config->caching->host : 'localhost',
                                'port'   => ($config->caching->port) ? $config->caching->port : 6379,
                                'prefix' =>  Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId() . '_CACHE_'
                        ));
                        break;
                        
                    default:
                        $backendOptions = array();
                        break;
                }
            }

            Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ . " cache of backend type '{$backendType}' enabled");
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                // logger is an object, that makes ugly traces :)
                $backendOptionsWithoutLogger = $backendOptions;
                if (isset($backendOptionsWithoutLogger['logger'])) {
                    unset($backendOptionsWithoutLogger['logger']);
                }
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " backend options: " . print_r($backendOptionsWithoutLogger, TRUE));
            }

        } else {
            Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ . ' cache disabled');
            $backendType = 'Test';
            $frontendOptions = array(
                'caching' => false
            );
            $backendOptions = array(
            );
        }

        // getting a Zend_Cache_Core object
        try {
            $cache = Zend_Cache::factory('Core', $backendType, $frontendOptions, $backendOptions);
            
        } catch (Zend_Cache_Exception $e) {
            $enabled = FALSE;
            if ('File' === $backendType && !is_dir($backendOptions['cache_dir'])) {
                // create cache directory and re-try
                if (mkdir($backendOptions['cache_dir'], 0770, true)) {
                    $enabled = $_enabled;
                }
            }
            
            Tinebase_Core::getLogger()->WARN(__METHOD__ . '::' . __LINE__ . ' Cache error: ' . $e->getMessage());

            self::setupCache($enabled);
            return;
        }


        // some important caches
        Zend_Locale::setCache($cache);
        Zend_Translate::setCache($cache);
        
        self::set(self::CACHE, $cache);
    }

    /**
     * places user credential cache id from cache adapter (if present) into registry
     */
    public static function setupUserCredentialCache()
    {
        try {
            $cache = Tinebase_Auth_CredentialCache::getInstance()->getCacheAdapter()->getCache();
        } catch (Zend_Db_Statement_Exception $zdse) {
            // could not get credential cache adapter, perhaps Tine 2.0 is not installed yet
            $cache = NULL;
        }
        if ($cache !== NULL) {
            self::set(self::USERCREDENTIALCACHE, $cache);
        }
    }

    /**
     * initializes the session
     */
    public static function setupSession()
    {
        self::startSession(array(
            'name'              => 'TINE20SESSID',
        ));
        
        $config = self::getConfig();
        define('TINE20_BUILDTYPE',     strtoupper($config->get('buildtype', 'DEVELOPMENT')));
        define('TINE20_CODENAME',      getDevelopmentRevision());
        define('TINE20_PACKAGESTRING', 'none');
        define('TINE20_RELEASETIME',   'none');
        
        if (isset(self::get(self::SESSION)->currentAccount)) {
            self::set(self::USER, self::get(self::SESSION)->currentAccount);
        }
    }
    
    /**
     * setup stream wrapper for tine20:// prefix
     * 
     */
    public static function setupStreamWrapper()
    {
        if (empty(Tinebase_Core::getConfig()->filesdir)) {
            Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . " filesdir config value not set. tine20:// streamwrapper not registered.");
            
            return;
        }
        
        stream_wrapper_register('tine20', 'Tinebase_FileSystem_StreamWrapper');
    }
    
    /**
     * start session helper function
     * 
     * @param array $_options
     * @param string $_namespace
     * @throws Exception
     */
    public static function startSession($_options = array(), $_namespace = 'tinebase')
    {
        self::setSessionOptions($_options);
        self::setSessionBackend();
        
        try {
            Zend_Session::start();
        } catch (Exception $e) {
            Zend_Session::destroy();
            self::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Session error: ' . $e->getMessage());
            self::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
            throw $e;
        }
        
        $session = new Zend_Session_Namespace($_namespace);

        if (!isset($session->jsonKey)) {
            $session->jsonKey = Tinebase_Record_Abstract::generateUID();
        }
        self::set('jsonKey', $session->jsonKey);

        self::set(self::SESSION, $session);
    }
    /**
     * set session options
     * 
     * @param array $_options
     */
    public static function setSessionOptions($_options = array())
    {
        Zend_Session::setOptions(array_merge($_options, array(
            'cookie_httponly'   => true,
            'hash_function'     => 1
        )));
        
        if (isset($_SERVER['REQUEST_URI'])) {
            // cut of path behind caldav/webdav (removeme when dispatching is refactored)
            if (isset($_SERVER['REDIRECT_WEBDAV']) && $_SERVER['REDIRECT_WEBDAV'] == 'true') {
                $decodedUri = Sabre_DAV_URLUtil::decodePath($_SERVER['REQUEST_URI']);
                $baseUri = '/' . substr($decodedUri, 0, strpos($decodedUri, 'webdav/') + strlen('webdav/'));
            } else if (isset($_SERVER['REDIRECT_CALDAV']) && $_SERVER['REDIRECT_CALDAV'] == 'true') {
                $decodedUri = Sabre_DAV_URLUtil::decodePath($_SERVER['REQUEST_URI']);
                $baseUri = '/' . substr($decodedUri, 0, strpos($decodedUri, 'caldav/') + strlen('caldav/'));
            } else {
                $baseUri = dirname($_SERVER['REQUEST_URI']);
            }
            
            if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
                $baseUri = '/' . $_SERVER['HTTP_HOST'] . (($baseUri == '/') ? '' : $baseUri);
            }
            
            // fix for windows server with backslash directory separator
            $baseUri = str_replace(DIRECTORY_SEPARATOR, '/', $baseUri);
            
            Zend_Session::setOptions(array(
                'cookie_path'     => $baseUri
            ));
        }
        
        if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) != 'OFF') {
            Zend_Session::setOptions(array(
                'cookie_secure'     => true
            ));
        }
    }
        
    /**
     * set session backend
     */
    public static function setSessionBackend()
    {
        $config = self::getConfig();
        $backendType = ($config->session && $config->session->backend) ? ucfirst($config->session->backend) : 'File';
        $maxLifeTime = ($config->session && $config->session->lifetime) ? $config->session->lifetime : 86400; // one day is default
        switch ($backendType) {
            case 'File':
                if ($config->gc_maxlifetime) {
                    self::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " config.inc.php key 'gc_maxlifetime' should be renamed to 'lifetime' and moved to 'session' group.");
                    $maxLifeTime = $config->get('gc_maxlifetime', 86400);
                }
                Zend_Session::setOptions(array(
                	'gc_maxlifetime'     => $maxLifeTime
                ));
                
                $sessionSavepath = self::getSessionDir();
                if (ini_set('session.save_path', $sessionSavepath) !== false) {
                    if (!is_dir($sessionSavepath)) {
                        mkdir($sessionSavepath, 0700);
                    }
                }
                break;
                
            case 'Redis':
                $host   = ($config->session->host) ? $config->session->host : 'localhost';
                $port   = ($config->session->port) ? $config->session->port : 6379;
                $prefix = Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId() . '_SESSION_';
                
                Zend_Session::setOptions(array(
                	'gc_maxlifetime' => $maxLifeTime,
                    'save_handler'   => 'redis',
                    'save_path'      => "tcp://$host:$port?prefix=$prefix"
                ));
                break;
                
            default:
                break;
        }
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Session of backend type '{$backendType}' configured.");
    }
    
    /**
     * initializes the database connection
     *
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public static function setupDatabaseConnection()
    {
        $config = self::getConfig();

        if (isset($config->database)) {
            $dbConfig = $config->database;

            if (! defined('SQL_TABLE_PREFIX')) {
                define('SQL_TABLE_PREFIX', $dbConfig->get('tableprefix') ? $dbConfig->get('tableprefix') : 'tine20_');
            }

            $dbConfigArray = $dbConfig->toArray();
            $constName = 'self::' . strtoupper($dbConfigArray['adapter']);
            if (empty($dbConfigArray['adapter']) || ! defined($constName)) {
                self::getLogger()->warn('Wrong db adapter configured (' . $dbConfigArray['adapter'] . '). Using default: ' . self::PDO_MYSQL);
                $dbBackend = self::PDO_MYSQL;
                $dbConfigArray['adapter'] = self::PDO_MYSQL;
            } else {
                $dbBackend = constant($constName);
            }

            switch($dbBackend) {
                case self::PDO_MYSQL:
                    if (! defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                        throw new Tinebase_Exception_Backend_Database('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY is not defined. Please check PDO extension.');
                    }
                    
                    // force some driver options
                    $dbConfigArray['driver_options'] = array(
                        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => FALSE,
                        // set utf8 charset
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8;",
                    );
                    $db = Zend_Db::factory('Pdo_Mysql', $dbConfigArray);
                    try {
                        // set mysql timezone to utc and activate strict mode
                        $db->query("SET time_zone ='+0:00';");
                        $db->query("SET SQL_MODE = 'STRICT_ALL_TABLES'");
                        $db->query("SET SESSION group_concat_max_len = 8192");
                    } catch (Exception $e) {
                        self::getLogger()->warn('Failed to set "SET SQL_MODE to STRICT_ALL_TABLES or timezone: ' . $e->getMessage());
                    }
                    break;
                    
                case self::PDO_OCI:
                    $db = Zend_Db::factory('Pdo_Oci', $dbConfigArray);
                    break;
                    
                case self::ORACLE:
                    $db = Zend_Db::factory(self::ORACLE, $dbConfigArray);
                    $db->supportPositionalParameters(true);
                    $db->setLobAsString(true);
                    break;
                    
                case self::PDO_PGSQL:
                    unset($dbConfigArray['adapter']);
                    unset($dbConfigArray['tableprefix']);
                    $db = Zend_Db::factory('Pdo_Pgsql', $dbConfigArray);
                    try {
                        // set mysql timezone to utc and activate strict mode
                        $db->query("SET timezone ='+0:00';");
                        // PostgreSQL has always been strict about making sure data is valid before allowing it into the database
                    } catch (Exception $e) {
                        self::getLogger()->warn('Failed to set "SET timezone: ' . $e->getMessage());
                    }
                    break;
                    
                default:
                    throw new Tinebase_Exception_UnexpectedValue('Invalid database adapter defined. Please set adapter to ' . self::PDO_MYSQL . ' or ' . self::PDO_OCI . ' in config.inc.php.');
                    break;
            }

            Zend_Db_Table_Abstract::setDefaultAdapter($db);

            // place table prefix into the concrete adapter
            $db->table_prefix = SQL_TABLE_PREFIX;

            self::set(self::DB, $db);

        } else {
            die ('database section not found in central configuration file');
        }
    }

    /**
     * get db profiling
     *
     */
    public static function getDbProfiling()
    {
        $config = self::getConfig()->database;

        if ((bool) $config->profiler) {
            $profiler = Zend_Db_Table::getDefaultAdapter()->getProfiler();

			if (! empty($config->profilerFilterElapsedSecs)) {
				$profiler->setFilterElapsedSecs($config->profilerFilterElapsedSecs);	
			}

            $data = array(
                'totalNumQueries' => $profiler->getTotalNumQueries(),
                'totalElapsedSec' => $profiler->getTotalElapsedSecs(),
                'longestTime'  	  => 0,
				'longestQuery' 	  => ''
            );

            if ((bool) $config->queryProfiles) {
                $data['queryProfiles'] = array();
                foreach($profiler->getQueryProfiles() as $profile) {
                    $data['queryProfiles'][] = array(
                        'query'       => $profile->getQuery(),
                        'elapsedSecs' => $profile->getElapsedSecs(),
                    );
                    
                    if ($profile->getElapsedSecs() > $data['longestTime']) {
				        $data['longestTime']  = $profile->getElapsedSecs();
				        $data['longestQuery'] = $profile->getQuery();
				    }
                }
            }

            self::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') value: ' . print_r($data, true));
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

        // get locale object from session or ...
        if ($session !== NULL && isset($session->userLocale) && is_object($session->userLocale) && ($session->userLocale->toString() === $_localeString || $_localeString == 'auto')) {
            $locale = $session->userLocale;
            self::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " session value: " . (string)$locale);
            
        // ... create new locale object
        } else {
            $localeString = $_localeString;
            
            if ($_localeString == 'auto') {
                // check if cookie with language is available
                if (isset($_COOKIE['TINE20LOCALE'])) {
                    $localeString = $_COOKIE['TINE20LOCALE'];
                } elseif (isset($session->currentAccount)) {
                    $localeString = self::getPreference()->{Tinebase_Preference::LOCALE};
                    self::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " preference '$localeString'");
                }
            }
            
            $locale = Tinebase_Translation::getLocale($localeString);
    
            // save in session and registry
            if ($session !== NULL) {
                $session->userLocale = $locale;
            }
        }
        
        self::getLogger()->info(__METHOD__ . '::' . __LINE__ . " user locale: " . (string)$locale);
        
        self::set('locale', $locale);
        
        // save locale as preference
        if ($_saveaspreference && Tinebase_Core::isRegistered(self::USER)) {
            self::getPreference()->{Tinebase_Preference::LOCALE} = (string)$locale;
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
            if (isset($session->timezone)) {
                $timezone = $session->timezone;
            } else {
                // get timezone from preferences
                $timezone = self::getPreference()->getValue(Tinebase_Preference::TIMEZONE);
                $session->timezone = $timezone;
            }

        } else {
            $timezone = $_timezone;
            $session->timezone = $timezone;
            
            if ($_saveaspreference) {
                // save as user preference
                self::getPreference()->setValue(Tinebase_Preference::TIMEZONE, $timezone);
            }
        }

        self::set(self::USERTIMEZONE, $timezone);

        return $timezone;
    }

//    /**
//     * function to initialize the smtp connection
//     *
//     */
//    public static function setupMailer()
//    {
//        $config = self::getConfig();
//
//        if (isset($config->smtp)) {
//            $mailConfig = $config->smtp;
//        } else {
//            $mailConfig = new Zend_Config(array(
//                'hostname' => 'localhost', 
//                'port' => 25
//            ));
//        }
//
//        $transport = new Zend_Mail_Transport_Smtp($mailConfig->hostname,  $mailConfig->toArray());
//        Zend_Mail::setDefaultTransport($transport);
//    }

    /**
     * set php execution life (max) time
     *
     * @param int $_seconds
     * @return int old max exexcution time in seconds
     */
    public static function setExecutionLifeTime($_seconds)
    {
        $oldMaxExcecutionTime = ini_get('max_execution_time');
        
        if ($oldMaxExcecutionTime > 0) {
            if ((bool)ini_get('safe_mode') === true) {
                if (Tinebase_Core::isRegistered(self::LOGGER)) {
                    Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' max_execution_time(' . $oldMaxExcecutionTime . ') is too low. Can\'t set limit to ' . $_seconds . ' because of safe mode restrictions.');    
                }
            } else {
            	if (Tinebase_Core::isRegistered(self::LOGGER)) {
            	    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' setting execution life time to: ' . $_seconds);    
            	}
                set_time_limit($_seconds);
            }
        }
        
        return $oldMaxExcecutionTime;
    }

    /******************************* REGISTRY ************************************/

    /**
     * get a value from the registry
     *
     */
    public static function get($index)
    {
        return (Zend_Registry::isRegistered($index)) ? Zend_Registry::get($index) : NULL;
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
     * Returns the auth typ from config or default value
     *
     * @return String
     */
    public static function getAuthType()
    {
        if (isset(Tinebase_Core::getConfig()->authentication)) {
            $authType = Tinebase_Core::getConfig()->authentication->get('backend', Tinebase_Auth::SQL);
        } else {
            $authType = Tinebase_Auth::SQL;
        }

        return ucfirst($authType);
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
     * get configured loglevel
     * 
     * @return Int
     */
    public static function getLogLevel()
    {
        $config = self::getConfig();
        
        return isset($config->logger) && $config->logger->priority ? (int)$config->logger->priority : Zend_Log::EMERG;
    }
    
    /**
     * check if given loglevel should be logged
     * 
     * @param  int $_prio
     * @return bool
     */
    public static function isLogLevel($_prio)
    {
        return self::getLogLevel() >= $_prio;
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
     * get cache from the registry
     *
     * @return Zend_Cache_Core the cache
     */
    public static function getCache()
    {
        return self::get(self::CACHE);
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
     * get locale from the registry
     *
     * @return Zend_Locale
     */
    public static function getLocale()
    {
        return self::get(self::LOCALE);
    }
        
    /**
     * get current user account
     *
     * @return Tinebase_Model_FullUser the user account record
     */
    public static function getUser()
    {
        $result = (self::isRegistered(self::USER)) ? self::get(self::USER) : NULL;
        return $result;
    }

    /**
     * get preferences instance by application name (create+save it to registry if it doesn't exist)
     *
     * @param string $_application
     * @param boolean $_throwException throws exception if class does not exist
     * @return Tinebase_Preference_Abstract
     */
    public static function getPreference($_application = 'Tinebase', $_throwException = FALSE)
    {
        $result = NULL;

        if (self::isRegistered(self::PREFERENCES)) {
            $prefs = self::get(self::PREFERENCES);
            if (isset($prefs[$_application])) {
                $result = $prefs[$_application];
            }
        } else {
            $prefs = array();
        }

        if ($result === NULL) {
            $prefClassName = $_application . '_Preference';
            if (@class_exists($prefClassName)) {
                $result = new $prefClassName();
                $prefs[$_application] = $result;
                self::set(self::PREFERENCES, $prefs);
            } else if ($_throwException) {
                throw new Tinebase_Exception_NotFound('No preference class found for app ' . $_application);
            }
        }

        return $result;
    }

    /**
     * get db adapter
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public static function getDb()
    {
        return self::get(self::DB);
    }

    /**
     * get temp dir string (without PATH_SEP at the end)
     *
     * @return string
     */
    public static function getTempDir()
    {
        return self::get(self::TMPDIR);
    }

    /**
     * get session dir string (without PATH_SEP at the end)
     *
     * @return string
     * 
     * @todo remove obsolete session config paths in 2011-05
     */
    public static function getSessionDir()
    {
        $sessionDirName ='tine20_sessions';
        $config = self::getConfig();
        
        $sessionDir = ($config->session && $config->session->path) ? $config->session->path : null;
        
        #####################################
        # LEGACY/COMPATIBILITY: 
        # (1) had to rename session.save_path key to sessiondir because otherwise the
        # generic save config method would interpret the "_" as array key/value seperator
        # (2) moved session config to subgroup 'session'
        if (empty($sessionDir)) {
            foreach (array('session.save_path', 'sessiondir') as $deprecatedSessionDir) {
                $sessionDir = $config->get($deprecatedSessionDir, null);
                if ($sessionDir) {
                    self::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " config.inc.php key '{$deprecatedSessionDir}' should be renamed to 'path' and moved to 'session' group.");
                }
            }
        }
        #####################################

        if (empty($sessionDir) || !@is_writable($sessionDir)) {

            $sessionDir = session_save_path();
            if (empty($sessionDir) || !@is_writable($sessionDir)) {
                $sessionDir = self::guessTempDir();
            }

            $sessionDir .= DIRECTORY_SEPARATOR . $sessionDirName;
        }
        return $sessionDir;
    }
    
    /**
     * Singleton instance
     *
     * @return Zend_Scheduler
     */
    public static function getScheduler()
    {
        if (! self::get(self::SCHEDULER) instanceof Zend_Scheduler) {
            $scheduler =  new Zend_Scheduler();
            $scheduler->setBackend(new Zend_Scheduler_Backend_Db(array(
                'DbAdapter' => self::getDb(),
                'tableName' => SQL_TABLE_PREFIX . 'scheduler',
                'taskClass' => 'Tinebase_Scheduler_Task'
            )));
            
            self::set(self::SCHEDULER, $scheduler);
        }
        return self::get(self::SCHEDULER);
    }
}
