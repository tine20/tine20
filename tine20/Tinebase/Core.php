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
 * @todo        implement get registry/config/session and replace $this->_config and $this->_session
 * @todo        use it in index.php 
 */

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
    /**
     * dispatch request
     *
     * @todo add ActiveSync
     */
    public static function dispatchRequest()
    {
        $server = NULL;
        
        if (( (isset($_SERVER['HTTP_X_TINE20_REQUEST_TYPE']) && $_SERVER['HTTP_X_TINE20_REQUEST_TYPE'] == 'JSON')  || 
              (isset($_POST['requestType']) && $_POST['requestType'] == 'JSON')
            ) && isset($_REQUEST['method'])) {
            $server = new Tinebase_Server_Json();
                    
        } elseif(preg_match('/^Mozilla\/4\.0 \(compatible; (snom...)\-SIP (\d+\.\d+\.\d+)/i', $_SERVER['HTTP_USER_AGENT'])) {
            // SNOM api
            $server = new Voipmanager_Server_Snom();
            
        } else {
            // HTTP api
            $server = new Tinebase_Server_Http();
        }        
        
        $server->handle();
    }
    
    /**
     * initializes the logger
     *
     */
    public static function setupLogger()
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
    public static function setupCache()
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
    public static function setupDatabaseConnection()
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
     * @param  string $_localeString
     * @param  bool   $_saveaspreference
     */
    public static function setupUserLocale($_localeString = 'auto', $_saveaspreference = FALSE)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " given localeString '$_localeString'");
        $localeString = NULL;
        if ($_localeString == 'auto') {
            // if the session already has a locale, use this, otherwise take the preference
            // NOTE: the preference allways exists, cuase setup gives one!
            if (isset($this->_session->userLocale)) {
                $localeString = $this->_session->userLocale;
                Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " session value '$localeString'");
            } elseif (isset($this->_session->currentAccount)) {
                $localeString = Tinebase_Config::getInstance()
                    ->getPreference(Zend_Registry::get('currentAccount')->getId(), 'Locale')
                    ->value;
                    
                Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " preference '$localeString'");
            }
        } 
        $locale = Tinebase_Translation::getLocale($localeString ? $localeString : $_localeString);
        
        // save in session and registry
        if ($this->_session !== NULL) {
            $this->_session->userLocale = (string)$locale;
        }
        Zend_Registry::set('locale', $locale);
        
        // save locale in config
        if ($_saveaspreference && Zend_Registry::isRegistered('currentAccount')) {
            $preference = new Tinebase_Model_Config(array(
                'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
                'name' => 'Locale',
                'value' => (string)$locale
            ));
            Tinebase_Config::getInstance()->setPreference(Zend_Registry::get('currentAccount')->getId(), $preference);
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
     * function to initialize the smtp connection
     *
     */
    public static function setupMailer()
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
}
