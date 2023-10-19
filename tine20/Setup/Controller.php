<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        move $this->_db calls to backend class
 */

/**
 * php helpers
 */

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'Tinebase' . DIRECTORY_SEPARATOR . 'Helper.php';

/**
 * class to handle setup of Tine 2.0
 *
 * @package     Setup
 * @subpackage  Controller
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
    protected $_backend = NULL;
    
    /**
     * the directory where applications are located
     *
     * @var string
     */
    protected $_baseDir;
    
    /**
     * the email configs to get/set
     *
     * @var array
     */
    protected $_emailConfigKeys = array();
    
    /**
     * number of updated apps
     * 
     * @var integer
     */
    protected $_updatedApplications = 0;

    const MAX_DB_PREFIX_LENGTH = 10;
    const INSTALL_NO_IMPORT_EXPORT_DEFINITIONS = 'noImportExportDefinitions';
    const INSTALL_NO_REPLICATION_SLAVE_CHECK = 'noReplicationSlaveCheck';

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}
    
    /**
     * url to Tine 2.0 wiki
     *
     * @var string
     */
    protected $_helperLink = ' <a href="http://wiki.tine20.org/Admins/Install_Howto" target="_blank">Check the Tine 2.0 wiki for support.</a>';

    /**
     * the temporary super user role
     * @var string
     */
    protected $_superUserRoleName = null;

    /**
     * the singleton pattern
     *
     * @return Setup_Controller
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new Setup_Controller;
        }
        
        return self::$_instance;
    }

    public static function destroyInstance()
    {
        self::$_instance = null;
    }

    /**
     * the constructor
     *
     */
    protected function __construct()
    {
        // setup actions could take quite a while we try to set max execution time to unlimited
        Setup_Core::setExecutionLifeTime(0);
        
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
        
        $this->_emailConfigKeys = array(
            'imap'  => Tinebase_Config::IMAP,
            'smtp'  => Tinebase_Config::SMTP,
            'sieve' => Tinebase_Config::SIEVE,
        );

        // initialize real config if Tinebase is installed
        if ($this->isInstalled('Tinebase') && ! Tinebase_Core::getConfig() instanceof Tinebase_Config_Abstract) {
            // we only have a Zend_Config - check if we can switch to Tinebase_Config
            Tinebase_Core::setupConfig();
        }
    }

    /**
     * check system/php requirements (env + ext check)
     *
     * @return array
     */
    public function checkRequirements(): array
    {
        $envCheck = $this->environmentCheck();
        
        $databaseCheck = $this->checkDatabase();
        
        $extCheck = new Setup_ExtCheck(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'essentials.xml');
        $extResult = $extCheck->getData();
        
        $optionalBinaries = $this->checkoptionalBinaries();

        $result = array(
            'success' => ($envCheck['success'] && $databaseCheck['success'] && $extResult['success']),
            'results' => array_merge($envCheck['result'], $databaseCheck['result'], $extResult['result'], $optionalBinaries['result']),
            'resultOptionalBinaries' => $optionalBinaries,
        );

        $result['totalcount'] = count($result['results']);
        
        return $result;
    }

    /**
     * check which optional binaries are available
     * 
     * @return array
     */
    public function checkOptionalBinaries()
    {
        
        $result = array(
            'result' => array(),
            'success' => false
        );
        
        $tnef = Tinebase_Core::systemCommandExists('tnef') ? 'tnef ' : '' ;
        $ytnef = Tinebase_Core::systemCommandExists('ytnef') ? 'ytnef ' : '' ;
        $tika = Tinebase_Config::getInstance()->{Tinebase_Config::FULLTEXT}->{Tinebase_Config::FULLTEXT_TIKAJAR}? 'tika' : '';

        if ( (empty($tnef) && empty($ytnef)) || empty($tika)) {
            $result['result'][] = array(
                'key' => 'OptionalBinaries',
                'value' => FALSE,
                'message' => 'The following optional binaries are missing: ' .
                (empty($tnef) && empty($ytnef) ? "tnef or ytnef" : "") . " " .
                (empty($tika) ? "tika" : "")  
            );
            return $result;
        }
        
        $result['result'][] = array(
            'key' => 'OptionalBinaries',
            'value' => TRUE,
            'message' => 'The following optional binaries are available: ' . 
            $tnef . $ytnef . $tika
        );
        return $result;    
    }
    
    /**
     * check which database extensions are available
     *
     * @return array
     */
    public function checkDatabase()
    {
        $result = array(
            'result'  => array(),
            'success' => false
        );
        
        $loadedExtensions = get_loaded_extensions();
        
        if (! in_array('PDO', $loadedExtensions)) {
            $result['result'][] = array(
                'key'       => 'Database',
                'value'     => FALSE,
                'message'   => "PDO extension not found."  . $this->_helperLink
            );
            
            return $result;
        }
        
        // check mysql requirements
        $missingMysqlExtensions = array_diff(array('pdo_mysql'), $loadedExtensions);
        
        // check pgsql requirements
        $missingPgsqlExtensions = array_diff(array('pgsql', 'pdo_pgsql'), $loadedExtensions);
        
        // check oracle requirements
        $missingOracleExtensions = array_diff(array('oci8'), $loadedExtensions);

        if (! empty($missingMysqlExtensions) && ! empty($missingPgsqlExtensions) && ! empty($missingOracleExtensions)) {
            $result['result'][] = array(
                'key'       => 'Database',
                'value'     => FALSE,
                'message'   => 'Database extensions missing. For MySQL install: ' . implode(', ', $missingMysqlExtensions) . 
                               ' For Oracle install: ' . implode(', ', $missingOracleExtensions) . 
                               ' For PostgreSQL install: ' . implode(', ', $missingPgsqlExtensions) .
                               $this->_helperLink
            );
            
            return $result;
        }
        
        $result['result'][] = array(
            'key'       => 'Database',
            'value'     => TRUE,
            'message'   => 'Support for following databases enabled: ' . 
                           (empty($missingMysqlExtensions) ? 'MySQL' : '') . ' ' .
                           (empty($missingOracleExtensions) ? 'Oracle' : '') . ' ' .
                           (empty($missingPgsqlExtensions) ? 'PostgreSQL' : '') . ' '
        );
        $result['success'] = TRUE;
        
        return $result;
    }
    
    /**
     * Check if tableprefix is longer than 6 charcters
     *
     * @return boolean
     */
    public function checkDatabasePrefix()
    {
        $config = Setup_Core::get(Setup_Core::CONFIG);
        if (isset($config->database->tableprefix) && strlen($config->database->tableprefix) > self::MAX_DB_PREFIX_LENGTH) {
            if (Setup_Core::isLogLevel(Zend_Log::ERR)) Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Tableprefix: "' . $config->database->tableprefix . '" is longer than ' . self::MAX_DB_PREFIX_LENGTH
                . '  characters! Please check your configuration.');
            return false;
        }
        return true;
    }
    
    /**
     * Check if logger is properly configured (or not configured at all)
     *
     * @return boolean
     */
    public function checkConfigLogger()
    {
        $config = Setup_Core::get(Setup_Core::CONFIG);
        if (!isset($config->logger) || !$config->logger->active) {
            return true;
        } else {
            return (
                isset($config->logger->filename)
                && (
                    file_exists($config->logger->filename) && is_writable($config->logger->filename)
                    || is_writable(dirname($config->logger->filename))
                )
            );
        }
    }
    
    /**
     * Check if caching is properly configured (or not configured at all)
     *
     * @return boolean
     */
    public function checkConfigCaching()
    {
        $result = false;
        
        $config = Setup_Core::get(Setup_Core::CONFIG);
        
        if (! isset($config->caching) || !$config->caching->active) {
            $result = true;
            
        } else if (! isset($config->caching->backend) || ucfirst($config->caching->backend) === 'File') {
            $result = $this->checkDir('path', 'caching', false);
            
        } else if (ucfirst($config->caching->backend) === 'Redis') {
            try {
                $result = $this->_checkRedisConnect(isset($config->caching->redis) ? $config->caching->redis->toArray() : array());
            } catch (RedisException $re) {
                Tinebase_Exception::log($re);
                $result = false;
            }
            
        } else if (ucfirst($config->caching->backend) === 'Memcached') {
            $result = $this->_checkMemcacheConnect(isset($config->caching->memcached) ? $config->caching->memcached->toArray() : array());
            
        }
        
        return $result;
    }
    
    /**
     * checks redis extension and connection
     * 
     * @param array $config
     * @return boolean
     */
    protected function _checkRedisConnect($config)
    {
        if (! extension_loaded('redis')) {
            Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' redis extension not loaded');
            return FALSE;
        }
        $redis = new Redis;
        $host = isset($config['host']) ? $config['host'] : 'localhost';
        $port = isset($config['port']) ? $config['port'] : 6379;
        
        $result = $redis->connect($host, $port);
        if ($result) {
            $redis->close();
        } else {
            Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not connect to redis server at ' . $host . ':' . $port);
        }
        
        return $result;
    }
    
    /**
     * checks memcached extension and connection
     * 
     * @param array $config
     * @return boolean
     */
    protected function _checkMemcacheConnect($config)
    {
        if (! extension_loaded('memcache')) {
            Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' memcache extension not loaded');
            return FALSE;
        }
        $memcache = new Memcache;
        $host = isset($config['host']) ? $config['host'] : 'localhost';
        $port = isset($config['port']) ? $config['port'] : 11211;
        $result = $memcache->connect($host, $port);
        
        return $result;
    }
    
    /**
     * Check if queue is properly configured (or not configured at all)
     *
     * @return boolean
     */
    public function checkConfigQueue()
    {
        $config = Setup_Core::get(Setup_Core::CONFIG);
        if (! isset($config->actionqueue) || ! $config->actionqueue->active) {
            $result = TRUE;
        } else {
            $result = $this->_checkRedisConnect($config->actionqueue->toArray());
        }
        
        return $result;
    }
    
    /**
     * check config session
     * 
     * @return boolean
     */
    public function checkConfigSession()
    {
        $result = FALSE;
        $config = Setup_Core::get(Setup_Core::CONFIG);
        if (! isset($config->session) || !$config->session->active) {
            return TRUE;
        } else if (ucfirst($config->session->backend) === 'File') {
            return $this->checkDir('path', 'session', FALSE);
        } else if (ucfirst($config->session->backend) === 'Redis') {
            $result = $this->_checkRedisConnect($config->session->toArray());
        }
        
        return $result;
    }
    
    /**
     * checks if path in config is writable
     *
     * @param string $_name
     * @param string $_group
     * @return boolean
     */
    public function checkDir($_name, $_group = NULL, $allowEmptyPath = TRUE)
    {
        $config = $this->getConfigData();
        if ($_group !== NULL && (isset($config[$_group]) || array_key_exists($_group, $config))) {
            $config = $config[$_group];
        }
        
        $path = (isset($config[$_name]) || array_key_exists($_name, $config)) ? $config[$_name] : false;
        if (empty($path)) {
            return $allowEmptyPath;
        } else {
            return @is_writable($path);
        }
    }
    
    /**
     * get list of applications as found in the filesystem
     *
     * @param boolean $getInstalled applications, too
     * @return array appName => setupXML
     */
    public function getInstallableApplications($getInstalled = false)
    {
        // create Tinebase tables first
        $applications = $getInstalled || ! $this->isInstalled('Tinebase')
            ? array('Tinebase' => $this->getSetupXml('Tinebase'))
            : array();
        
        try {
            $dirIterator = new DirectoryIterator($this->_baseDir);
        } catch (Exception $e) {
            Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not open base dir: ' . $this->_baseDir);
            throw new Tinebase_Exception_AccessDenied('Could not open Tine 2.0 root directory.');
        }
        
        foreach ($dirIterator as $item) {
            $appName = $item->getFileName();
            if ($appName[0] != '.' && $appName != 'Tinebase' && $item->isDir()) {
                $fileName = $this->_baseDir . $appName . '/Setup/setup.xml' ;
                if (file_exists($fileName) && ($getInstalled || ! $this->isInstalled($appName))) {
                    $applications[$appName] = $this->getSetupXml($appName);
                }
            }
        }
        
        return $applications;
    }

    protected function _getUpdatesByPrio(&$applicationCount)
    {
        $applicationController = Tinebase_Application::getInstance();

        $updatesByPrio = [];
        $maxMajorV = Tinebase_Config::TINEBASE_VERSION;

        if (Setup_Core::isLogLevel(Zend_Log::DEBUG)) Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Checking for updates up to major version: ' . $maxMajorV);

        /** @var Tinebase_Model_Application $application */
        foreach ($applicationController->getApplications() as $application) {

            $stateUpdates = json_decode($applicationController->getApplicationState($application,
                Tinebase_Application::STATE_UPDATES, true) ?? '', true);

            for ($majorV = 0; $majorV <= $maxMajorV; ++$majorV) {
                /** @var Setup_Update_Abstract $class */
                $class = $application->name . '_Setup_Update_' . $majorV;
                if (class_exists($class)) {
                    $updates = $class::getAllUpdates();
                    $allUpdates = [];
                    foreach ($updates as $prio => $byPrio) {
                        foreach ($byPrio as &$update) {
                            $update['prio'] = $prio;
                        }
                        unset($update);
                        $allUpdates += $byPrio;
                    }

                    if (is_array($stateUpdates) && count($stateUpdates) > 0) {
                        $allUpdates = array_diff_key($allUpdates, $stateUpdates);
                    }
                    if (!empty($allUpdates)) {
                        ++$applicationCount;
                    }
                    foreach ($allUpdates as $update) {
                        if (!isset($updatesByPrio[$update['prio']])) {
                            $updatesByPrio[$update['prio']] = [];
                        }
                        $updatesByPrio[$update['prio']][] = $update;
                    }
                }
            }
        }

        if (Setup_Core::isLogLevel(Zend_Log::DEBUG)) Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Got updates: ' . print_r($updatesByPrio, true));

        return $updatesByPrio;
    }

    protected function preUpdateHooks()
    {
        // put any db struct updates here that need to be executed before the update stuff runs ... like changes to
        // user / group table (as the setup user might get created!)
        // application / application_state tables
        // NOTE: this function, this code here will be executed every time somebody does update

    }

    /**
     * updates installed applications. does nothing if no applications are installed
     *
     * applications is legacy, we always update all installed applications
     *
     * @param Tinebase_Record_RecordSet $_applications
     * @param array $options
     * @return  array   messages
     * @throws Tinebase_Exception
     *
     * TODO refactor signature ... we dont want that? do we? always all...
     */
    public function updateApplications(Tinebase_Record_RecordSet $_applications = null, array $options = [
        'strict' => false,
        'skipQueueCheck' => false,
        'rerun' => [],
    ])
    {
        $this->clearCache();

        if (! empty($options['rerun'])) {
            $this->_removeUpdatesFromAppState($options['rerun']);
        }

        $this->preUpdateHooks();

        if (null === ($user = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly())) {
            throw new Tinebase_Exception('could not create setup user');
        }
        Tinebase_Core::set(Tinebase_Core::USER, $user);

        $result = [
            'updated' => 0,
            'updates' => [],
        ];
        $iterationCount = 0;
        do {
            $updatesByPrio = $this->_getUpdatesByPrio($result['updated']);
            if (empty($updatesByPrio) && $iterationCount > 0) {
                break;
            }

            if (!isset($updatesByPrio[Setup_Update_Abstract::PRIO_TINEBASE_AFTER_STRUCTURE])) {
                $updatesByPrio[Setup_Update_Abstract::PRIO_TINEBASE_AFTER_STRUCTURE] = [];
            }
            array_unshift($updatesByPrio[Setup_Update_Abstract::PRIO_TINEBASE_AFTER_STRUCTURE], [
                Setup_Update_Abstract::CLASS_CONST      => self::class,
                Setup_Update_Abstract::FUNCTION_CONST   => 'updateAllImportExportDefinitions',
            ]);

            ksort($updatesByPrio, SORT_NUMERIC);
            $db = Setup_Core::getDb();
            $classes = [self::class => $this];

            try {
                $this->_prepareUpdate(Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly(), $options['skipQueueCheck']);

                foreach ($updatesByPrio as $prio => $updates) {
                    foreach ($updates as $update) {
                        $className = $update[Setup_Update_Abstract::CLASS_CONST];
                        $functionName = $update[Setup_Update_Abstract::FUNCTION_CONST];
                        if (!isset($classes[$className])) {
                            $classes[$className] = new $className($this->_backend);
                            $result['updates'][] = $className;
                        }
                        $class = $classes[$className];

                        try {
                            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

                            Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                                . ' Updating ' . $className . '::' . $functionName
                            );

                            $class->$functionName();

                            if (Tinebase_TransactionManager::getInstance()->hasOpenTransactions()) {
                                try {
                                    Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                                } catch (PDOException $pe) {
                                    Tinebase_TransactionManager::getInstance()->resetTransactions();
                                    Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $pe->getMessage());
                                }
                            } else if (Setup_Core::isLogLevel(Zend_Log::NOTICE)) {
                                Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                                    . ' Update ' . $className . '::' . $functionName . ' already closed the transaction');
                            }

                        } catch (Exception $e) {
                            try {
                                Tinebase_TransactionManager::getInstance()->rollBack();
                            } catch (PDOException $pe) {
                                Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Got PDOException: ' . $pe->getMessage());
                            }
                            Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                            Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                            throw $e;
                        }
                    }
                }

                if (Setup_SchemaTool::hasSchemaUpdates()) {
                    Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ .
                        ' pending schema updates found, this should not happen!');
                    if ($options['strict']) {
                        throw new Setup_Backend_Exception_NotImplemented('missing schema updates in update scripts');
                    }
                    Setup_SchemaTool::updateAllSchema();
                }

            } finally {
                $this->_cleanUpUpdate();
            }
        } while (++$iterationCount < 5);

        $this->clearCache();
        
        return $result;
    }

    protected function _removeUpdatesFromAppState(array $updates)
    {
        foreach ($updates as $update) {
            // update string expected in this form: UserManual_Setup_Update_15::update001
            if (preg_match('/([a-z0-9]+)_/i', $update, $matches)) {
                $appName = $matches[1];
                if (Tinebase_Application::getInstance()->isInstalled($appName)) {
                    $app = Tinebase_Application::getInstance()->getApplicationByName($appName);
                    $state = Tinebase_Helper::jsonDecode(Tinebase_Application::getInstance()->getApplicationState(
                        $app, Tinebase_Application::STATE_UPDATES, true));
                    if (isset($state[$update])) {
                        unset($state[$update]);
                        Tinebase_Application::getInstance()->setApplicationState(
                            $app, Tinebase_Application::STATE_UPDATES, json_encode($state));
                    }
                }
            }
        }
    }

    public function updateAllImportExportDefinitions()
    {
        /** @var Tinebase_Model_Application $application */
        foreach (Tinebase_Application::getInstance()->getApplications()->filter('status', Tinebase_Application::ENABLED)
                as $application) {
            $this->createImportExportDefinitions($application, Tinebase_Core::isReplicationSlave());
        }
    }

    /**
     * load the setup.xml file and returns a simplexml object
     *
     * @param string $_applicationName name of the application
     * @param boolean $_disableAppIfNotFound
     * @return SimpleXMLElement|null
     * @throws Setup_Exception_NotFound
     */
    public function getSetupXml($_applicationName, $_disableAppIfNotFound = false)
    {
        $setupXML = $this->_baseDir . ucfirst($_applicationName) . '/Setup/setup.xml';

        if (! file_exists($setupXML)) {
            if ($_disableAppIfNotFound) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $setupXML
                    . ' not found - disabling application "' . $_applicationName . '".');
                $application = Tinebase_Application::getInstance()->getApplicationByName($_applicationName);
                Tinebase_Application::getInstance()->setApplicationStatus(
                    array($application->getId()),
                    Tinebase_Application::DISABLED);
                return null;
            } else {
                throw new Setup_Exception_NotFound($setupXML . ' not found. If application got renamed or deleted, re-run setup.php.');
            }
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
        $xmlTables = $this->getSetupXml($_application->name, true);
        if ($xmlTables && isset($xmlTables->tables)) {
            foreach ($xmlTables->tables[0] as $tableXML) {
                $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableXML);
                if (true == $this->_backend->tableExists($table->name)) {
                    try {
                        $this->_backend->checkTable($table);
                    } catch (Setup_Exception $e) {
                        Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " Checking table failed with message '{$e->getMessage()}'");
                    }
                } else {
                    throw new Setup_Exception('Table ' . $table->name . ' for application' . $_application->name . " does not exist. \n<strong>Update broken</strong>");
                }
            }
        }
    }
    
    /**
     * prepare update
     *
     * - check minimal required version is installed
     * - checks/disables action queue
     * - creates superuser role for setupuser
     *
     * @see 0013414: update scripts should work without dedicated setupuser
     * @param Tinebase_Model_User $_user
     * @throws Tinebase_Exception
     */
    protected function _prepareUpdate(Tinebase_Model_User $_user, $skipQueueCheck = false)
    {
        $setupXml = $this->getSetupXml('Tinebase');
        if(!empty($setupXml->minimumRequiredVersion) &&
            version_compare(Setup_Update_Abstract::getAppVersion('Tinebase'), $setupXml->minimumRequiredVersion) < 0 ) {
            throw new Tinebase_Exception('Major version jumps are not allowed. Upgrade your current major Version ' .
                'to the most recent minor Version, then upgrade to the most recent next major version. Repeat until ' .
                'you reached the desired major version you want to upgrade to');
        }

        if (! $skipQueueCheck) {
            $this->_checkActionQueue();
        }

        // set action to direct
        Tinebase_ActionQueue::getInstance(null, Tinebase_ActionQueue::BACKEND_DIRECT);
        Tinebase_ActionQueue::getInstance(Tinebase_ActionQueue::QUEUE_LONG_RUN, Tinebase_ActionQueue::BACKEND_DIRECT);

        $roleController = Tinebase_Acl_Roles::getInstance();
        $applicationController = Tinebase_Application::getInstance();
        $oldModLog = $roleController->modlogActive(false);
        Tinebase_Model_User::forceSuperUser();

        $useNotes = $roleController->useNotes(false);
        try {
            Tinebase_Model_Role::setIsReplicable(false);

            $toDelete = [];
            foreach ($roleController->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    Tinebase_Model_Role::class, [
                        ['field' => 'name', 'operator' => 'startswith', 'value' => 'superUser']
                    ])) as $role) {
                if (strlen($role->name) === strlen('superUser') + 40) {
                    $toDelete[] = $role->getId();
                }
            }
            if (!emptY($toDelete)) {
                $roleController->delete($toDelete);
            }

            $this->_superUserRoleName = 'superUser' . Tinebase_Record_Abstract::generateUID();
            $superUserRole = new Tinebase_Model_Role(array(
                'name' => $this->_superUserRoleName
            ));
            $rights = array();

            /** @var Tinebase_Model_Application $application */
            foreach ($applicationController->getApplications() as $application) {
                $appId = $application->getId();
                foreach ($applicationController->getAllRights($appId) as $right) {
                    $rights[] = array(
                        'application_id' => $appId,
                        'right' => $right,
                    );
                }
            }

            $roleController->create($superUserRole);
            $roleController->setRoleRights($superUserRole->getId(), $rights);
            $roleController->setRoleMemberships(array(
                'type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'id' => $_user->getId()
            ), [$superUserRole->getId()]);
        } finally {
            Tinebase_Model_Role::setIsReplicable();
            $roleController->modlogActive($oldModLog);
            $roleController->useNotes($useNotes);
        }
    }

    protected function _checkActionQueue()
    {
        // check action queue is empty and wait for it to finish
        $timeStart = time();
        foreach (Tinebase_ActionQueue::getAllInstances() as $actionQueue) {
            while ($actionQueue->getQueueSize() > 0 && time() - $timeStart < 300) {
                usleep(10000);
            }
            if (time() - $timeStart >= 300) {
                throw new Tinebase_Exception('waited for Action Queue to become empty for more than 300 sec');
            }
        }
    }

    /**
     * cleanup after update
     *
     * - removes setupuser superuser role
     * - re-enables action queue
     */
    protected function _cleanUpUpdate()
    {
        $roleController = Tinebase_Acl_Roles::getInstance();
        $oldModLog = $roleController->modlogActive(false);
        try {
            Tinebase_Model_Role::setIsReplicable(false);
            if (null !== $this->_superUserRoleName) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . ' Removing superuser role ' . $this->_superUserRoleName);
                // TODO: check: will the role membership be deleted? How? DB constraint?
                $roleController->delete($roleController->getRoleByName($this->_superUserRoleName));
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Superuser role ' . $this->_superUserRoleName
                . ' not found - skipping deletion');
        } finally {
            Tinebase_Model_Role::setIsReplicable(true);
            $roleController->modlogActive($oldModLog);
            Tinebase_Model_User::forceSuperUser(false);
            $this->_superUserRoleName = null;
        }
    }

    /**
     * checks if update is required
     *
     * TODO remove $_application parameter and legacy code
     *
     * @param Tinebase_Model_Application $_application
     * @return boolean
     */
    public function updateNeeded($_application = null)
    {
        $setupBackend = Setup_Backend_Factory::factory();
        if (!$setupBackend->supports('mysql >= 5.7.5 | mariadb >= 10.2')) {
            throw new Tinebase_Exception_Backend('mysql >= 5.7.5 | mariadb >= 10.2 required');
        }

        if (null === $_application) {
            $count = 0;
            $this->_getUpdatesByPrio($count);
            return $count > 0;
        }

        // TODO remove legacy code below
        $setupXml = $this->getSetupXml($_application->name, true);
        if (! $setupXml) {
            return false;
        }

        $updateNeeded = version_compare($_application->version, $setupXml->version);
        
        if($updateNeeded === -1) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' updates required');
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
        $installable = $this->getInstallableApplications(/* $getInstalled */ true);
        $applications = array();
        // get installed apps
        if (Setup_Core::get(Setup_Core::CHECKDB)) {
            try {
                $installed = Tinebase_Application::getInstance()->getApplications(NULL, 'id')->toArray();
                
                // merge to create result array
                foreach ($installed as $application) {
                    
                    if (! (isset($installable[$application['name']]) || array_key_exists($application['name'], $installable))) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' App ' . $application['name'] . ' does not exist any more.');
                        continue;
                    }
                    
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
                if (empty($applicationTable)) {
                    Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Applications table empty');
                    $result = TRUE;
                }
            } catch (Zend_Db_Statement_Exception $zdse) {
                Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $zdse->getMessage());
                $result = TRUE;
            } catch (Zend_Db_Adapter_Exception $zdae) {
                Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $zdae->getMessage());
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
        $success = TRUE;



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
                $required = Tinebase_Helper::convertToBytes($newValue);
                $set = Tinebase_Helper::convertToBytes($oldValue);
                
                if ($set > -1 && $set < $required) {
                    $result[] = array(
                        'key'       => $variable,
                        'value'     => FALSE,
                        'message'   => "You need to set $variable equal or greater than $required (now: $set)." . $this->_helperLink
                    );
                    $success = FALSE;
                }

            } elseif ($oldValue != $newValue) {
                if (ini_set($variable, $newValue) === false) {
                    $result[] = array(
                        'key'       => $variable,
                        'value'     => FALSE,
                        'message'   => "You need to set $variable from $oldValue to $newValue."  . $this->_helperLink
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
                'priority' => '5'
            ),
            'caching' => array(
               'active' => 1,
               'lifetime' => 3600,
               'backend' => 'File',
               'path' => $defaultPath,
            ),
            'tmpdir' => $defaultPath,
            'session' => array(
                'path'      => Tinebase_Session::getSessionDir(),
                'liftime'   => 86400,
            ),
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
        $config = Setup_Core::get(Setup_Core::CONFIG);
        if ($config instanceof Tinebase_Config_Abstract) {
            $configArray = $config->getConfigFileData();
        } else {
            $configArray = $config->toArray();
        }
        
        #####################################
        # LEGACY/COMPATIBILITY:
        # (1) had to rename session.save_path key to sessiondir because otherwise the
        # generic save config method would interpret the "_" as array key/value seperator
        # (2) moved session config to subgroup 'session'
        if (empty($configArray['session']) || empty($configArray['session']['path'])) {
            foreach (array('session.save_path', 'sessiondir') as $deprecatedSessionDir) {
                $sessionDir = (isset($configArray[$deprecatedSessionDir]) || array_key_exists($deprecatedSessionDir, $configArray)) ? $configArray[$deprecatedSessionDir] : '';
                if (! empty($sessionDir)) {
                    if (empty($configArray['session'])) {
                        $configArray['session'] = array();
                    }
                    $configArray['session']['path'] = $sessionDir;
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " config.inc.php key '{$deprecatedSessionDir}' should be renamed to 'path' and moved to 'session' group.");
                }
            }
        }
        #####################################
        
        return $configArray;
    }
    
    /**
     * save data to config file
     *
     * @param array   $_data
     * @param boolean $_merge
     */
    public function saveConfigData($_data, $_merge = TRUE)
    {
        if (!empty($_data['setupuser']['password']) && !Setup_Auth::isMd5($_data['setupuser']['password'])) {
            $password = $_data['setupuser']['password'];
            $_data['setupuser']['password'] = md5($_data['setupuser']['password']);
        }
        if (Setup_Core::configFileExists() && !Setup_Core::configFileWritable()) {
            throw new Setup_Exception('Config File is not writeable.');
        }
        
        if (Setup_Core::configFileExists()) {
            $doLogin = FALSE;
            $filename = Setup_Core::getConfigFilePath();
        } else {
            $doLogin = TRUE;
            $filename = dirname(__FILE__) . '/../config.inc.php';
        }
        
        $config = $this->writeConfigToFile($_data, $_merge, $filename);
        
        Setup_Core::set(Setup_Core::CONFIG, $config);
        
        Setup_Core::setupLogger();
        
        if ($doLogin && isset($password)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Create session for setup user ' . $_data['setupuser']['username']);
            $this->login($_data['setupuser']['username'], $password);
        }
    }
    
    /**
     * write config to a file
     *
     * @param array $_data
     * @param boolean $_merge
     * @param string $_filename
     * @return Zend_Config
     */
    public function writeConfigToFile($_data, $_merge, $_filename)
    {
        // merge config data and active config
        if ($_merge) {
            $activeConfig = Setup_Core::get(Setup_Core::CONFIG);
            $configArray = $activeConfig instanceof Tinebase_Config_Abstract
                ? $activeConfig->getConfigFileData()
                : $activeConfig->toArray();
            $config = new Zend_Config($configArray, true);
            $config->merge(new Zend_Config($_data));
        } else {
            $config = new Zend_Config($_data);
        }
        
        // write to file
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating config.inc.php');
        $writer = new Zend_Config_Writer_Array(array(
            'config'   => $config,
            'filename' => $_filename,
        ));
        $writer->write();
        
        return $config;
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
            'redirectSettings'  => $this->_getRedirectSettings(),
            'password'          => $this->_getPasswordSettings(),
            'saveusername'      => $this->_getReuseUsernameSettings()
        );
    }
    
    /**
     * Update authentication data
     *
     * Needs Tinebase tables to store the data, therefore
     * installs Tinebase if it is not already installed
     *
     * @param array $_authenticationData
     */
    public function saveAuthentication($_authenticationData)
    {
        if ($this->isInstalled('Tinebase')) {
            // NOTE: Tinebase_Setup_Initialize calls this function again so
            //       we come to this point on initial installation _and_ update
            $this->_updateAuthentication($_authenticationData);
        } else {
            $installationOptions = array('authenticationData' => $_authenticationData);
            $this->installApplications(array('Tinebase'), $installationOptions);
        }
    }

    /**
     * Save {@param $_authenticationData} to config file
     *
     * @param array $_authenticationData [hash containing settings for authentication and accountsStorage]
     * @return void
     */
    protected function _updateAuthentication($_authenticationData)
    {
        $this->_enableCaching();
        
        if (isset($_authenticationData['authentication'])) {
            $this->_updateAuthenticationProvider($_authenticationData['authentication']);
        }
        
        if (isset($_authenticationData['accounts'])) {
            $this->_updateAccountsStorage($_authenticationData['accounts']);
        }
        
        if (isset($_authenticationData['redirectSettings'])) {
            $this->_updateRedirectSettings($_authenticationData['redirectSettings']);
        }
        
        if (isset($_authenticationData['password'])) {
            $this->_updatePasswordSettings($_authenticationData['password']);
        }
        
        if (isset($_authenticationData['saveusername'])) {
            $this->_updateReuseUsername($_authenticationData['saveusername']);
        }
        
        if (isset($_authenticationData['acceptedTermsVersion'])) {
            $this->saveAcceptedTerms($_authenticationData['acceptedTermsVersion']);
        }
    }
    
    /**
     * enable caching to make sure cache gets cleaned if config options change
     */
    protected function _enableCaching()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Activate caching backend if available ...');
        
        Tinebase_Core::setupCache();
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
        $config = (isset($_data[$_data['backend']])) ? $_data[$_data['backend']] : $_data;
        
        $excludeKeys = array('adminLoginName', 'adminPassword', 'adminPasswordConfirmation');
        foreach ($excludeKeys as $key) {
            if ((isset($config[$key]) || array_key_exists($key, $config))) {
                unset($config[$key]);
            }
        }
        
        Tinebase_Auth::setBackendConfiguration($config, null, true);
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
        $config = (isset($_data[$_data['backend']])) ? $_data[$_data['backend']] : $_data;
        Tinebase_User::setBackendConfiguration($config, null, true);
        Tinebase_User::saveBackendConfiguration();
        
        if ($originalBackend != $newBackend && $this->isInstalled('Addressbook') && $originalBackend == Tinebase_User::SQL) {
            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Switching from $originalBackend to $newBackend account storage");
            try {
                $db = Setup_Core::getDb();
                $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
                $this->_migrateFromSqlAccountsStorage();
                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
            } catch (Exception $e) {
                Tinebase_TransactionManager::getInstance()->rollBack();
                Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                
                Tinebase_User::setBackendType($originalBackend);
                Tinebase_User::saveBackendConfiguration();
                
                throw $e;
            }
        }
    }
    
    /**
     * migrate from SQL account storage to another one (for example LDAP)
     * - deletes all users, groups and roles because they will be
     *   imported from new accounts storage backend
     */
    protected function _migrateFromSqlAccountsStorage()
    {
        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Deleting all user accounts, groups, roles and rights');
        Tinebase_User::factory(Tinebase_User::SQL)->deleteAllUsers();
        
        $contactSQLBackend = new Addressbook_Backend_Sql();
        $allUserContactIds = $contactSQLBackend->search(new Addressbook_Model_ContactFilter(array('type' => 'user')), null, true);
        if (count($allUserContactIds) > 0) {
            $contactSQLBackend->delete($allUserContactIds);
        }

        Tinebase_Group::factory(Tinebase_Group::SQL)->deleteAllGroups();
        $listsSQLBackend = new Addressbook_Backend_List();
        $allGroupListIds = $listsSQLBackend->search(new Addressbook_Model_ListFilter(array('type' => 'group')), null, true);
        if (count($allGroupListIds) > 0) {
            $listsSQLBackend->delete($allGroupListIds);
        }

        $roles = Tinebase_Acl_Roles::getInstance();
        $roles->deleteAllRoles();
        
        // import users (from new backend) / create initial users (SQL)
        Tinebase_User::syncUsers(array('syncContactData' => TRUE));
        
        $roles->createInitialRoles();
        $applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
        foreach ($applications as $application) {
             Setup_Initialize::initializeApplicationRights($application);
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
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' ' . print_r($_data, 1));
        $keys = array(Tinebase_Config::REDIRECTURL, Tinebase_Config::REDIRECTALWAYS, Tinebase_Config::REDIRECTTOREFERRER);
        foreach ($keys as $key) {
            if ((isset($_data[$key]) || array_key_exists($key, $_data))) {
                if (strlen($_data[$key]) === 0) {
                    Tinebase_Config::getInstance()->delete($key);
                } else {
                    Tinebase_Config::getInstance()->set($key, $_data[$key]);
                }
            }
        }
    }

    /**
     * update pw settings
     * 
     * @param array $data
     */
    protected function _updatePasswordSettings($data)
    {
        foreach ($data as $config => $value) {
            Tinebase_Config::getInstance()->get(Tinebase_Config::USER_PASSWORD_POLICY)->{$config} = $value;
        }
    }
    
    /**
     * update pw settings
     * 
     * @param array $data
     */
    protected function _updateReuseUsername($data)
    {
        foreach ($data as $config => $value) {
            Tinebase_Config::getInstance()->set($config, $value);
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
        $result = Tinebase_Auth::getBackendConfigurationWithDefaults(Setup_Core::get(Setup_Core::CHECKDB));
        $result['backend'] = (Setup_Core::get(Setup_Core::CHECKDB)) ? Tinebase_Auth::getConfiguredBackend() : Tinebase_Auth::SQL;

        return $result;
    }
    
    /**
     * get Accounts storage data
     *
     * @return array
     */
    protected function _getAccountsStorageData()
    {
        $result = Tinebase_User::getBackendConfigurationWithDefaults(Setup_Core::get(Setup_Core::CHECKDB));
        $result['backend'] = (Setup_Core::get(Setup_Core::CHECKDB)) ? Tinebase_User::getConfiguredBackend() : Tinebase_User::SQL;

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
              Tinebase_Config::REDIRECTURL => '',
              Tinebase_Config::REDIRECTTOREFERRER => '0'
        );
        if (Setup_Core::get(Setup_Core::CHECKDB) && $this->isInstalled('Tinebase')) {
            $return[Tinebase_Config::REDIRECTURL] = Tinebase_Config::getInstance()->get(Tinebase_Config::REDIRECTURL, '');
            $return[Tinebase_Config::REDIRECTTOREFERRER] = Tinebase_Config::getInstance()->get(Tinebase_Config::REDIRECTTOREFERRER, '');
        }
        return $return;
    }

    /**
     * get password settings
     * 
     * @return array
     * 
     * @todo should use generic mechanism to fetch setup related configs
     */
    protected function _getPasswordSettings()
    {
        $configs = array(
            Tinebase_Config::PASSWORD_CHANGE                     => 1,
            Tinebase_Config::PASSWORD_MANDATORY                  => 0,
            Tinebase_Config::PASSWORD_POLICY_ACTIVE              => 0,
            Tinebase_Config::PASSWORD_POLICY_ONLYASCII           => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_LENGTH          => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_WORD_CHARS      => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_UPPERCASE_CHARS => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_SPECIAL_CHARS   => 0,
            Tinebase_Config::PASSWORD_POLICY_MIN_NUMBERS         => 0,
            Tinebase_Config::PASSWORD_POLICY_CHANGE_AFTER        => 0,
            Tinebase_Config::PASSWORD_POLICY_FORBID_USERNAME     => 0,
        );

        $result = array();
        $tinebaseInstalled = $this->isInstalled('Tinebase');
        foreach ($configs as $config => $default) {
            if ($tinebaseInstalled) {
                $result[$config] = ($config === Tinebase_Config::PASSWORD_CHANGE)
                    ? Tinebase_Config::getInstance()->get($config)
                    : Tinebase_Config::getInstance()->get(Tinebase_Config::USER_PASSWORD_POLICY)->{$config};
            } else {
                $result[$config] = $default;
            }
        }
        
        return $result;
    }
    
    /**
     * get Reuse Username to login textbox
     * 
     * @return array
     * 
     * @todo should use generic mechanism to fetch setup related configs
     */
    protected function _getReuseUsernameSettings()
    {
        $configs = array(
            Tinebase_Config::REUSEUSERNAME_SAVEUSERNAME         => 0,
        );

        $result = array();
        $tinebaseInstalled = $this->isInstalled('Tinebase');
        foreach ($configs as $config => $default) {
            $result[$config] = ($tinebaseInstalled) ? Tinebase_Config::getInstance()->get($config, $default) : $default;
        }
        
        return $result;
    }
    
    /**
     * get email config
     *
     * @return array
     */
    public function getEmailConfig()
    {
        $result = array();
        
        foreach ($this->_emailConfigKeys as $configName => $configKey) {
            $config = Tinebase_Config::getInstance()->get($configKey, new Tinebase_Config_Struct(array()))->toArray();
            if (! empty($config) && ! isset($config['active'])) {
                $config['active'] = TRUE;
            }
            $result[$configName] = $config;
        }
        
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
        // this is a dangerous TRACE as there might be passwords in here!
        //if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_data, TRUE));
        
        $this->_enableCaching();
        
        foreach ($this->_emailConfigKeys as $configName => $configKey) {
            if ((isset($_data[$configName]) || array_key_exists($configName, $_data))) {
                // fetch current config first and preserve all values that aren't in $_data array
                $currentConfig = Tinebase_Config::getInstance()->get($configKey, new Tinebase_Config_Struct(array()))->toArray();
                $newConfig = array_merge($_data[$configName], array_diff_key($currentConfig, $_data[$configName]));
                Tinebase_Config::getInstance()->set($configKey, $newConfig);
            }
        }
    }
    
    /**
     * returns all email config keys
     *
     * @return array
     */
    public function getEmailConfigKeys()
    {
        return $this->_emailConfigKeys;
    }
    
    /**
     * get accepted terms config
     *
     * @return integer
     */
    public function getAcceptedTerms()
    {
        return Tinebase_Config::getInstance()->get(Tinebase_Config::ACCEPTEDTERMSVERSION, 0);
    }
    
    /**
     * save acceptedTermsVersion
     *
     * @param $_data
     * @return void
     */
    public function saveAcceptedTerms($_data)
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::ACCEPTEDTERMSVERSION, $_data);
    }
    
    /**
     * save config option in db
     *
     * @param string $key
     * @param string|array $value
     * @param string $applicationName
     * @return void
     */
    public function setConfigOption($key, $value, $applicationName = 'Tinebase')
    {
        $config = Tinebase_Config_Abstract::factory($applicationName);
        
        if ($config) {
            if (null === $config->getDefinition($key)) {
                throw new Tinebase_Exception_InvalidArgument('config property ' . $key .
                    ' does not exist in ' . get_class($config));
            }
            $config->set($key, $value);
        }
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
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Valid credentials, setting username in session and registry.');
            Tinebase_Session::regenerateId();
            
            Setup_Core::set(Setup_Core::USER, $_username);
            Setup_Session::getSessionNamespace()->setupuser = $_username;
            return true;
            
        } else {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Invalid credentials! ' . print_r($authResult->getMessages(), TRUE));
            Tinebase_Session::expireSessionCookie();
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
        $_SESSION = array();
        
        Tinebase_Session::destroyAndRemoveCookie();
    }
    
    /**
     * install list of applications
     *
     * @param array $_applications list of application names
     * @param array|null $_options
     * @return integer
     */
    public function installApplications($_applications, $_options = null)
    {
        $this->clearCache();

        if (!isset($_options[self::INSTALL_NO_REPLICATION_SLAVE_CHECK]) ||
                !$_options[self::INSTALL_NO_REPLICATION_SLAVE_CHECK]) {
            if (Setup_Core::isReplicationSlave()) {
                throw new Setup_Exception('Replication slaves can not install an app');
            }
        }
        
        // check requirements for initial install / add required apps to list
        if (! $this->isInstalled('Tinebase')) {
    
            $minimumRequirements = array('Addressbook', 'Tinebase', 'Admin');
            
            foreach ($minimumRequirements as $requiredApp) {
                if (!in_array($requiredApp, $_applications) && !$this->isInstalled($requiredApp)) {
                    // Addressbook has to be installed with Tinebase for initial data (user contact)
                    Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' ' . $requiredApp . ' has to be installed first (adding it to list).'
                    );
                    $_applications[] = $requiredApp;
                }
            }

            Tinebase_Application::getInstance()->omitModLog(true);
            Tinebase_Scheduler::getInstance()->modlogActive(false);
            Tinebase_Scheduler::getInstance()->useNotes(false);
        } else {
            $setupUser = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
            if ($setupUser && ! Tinebase_Core::getUser() instanceof Tinebase_Model_User) {
                Tinebase_Core::set(Tinebase_Core::USER, $setupUser);
            }
        }
        
        // get xml and sort apps first
        $applications = array();
        foreach ($_applications as $appId => $applicationName) {
            if ($this->isInstalled($applicationName)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " skipping installation of application {$applicationName} because it is already installed");
            } else {
                $applications[$applicationName] = $this->getSetupXml($applicationName);
                if (strlen($appId) === 40) {
                    $applications[$applicationName]->id = $appId;
                }
            }
        }
        $applications = $this->sortInstallableApplications($applications);
        
        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Installing applications: ' . print_r(array_keys($applications), true));

        $fsConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::FILESYSTEM);
        if ($fsConfig && ($fsConfig->{Tinebase_Config::FILESYSTEM_CREATE_PREVIEWS} ||
                $fsConfig->{Tinebase_Config::FILESYSTEM_INDEX_CONTENT})) {
            $fsConfig->unsetParent();
            $fsConfig->{Tinebase_Config::FILESYSTEM_CREATE_PREVIEWS} = false;
            $fsConfig->{Tinebase_Config::FILESYSTEM_INDEX_CONTENT} = false;
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::FILESYSTEM, $fsConfig);
        }

        $count = 0;
        foreach ($applications as $name => $xml) {
            if (! $xml) {
                Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Could not install application ' . $name);
            } else {
                $this->_installApplication($xml, $_options);
                $count++;
            }
        }

        $this->clearCache();

        Tinebase_Event::reFireForNewApplications();

        // create eventually missing foreign key constraints (cross application constraints...)
        Setup_SchemaTool::updateAllSchema();

        return $count;
    }

    public function setMaintenanceMode($options)
    {
        if (! isset($options['state'])) {
            return false;
        }
        switch ($options['state']) {
            case Tinebase_Config::MAINTENANCE_MODE_OFF:
                Tinebase_Config::getInstance()->{Tinebase_Config::MAINTENANCE_MODE} = '';
                break;

            case Tinebase_Config::MAINTENANCE_MODE_NORMAL:
                Tinebase_Config::getInstance()->{Tinebase_Config::MAINTENANCE_MODE} =
                    Tinebase_Config::MAINTENANCE_MODE_NORMAL;
                // delete sessions
                Tinebase_Session::setSessionBackend();
                if (($sessionHandler = Zend_Session::getSaveHandler()) instanceof Zend_Session_SaveHandler_Interface) {
                    $sessionHandler->gc(0);
                }
                break;

            case Tinebase_Config::MAINTENANCE_MODE_ALL:
                Tinebase_Config::getInstance()->{Tinebase_Config::MAINTENANCE_MODE} =
                    Tinebase_Config::MAINTENANCE_MODE_ALL;
                // delete sessions
                Tinebase_Session::setSessionBackend();
                if (($sessionHandler = Zend_Session::getSaveHandler()) instanceof Zend_Session_SaveHandler_Interface) {
                    $sessionHandler->gc(0);
                }
                break;

            default:
                return false;
        }
        return true;
    }

    /**
     * install tine from dump file
     *
     * @param $options
     * @throws Setup_Exception
     * @return boolean
     */
    public function installFromDump($options)
    {
        $this->clearCache();

        if ($this->isInstalled('Tinebase')) {
            Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Tinebase is already installed.');
            return false;
        }

        $mysqlBackupFile = null;
        if (isset($options['backupDir'])) {
            $mysqlBackupFile = $options['backupDir'] . '/tine20_mysql.sql.bz2';
        } else if (isset($options['backupUrl'])) {
            // download files first and put them in temp dir
            $tempDir = Tinebase_Core::getTempDir();
            foreach (array(
                         array('file' => 'tine20_config.tar.bz2', 'param' => 'config'),
                         array('file' => 'tine20_mysql.sql.bz2', 'param' => 'db'),
                         array('file' => 'tine20_files.tar.bz2', 'param' => 'files')
                    ) as $download) {
                if (isset($options[$download['param']])) {
                    $targetFile = $tempDir . DIRECTORY_SEPARATOR . $download['file'];
                    $fileUrl = $options['backupUrl'] . '/' . $download['file'];
                    Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Downloading ' . $fileUrl
                        . ' to ' . $targetFile);
                    if ($download['param'] === 'db') {
                        $mysqlBackupFile = $targetFile;
                    }
                    file_put_contents(
                        $targetFile,
                        fopen($fileUrl, 'r')
                    );
                }
            }
            $options['backupDir'] = $tempDir;
        } else {
            throw new Setup_Exception("backupDir or backupUrl param required");
        }

        if (! $mysqlBackupFile || ! file_exists($mysqlBackupFile) || filesize($mysqlBackupFile) === 0) {
            throw new Setup_Exception("$mysqlBackupFile not found");
        }

        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Installing from dump ' . $mysqlBackupFile);

        if (! isset($options['keepTinebaseID']) || ! $options['keepTinebaseID']) {
            $this->_replaceTinebaseidInDump($mysqlBackupFile);
        }
        $this->restore($options);

        $setupUser = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
        if ($setupUser && ! Tinebase_Core::getUser() instanceof Tinebase_Model_User) {
            Tinebase_Core::set(Tinebase_Core::USER, $setupUser);
        }

        // make sure we have the right instance id
        Tinebase_Core::unsetTinebaseId();
        // save the master id
        $replicationMasterId = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();

        // do updates now, because maybe application state updates are not yet there
        Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_ALL);
        Tinebase_Application::getInstance()->resetClassCache();
        try {
            $this->updateApplications();
        } catch (Tinebase_Exception_Backend $e) {
            if (strpos($e->getMessage(), 'you still have some utf8 ') === 0) {
                $fe = new Setup_Frontend_Cli();
                $fe->_migrateUtf8mb4();
                $this->updateApplications();
            }
        }

        // then set the replication master id
        $tinebase = Tinebase_Application::getInstance()->getApplicationByName('Tinebase');
        Tinebase_Application::getInstance()->setApplicationState($tinebase,
            Tinebase_Application::STATE_REPLICATION_MASTER_ID, $replicationMasterId);

        return true;
    }

    /**
     * replace old Tinebase ID in dump to make sure we have a unique installation ID
     *
     * TODO: think about moving the Tinebase ID (and more info) to a metadata.json file in the backup zip
     *
     * @param $mysqlBackupFile
     * @return string the old TinebaseId
     * @throws Setup_Exception
     */
    protected function _replaceTinebaseidInDump($mysqlBackupFile)
    {
        // fetch old Tinebase ID
        $cmd = "bzcat $mysqlBackupFile | grep \",'Tinebase','enabled'\"";
        $result = exec($cmd);
        if (! preg_match("/'([0-9a-f]+)','Tinebase'/", $result, $matches)) {
            throw new Setup_Exception('could not find Tinebase ID in dump');
        }
        $oldTinebaseId = $matches[1];
        Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Replacing old Tinebase id: ' . $oldTinebaseId);

        $cmd = "bzcat $mysqlBackupFile | sed s/"
            . $oldTinebaseId . '/'
            . Tinebase_Record_Abstract::generateUID() . "/g | " // g for global!
            . "bzip2 > " . $mysqlBackupFile . '.tmp';

        Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $cmd);

        exec($cmd);
        copy($mysqlBackupFile . '.tmp', $mysqlBackupFile);
        unlink($mysqlBackupFile . '.tmp');

        return $oldTinebaseId;
    }

    /**
     * delete list of applications
     *
     * @param array $_applications list of application names
     * @param array $_options
     * @return integer number of uninstalled apps
     * @throws Tinebase_Exception
     */
    public function uninstallApplications($_applications, $_options = [])
    {
        try {
            $user = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
            Tinebase_Core::set(Tinebase_Core::USER, $user);
        } catch (Exception $e) {
            // try without setup user - Addressbook might be already uninstalled
            Tinebase_Exception::log($e);
        }

        $this->clearCache();

        //sanitize input
        $_applications = array_unique(array_filter($_applications));

        $installedApps = Tinebase_Application::getInstance()->getApplications();
        
        // uninstall all apps if tinebase ist going to be uninstalled
        if (in_array('Tinebase', $_applications)) {
            $_applications = $installedApps->name;
        } else {
            // prevent Addressbook and Admin from being uninstalled
            if(($key = array_search('Addressbook', $_applications)) !== false) {
                unset($_applications[$key]);
            }
            if(($key = array_search('Admin', $_applications)) !== false) {
                unset($_applications[$key]);
            }
        }
        
        // deactivate foreign key check if all installed apps should be uninstalled
        $deactivatedForeignKeyCheck = false;
        if (in_array('Tinebase', $_applications) && get_class($this->_backend) === 'Setup_Backend_Mysql') {
            $this->_backend->setForeignKeyChecks(0);
            $deactivatedForeignKeyCheck = true;
        }

        // get xml and sort apps first
        $applications = array();
        foreach ($_applications as $applicationName) {
            try {
                $applications[$applicationName] = $this->getSetupXml($applicationName);
            } catch (Setup_Exception_NotFound $senf) {
                // application setup.xml not found
                Tinebase_Exception::log($senf);
                $applications[$applicationName] = null;
            }
        }
        $applications = $this->_sortUninstallableApplications($applications);

        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Uninstalling applications: '
            . print_r(array_keys($applications), true));

        if (count($_applications) > count($applications)) {
            Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Some applications could not be uninstalled (check dependencies).');
        }

        foreach ($applications as $name => $xml) {
            $app = Tinebase_Application::getInstance()->getApplicationByName($name);
            $this->_uninstallApplication($app, false, isset($_options[self::INSTALL_NO_REPLICATION_SLAVE_CHECK]) ?
                $_options[self::INSTALL_NO_REPLICATION_SLAVE_CHECK] : false);
        }

        if (true === $deactivatedForeignKeyCheck) {
            $this->_backend->setForeignKeyChecks(1);
        }

        $this->clearCache();

        return count($applications);
    }
    
    /**
     * install given application
     *
     * @param  SimpleXMLElement $_xml
     * @param  array|null $_options
     * @return void
     * @throws Tinebase_Exception_Backend_Database
     * @throws Exception
     */
    protected function _installApplication(SimpleXMLElement $_xml, $_options = null)
    {
        static $deferImportExport = [];

        if ($this->_backend === NULL) {
            throw new Tinebase_Exception_Backend_Database('Need configured and working database backend for install.');
        }
        
        if (!$this->checkDatabasePrefix()) {
            throw new Tinebase_Exception_Backend_Database('Tableprefix is too long');
        }
        
        try {
            if (Setup_Core::isLogLevel(Zend_Log::INFO)) Setup_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' Installing application: ' . $_xml->name);

            $appData = [
                'name'      => (string)$_xml->name,
                'status'    => $_xml->status ? (string)$_xml->status : Tinebase_Application::ENABLED,
                'order'     => $_xml->order ? (string)$_xml->order : 99,
                'version'   => (string)$_xml->version
            ];
            if ($_xml->id && strlen($_xml->id) === 40) {
                $appData['id'] = (string)$_xml->id;
            }
            $application = new Tinebase_Model_Application($appData);

            if ('Tinebase' !== $application->name) {
                $application = Tinebase_Application::getInstance()->addApplication($application);
            }

            // do doctrine/MCV2 then old xml
            $this->_createModelConfigSchema($_xml->name);

            // traditional xml declaration
            $createdTables = [];
            if (isset($_xml->tables)) {
                foreach ($_xml->tables[0] as $tableXML) {
                    $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableXML);
                    if ($this->_createTable($table) !== true) {
                        // table was gracefully not created, maybe due to missing requirements, just continue
                        continue;
                    }
                    $createdTables[] = $table;
                }
            }

            if ('Tinebase' === $application->name) {
                $application = Tinebase_Application::getInstance()->addApplication($application);
                $tbInstance = Setup_Core::getApplicationInstance($application->name, '', true);
                Setup_SchemaTool::updateApplicationTable($tbInstance->getModels(true /* MCv2only */));
            }

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
            
            Setup_Initialize::initialize($application, $_options);

            if (!isset($_options[self::INSTALL_NO_IMPORT_EXPORT_DEFINITIONS])) {
                switch ($application->name) {
                    case Tinebase_Config::APP_NAME:
                    case Admin_Config::APP_NAME:
                        $that = $this;
                        $deferImportExport[] = function() use ($application, $that) {
                            $that->createImportExportDefinitions($application);
                        };
                        break;
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case Addressbook_Config::APP_NAME:
                        foreach ($deferImportExport as $func) {
                            $func();
                        }
                        $deferImportExport = [];
                        // no break!
                    default:
                        // look for import definitions and put them into the db
                        $this->createImportExportDefinitions($application);
                        break;
                }
            }

            // fill update state with all available updates of the current version, as we do not need to run them again
            $appMajorV = (int)$application->getMajorVersion();
            for ($majorV = 0; $majorV <= $appMajorV; ++$majorV) {
                /** @var Setup_Update_Abstract $class */
                $class = $application->name . '_Setup_Update_' . $majorV;
                if (class_exists($class) && !empty($updatesByPrio = $class::getAllUpdates())) {
                    if (!($state = json_decode(Tinebase_Application::getInstance()->getApplicationState(
                            $application->getId(), Tinebase_Application::STATE_UPDATES, true), true))) {
                        $state = [];
                    }
                    $now = Tinebase_DateTime::now()->format(Tinebase_Record_Abstract::ISO8601LONG);

                    foreach ($updatesByPrio as $updates) {
                        foreach (array_keys($updates) as $updateKey) {
                            $state[$updateKey] = $now;
                        }
                    }

                    Tinebase_Application::getInstance()->setApplicationState($application->getId(),
                        Tinebase_Application::STATE_UPDATES, json_encode($state));
                }
            }
        } catch (Exception $e) {
            Tinebase_Exception::log($e, /* suppress trace */ false);
            throw $e;
        }
    }

    protected function _createModelConfigSchema(string $appName): void
    {
        $application = Setup_Core::getApplicationInstance($appName, '', true);
        $models = $application->getModels(true /* MCv2only */);

        if (count($models) > 0) {
            // create tables using doctrine 2
            // NOTE: we don't use createSchema here because some tables might already been created
            Setup_SchemaTool::updateSchema($models);
        }
    }

    protected function _createTable($table)
    {
        if (Setup_Core::isLogLevel(Zend_Log::DEBUG)) Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Creating table: ' . $table->name);

        try {
            $result = $this->_backend->createTable($table);
        } catch (Zend_Db_Statement_Exception $zdse) {
            throw new Tinebase_Exception_Backend_Database('Could not create table: ' . $zdse->getMessage());
        } catch (Zend_Db_Adapter_Exception $zdae) {
            throw new Tinebase_Exception_Backend_Database('Could not create table: ' . $zdae->getMessage());
        }

        return $result;
    }

    /**
     * look for export & import definitions and put them into the db
     *
     * @param Tinebase_Model_Application $_application
     * @param boolean $_onlyDefinitions
     */
    public function createImportExportDefinitions($_application, $_onlyDefinitions = false)
    {
        foreach (array('Import', 'Export') as $type) {
            $path =
                $this->_baseDir . $_application->name .
                DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . 'definitions';
    
            if (file_exists($path)) {
                $lambda = function($path, $application) {
                    if (preg_match("/\.xml/", $path)) {
                        try {
                            Tinebase_ImportExportDefinition::getInstance()->updateOrCreateFromFilename($path,
                                $application);
                        } catch (Exception $e) {
                            Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                                . ' Not installing import/export definion from file: ' . $path
                                . ' / Error message: ' . $e->getMessage());
                        }
                    }
                };
                foreach (new DirectoryIterator($path) as $item) {
                    if ($item->isDir()) {
                        try {
                            $otherApp = Tinebase_Application::getInstance()->getApplicationByName($item->getFilename());
                        } catch (Tinebase_Exception_NotFound $e) {
                            continue;
                        }
                        foreach (new DirectoryIterator($item->getPathname()) as $otherItem) {
                            $lambda($otherItem->getPathname(), $otherApp);
                        }
                    } else {
                        $lambda($item->getPathname(), $_application);
                    }
                }
            }

            if (true === $_onlyDefinitions) {
                continue;
            }

            $path =
                $this->_baseDir . $_application->name .
                DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . 'templates';

            if (file_exists($path)) {
                $fileSystem = Tinebase_FileSystem::getInstance();

                $basepath = $fileSystem->getApplicationBasePath(
                    'Tinebase',
                    Tinebase_FileSystem::FOLDER_TYPE_SHARED
                ) . '/' . strtolower($type);

                if (false === $fileSystem->isDir($basepath)) {
                    $fileSystem->createAclNode($basepath);
                }

                $templateAppPath = Tinebase_Model_Tree_Node_Path::createFromPath($basepath . '/templates/' . $_application->name);

                if (! $fileSystem->isDir($templateAppPath->statpath)) {
                    $fileSystem->mkdir($templateAppPath->statpath);
                }

                $lambda = function($item, $fileSystem, $templateAppPath) {
                    if (false === ($content = file_get_contents($item->getPathname()))) {
                        Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' Could not import template: ' . $item->getPathname());
                        return;
                    }
                    if (false === ($file = $fileSystem->fopen($templateAppPath->statpath . '/' . $item->getFileName(), 'w'))) {
                        Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' could not open ' . $templateAppPath->statpath . '/' . $item->getFileName() . ' for writting');
                        return;
                    }
                    fwrite($file, $content);
                    if (true !== $fileSystem->fclose($file)) {
                        Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' write to ' . $templateAppPath->statpath . '/' . $item->getFileName() . ' did not succeed');
                        return;
                    }
                };

                foreach (new DirectoryIterator($path) as $item) {
                    if ($item->isDir()) {
                        try {
                            $otherApp = Tinebase_Application::getInstance()->getApplicationByName($item->getFilename());
                        } catch (Tinebase_Exception_NotFound $e) {
                            continue;
                        }
                        $otherTemplateAppPath = Tinebase_Model_Tree_Node_Path::createFromPath($basepath . '/templates/'
                            . $otherApp->name);
                        if (! $fileSystem->isDir($otherTemplateAppPath->statpath)) {
                            $fileSystem->mkdir($otherTemplateAppPath->statpath);
                        }
                        foreach (new DirectoryIterator($item->getPathname()) as $otherItem) {
                            if ($otherItem->isFile()) {
                                $lambda($otherItem, $fileSystem, $otherTemplateAppPath);
                            }
                        }
                    } else {
                        $lambda($item, $fileSystem, $templateAppPath);
                    }
                }
            }
        }
    }
    
    /**
     * uninstall app
     *
     * @param Tinebase_Model_Application $_application
     * @throws Setup_Exception
     */
    protected function _uninstallApplication(Tinebase_Model_Application $_application, $uninstallAll = false, $noSlaveCheck = false)
    {
        if ($this->_backend === null) {
            throw new Setup_Exception('No setup backend available');
        }
        if (!$noSlaveCheck && Setup_Core::isReplicationSlave()) {
            throw new Setup_Exception('Replication slaves can not uninstall an app');
        }
        
        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Uninstall ' . $_application);
        try {
            $applicationTables = Tinebase_Application::getInstance()->getApplicationTables($_application);
            $applicationTables = array_diff($applicationTables, [
                'applications',
                'application_states',
                'application_tables'
            ]);
        } catch (Zend_Db_Statement_Exception $zdse) {
            Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " " . $zdse);
            throw new Setup_Exception('Could not uninstall ' . $_application . ' (you might need to remove the tables by yourself): ' . $zdse->getMessage());
        }
        $disabledFK = FALSE;
        $db = Tinebase_Core::getDb();
        
        do {
            $oldCount = count($applicationTables);

            if ($_application->name == 'Tinebase') {
                $installedApplications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
                if (count($installedApplications) !== 1) {
                    Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Installed apps: ' . print_r($installedApplications->name, true));
                    throw new Setup_Exception_Dependency('Failed to uninstall application "Tinebase" because of dependencies to other installed applications.');
                }
            }

            foreach ($applicationTables as $key => $table) {
                Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Remove table: $table");
                
                try {
                    // drop foreign keys which point to current table first
                    $foreignKeys = $this->_backend->getExistingForeignKeys($table);
                    foreach ($foreignKeys as $foreignKey) {
                        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . 
                            " Drop index: " . $foreignKey['table_name'] . ' => ' . $foreignKey['constraint_name']);
                        $this->_backend->dropForeignKey($foreignKey['table_name'], $foreignKey['constraint_name']);
                    }
                    
                    // drop table
                    $this->_backend->dropTable($table, $_application->name);
                    Setup_SchemaTool::addUninstalledTable(SQL_TABLE_PREFIX . $table);
                    
                    unset($applicationTables[$key]);
                    
                } catch (Zend_Db_Statement_Exception $e) {
                    // we need to catch exceptions here, as we don't want to break here, as a table
                    // might still have some foreign keys
                    // this works with mysql only
                    $message = $e->getMessage();
                    Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " Could not drop table $table - " . $message);
                    
                    // remove app table if table not found in db
                    if (preg_match('/SQLSTATE\[42S02\]: Base table or view not found/', $message) && $_application->name != 'Tinebase') {
                        Tinebase_Application::getInstance()->removeApplicationTable($_application, $table);
                        Setup_SchemaTool::addUninstalledTable(SQL_TABLE_PREFIX . $table);
                        unset($applicationTables[$key]);
                    } else {
                        Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " Disabling foreign key checks ... ");
                        if ($db instanceof Zend_Db_Adapter_Pdo_Mysql) {
                            $db->query("SET FOREIGN_KEY_CHECKS=0");
                        }
                        $disabledFK = TRUE;
                    }
                }
            }
            
            if ($oldCount > 0 && count($applicationTables) == $oldCount) {
                throw new Setup_Exception('dead lock detected oldCount: ' . $oldCount);
            }
        } while (count($applicationTables) > 0);

        if ($_application->name == 'Tinebase') {
            $db->query('DROP TABLE ' . SQL_TABLE_PREFIX . 'applications');
            $db->query('DROP TABLE ' . SQL_TABLE_PREFIX . 'application_states');
            $db->query('DROP TABLE ' . SQL_TABLE_PREFIX . 'application_tables');
        }

        if ($disabledFK) {
            if ($db instanceof Zend_Db_Adapter_Pdo_Mysql) {
                Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " Enabling foreign key checks again... ");
                $db->query("SET FOREIGN_KEY_CHECKS=1");
            }
        }
        
        if ($_application->name != 'Tinebase') {
            if (!$uninstallAll) {
                Tinebase_Relations::getInstance()->removeApplication($_application->name);

                Tinebase_Timemachine_ModificationLog::getInstance()->removeApplication($_application);

                // delete containers, config options and other data for app
                try {
                    Tinebase_Application::getInstance()->removeApplicationAuxiliaryData($_application);
                } catch (Exception $e) {
                    Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' removeApplicationAuxiliaryData of app ' . $_application->name . ' failed: ' . $e);
                }
            }
            
            // remove application from table of installed applications
            Tinebase_Application::getInstance()->deleteApplication($_application);
        }

        try {
            Setup_Uninitialize::uninitialize($_application);
        } catch (Exception $e) {
            Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Uninitializing app '
                . $_application->name . ' failed: ' . $e);
        }

        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Removed app: " . $_application->name);
    }

    /**
     * sort applications by checking dependencies
     *
     * @param array $_applications
     * @return array
     */
    public function sortInstallableApplications($_applications)
    {
        $result = array();
        
        // begin with Tinebase, Admin and Addressbook
        $alwaysOnTop = array('Tinebase', 'Admin', 'Addressbook');
        foreach ($alwaysOnTop as $app) {
            if (isset($_applications[$app])) {
                $result[$app] = $_applications[$app];
                unset($_applications[$app]);
            }
        }

        // sort by order
        uasort($_applications, function($a, $b) {
            $aOrder = isset($a->order) ? (int) $a->order : 100;
            $bOrder = isset($b->order) ? (int) $b->order : 100;
            if ($aOrder == $bOrder) {
                // sort alphabetically
                return ((string) $a->name < (string) $b->name) ? -1 : 1;
            }
            return ($aOrder < $bOrder) ? -1 : 1;
        });

        // get all apps to install ($name => $dependencies)
        $appsToSort = array();
        foreach ($_applications as $name => $xml) {
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
        
        // re-sort apps
        $count = 0;
        while (count($appsToSort) > 0 && $count < MAXLOOPCOUNT) {
            foreach ($appsToSort as $name => $depends) {

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
                " Some Applications could not be installed because of dependencies (app => depends): "
                . print_r($appsToSort, TRUE));
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

        // if not everything is going to be uninstalled, we need to check the dependencies of the applications
        // that stay installed.
        if (!isset($_applications['Tinebase'])) {
            $installedApps = Tinebase_Application::getInstance()->getApplications()->name;
            $xml = array();

            do {
                $changed = false;
                $stillInstalledApps = array_diff($installedApps, array_keys($_applications));
                foreach ($stillInstalledApps as $name) {
                    if (!isset($xml[$name])) {
                        try {
                            $xml[$name] = $this->getSetupXml($name);
                        } catch (Setup_Exception_NotFound $senf) {
                            Tinebase_Exception::log($senf);
                        }
                    }
                    $depends = isset($xml[$name]) ? (array) $xml[$name]->depends : array();
                    if (isset($depends['application'])) {
                        foreach ((array)$depends['application'] as $app) {
                            if(isset($_applications[$app])) {
                                unset($_applications[$app]);
                                $changed = true;
                                Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                                    ' App ' . $name . ' still depends on: ' . $app . ' - so it cannot be uninstalled.');
                            }
                        }
                    }
                }
            } while(true === $changed);
        }
        
        // get all apps to uninstall ($name => $dependencies)
        $appsToSort = array();
        foreach ($_applications as $name => $xml) {
            if ($name !== 'Tinebase') {
                $appsToSort[$name] = array();
                $depends = $xml ? (array)$xml->depends : array();
                if (isset($depends['application'])) {
                    foreach ((array)$depends['application'] as $app) {
                        // don't add tinebase (all apps depend on Tinebase)
                        if ($app !== 'Tinebase') {
                            $appsToSort[$name][] = $app;
                        }
                    }
                }
            }
        }

        // re-sort apps
        $count = 0;
        while (count($appsToSort) > 0 && $count < MAXLOOPCOUNT) {
            foreach ($appsToSort as $name => $depends) {
                // don't uninstall if another app depends on this one
                $otherAppDepends = FALSE;
                foreach ($appsToSort as $innerName => $innerDepends) {
                    if (in_array($name, $innerDepends)) {
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
        }
        
        return $result;
    }
    
    /**
     * check if an application is installed
     *
     * @param string $appname
     * @return boolean
     */
    public function isInstalled($appname = 'Tinebase')
    {
        try {
            $result = Tinebase_Application::getInstance()->isInstalled($appname);
        } catch (Exception $e) {
            Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Application ' . $appname . ' is not installed.');
            Setup_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $e);
            $result = FALSE;
        }
        
        return $result;
    }
    
    /**
     * clear caches
     *
     * @param bool $deactivateCache after clearing
     * @return array
     */
    public function clearCache(bool $deactivateCache = true): array
    {
        $cachesCleared = [];

        // setup cache (via Tinebase because it is disabled in setup by default)
        Tinebase_Core::setupCache();
        Tinebase_Controller::getInstance()->cleanupCache(Zend_Cache::CLEANING_MODE_ALL);
        $cachesCleared[] = 'TinebaseCache';

        Tinebase_Application::getInstance()->resetClassCache();
        $cachesCleared[] = 'ApplicationClassCache';
        Tinebase_Cache_PerRequest::getInstance()->reset();
        $cachesCleared[] = 'RequestCache';
        
        clearstatcache();
        $cachesCleared[] = 'StatCache';

        Tinebase_Config::getInstance()->clearCache();
        $cachesCleared[] = 'ConfigCache';

        $this->clearCacheDir();
        $cachesCleared[] = 'RoutesCache';
    
        if ($deactivateCache) {
            Tinebase_Core::setupCache(false);
        }

        return $cachesCleared;
    }

    /**
     * clear cache directories
     */
    public function clearCacheDir()
    {
        $cacheDir = rtrim(Tinebase_Core::getCacheDir(), '/');
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Clearing routing cache in ' . $cacheDir . ' ...');

        foreach (new DirectoryIterator($cacheDir) as $directoryIterator) {
            if (strpos($directoryIterator->getFilename(), 'route.cache') !== false && $directoryIterator->isFile()) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Deleting routing cache file ' . $directoryIterator->getPathname());
                unlink($directoryIterator->getPathname());
            }
        }

        if (is_dir($cacheDir . '/tine20Twig')) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Clearing twig cache in ' . $cacheDir . ' ...');
            exec('rm -rf ' . $cacheDir . '/tine20Twig/*');
        }
    }

    /**
     * returns TRUE if filesystem is available
     * 
     * @return boolean
     */
    public function isFilesystemAvailable()
    {
        if ($this->_isFileSystemAvailable === null) {
            try {
                $session = Tinebase_Session::getSessionNamespace();

                if (isset($session->filesystemAvailable)) {
                    $this->_isFileSystemAvailable = $session->filesystemAvailable;

                    return $this->_isFileSystemAvailable;
                }
            } catch (Zend_Session_Exception $zse) {
                $session = null;
            }

            $this->_isFileSystemAvailable = (!empty(Tinebase_Core::getConfig()->filesdir) && is_writeable(Tinebase_Core::getConfig()->filesdir));

            if ($session instanceof Zend_Session_Namespace) {
                if (Tinebase_Session::isWritable()) {
                    $session->filesystemAvailable = $this->_isFileSystemAvailable;
                }
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Filesystem available: ' . ($this->_isFileSystemAvailable ? 'yes' : 'no'));
        }

        return $this->_isFileSystemAvailable;
    }

    /**
     * backup
     *
     * @param $options array(
     *      'backupDir'  => string // where to store the backup
     *      'noTimestamp => bool   // don't append timestamp to backup dir
     *      'config'     => bool   // backup config
     *      'db'         => bool   // backup database
     *      'files'      => bool   // backup files
     *      'novalidate' => bool   // do not validate sql backup
     *    )
     */
    public function backup($options)
    {
        if (! $this->isInstalled('Tinebase')) {
            Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Tine 2.0 is not installed');
            return;
        }

        $config = Setup_Core::getConfig();

        $backupDir = isset($options['backupDir']) ? $options['backupDir'] : $config->backupDir;
        if (! $backupDir) {
            throw new Exception('backupDir not configured');
        }

        if (! isset($options['db']) && ! isset($options['files']) && ! isset($options['config'])) {
            // files & db are default
            $options['db'] = true;
            $options['files'] = true;
        }

        if (! isset($options['noTimestamp'])) {
            $backupDir .= '/' . date_create('now', new DateTimeZone('UTC'))->format('Y-m-d-H-i-s');
        }

        if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true)) {
            throw new Exception("$backupDir could  not be created");
        }

        if (isset($options['config']) && $options['config']) {
            $configFile = stream_resolve_include_path('config.inc.php');
            $configDir = dirname($configFile);

            $files = file_exists("$configDir/index.php") ? 'config.inc.php' : '.';
            `cd $configDir; tar cjf $backupDir/tine20_config.tar.bz2 $files`;

            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Backup of config file successful');
        }

        if (isset($options['db']) && $options['db']) {
            if (! $this->_backend) {
                throw new Exception('db not configured, cannot backup');
            }

            $backupOptions = array(
                'backupDir'         => $backupDir,
                'structTables'      => $this->getBackupStructureOnlyTables(),
                'novalidate'        => isset($options['novalidate']) && $options['novalidate'],
            );

            $this->_backend->backup($backupOptions);

            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Backup of DB successful');
        }
        
        if (isset($options['emailusers']) && $options['emailusers']) {
            $options['backupDir'] = $backupDir;
            
            foreach ([Tinebase_Config::SMTP, Tinebase_Config::IMAP] as $backendConfig) {
                try {
                    if (!Tinebase_EmailUser::manages($backendConfig)) {
                        Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 'backend : '
                            . $backendConfig . ' is not configured');
                        continue;
                    }

                    $backend = Tinebase_EmailUser::getInstance($backendConfig);
                    $backend->backup($options);
                    Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Backup of '
                        . $backendConfig . ' email users successful');
                } catch (Tinebase_Exception_Backend $teb) {
                    Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $teb->getMessage());
                }
            }
        }

        $filesDir = isset($config->filesdir) ? $config->filesdir : false;
        if (isset($options['files']) && $options['files'] && $filesDir) {
            `cd $filesDir; tar cjf $backupDir/tine20_files.tar.bz2 .`;

            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Backup of files successful');
        }
    }

    /**
     * returns an array of all tables of all applications that should only backup the structure
     *
     * @return array
     * @throws Setup_Exception_NotFound
     *
     * TODO support <backupStructureOnly>true</backupStructureOnly> for MC models without a table definition in setup.xml
     */
    public function getBackupStructureOnlyTables()
    {
        $tables = array();

        // find tables that only backup structure
        $applications = Tinebase_Application::getInstance()->getApplications();

        /**
         * @var $application Tinebase_Model_Application
         */
        foreach ($applications as $application) {
            $tableDef = $this->getSetupXml($application->name, true);
            if (! $tableDef) {
                continue;
            }
            $structOnlys = $tableDef->xpath('//table/backupStructureOnly[text()="true"]');

            foreach ($structOnlys as $structOnly) {
                $tableName = $structOnly->xpath('./../name/text()');
                $tables[] = SQL_TABLE_PREFIX . $tableName[0];
            }
        }

        return array_merge($tables, $this->_getNonXmlStructOnlyTables());
    }

    /**
     * TODO move this info to MC models or application_tables
     *
     * @return array
     */
    protected function _getNonXmlStructOnlyTables(): array
    {
        $tables = [];
        if ($this->isInstalled('Felamimail')) {
            $tables[] = SQL_TABLE_PREFIX . 'felamimail_attachmentcache';
        }

        // TODO add UserManual content tables?

        return $tables;
    }

    /**
     * restore
     *
     * @param $options array(
     *      'backupDir'  => string // location of backup to restore
     *      'config'     => bool   // restore config
     *      'db'         => bool   // restore database
     *      'files'      => bool   // restore files
     *    )
     *
     * @param $options
     * @throws Setup_Exception
     */
    public function restore($options)
    {
        if (! isset($options['backupDir'])) {
            throw new Setup_Exception("you need to specify the backupDir");
        }

        if (isset($options['config']) && $options['config']) {
            $configBackupFile = $options['backupDir']. '/tine20_config.tar.bz2';
            if (! file_exists($configBackupFile)) {
                throw new Setup_Exception("$configBackupFile not found");
            }

            $configDir = isset($options['configDir']) ? $options['configDir'] : false;
            if (!$configDir) {
                $configFile = stream_resolve_include_path('config.inc.php');
                if (!$configFile) {
                    throw new Setup_Exception("can't detect configDir, please use configDir option");
                }
                $configDir = dirname($configFile);
            }

            `cd $configDir; tar xf $configBackupFile`;

            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Restore of config file successful');
        }

        Setup_Core::setupConfig();
        $config = Setup_Core::getConfig();

        if (isset($options['db']) && $options['db']) {
            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Starting DB restore');
            $this->_backend->restore($options['backupDir']);
            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Restore of DB successful');
        }

        $filesDir = isset($config->filesdir) ? $config->filesdir : false;
        if (isset($options['files']) && $options['files']) {
            $dir = $options['backupDir'];
            $filesBackupFile = $dir . '/tine20_files.tar.bz2';
            if (! file_exists($filesBackupFile)) {
                throw new Setup_Exception("$filesBackupFile not found");
            }
            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Starting files restore');
            `cd $filesDir; tar xf $filesBackupFile`;
            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Restore of files successful');
        }

        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Clearing cache after restore ...');
        $this->_enableCaching();
        Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_ALL);
    }

    public function compareSchema($options)
    {
        if (! isset($options['otherdb'])) {
            throw new Exception("you need to specify the otherdb");
        }

        return Setup_SchemaTool::compareSchema($options['otherdb'], isset($options['otheruser']) ?
            $options['otheruser'] : null, isset($options['otherpassword']) ? $options['otherpassword'] : null);
    }

    /**
     * @return array
     */
    public function upgradeMysql564()
    {
        $setupBackend = Setup_Backend_Factory::factory();
        if (!$setupBackend->supports('mysql >= 5.6.4 | mariadb >= 10.0.5')) {
            return ['DB backend does not support the features - upgrade to mysql >= 5.6.4 or mariadb >= 10.0.5'];
        }
        if (!Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_FULLTEXT_INDEX)) {
            return ['full text index feature is disabled'];
        }

        $failures = array();
        $setupUpdate = new Setup_Update_Abstract($setupBackend);

        /** @var Tinebase_Model_Application $application */
        foreach (Tinebase_Application::getInstance()->getApplications() as $application) {
            try {
                $xml = $this->getSetupXml($application->name);
            } catch (Setup_Exception_NotFound $senf) {
                // app is not available any more
                $failures[] = $senf->getMessage();
                continue;
            }
            // should we check $xml->enabled? I don't think so, we asked Tinebase_Application for the applications...

            // get all MCV2 models for all apps, you never know...
            $controllerInstance = null;
            try {
                $controllerInstance = Tinebase_Core::getApplicationInstance($application->name, '', true);
            } catch(Tinebase_Exception_NotFound $tenf) {
                $failures[] = 'could not get application controller for app: ' . $application->name;
            }
            if (null !== $controllerInstance) {
                try {
                    $setupUpdate->updateSchema($application->name, $controllerInstance->getModels(true));
                } catch (Exception $e) {
                    $failures[] = 'could not update MCV2 schema for app: ' . $application->name;
                }
            }

            if (!empty($xml->tables)) {
                foreach ($xml->tables->table as $table) {
                    if (!empty($table->requirements) && !$setupBackend->tableExists((string)$table->name)) {
                        foreach ($table->requirements->required as $requirement) {
                            if (!$setupBackend->supports((string)$requirement)) {
                                continue 2;
                            }
                        }
                        $setupBackend->createTable(new Setup_Backend_Schema_Table_Xml($table->asXML()));
                        continue;
                    }

                    // check for fulltext index
                    foreach ($table->declaration->index as $index) {
                        if (empty($index->fulltext)) {
                            continue;
                        }
                        $declaration = new Setup_Backend_Schema_Index_Xml($index->asXML());
                        // TODO should check if index already exists
                        try {
                            $setupBackend->addIndex((string)$table->name, $declaration);
                        } catch (Exception $e) {
                            $failures[] = (string)$table->name . ': ' . (string)$index->name . '(error: ' . $e->getMessage() . ')';
                        }
                    }
                }
            }
        }

        return $failures;
    }


    /**
     * Add a new auth token to table tine20_auth_token
     *
     * Parameter $options has to be array with this structure:
     *      array(
     *          'user'          => string   // Username to create the token for
     *          'id'            => string   // Value for field tine20_auth_token.id
     *          'auth_token'    => string   // Value for field tine20_auth_token.auth_token
     *          'valid_until'   => string   // Value for field tine20_auth_token.valid_until
     *          'channels'      => string   // Comma separated list of channel names. Values for JSON array in field tine20_auth_token.channels
     *      )
     *
     * @param Array $options
     * @return Array $result  Array with all fields of new record
     */
    public function addAuthToken($options)
    {
        $result = null;

        $db = Setup_Core::getDb();

        $channels = explode(',', $options['channels']);
        foreach($channels as &$channel) {
            $channel = '"' . $channel . '"';
        }

        try {
            $query = 'INSERT INTO ' . SQL_TABLE_PREFIX . 'auth_token (id, auth_token, account_id, valid_until, channels) VALUES ';
            $query .= '(';
            $query .= $db->quote($options['id']) . ', ';
            $query .= $db->quote($options['auth_token']) . ', ';
            $query .= '(SELECT id FROM ' . SQL_TABLE_PREFIX . 'accounts WHERE login_name = ' . $db->quote($options['user']) . '), ';
            $query .= $db->quote($options['valid_until']) . ', ';
            $query .= $db->quote('[' . implode(',', $channels) . ']');
            $query .= ')';

            $db->query($query);

            $dbResult = $db->query('SELECT * FROM ' . SQL_TABLE_PREFIX . 'auth_token WHERE id = ' . $db->quote($options['id']))->fetchAll();
            $result = $dbResult[0];

        } catch (Zend_Db_Exception $zde) {
            Tinebase_Exception::log($zde);
        }

        return $result;
    }
}
