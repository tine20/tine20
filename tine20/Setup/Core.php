<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 *
 */

/**
 * dispatcher and initialisation class (functions are static)
 * - dispatchRequest() function
 * - initXYZ() functions 
 * - has registry and config
 * 
 * @package     Setup
 */
class Setup_Core extends Tinebase_Core
{
    /**
     * constant for config registry index
     *
     */
    const CHECKDB = 'checkDB';

    /**
     * init setup framework
     */
    public static function initFramework()
    {
        Setup_Core::setupConfig();
        
        Setup_Core::setupTempDir();
        
        //Database Connection must be setup before cache because setupCache uses constant "SQL_TABLE_PREFIX"
        Setup_Core::setupDatabaseConnection();
        
        Setup_Core::setupStreamWrapper();
        
        //Cache must be setup before User Locale because otherwise Zend_Locale tries to setup 
        //its own cache handler which might result in a open_basedir restriction depending on the php.ini settings 
        Setup_Core::setupCache();
        
        Setup_Core::setupBuildConstants();
        
        // setup a temporary user locale/timezone. This will be overwritten later but we 
        // need to handle exceptions during initialisation process such as seesion timeout
        Setup_Core::setLocale('en_US');
        Setup_Core::set(Tinebase_Core::USERTIMEZONE, 'UTC');
        
        Setup_Core::setupUserLocale();
        
        header('X-API: http://www.tine20.org/apidocs/tine20/');
    }
    
    /**
     * startSetupSession
     * 
     * TODO remove redundancy with Tinebase_Core::startCoreSession()
     */
    public static function startSetupSession ()
    {
        if (! Setup_Session::isStarted()) {
            try {
                Setup_Session::setSessionBackend();
            } catch (PDOException $pdoe) {
                Tinebase_Exception::log($pdoe);
                return;
            }
            Zend_Session::start();
        }

        $setupSession = Setup_Session::getSessionNamespace();
        
        if (isset($setupSession->setupuser)) {
            self::set(self::USER, $setupSession->setupuser);
        }
        
        if (!isset($setupSession->jsonKey)) {
            $setupSession->jsonKey = Tinebase_Record_Abstract::generateUID();
        }
        self::set('jsonKey', $setupSession->jsonKey);
    }
    
    /**
     * dispatch request
     *
     * @see Tinebase_Core::dispatchRequest()
     */
    public static function dispatchRequest()
    {
        $request = new \Zend\Http\PhpEnvironment\Request();
        self::set(self::REQUEST, $request);
        
        $server = NULL;

        // we need to initialize sentry at the very beginning to catch ALL errors
        self::setupSentry();
        
        /**************************** JSON API *****************************/
        if ( (isset($_SERVER['HTTP_X_TINE20_REQUEST_TYPE']) && $_SERVER['HTTP_X_TINE20_REQUEST_TYPE'] == 'JSON')  || 
             (isset($_POST['requestType']) && $_POST['requestType'] == 'JSON')
           ) {
            $server = new Setup_Server_Json();
        
        /**************************** CLI API *****************************/
        } elseif (php_sapi_name() == 'cli') {
            $server = new Setup_Server_Cli();
        
        /**************************** HTTP API ****************************/
        } else {
            $server = new Setup_Server_Http();
        }
        
        $server->handle();
    }
    
    /**
     * setups global config
     * 
     * NOTE a config object will be instantiated regardless of the existance of 
     *      the config file!
     *
     * @return void
     */
    public static function setupConfig()
    {
        if (self::configFileExists()) {
            $config = new Zend_Config(require self::getConfigFilePath());
        } else {
            $config = new Zend_Config(array());
        }
        self::set(self::CONFIG, $config);
    }
    
    /**
     * checks if global config file exists
     *
     * @return bool
     */
    public static function configFileExists()
    {
        return (bool)self::getConfigFilePath();
    }
    
