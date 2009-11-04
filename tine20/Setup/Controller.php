<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        move $this->_db calls to backend class
 * @todo        add role rights (run, admin) to all new installed apps
 * @todo        test user defined paths (logger/cache/tmpdir)
 */

/**
 * php helpers
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'Tinebase' . DIRECTORY_SEPARATOR . 'Helper.php';

/**
 * class to handle setup of Tine 2.0
 *
 * @package     Setup
 */
class Setup_Controller
{
    /**
     * holds the instance of the singleton
     *
     * @var Setup_Controller
     */
    private static $_instance = NULL;
    
    /**
     * setup backend
     *
     * @var Setup_Backend_Interface
     */
    protected $_backend;
    
    /**
     * the directory where applications are located
     *
     * @var string
     */
    protected $_baseDir;
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * the singleton pattern
     *
     * @return Setup_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Setup_Controller;
        }
        
        return self::$_instance;
    }

    /**
     * the constructor
     *
     */
    private function __construct()
    {
        // setup actions could take quite a while we try to set max execution time to 5 minutes
        Setup_Core::setExecutionLifeTime(300);
        
        if (!defined('MAXLOOPCOUNT')) {
            define('MAXLOOPCOUNT', 50);
        }
        
        $this->_baseDir = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
        
        if (Setup_Core::get(Setup_Core::CHECKDB)) {
            $this->_db = Setup_Core::getDb();
            $this->_backend = Setup_Backend_Factory::factory();      
        } else {
            $this->_db = NULL;
        }
    }

    /**
     * check system/php requirements (env + ext check)
     *
     * @return array
     * 
     * @todo add message to results array
     */
    public function checkRequirements()
    {
        $envCheck = $this->environmentCheck();
        
        $extCheck = new Setup_ExtCheck(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'essentials.xml');
        $extResult = $extCheck->getData();

        $result = array(
            'success' => ($envCheck['success'] && $extResult['success']),
            'results' => array_merge($envCheck['result'], $extResult['result']),
        );

        $result['totalcount'] = count($result['results']);
        
        return $result;
    }
    
    /**
     * Check if logger is propperly configured (or not configured at all)
     * 
     * @return bool
     */
    public function checkConfigLogger()
    {
        $config = Setup_Core::getConfig();
        if (!isset($config->logger) || !$config->logger->active) {
            return true;
        } else {
            return (isset($config->logger->filename) && is_writable($config->logger->filename));
        }
    }
    
    /**
     * Check if caching is propperly configured (or not configured at all)
     * 
     * @return bool
     */
    public function checkConfigCaching()
    {
        $config = Setup_Core::getConfig();
        if (!isset($config->caching) || !$config->caching->active) {
            return true;
        } else {
            return (isset($config->caching->path) && is_writable($config->caching->path));
        }
    }
    
    /**
     * Check if tmpdir is propperly configured (or not configured at all)
     * 
     * @return bool
     */
    public function checkConfigTmpDir()
    {
        $config = Setup_Core::getConfig();
        if (!isset($config->tmpdir) || empty($config->tmpdir)) {
            return true;
        } else {
            return @is_writable($config->tmpdir);
        }
    }
    
    /**
     * Check if session save path is propperly configured (or not configured at all)
     * 
     * @return bool
     */
    public function checkConfigSessionDir()
    {
        $config = Setup_Core::getConfig();
        $sessionSavePath = $config->get('sessiondir', null);
        if (empty($sessionSavePath)) {
            return true;
        } else {
            return @is_writable($sessionSavePath);
        }
    }
    
    /**
     * get list of applications as found in the filesystem
     *
     * @return array appName => setupXML
     */
    public function getInstallableApplications()
    {
        // create Tinebase tables first
        $applications = array('Tinebase' => $this->getSetupXml('Tinebase'));
        
        foreach (new DirectoryIterator($this->_baseDir) as $item) {
            $appName = $item->getFileName();
            if($appName{0} != '.' && $appName != 'Tinebase' && $item->isDir() && $appName != 'ExampleApplication' ) {
                $fileName = $this->_baseDir . $item->getFileName() . '/Setup/setup.xml' ;
                if(file_exists($fileName)) {
                    $applications[$item->getFileName()] = $this->getSetupXml($item->getFileName());
                }
            }
        }
        
        return $applications;
    }
                 
    /**
     * updates installed applications. does nothing if no applications are installed
     * 
     * @param Tinebase_Record_RecordSet $_applications
     * @return  array   messages
     */
    public function updateApplications(Tinebase_Record_RecordSet $_applications)
    {
        $smallestMajorVersion = NULL;
        $biggestMajorVersion = NULL;
        
        //find smallest major version
        foreach($_applications as $application) {
            if($smallestMajorVersion === NULL || $application->getMajorVersion() < $smallestMajorVersion) {
                $smallestMajorVersion = $application->getMajorVersion();
            }
            if($biggestMajorVersion === NULL || $application->getMajorVersion() > $biggestMajorVersion) {
                $biggestMajorVersion = $application->getMajorVersion();
            }
        }
        
        $messages = array();
        
        // update tinebase first (to biggest major version)
        $tinebase = $_applications->filter('name', 'Tinebase')->getFirstRecord();
        if (! empty($tinebase)) {
            unset($_applications[$_applications->getIndexById($tinebase->getId())]);
        
            list($major, $minor) = explode('.', $this->getSetupXml('Tinebase')->version[0]);
            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating Tinebase to version ' . $major . '.' . $minor);
            
            for ($majorVersion = $tinebase->getMajorVersion(); $majorVersion <= $major; $majorVersion++) {
                $messages += $this->updateApplication($tinebase, $majorVersion);
            }
        }
            
        // update the rest
        for ($majorVersion = $smallestMajorVersion; $majorVersion <= $biggestMajorVersion; $majorVersion++) {
            foreach ($_applications as $application) {
                if ($application->getMajorVersion() <= $majorVersion) {
                    $messages += $this->updateApplication($application, $majorVersion);
                }
            }
        }
        
        return $messages;
    }    
        
    /**
     * load the setup.xml file and returns a simplexml object
     *
     * @param string $_applicationName name of the application
     * @return SimpleXMLElement
     */
    public function getSetupXml($_applicationName)
    {
        $setupXML = $this->_baseDir . ucfirst($_applicationName) . '/Setup/setup.xml';

        if (!file_exists($setupXML)) {
            throw new Setup_Exception_NotFound(ucfirst($_applicationName) . '/Setup/setup.xml not found. If application got renamed or deleted, re-run setup.php.');
        }
        
        $xml = simplexml_load_file($setupXML);

        return $xml;
    }
    
    /**
     * check update
     *
     * @param   Tinebase_Model_Application $_application
     * @throws  Setup_Exception
     */
    public function checkUpdate(Tinebase_Model_Application $_application)  
    {
        $xmlTables = $this->getSetupXml($_application->name);
        if(isset($xmlTables->tables)) {
            foreach ($xmlTables->tables[0] as $tableXML) {
                $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableXML);
                if (true == $this->_backend->tableExists($table->name)) {
                    try {
                        $this->_backend->checkTable($table);
                    } catch (Setup_Exception $e) {
                        Setup_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . " Checking table failed with message '{$e->getMessage()}'");
                    }
                } else {
                    throw new Setup_Exception('Table ' . $table->name . ' for application' . $_application->name . " does not exist. \n<strong>Update broken</strong>");
                }
            }
        }
    }
    
    /**
     * update installed application
     *
     * @param   Tinebase_Model_Application    $_application
     * @param   string    $_majorVersion
     * @return  array   messages
     * @throws  Setup_Exception if current app version is too high
     */
    public function updateApplication(Tinebase_Model_Application $_application, $_majorVersion)
    {
        $setupXml = $this->getSetupXml($_application->name);
        $messages = array();
        
        switch(version_compare($_application->version, $setupXml->version)) {
            case -1:
                $message = "Executing updates for " . $_application->name . " (starting at " . $_application->version . ")";
                
                $messages[] = $message;
                Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' . $message);

                list($fromMajorVersion, $fromMinorVersion) = explode('.', $_application->version);
        
                $minor = $fromMinorVersion;
               
                if(file_exists(ucfirst($_application->name) . '/Setup/Update/Release' . $_majorVersion . '.php')) {
                    $className = ucfirst($_application->name) . '_Setup_Update_Release' . $_majorVersion;
                
                    $update = new $className($this->_backend);
                
                    $classMethods = get_class_methods($update);
              
                    // we must do at least one update
                    do {
                        $functionName = 'update_' . $minor;
                        
                        try {
                            $db = Setup_Core::getDb();
                            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
                        
                            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                                . ' Updating ' . $_application->name . ' - ' . $functionName
                            );
                            
                            $update->$functionName();
                        
                            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                
                        } catch (Exception $e) {
                            Tinebase_TransactionManager::getInstance()->rollBack();
                            Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                            Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                            throw $e;
                        }
                            
                        $minor++;
                    } while(array_search('update_' . $minor, $classMethods) !== false);
                }
                
                $messages[] = "<strong> Updated " . $_application->name . " successfully to " .  $_majorVersion . '.' . $minor . "</strong>";
                
                // update app version 
                $updatedApp = Tinebase_Application::getInstance()->getApplicationById($_application->getId());
                $_application->version = $updatedApp->version;
                Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updated ' . $_application->name . " successfully to " .  $_application->version);
                
                break; 
                
            case 0:
                Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No update needed for ' . $_application->name);
                break;
                
            case 1:
                throw new Setup_Exception('Current application version is higher than version from setup.xml: '
                    . $_application->version . ' > ' . $setupXml->version
                );
                break;
        }
        
        return $messages;
    }

    /**
     * checks if update is required
     *
     * @return boolean
     */
    public function updateNeeded($_application)
    {
        $setupXml = $this->getSetupXml($_application->name);
        
        $updateNeeded = version_compare($_application->version, $setupXml->version);
        
        if($updateNeeded === -1) {
            return true;
        }
        
        return false;        
    }
    
    /**
     * search for installed and installable applications
     *
     * @return array
     */
    public function searchApplications()
    {
        // get installable apps
        $installable = $this->getInstallableApplications();
        
        // get installed apps
        if (Setup_Core::get(Setup_Core::CHECKDB)) {
            try {
                $installed = Tinebase_Application::getInstance()->getApplications(NULL, 'id')->toArray();
                
                // merge to create result array
                $applications = array();
                foreach ($installed as $application) {
                    $depends = (array) $installable[$application['name']]->depends;
                    if (isset($depends['application'])) {
                        $depends = implode(', ', (array) $depends['application']);
                    }
                    
                    $application['current_version'] = (string) $installable[$application['name']]->version;
                    $application['install_status'] = (version_compare($application['version'], $application['current_version']) === -1) ? 'updateable' : 'uptodate';
                    $application['depends'] = $depends;
                    $applications[] = $application;
                    unset($installable[$application['name']]);
                }
            } catch (Zend_Db_Statement_Exception $zse) {
                // no tables exist
            }
        }
        
        foreach ($installable as $name => $setupXML) {
            $depends = (array) $setupXML->depends;
            if (isset($depends['application'])) {
                $depends = implode(', ', (array) $depends['application']);
            }
            
            $applications[] = array(
                'name'              => $name,
                'current_version'   => (string) $setupXML->version,
                'install_status'    => 'uninstalled',
                'depends'           => $depends,
            );
        }
        
        return array(
            'results'       => $applications,
            'totalcount'    => count($applications)
        );
    }

    /**
     * checks if setup is required
     *
     * @return boolean
     */
    public function setupRequired()
    {
        $result = FALSE;
        
        // check if applications table exists / only if db available
        if (Setup_Core::isRegistered(Setup_Core::DB)) {
            try {
                $applicationTable = Setup_Core::getDb()->describeTable(SQL_TABLE_PREFIX . 'applications');
            } catch (Zend_Db_Statement_Exception $e) {
                $result = TRUE;
            }
        }
        
        return $result;
    }
    
    /**
     * do php.ini environment check
     *
     * @return array
     */
    public function environmentCheck()
    {
        $result = array();
        $message = array();
        $success = TRUE;
        
        $helperLink = ' <a href="http://www.tine20.org/wiki/index.php/Admins/Install_Howto" target="_blank">Check the Tine 2.0 wiki for support.</a>';
        
        // check php environment
        $requiredIniSettings = array(
            'magic_quotes_sybase'  => 0,
            'magic_quotes_gpc'     => 0,
            'magic_quotes_runtime' => 0,
            'mbstring.func_overload' => 0,
            'eaccelerator.enable' => 0,
            'memory_limit' => '48M'
        );
        
        foreach ($requiredIniSettings as $variable => $newValue) {
            $oldValue = ini_get($variable);
            
            if ($variable == 'memory_limit') {
                $required = convertToBytes($newValue);
                $set = convertToBytes($oldValue);
                
                if ( $set < $required) {
                    $result[] = array(
                        'key'       => $variable,
                        'value'     => FALSE,
                        'message'   => "You need to set $variable equal or greater than $required (now: $set)." . $helperLink 
                    );
                    $success = FALSE;
                }

            } elseif ($oldValue != $newValue) {
                if (ini_set($variable, $newValue) === false) {
                    $result[] = array(
                        'key'       => $variable,
                        'value'     => FALSE,
                        'message'   => "You need to set $variable from $oldValue to $newValue."  . $helperLink
                    );
                    $success = FALSE;
                }
            } else {
                $result[] = array(
                    'key'       => $variable,
                    'value'     => TRUE,
                    'message'   => ''
                );
            }
        }
        
        return array(
            'result'        => $result,
            'success'       => $success,
        );
    }
    
    /**
     * get config file default values
     *
     * @return array
     */
    public function getConfigDefaults()
    {
        $defaultPath = Setup_Core::guessTempDir();
        
        $result = array(
            'database' => array(
                'host'  => 'localhost',
                'dbname' => 'tine20',
                'username' => 'tine20',
                'password' => '',
                'adapter' => 'pdo_mysql',
                'tableprefix' => 'tine20_',
                'port'          => 3306
            ),
            'logger' => array(
                'filename' => $defaultPath . DIRECTORY_SEPARATOR . 'tine20.log',
                'priority' => '7'    
            ),
            'caching' => array(
                   'active' => 1,
                   'lifetime' => 3600,
                   'backend' => 'File',
                   'path' => $defaultPath,
            ),
            'tmpdir' => $defaultPath,
            'sessiondir' => Setup_Core::getSessionDir(),
        );
        
        return $result;
    }

    /**
     * get config file values
     *
     * @return array
     */
    public function getConfigData()
    {
        $configArray = Setup_Core::getConfig()->toArray();
        
        #####################################
        # LEGACY/COMPATIBILITY: had to rename session.save_path key to sessiondir because otherwise the 
        # generic save config method would interpret the "_" as array key/value seperator
        if (empty($configArray['sessiondir']) && !empty($configArray['session.save_path'])) {
          $configArray['sessiondir'] = $configArray['session.save_path'];
          Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " config.inc.php key 'session.save_path' should be renamed to 'sessiondir'");
        }
        #####################################
        
        return $configArray;
    }
    
    /**
     * save data to config file
     *
     * @param array $_data
     * @param boolean $_merge
     */
    public function saveConfigData($_data, $_merge = TRUE)
    {
        
        if (!empty($_data['setupuser']['password']) && !Setup_Auth::isMd5($_data['setupuser']['password'])) {
          $_data['setupuser']['password'] = md5($_data['setupuser']['password']);
        }
        if (Setup_Core::configFileExists() && !Setup_Core::configFileWritable()) {
            throw new Setup_Exception('Config File is not writeable.');
        }
            
        // merge config data and active config
        if ($_merge) {
            $activeConfig = Setup_Core::getConfig();
            $config = new Zend_Config($activeConfig->toArray(), true);
            $config->merge(new Zend_Config($_data));
        } else {
            $config = new Zend_Config($_data);
        }
        
        // write to file
        $writer = new Zend_Config_Writer_Array(array(
            'config'   => $config,
            'filename' => dirname(__FILE__) . '/../config.inc.php'
        ));
        $writer->write();
        
        // set as active config
        Setup_Core::set(Setup_Core::CONFIG, $config);
    }
    
    /**
     * load authentication data
     * 
     * @return array
     */
    public function loadAuthenticationData()
    {
        return array(
                'authentication'    => $this->_getAuthProviderData(),
                'accounts'          => $this->_getAccountsStorageData(),
                'redirectSettings'  => $this->_getRedirectSettings()
            );
    }
    
    /**
     * Update authentication data
     * 
     * Needs Tinebase tables to store the data, therefore
     * installs Tinebase if it is not already installed
     * 
     * @param array $_authenticationData
     * 
     * @return bool
     */
    public function saveAuthentication($_authenticationData)
    {    
        if ($this->isInstalled('Tinebase')) {
            $this->_updateAuthentication($_authenticationData);
        } else {
            $installationOptions = array('authenticationData' => $_authenticationData);
            $this->installApplications(array('Tinebase'), $installationOptions);
        }
    }

    /**
     * Save {@param $_authenticationData} to config file
     * @todo save to config table instead of config file!
     * 
     * @param array $_authenticationData [hash containing settings for authentication and accountsStorage]
     * @return void
     */
    protected function _updateAuthentication($_authenticationData)
    {
        if (isset($_authenticationData['authentication'])) {
            $this->_updateAuthenticationProvider($_authenticationData['authentication']);
        }
        
        if (isset($_authenticationData['accounts'])) {
            $this->_updateAccountsStorage($_authenticationData['accounts']);
        }
        
        if (isset($_authenticationData['redirectSettings'])) {
          $this->_updateRedirectSettings($_authenticationData['redirectSettings']);
        }        
    }
    
    /**
     * Update authentication provider
     * 
     * @param array $_data
     * @return void
     */
    protected function _updateAuthenticationProvider($_data)
    {
        Tinebase_Auth::setBackendType($_data['backend']);
        
        $excludeKeys = array('adminLoginName', 'adminPassword', 'adminPasswordConfirmation');
        foreach ($excludeKeys as $key) {
          if (array_key_exists($key, $_data[$_data['backend']])) {
              unset($_data[$_data['backend']][$key]);
          }
        }
        
        Tinebase_Auth::setBackendConfiguration($_data[$_data['backend']]);
        Tinebase_Auth::saveBackendConfiguration();
    }
    
    /**
     * Update accountsStorage
     * 
     * @param array $_data
     * @return void
     */
    protected function _updateAccountsStorage($_data)
    {
        $originalBackend = Tinebase_User::getConfiguredBackend();
        $newBackend = $_data['backend'];
        Tinebase_User::setBackendType($_data['backend']);
        Tinebase_User::setBackendConfiguration($_data[$_data['backend']]);
        Tinebase_User::saveBackendConfiguration();
       
        if ($originalBackend != $newBackend && $this->isInstalled('Addressbook')) {
            if ($originalBackend == Tinebase_User::SQL) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Deleteing all user accounts, groups, roles and rights');
                //delete all users, groups and roles because they will be imported from new accounts storage backend
                Tinebase_User::factory(Tinebase_User::SQL)->deleteAllUsers();
                Tinebase_Group::factory(Tinebase_Group::SQL)->deleteAllGroups();
                
                $roles = Tinebase_Acl_Roles::getInstance();
                $roles->deleteAllRoles();
                
                Tinebase_Group::getInstance()->importGroups();
                $roles->createInitialRoles();
                $applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
                foreach ($applications as $application)
                {
                     Setup_Initialize::initializeApplicationRights($application);
                }
                
                Tinebase_User::getInstance()->importUsers(); //import users(ldap)/create initial users(sql)
                Tinebase_Group::getInstance()->importGroupMembers(); //import groups members(ldap)
                
            }
        }
    }
    
    /**
     * Update redirect settings
     * 
     * @param array $_data
     * @return void
     */
    protected function _updateRedirectSettings($_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_data, 1));
        $keys = array(Tinebase_Model_Config::REDIRECTURL, Tinebase_Model_Config::REDIRECTTOREFERRER);
        foreach ($keys as $key) {
            if (array_key_exists($key, $_data)) {
                if (strlen($_data[$key]) === 0) {
                    Tinebase_Config::getInstance()->deleteConfigForApplication($key);
                } else {
                    Tinebase_Config::getInstance()->setConfigForApplication($key, $_data[$key]);
                }
            }
        }
    }
    
    /**
     * 
     * get auth provider data
     * 
     * @return array
     * 
     * @todo get this from config table instead of file!
     */
    protected function _getAuthProviderData()
    {
        $result = Tinebase_Auth::getBackendConfigurationWithDefaults();
        $result['backend'] = Tinebase_Auth::getConfiguredBackend();

        return $result;
    }
    
    protected function _getAccountsStorageData()
    {
        $result = Tinebase_User::getBackendConfigurationWithDefaults();
        $result['backend'] = Tinebase_User::getConfiguredBackend();

        return $result;
    }
    
    /**
     * Get redirect Settings from config table.
     * If Tinebase is not installed, default values will be returned.
     * 
     * @return array
     */
    protected function _getRedirectSettings()
    {
      $return = array(
              Tinebase_Model_Config::REDIRECTURL => '',
              Tinebase_Model_Config::REDIRECTTOREFERRER => '0'
        );       
      if ($this->isInstalled('Tinebase')) {
          $return[Tinebase_Model_Config::REDIRECTURL] = Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::REDIRECTURL, NULL, '')->value;
          $return[Tinebase_Model_Config::REDIRECTTOREFERRER] = Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::REDIRECTTOREFERRER, NULL, '')->value;      
      }
      return $return;
    }

    
    /**
     * get email config
     * 
     * @return array
     */
    public function getEmailConfig()
    {
        $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
        $smtpConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::SMTP);
        
        $result = array(
            'imap' => $imapConfig,
            'smtp' => $smtpConfig
        );
        
        return $result;
    }
    
    /**
     * save email config
     * 
     * @param array $_data
     * @return void
     */
    public function saveEmailConfig($_data)
    {
        //Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_data, TRUE)); 

        Tinebase_Config::getInstance()->setConfigForApplication(Tinebase_Model_Config::IMAP, Zend_Json::encode($_data['imap']));
        Tinebase_Config::getInstance()->setConfigForApplication(Tinebase_Model_Config::SMTP, Zend_Json::encode($_data['smtp']));
    }
    
    /**
     * save config option in db using {@see Tinebase_Config::setConfigForApplication}
     * 
     * @param String $_key
     * @param String || array $_value
     * @return void
     */
    public function setConfigOption($_key, $_value)
    {
        $value = is_string($_value) ? $_value : Zend_Json::encode($_value);
        Tinebase_Config::getInstance()->setConfigForApplication($_key, $value);
    }
    
    /**
     * create new setup user session
     *
     * @param   string $_username
     * @param   string $_password
     * @return  bool
     */
    public function login($_username, $_password)
    {
        $setupAuth = new Setup_Auth($_username, $_password); 
        $authResult = Zend_Auth::getInstance()->authenticate($setupAuth);
        
        if ($authResult->isValid()) {
            //Zend_Session::registerValidator(new Zend_Session_Validator_HttpUserAgent());
            Zend_Session::regenerateId();
            
            Setup_Core::set(Setup_Core::USER, $_username);
            Setup_Core::getSession()->setupuser = $_username;            
            return true;
            
        } else {
            Zend_Session::destroy();
            sleep(2);
            return false;
        }
    }
    
    /**
     * destroy session
     *
     * @return void
     */
    public function logout()
    {
        Zend_Session::destroy();
    }   
    
    /**
     * install list of applications
     *
     * @param array $_applications list of application names
     * @param array | optional $_options
     * @return void
     * @todo remove deprecated code
     */
    public function installApplications($_applications, $_options = null)
    {
        // check requirements for initial install / add required apps to list
        if (! $this->isInstalled('Tinebase')) {
    
            $minimumRequirements = array('Tinebase', 'Addressbook', 'Admin');
            
            foreach ($minimumRequirements as $requiredApp) {
                if (!in_array($requiredApp, $_applications) && !$this->isInstalled($requiredApp)) {
                    // Addressbook has to be installed with Tinebase for initial data (user contact)
                    Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                        . ' ' . $requiredApp . ' has to be installed first (adding it to list).'
                    ); 
                    $_applications[] = $requiredApp;
                }
            }
        }
        
        // get xml and sort apps first
        $applications = array();
        foreach($_applications as $applicationName) {       	
            if ($this->isInstalled($applicationName)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " skipping installation of application {$applicationName} because it is already installed");
            } else {
                $applications[$applicationName] = $this->getSetupXml($applicationName);
            }
        }
        $applications = $this->_sortInstallableApplications($applications);
                
        Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' installing applications: ' . print_r(array_keys($applications), true));
        
        foreach ($applications as $name => $xml) {
            $this->_installApplication($xml, $_options);
        }

    }

    /**
     * delete list of applications
     *
     * @param array $_applications list of application names
     */
    public function uninstallApplications($_applications)
    {
        // deactivate foreign key check if all installed apps should be uninstalled
        $installedApps = Tinebase_Application::getInstance()->getApplications();
        if (count($installedApps) == count($_applications) && get_class($this->_backend) == 'Setup_Backend_Mysql') {
            $this->_backend->setForeignKeyChecks(0);
            foreach ($installedApps as $app) {
                if ($app->name != 'Tinebase') {
                    $this->_uninstallApplication($app);
                } else {
                    $tinebase = $app;
                }
            }
            // tinebase should be uninstalled last
            $this->_uninstallApplication($tinebase);
            $this->_backend->setForeignKeyChecks(1);
        } else {
            
            // get xml and sort apps first
            $applications = array();
            foreach($_applications as $applicationName) {
                $applications[$applicationName] = $this->getSetupXml($applicationName);
            }
            $applications = $this->_sortUninstallableApplications($applications);
            
            foreach ($applications as $name => $xml) {
                $app = Tinebase_Application::getInstance()->getApplicationByName($name);
                $this->_uninstallApplication($app);
            }
        }
    }
    
    /**
     * install given application
     *
     * @param  SimpleXMLElement $_xml
     * @param  array | optional $_options
     * @return void
     */
    protected function _installApplication($_xml, $_options = null)
    {
        $createdTables = array();
        if (isset($_xml->tables)) {
            foreach ($_xml->tables[0] as $tableXML) {
                $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableXML);
                $this->_backend->createTable($table);
                $createdTables[] = $table;
            }
        }

        $application = new Tinebase_Model_Application(array(
            'name'      => (string)$_xml->name,
            'status'    => $_xml->status ? (string)$_xml->status : Tinebase_Application::ENABLED,
            'order'     => $_xml->order ? (string)$_xml->order : 99,
            'version'   => (string)$_xml->version
        ));
        
        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' installing application: ' . $_xml->name);
        
        $application = Tinebase_Application::getInstance()->addApplication($application);
        
        // keep track of tables belonging to this application
        foreach ($createdTables as $table) {
            Tinebase_Application::getInstance()->addApplicationTable($application, (string) $table->name, (int) $table->version);
        }
        
        // insert default records
        if (isset($_xml->defaultRecords)) {
            foreach ($_xml->defaultRecords[0] as $record) {
                $this->_backend->execInsertStatement($record);
            }
        }
        
        // look for import definitions and put them into the db
        $this->_createImportExportDefinitions($application);
        
        Setup_Initialize::initialize($application, $_options);
    }

    /**
     * look for import definitions and put them into the db
     *
     * @param Tinebase_Model_Application $_application
     * 
     * @todo add support for export definitions
     */
    protected function _createImportExportDefinitions($_application)
    {
        $path = 
            $this->_baseDir . DIRECTORY_SEPARATOR . $_application->name . 
            DIRECTORY_SEPARATOR . 'Import' . DIRECTORY_SEPARATOR . 'definitions';

        if (file_exists($path)) {
            $definitionBackend = new Tinebase_ImportExportDefinition();
            
            foreach (new DirectoryIterator($path) as $item) {
                $filename = $path . DIRECTORY_SEPARATOR . $item->getFileName();
                if (preg_match("/\.xml/", $filename)) {
                    
                    // create definition
                    try {
                        $definition = $definitionBackend->getFromFile(
                            $filename, 
                            $_application->getId(), 
                            preg_replace("/\.xml/", '', $item->getFileName())
                        );
                        $definitionBackend->create($definition);
                    } catch (Tinebase_Exception_Record_Validation $erv) {
                        Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' not installing import/export definion: ' . $erv->getMessage());
                    }
                }
            }
        }
    }
    
    /**
     * uninstall app
     *
     * @param Tinebase_Model_Application $_application
     */
    protected function _uninstallApplication(Tinebase_Model_Application $_application)
    {
        Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Uninstall $_application");
        $applicationTables = Tinebase_Application::getInstance()->getApplicationTables($_application);
        
        do {
            $oldCount = count($applicationTables);

            if ($_application == 'Tinebase') {
                $installedApplications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
                if (count($installedApplications) !== 1) {
                    throw new Setup_Exception_Dependency('Failed to uninstall application "Tinebase" because of dependencies to other installed applications.');
                }
            }

            foreach ($applicationTables as $key => $table) {
                Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Remove table: $table");
                
                try {
                    $this->_backend->dropTable($table);
                    if ($_application != 'Tinebase') {
                        Tinebase_Application::getInstance()->removeApplicationTable($_application, $table);
                    }
                    unset($applicationTables[$key]);
                } catch(Zend_Db_Statement_Exception $e) {
                    // we need to catch exceptions here, as we don't want to break here, as a table
                    // might still have some foreign keys
                    $message = $e->getMessage();
                    Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " Could not drop table $table - " . $message);
                    
                    // remove app table if table not found in db
                    if (preg_match('/SQLSTATE\[42S02\]: Base table or view not found/', $message) && $_application != 'Tinebase') {
                        Tinebase_Application::getInstance()->removeApplicationTable($_application, $table);
                        unset($applicationTables[$key]);
                    }
                }
                
            }
            
            if ($oldCount > 0 && count($applicationTables) == $oldCount) {
                throw new Setup_Exception('dead lock detected oldCount: ' . $oldCount);
            }
        } while (count($applicationTables) > 0);
                
        if ($_application != 'Tinebase') {
            // remove application from table of installed applications
            $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_application);
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('application_id') . '= ?', $applicationId)
            );
            
            $this->_db->delete(SQL_TABLE_PREFIX . 'role_rights', $where);        
            $this->_db->delete(SQL_TABLE_PREFIX . 'container', $where);
            $this->_db->delete(SQL_TABLE_PREFIX . 'importexport_definition', $where);
                    
            Tinebase_Application::getInstance()->deleteApplication($_application);
        }
        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Removed app: " . $_application->name);
    }

    /**
     * sort applications by checking dependencies
     *
     * @param array $_applications
     * @return array
     */
    protected function _sortInstallableApplications($_applications)
    {
        // begin with Tinebase
        if (isset($_applications['Tinebase'])) {
            $result['Tinebase'] = $_applications['Tinebase'];
            unset($_applications['Tinebase']);
        } else {
            $result = array();
        }
        
        // get all apps to install ($name => $dependencies)
        $appsToSort = array();
        foreach($_applications as $name => $xml) {
            $depends = (array) $xml->depends; 
            if (isset($depends['application'])) {
                if ($depends['application'] == 'Tinebase') {
                    $appsToSort[$name] = array();
                    
                } else {
                    $depends['application'] = (array) $depends['application'];
                    
                    foreach ($depends['application'] as $app) {
                        // don't add tinebase (all apps depend on tinebase)
                        if ($app != 'Tinebase') {
                            $appsToSort[$name][] = $app;
                        }
                    }
                }
            } else {
                $appsToSort[$name] = array();
            }
        }
        
        //Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($appsToSort, true));
        
        // re-sort apps
        $count = 0;
        while (count($appsToSort) > 0 && $count < MAXLOOPCOUNT) {
            
            foreach($appsToSort as $name => $depends) {

                if (empty($depends)) {
                    // no dependencies left -> copy app to result set
                    $result[$name] = $_applications[$name];
                    unset($appsToSort[$name]);
                } else {
                    foreach ($depends as $key => $dependingAppName) {
                        if (in_array($dependingAppName, array_keys($result)) || $this->isInstalled($dependingAppName)) {
                            // remove from depending apps because it is already in result set
                            unset($appsToSort[$name][$key]);
                        }
                    }
                }
            }
            $count++;
        }
        
        if ($count == MAXLOOPCOUNT) {
            Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                " Some Applications could not be installed because of (cyclic?) dependencies: " . print_r(array_keys($appsToSort), TRUE));
        }
        
        return $result;
    }

    /**
     * sort applications by checking dependencies
     *
     * @param array $_applications
     * @return array
     */
    protected function _sortUninstallableApplications($_applications)
    {
        $result = array();
        
        // get all apps to uninstall ($name => $dependencies)
        $appsToSort = array();
        foreach($_applications as $name => $xml) {
            if ($name !== 'Tinebase') {
                $depends = (array) $xml->depends; 
                if (isset($depends['application'])) {
                    if ($depends['application'] == 'Tinebase') {
                        $appsToSort[$name] = array();
                        
                    } else {
                        $depends['application'] = (array) $depends['application'];
                        
                        foreach ($depends['application'] as $app) {
                            // don't add tinebase (all apps depend on tinebase)
                            if ($app != 'Tinebase') {
                                $appsToSort[$name][] = $app;
                            }
                        }
                    }
                } else {
                    $appsToSort[$name] = array();
                }
            }
        }
        
        // re-sort apps
        $count = 0;
        while (count($appsToSort) > 0 && $count < MAXLOOPCOUNT) {

            foreach($appsToSort as $name => $depends) {
                //Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " - $count $name - " . print_r($depends, true));
                
                // don't uninstall if another app depends on this one
                $otherAppDepends = FALSE;
                foreach($appsToSort as $innerName => $innerDepends) {
                    if(in_array($name, $innerDepends)) {
                        $otherAppDepends = TRUE;
                        break;
                    }
                }
                
                // add it to results
                if (!$otherAppDepends) {
                    $result[$name] = $_applications[$name];
                    unset($appsToSort[$name]);
                }
            }
            $count++;
        }
        
        if ($count == MAXLOOPCOUNT) {
            Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                " Some Applications could not be uninstalled because of (cyclic?) dependencies: " . print_r(array_keys($appsToSort), TRUE));
        }

        // Tinebase is uninstalled last
        if (isset($_applications['Tinebase'])) {
            $result['Tinebase'] = $_applications['Tinebase'];
            unset($_applications['Tinebase']);
        }
        
        return $result;
    }
    
    /**
     * check if an application is installed
     *
     * @param string $appname
     * @return boolean
     */
    public function isInstalled($appname)
    {
        $result = TRUE;
        try {
            $app = Tinebase_Application::getInstance()->getApplicationByName($appname);
        } catch (Exception $e) {
            $result = FALSE;
        }
        
        return $result;
    }
}
