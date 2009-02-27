<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * setups golbal config
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
     * initializes the database connection
     *
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public static function setupDatabaseConnection()
    {
        // check database first
        try {
            parent::setupDatabaseConnection();
        } catch (Zend_Db_Adapter_Exception $zae) {
            self::set('checkDB', FALSE);
        }
        
        //-- try to write to db
        
        self::set('checkDB', TRUE);
    }
    
    /**
     * setups the logger
     * 
     * NOTE: if no logger is configured, we write to stderr in setup
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
            $writer = new Zend_Log_Writer_Stream('php://stderr');;
            $logger->addWriter($writer);
        }

        self::set(self::LOGGER, $logger);

        $logger->debug(__METHOD__ . '::' . __LINE__ .' logger initialized');
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
        
        define('TINE20SETUP_BUILDTYPE',     strtoupper($config->get('buildtype', 'DEVELOPMENT')));
        define('TINE20SETUP_CODENAME',      'trunk');
        define('TINE20SETUP_PACKAGESTRING', 'none');
        define('TINE20SETUP_RELEASETIME',   Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG));

        if (TINE20SETUP_BUILDTYPE == 'RELEASE') {
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
}