    /**
     * Searches for config.inc.php in include paths and returnes the first match
     *
     * @return String
     */
    public static function getConfigFilePath()
    {
        $includePaths = explode(PATH_SEPARATOR, get_include_path());
        foreach ($includePaths as $includePath) {
            $path = $includePath . '/config.inc.php';
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * checks if global config file or tine root is writable
     *
     * @return bool
     */
    public static function configFileWritable()
    {
        if (self::configFileExists()) {
            $configFilePath = self::getConfigFilePath();
            return is_writable($configFilePath);
        } else {
            $path = dirname(dirname(__FILE__));
            $testfilename = $path . DIRECTORY_SEPARATOR . uniqid(mt_rand()).'.tmp';
            if (!($f = @fopen($testfilename, 'w'))) {
                error_log(__METHOD__ . '::' . __LINE__ . ' Your tine root dir ' . $path . ' is not writable for the webserver! Config file can\'t be created.');
                return false;
            }
            fclose($f);
            unlink($testfilename);
            return true;
        }
    }
    
    /**
     * initializes the database connection
     * 
     * @return boolean
     * 
     * @todo try to write to db, if it fails: self::set(Setup_Core::CHECKDB, FALSE);
     */
    public static function setupDatabaseConnection()
    {
        $dbcheck = FALSE;
        
        // check database first
        if (self::configFileExists()) {
            $dbConfig = Tinebase_Core::getConfig()->database;
            
            if ($dbConfig->adapter === self::PDO_MYSQL && (! defined(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY) || ! defined(PDO::MYSQL_ATTR_INIT_COMMAND))) {
                Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                    . ' MySQL PDO constants not defined.');
                return FALSE;
            }
            
            try {
                parent::setupDatabaseConnection();
                
                $serverVersion = self::getDb()->getServerVersion();
                
                switch ($dbConfig->adapter) {
                    case self::PDO_MYSQL:
                        if (version_compare(self::MYSQL_MINIMAL_VERSION, $serverVersion, '<')) {
                            $dbcheck = TRUE;
                        } else {
                            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                                . ' MySQL server version incompatible! ' . $serverVersion
                                . ' < ' . self::MYSQL_MINIMAL_VERSION
                            );
                        }
                        break;
                        
                    case self::ORACLE:

                        if (version_compare(self::ORACLE_MINIMAL_VERSION, $serverVersion, '<')) {
                            self::set(Setup_Core::CHECKDB, TRUE);
                        } else {
                            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                                . ' Oracle server version incompatible! ' . $serverVersion
                                . ' < ' . self::ORACLE_MINIMAL_VERSION
                            );
                        }

                        $dbcheck = TRUE;
                        break;

                    case self::PDO_PGSQL:
                        if (version_compare(self::PGSQL_MINIMAL_VERSION, $serverVersion, '<')) {
                            $dbcheck = TRUE;
                        } else {
                            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                                . ' PostgreSQL server version incompatible! ' . $serverVersion
                                . ' < ' . self::PGSQL_MINIMAL_VERSION
                            );
                        }
                        break;
                    
                    default:
                        // @todo check version requirements for other db adapters
                        $dbcheck = TRUE;
                        break;
                }
                
            } catch (Zend_Db_Adapter_Exception $zae) {
                Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' . $zae->getMessage());
            } catch (Zend_Db_Exception $zde) {
                Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' . $zde->getMessage());
            }
        }
        
        self::set(Setup_Core::CHECKDB, $dbcheck);
        return $dbcheck;
    }
    
    /**
     * setups the logger
     * 
     * NOTE: if no logger is configured, we write to stderr in setup
     *
     * @param $_defaultWriter Zend_Log_Writer_Abstract default log writer
     */
    public static function setupLogger(Zend_Log_Writer_Abstract $_defaultWriter = NULL)
    {
        $writer = new Zend_Log_Writer_Stream('php://stderr');
        parent::setupLogger($writer);
    }
    
    /**
     * initializes the build constants like buildtype, package information, ...
     */
    public static function setupBuildConstants()
    {
        parent::setupBuildConstants();

        define('TINE20SETUP_CODENAME',       Tinebase_Helper::getDevelopmentRevision());
        define('TINE20SETUP_PACKAGESTRING', 'none');
        define('TINE20SETUP_RELEASETIME',   'none');
    }
    
    /**
     * setup the cache and add it to zend registry
     * 
     * Ignores {@param $_enabled} and always sets it to false
     *
     */
    public static function setupCache($_enabled = true)
    {
        // disable caching for setup
        parent::setupCache(false);
    }

    /**
     * returns TRUE if doctrine is available for model config v2 stuff
     *
     * @return bool
     */
    public static function isDoctrineAvailable()
    {
        return PHP_VERSION_ID >= 50500 && interface_exists('Doctrine\Common\Persistence\Mapping\Driver\MappingDriver');
    }
}
