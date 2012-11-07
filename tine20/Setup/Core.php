<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
        
        Setup_Core::setupSession();
        
        Setup_Core::startSession();
        
        // setup a temporary user locale/timezone. This will be overwritten later but we 
        // need to handle exceptions during initialisation process such as seesion timeout
        Setup_Core::set('locale', new Zend_Locale('en_US'));
        Setup_Core::set('userTimeZone', 'UTC');
        
        Setup_Core::setupUserLocale();
        
        header('X-API: http://www.tine20.org/apidocs/tine20/');
    }
    
    /**
     * dispatch request
     *
     */
    public static function dispatchRequest()
    {
        $server = NULL;
        
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
        if(self::configFileExists()) {
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
     * initializes the session
     */
    public static function setupSession()
    {
        self::setSessionOptions(array(
            'name' => 'TINE20SETUPSESSID'
        ));
    }
    
    /**
     * initializes the build constants like buildtype, package information, ...
     */
    public static function setupBuildConstants()
    {
        $config = self::getConfig();
        define('TINE20_BUILDTYPE',           strtoupper($config->get('buildtype', 'DEVELOPMENT')));
        define('TINE20SETUP_CODENAME',       getDevelopmentRevision());
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
}
