<?php
/**
 * Tine 2.0
 * 
 * @package     Egwbase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * the class provides functions to handle applications
 * 
 * @package     Egwbase
 * @subpackage  Server
 */
class Egwbase_Controller
{
    /**
     * holdes the instance of the singleton
     *
     * @var Egwbase_Controller
     */
    private static $instance = NULL;
    
    /**
     * stores the egwbase session namespace
     *
     * @var Zend_Session_Namespace
     */
    protected $session;
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        Zend_Session::start();

        $this->setupLogger();

        $this->setupDatabaseConnection();

        $this->setupUserLocale();

        $this->setupTimezones();
        
        $this->session = new Zend_Session_Namespace('egwbase');
        
        if(!isset($this->session->jsonKey)) {
            $this->session->jsonKey = md5(mktime());
        }
        Zend_Registry::set('jsonKey', $this->session->jsonKey);

        if(isset($this->session->currentAccount)) {
            Zend_Registry::set('currentAccount', $this->session->currentAccount);
        }

    }
    
    /**
     * the singleton pattern
     *
     * @return Egwbase_Controller
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Egwbase_Controller;
        }
        
        return self::$instance;
    }
    
    /**
     * Enter description here...
     * 
     * @todo implement json key check
     *
     */
    public function handle()
    {

        $auth = Zend_Auth::getInstance();

        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && isset($_REQUEST['method']) 
              && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' && !empty($_REQUEST['method'])) {
                  
            Zend_Registry::get('logger')->debug('is json request. method: ' . $_REQUEST['method']);
            //Json request from ExtJS

            // is it save to use the jsonKey from $_GET too???
            // can we move this to the Zend_Json_Server???
            if($_POST['jsonKey'] != Zend_Registry::get('jsonKey')) {
                error_log('wrong JSON Key sent!!! expected: ' . Zend_Registry::get('jsonKey') . ' got: ' . $_POST['jsonKey'] . ' :: ' . $_REQUEST['method']);
                //throw new Exception('wrong JSON Key sent!!!');
            }

            $server = new Zend_Json_Server();

            $server->setClass('Egwbase_Json', 'Egwbase');

            if(Zend_Auth::getInstance()->hasIdentity()) {
            	// register addidional Egwbase Json servers
            	Egwbase_Json::setJsonServers($server);
            	
                $userApplications = Zend_Registry::get('currentAccount')->getApplications();
                
                foreach ($userApplications as $application) {
                    $applicationName = ucfirst($application->app_name);
                    try {
                        $server->setClass($applicationName.'_Json', $applicationName);
                    } catch (Exception $e) {
                        // do nothing
                    }
                }
            }

            $server->handle($_REQUEST);

        } else {
            Zend_Registry::get('logger')->debug('is http request. method: ' . ( isset($_REQUEST['method']) ? $_REQUEST['method'] : 'EMPTY' ) );
            // HTTP request
    
            $server = new Egwbase_Http_Server();
    
            $server->setClass('Egwbase_Http', 'Egwbase');
    
            if(Zend_Auth::getInstance()->hasIdentity()) {
                $userApplications = Zend_Registry::get('currentAccount')->getApplications();
                
                foreach ($userApplications as $application) {
                    $applicationName = ucfirst($application->app_name);
                    try {
                        $server->setClass($applicationName.'_Http', $applicationName);
                    } catch (Exception $e) {
                        // do nothing
                    }
                }
            }
    
            if(empty($_REQUEST['method'])) {
                if(Zend_Auth::getInstance()->hasIdentity()) {
                    $_REQUEST['method'] = 'Egwbase.mainScreen';
                } else {
                    $_REQUEST['method'] = 'Egwbase.login';
                }
            }
    
            $server->handle($_REQUEST);
    
        }
    }
    
    
    protected function setupLogger()
    {
        $logger = new Zend_Log();
        
        try {
            $loggerConfig = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini', 'logger');
            
            $filename = $loggerConfig->filename;
            $priority = (int)$loggerConfig->priority;

            $writer = new Zend_Log_Writer_Stream($filename);
            $logger->addWriter($writer);

            $filter = new Zend_Log_Filter_Priority($priority);
            $logger->addFilter($filter);

        } catch (Exception $e) {
            $writer = new Zend_Log_Writer_Null;
            $logger->addWriter($writer);
        }

        Zend_Registry::set('logger', $logger);

        Zend_Registry::get('logger')->debug('logger initialized');
    }
    
    protected function setupDatabaseConnection()
    {
        $dbConfig = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini', 'database');
        Zend_Registry::set('dbConfig', $dbConfig);
        
        define('SQL_TABLE_PREFIX', $dbConfig->get('tableprefix') ? $dbConfig->get('tableprefix') : 'egw_');
    
        $db = Zend_Db::factory('PDO_MYSQL', Zend_Registry::get('dbConfig')->toArray());
        Zend_Db_Table_Abstract::setDefaultAdapter($db);
        Zend_Registry::set('dbAdapter', $db);
    }
    
    protected function setupUserLocale()
    {
    	try {
            $locale = new Zend_Locale();
    	} catch (Zend_Locale_Exception $e) {
    		$locale = new Zend_Locale('en_US');
    	}
        Zend_Registry::set('locale', $locale);
    }
    
    protected function setupTimezones()
    {
        // All server operations are done in UTC
        date_default_timezone_set('UTC');
        
        // Timezone for client
        Zend_Registry::set('userTimeZone', 'Europe/Berlin');
    }

    public function login($_username, $_password, $_ipAddress)
    {
        $authResult = Egwbase_Auth::getInstance()->authenticate($_username, $_password);
        
        if ($authResult->isValid()) {
            $accountsController = Egwbase_Account::getInstance();
            try {
                $account = $accountsController->getFullAccountByLoginName($authResult->getIdentity());
            } catch (Exception $e) {
                Zend_Session::destroy();
                
                throw new Exception('account ' . $authResult->getIdentity() . ' not found in account storage');
            }
            
            Zend_Registry::set('currentAccount', $account);

            $this->session->currentAccount = $account;
            
            $account->setLoginTime($_ipAddress);
            
            Egwbase_AccessLog::getInstance()->addLoginEntry(
                session_id(),
                $authResult->getIdentity(),
                $_ipAddress,
                $authResult->getCode(),
                Zend_Registry::get('currentAccount')->accountId
            );
            
            return true;
        } else {
            Egwbase_AccessLog::getInstance()->addLoginEntry(
                session_id(),
                $_username,
                $_ipAddress,
                $authResult->getCode()
            );
            
            Egwbase_AccessLog::getInstance()->addLogoutEntry(
                session_id(),
                $_ipAddress
            );
            
            Zend_Session::destroy();
            
            sleep(2);
            
            return false;
        }
    }
    
    public function changePassword($_oldPassword, $_newPassword1, $_newPassword2)
    {
        //error_log(print_r(Zend_Registry::get('currentAccount')->toArray(), true));
        $loginName = Zend_Registry::get('currentAccount')->accountLoginName;
        Zend_Registry::get('logger')->debug("change password for $loginName");
        
        if(!Egwbase_Auth::getInstance()->isValidPassword($loginName, $_oldPassword)) {
            throw new Exception('old password worng');
        }
        
        Egwbase_Auth::getInstance()->setPassword($loginName, $_newPassword1, $_newPassword2);
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
    
            Egwbase_AccessLog::getInstance()->addLogoutEntry(
                session_id(),
                $_ipAddress,
                $currentAccount->accountId
            );
        }
        
        Zend_Session::destroy();
    }   
}