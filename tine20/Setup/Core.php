<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: Core.php 5153 2008-10-29 14:23:09Z p.schuele@metaways.de $
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
     * dispatch request
     *
     */
    public static function dispatchRequest()
    {
        $server = NULL;
        
        /**************************** JSON API *****************************/

        if (( (isset($_SERVER['HTTP_X_TINE20_REQUEST_TYPE']) && $_SERVER['HTTP_X_TINE20_REQUEST_TYPE'] == 'JSON')  || 
              (isset($_POST['requestType']) && $_POST['requestType'] == 'JSON')
            ) && isset($_REQUEST['method'])) {
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
     * NOTE a config object will be intanciated regardless of the existance of 
     *      the conffile!
     *
     * @return void
     */
    public static function setupConfig()
    {
        if(self::configFileExists()) {
            $config = new Zend_Config(require dirname(__FILE__) . '/../config.inc.php');
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
        return file_exists(dirname(__FILE__) . '/../config.inc.php');
    }

    /**
     * checks if global config file or tine root is writable
     *
     * @return bool
     */
    public static function configFileWritable()
    {
        $path = dirname(dirname(__FILE__));
        
        if (self::configFileExists()) {
            return is_writable($path . DIRECTORY_SEPARATOR . 'config.inc.php');
        } else {
            $testfilename = $path . uniqid(mt_rand()).'.tmp';
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
     * @throws  Tinebase_Exception_UnexpectedValue
     * 
     * @todo try to write to db, if it fails: self::set(Setup_Core::CHECKDB, FALSE);
     */
    public static function setupDatabaseConnection()
    {
        self::set(Setup_Core::CHECKDB, FALSE);
        
        // check database first
        if (self::configFileExists()) {
            try {
                parent::setupDatabaseConnection();
                
                // check (mysql)db server version
                $ext = new Setup_ExtCheck(dirname(__FILE__) . '/essentials.xml');
                if ($mysqlRequired = $ext->getExtensionData('MySQL')) {
                    $dbConfig = Tinebase_Core::getConfig()->database;
                    $link = @mysql_connect($dbConfig->host, $dbConfig->username, $dbConfig->password);
                    if ($link) {
                        $serverVersion = @mysql_get_server_info();
                        if (version_compare($mysqlRequired['VERSION'], $serverVersion, '<')) {
                            self::set(Setup_Core::CHECKDB, TRUE);
                        } else {
                            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                                . ' MySQL server version incompatible! ' . $serverVersion
                                . ' < ' . $mysqlRequired['VERSION']
                            );
                        }
                    }
                } else {
                    self::set(Setup_Core::CHECKDB, TRUE);
                }
            } catch (Zend_Db_Adapter_Exception $zae) {
                Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $zae->getMessage());
            }
        }
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
     *
     */
    public static function setupSession()
    {
        $config = self::getConfig();
        
        Zend_Session::setOptions(array(
            'name'              => 'TINE20SETUPSESSID',
            'cookie_httponly'   => true,
            'hash_function'     => 1,
        
        ));
        if(isset($_SERVER['HTTPS'])) {
            Zend_Session::setOptions(array(
                'cookie_secure'     => true,
            ));
        }
        
        Zend_Session::start();
        
        define('TINE20_BUILDTYPE',          strtoupper($config->get('buildtype', 'DEVELOPMENT')));
        define('TINE20SETUP_CODENAME',      'trunk');
        define('TINE20SETUP_PACKAGESTRING', 'none');
        define('TINE20SETUP_RELEASETIME',   'none');

        if (TINE20_BUILDTYPE == 'RELEASE') {
            // set error mode to suppress notices & warnings in release mode
            error_reporting(E_ERROR);
        }
                
        $session = new Zend_Session_Namespace('tinesetup');
        
        if (!isset($session->jsonKey)) {
            $session->jsonKey = Tinebase_Record_Abstract::generateUID();
        }
        self::set('jsonKey', $session->jsonKey);

        if (isset($session->setupuser)) {
            self::set(self::USER, $session->setupuser);
        }
        
        self::set(self::SESSION, $session);
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
