<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * cli server
 *
 * This class handles all requests from cli scripts
 *
 * @package     Tinebase
 * @subpackage  Frontend
 */
class Tinebase_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Tinebase';

    /**
     * authentication
     *
     * @param string $_username
     * @param string $_password
     */
    public function authenticate($_username, $_password)
    {
        $authResult = Tinebase_Auth::getInstance()->authenticate($_username, $_password);
        
        if ($authResult->isValid()) {
            $accountsController = Tinebase_User::getInstance();
            try {
                $account = $accountsController->getFullUserByLoginName($authResult->getIdentity());
            } catch (Tinebase_Exception_NotFound $e) {
                echo 'account ' . $authResult->getIdentity() . ' not found in account storage'."\n";
                exit();
            }
            
            Tinebase_Core::set('currentAccount', $account);

            $ipAddress = '127.0.0.1';
            $account->setLoginTime($ipAddress);

            Tinebase_AccessLog::getInstance()->create(new Tinebase_Model_AccessLog(array(
                'sessionid'     => 'cli call',
                'login_name'    => $authResult->getIdentity(),
                'ip'            => $ipAddress,
                'li'            => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
                'lo'            => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
                'result'        => $authResult->getCode(),
                'account_id'    => Tinebase_Core::getUser()->getId(),
                'clienttype'    => 'TineCli',
            )));
            
        } else {
            echo "Wrong username and/or password.\n";
            exit();
        }
    }
    
    /**
     * handle request (call -ApplicationName-_Cli.-MethodName- or -ApplicationName-_Cli.getHelp)
     *
     * @param Zend_Console_Getopt $_opts
     * @return boolean success
     */
    public function handle($_opts)
    {
        list($application, $method) = explode('.', $_opts->method);
        $class = $application . '_Frontend_Cli';
        
        if (@class_exists($class)) {
            $object = new $class;
            if ($_opts->info) {
                $result = $object->getHelp();
            } else if (method_exists($object, $method)) {
                $result = call_user_func(array($object, $method), $_opts);
            } else {
                $result = FALSE;
                echo "Method $method not found.\n";
            }
        } else {
            echo "Class $class does not exist.\n";
            $result = FALSE;
        }
        
        return $result;
    }

    /**
     * trigger async events (for example via cronjob)
     *
     * @param Zend_Console_Getopt $_opts
     * @return boolean success
     */
    public function triggerAsyncEvents($_opts)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Triggering async events from CLI.');
        
        $userController = Tinebase_User::getInstance();
        
        // deactivate user plugins (like postfix/dovecot email backends) for async job user
        $userController->unregisterAllPlugins();
        
        try {
            $cronuser = $userController->getFullUserByLoginName($_opts->username);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $cronuser = $this->_getCronuserFromConfigOrCreateOnTheFly();
        }
        Tinebase_Core::set(Tinebase_Core::USER, $cronuser);
        
        $scheduler = Tinebase_Core::getScheduler();
        $responses = $scheduler->run();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' ' . print_r(array_keys($responses), TRUE));
        
        $responseString = ($responses) ? implode(',', array_keys($responses)) : 'NULL';
        echo "Tine 2.0 scheduler run (" . $responseString . ") complete.\n";
        
        return TRUE;
    }
    
    /**
     * try to get user for cronjob from config
     * 
     * @return Tinebase_Model_FullUser
     */
    protected function _getCronuserFromConfigOrCreateOnTheFly()
    {
        try {
            $cronuserId = Tinebase_Config::getInstance()->get(Tinebase_Config::CRONUSERID);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting user with id ' . $cronuserId . ' as cronuser.');
            $cronuser = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $cronuserId, 'Tinebase_Model_FullUser');
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
            
            $cronuser = $this->_createCronuser();
            Tinebase_Config::getInstance()->set(Tinebase_Config::CRONUSERID, $cronuser->getId());
        }
        
        return $cronuser;
    }
    
    /**
     * create new cronuser
     * 
     * @return Tinebase_Model_FullUser
     */
    protected function _createCronuser()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Creating new cronuser.');
        
        $adminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        $cronuser = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'cronuser',
            'accountStatus'         => Tinebase_Model_User::ACCOUNT_STATUS_DISABLED,
            'visibility'            => Tinebase_Model_FullUser::VISIBILITY_HIDDEN,
            'accountPrimaryGroup'   => $adminGroup->getId(),
            'accountLastName'       => 'cronuser',
            'accountDisplayName'    => 'cronuser',
            'accountExpires'        => NULL,
        ));
        $cronuser = Tinebase_User::getInstance()->addUser($cronuser);
        Tinebase_Group::getInstance()->addGroupMember($cronuser->accountPrimaryGroup, $cronuser->getId());
        
        return $cronuser;
    }
    
    /**
     * process given queue job
     *  --message json encoded task
     *
     * @TODO rework user management, jobs should be executed as the right user in future
     * 
     * @param Zend_Console_Getopt $_opts
     * @return boolean success
     */
    public function executeQueueJob($_opts)
    {
        try {
            $cronuser = Tinebase_User::getInstance()->getFullUserByLoginName($_opts->username);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $cronuser = $this->_getCronuserFromConfigOrCreateOnTheFly();
        }
        
        Tinebase_Core::set(Tinebase_Core::USER, $cronuser);
        
        $args = $_opts->getRemainingArgs();
        $message = preg_replace('/^message=/', '', $args[0]);
        
        if (! $message) {
            throw new Tinebase_Exception_InvalidArgument('mandatory parameter "message" is missing');
        }
        
        Tinebase_ActionQueue::getInstance()->executeAction($message);
        
        return TRUE;
    }
    
    /**
     * clear table as defined in arguments
     * can clear the following tables:
     * - credential_cache
     * - access_log
     * - async_job
     * - temp_files
     * 
     * if param date is given (date=2010-09-17), all records before this date are deleted (if the table has a date field)
     * 
     * @param $_opts
     * @return boolean success
     */
    public function clearTable(Zend_Console_Getopt $_opts)
    {
        if (! $this->_checkAdminRight()) {
            return FALSE;
        }
        
        $args = $this->_parseArgs($_opts, array('tables'), 'tables');
        $dateString = (isset($args['date']) || array_key_exists('date', $args)) ? $args['date'] : NULL;

        $db = Tinebase_Core::getDb();
        foreach ($args['tables'] as $table) {
            switch ($table) {
                case 'access_log':
                    $date = ($dateString) ? new Tinebase_DateTime($dateString) : NULL;
                    Tinebase_AccessLog::getInstance()->clearTable($date);
                    break;
                case 'async_job':
                    $where = ($dateString) ? array(
                        $db->quoteInto($db->quoteIdentifier('end_time') . ' < ?', $dateString)
                    ) : array();
                    $where[] = $db->quoteInto($db->quoteIdentifier('status') . ' < ?', 'success');
                    
                    echo "\nRemoving all successful async_job entries " . ($dateString ? "before $dateString " : "") . "...";
                    $deleteCount = $db->delete(SQL_TABLE_PREFIX . $table, $where);
                    echo "\nRemoved $deleteCount records.";
                    break;
                case 'credential_cache':
                    Tinebase_Auth_CredentialCache::getInstance()->clearCacheTable($dateString);
                    break;
                case 'temp_files':
                    Tinebase_TempFile::getInstance()->clearTableAndTempdir($dateString);
                    break;
                default:
                    echo 'Table ' . $table . " not supported or argument missing.\n";
            }
            echo "\nCleared table $table.";
        }
        echo "\n\n";
        
        return TRUE;
    }
    
    /**
     * purge deleted records
     * 
     * if param date is given (for example: date=2010-09-17), all records before this date are deleted (if the table has a date field)
     * if table names are given, purge only records from this tables
     * 
     * @param $_opts
     * @return boolean success
     */
    public function purgeDeletedRecords(Zend_Console_Getopt $_opts)
    {
        if (! $this->_checkAdminRight()) {
            return FALSE;
        }

        $args = $this->_parseArgs($_opts, array(), 'tables');

        if (! (isset($args['tables']) || array_key_exists('tables', $args)) || empty($args['tables'])) {
            echo "No tables given.\nPurging records from all tables!\n";
            $args['tables'] = $this->_getAllApplicationTables();
        }
        
        $db = Tinebase_Core::getDb();
        
        if ((isset($args['date']) || array_key_exists('date', $args))) {
            echo "\nRemoving all deleted entries before {$args['date']} ...";
            $where = array(
                $db->quoteInto($db->quoteIdentifier('deleted_time') . ' < ?', $args['date'])
            );
        } else {
            echo "\nRemoving all deleted entries ...";
            $where = array();
        }
        $where[] = $db->quoteInto($db->quoteIdentifier('is_deleted') . ' = ?', 1);
        
        foreach ($args['tables'] as $table) {
            try {
                $schema = Tinebase_Db_Table::getTableDescriptionFromCache(SQL_TABLE_PREFIX . $table);
            } catch (Zend_Db_Statement_Exception $zdse) {
                echo "\nCould not get schema (" . $zdse->getMessage() ."). Skipping table $table";
                continue;
            }
            if (! (isset($schema['is_deleted']) || array_key_exists('is_deleted', $schema)) || ! (isset($schema['deleted_time']) || array_key_exists('deleted_time', $schema))) {
                continue;
            }
            
            $deleteCount = 0;
            try {
                $deleteCount = $db->delete(SQL_TABLE_PREFIX . $table, $where);
            } catch (Zend_Db_Statement_Exception $zdse) {
                echo "\nFailed to purge deleted records for table $table. " . $zdse->getMessage();
            }
            if ($deleteCount > 0) {
                echo "\nCleared table $table (deleted $deleteCount records).";
            }
        }
        echo "\n\n";
        
        return TRUE;
    }
    
    /**
     * get all app tables
     * 
     * @return array
     */
    protected function _getAllApplicationTables()
    {
        $result = array();
        
        $enabledApplications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
        foreach ($enabledApplications as $application) {
            $result = array_merge($result, Tinebase_Application::getInstance()->getApplicationTables($application));
        }
        
        return $result;
    }
    
    /**
     * add new customfield config
     * 
     * needs args like this:
     * application="Addressbook" name="datefield" label="Date" model="Addressbook_Model_Contact" type="datefield"
     * @see Tinebase_Model_CustomField_Config for full list 
     * 
     * @param $_opts
     * @return boolean success
     */
    public function addCustomfield(Zend_Console_Getopt $_opts)
    {
        if (! $this->_checkAdminRight()) {
            return FALSE;
        }
        
        // parse args
        $args = $_opts->getRemainingArgs();
        $data = array();
        foreach ($args as $idx => $arg) {
            list($key, $value) = explode('=', $arg);
            if ($key == 'application') {
                $key = 'application_id';
                $value = Tinebase_Application::getInstance()->getApplicationByName($value)->getId();
            }
            $data[$key] = $value;
        }
        
        $customfieldConfig = new Tinebase_Model_CustomField_Config($data);
        $cf = Tinebase_CustomField::getInstance()->addCustomField($customfieldConfig);

        echo "\nCreated customfield: ";
        print_r($cf->toArray());
        echo "\n";
        
        return TRUE;
    }
    
    /**
     * nagios monitoring for tine 2.0 database connection
     * 
     * @return integer
     * @see http://nagiosplug.sourceforge.net/developer-guidelines.html#PLUGOUTPUT
     */
    public function monitoringCheckDB()
    {
        $message = 'DB CONNECTION FAIL';
        try {
            if (! Setup_Core::isRegistered(Setup_Core::CONFIG)) {
                Setup_Core::setupConfig();
            }
            if (! Setup_Core::isRegistered(Setup_Core::LOGGER)) {
                Setup_Core::setupLogger();
            }
            $time_start = microtime(true);
            $dbcheck = Setup_Core::setupDatabaseConnection();
            $time = (microtime(true) - $time_start) * 1000;
        } catch (Exception $e) {
            $message .= ': ' . $e->getMessage();
            $dbcheck = FALSE;
        }
        
        if ($dbcheck) {
            echo "DB CONNECTION OK | connecttime={$time}ms;;;;\n";
            return 0;
        } 
        
        echo $message . "\n";
        return 2;
    }
    
    /**
     * nagios monitoring for tine 2.0 config file
     * 
     * @return integer
     * @see http://nagiosplug.sourceforge.net/developer-guidelines.html#PLUGOUTPUT
     */
    public function monitoringCheckConfig()
    {
        $message = 'CONFIG FAIL';
        $configcheck = FALSE;
        
        $configfile = Setup_Core::getConfigFilePath();
        if ($configfile) {
            $configfile = escapeshellcmd($configfile);
            if (preg_match('/^win/i', PHP_OS)) {
                exec("php -l $configfile 2> NUL", $error, $code);
            } else {
                exec("php -l $configfile 2> /dev/null", $error, $code);
            }
            if ($code == 0) {
                $configcheck = TRUE;
            } else {
                $message .= ': CONFIG FILE SYNTAX ERROR';
            }
        } else {
            $message .= ': CONFIG FILE MISSING';
        }
        
        if ($configcheck) {
            echo "CONFIG FILE OK\n";
            return 0;
        } else {
            echo $message . "\n";
            return 2;
        }
    }
    
    /**
    * nagios monitoring for tine 2.0 async cronjob run
    *
    * @return integer
    * 
    * @see http://nagiosplug.sourceforge.net/developer-guidelines.html#PLUGOUTPUT
    * @see 0008038: monitoringCheckCron -> check if cron did run in the last hour
    */
    public function monitoringCheckCron()
    {
        $message = 'CRON FAIL';
        $result  = 2;
        
        try {
            $lastJob = Tinebase_AsyncJob::getInstance()->getLastJob('Tinebase_Event_Async_Minutely');
            
            if ($lastJob === NULL) {
                $message .= ': NO LAST JOB FOUND';
                $result = 1;
            } else {
                if ($lastJob->end_time instanceof Tinebase_DateTime) {
                    $duration = $lastJob->end_time->getTimestamp() - $lastJob->start_time->getTimestamp();
                    $valueString = ' | duration=' . $duration . 's;;;;';
                    $valueString .= ' end=' . $lastJob->end_time->getIso() . ';;;;';
                } else {
                    $valueString = '';
                }
                
                if ($lastJob->status === Tinebase_Model_AsyncJob::STATUS_RUNNING && Tinebase_DateTime::now()->isLater($lastJob->end_time)) {
                    $message .= ': LAST JOB TOOK TOO LONG';
                    $result = 1;
                } else if ($lastJob->status === Tinebase_Model_AsyncJob::STATUS_FAILURE) {
                    $message .= ': LAST JOB FAILED';
                    $result = 1;
                } else if (Tinebase_DateTime::now()->isLater($lastJob->start_time->addHour(1))) {
                    $message .= ': NO JOB IN THE LAST HOUR';
                    $result = 1;
                } else {
                    $message = 'CRON OK';
                    $result = 0;
                }
                $message .= $valueString;
            }
        } catch (Exception $e) {
            $message .= ': ' . $e->getMessage();
            $result = 2;
        }
        
        echo $message . "\n";
        return $result;
    }
    
    /**
     * nagios monitoring for tine 2.0 logins during the last 5 mins
     * 
     * @return number
     * 
     * @todo allow to configure timeslot
     */
    public function monitoringLoginNumber()
    {
        $message = 'LOGINS';
        $result  = 0;
        
        try {
            $filter = new Tinebase_Model_AccessLogFilter(array(
                array('field' => 'li', 'operator' => 'after', 'value' => Tinebase_DateTime::now()->subMinute(5))
            ));
            $accesslogs = Tinebase_AccessLog::getInstance()->search($filter, NULL, FALSE, TRUE);
            $valueString = ' | count=' . count($accesslogs) . ';;;;';
            $message .= ' OK' . $valueString;
        } catch (Exception $e) {
            $message .= ' FAIL: ' . $e->getMessage();
            $result = 2;
        }
        
        echo $message . "\n";
        return $result;
    }

    /**
     * nagios monitoring for tine 2.0 active users
     *
     * @return number
     *
     * @todo allow to configure timeslot / currently the active users of the last month are returned
     */
    public function monitoringActiveUsers()
    {
        $message = 'ACTIVE USERS';
        $result  = 0;

        try {
            $userCount = Tinebase_User::getInstance()->getActiveUserCount();
            $valueString = ' | count=' . $userCount . ';;;;';
            $message .= ' OK' . $valueString;
        } catch (Exception $e) {
            $message .= ' FAIL: ' . $e->getMessage();
            $result = 2;
        }

        echo $message . "\n";
        return $result;
    }

    /**
     * undo changes to records defined by certain criteria (user, date, fields, ...)
     * 
     * example: $ php tine20.php --username pschuele --method Tinebase.undo -d 
     *   -- record_type=Addressbook_Model_Contact modification_time=2013-05-08 modification_account=3263
     * 
     * @param Zend_Console_Getopt $opts
     */
    public function undo(Zend_Console_Getopt $opts)
    {
        if (! $this->_checkAdminRight()) {
            return FALSE;
        }
        
        $data = $this->_parseArgs($opts, array('modification_time'));
        
        // build filter from params
        $filterData = array();
        $allowedFilters = array(
            'record_type',
            'modification_time',
            'modification_account',
            'record_id',
            'modified_attribute'
        );
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFilters)) {
                $operator = ($key === 'modification_time') ? 'within' : 'equals';
                $filterData[] = array('field' => $key, 'operator' => $operator, 'value' => $value);
            }
        }
        $filter = new Tinebase_Model_ModificationLogFilter($filterData);
        
        $dryrun = $opts->d;
        $overwrite = (isset($data['overwrite']) && $data['overwrite']) ? TRUE : FALSE;
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->undo($filter, $overwrite, $dryrun);
        
        if (! $dryrun) {
            echo 'Reverted ' . $result['totalcount'] . " change(s)\n";
        } else {
            echo "Dry run\n";
            echo 'Would revert ' . $result['totalcount'] . " change(s):\n";
            foreach ($result['undoneModlogs'] as $modlog) {
                echo 'id ' . $modlog->record_id . ' [' . $modlog->modified_attribute . ']: ' . $modlog->new_value . ' -> ' . $modlog->old_value . "\n";
            }
        }
        echo 'Failcount: ' . $result['failcount'] . "\n";
        return 0;
    }
    
    /**
     * creates demo data for all applications
     * accepts same arguments as Tinebase_Frontend_Cli_Abstract::createDemoData
     * and the additional argument "skipAdmin" to force no user/group/role creation
     * 
     * @param Zend_Console_Getopt $_opts
     */
    public function createAllDemoData($_opts)
    {
        if (! $this->_checkAdminRight()) {
            return FALSE;
        }
        
        // fetch all applications and check if required are installed, otherwise remove app from array
        $applications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED)->name;
        foreach($applications as $appName) {
            echo 'Searching for DemoData in application "' . $appName . '"...' . PHP_EOL;
            $className = $appName.'_Setup_DemoData';
            if (class_exists($className)) {
                echo 'DemoData in application "' . $appName . '" found!' . PHP_EOL;
                $required = $className::getRequiredApplications();
                foreach($required as $requiredApplication) {
                    if (! Tinebase_Helper::in_array_case($applications, $requiredApplication)) {
                        echo 'Creating DemoData for Application ' . $appName . ' is impossible, because application "' . $requiredApplication . '" is not installed.' . PHP_EOL;
                        continue 2;
                    }
                }
                $this->_applicationsToWorkOn[$appName] = array('appName' => $appName, 'required' => $required);
            } else {
                echo 'DemoData in application "' . $appName . '" not found.' . PHP_EOL . PHP_EOL;
            }
        }
        
        foreach($this->_applicationsToWorkOn as $app => $cfg) {
            $this->_createDemoDataRecursive($app, $cfg, $_opts);
        }
    }
    
    /**
     * creates demo data and calls itself if there are required apps
     * 
     * @param string $app
     * @param array $cfg
     * @param Zend_Console_Getopt $opts
     */
    protected function _createDemoDataRecursive($app, $cfg, $opts)
    {
        if (isset($cfg['required']) && is_array($cfg['required'])) {
            foreach($cfg['required'] as $requiredApp) {
                $this->_createDemoDataRecursive($requiredApp, $this->_applicationsToWorkOn[$requiredApp], $opts);
            }
        }
        
        $className = $app . '_Frontend_Cli';
        
        $classNameDD = $app . '_Setup_DemoData';
        
        if (class_exists($className)) {
            if (! $classNameDD::hasBeenRun()) {
                echo 'Creating DemoData in application "' . $app . '"...' . PHP_EOL;
                $class = new $className();
                $class->createDemoData($opts, FALSE);
            } else {
                echo 'DemoData for ' . $app . ' has been run already, skipping...' . PHP_EOL;
            }
        } else {
            echo 'Could not found ' . $className . ', so DemoData for application "' . $app . '" could not be created!';
        }
    }
    
    /**
     * clears deleted files from filesystem + database
     * @return boolean
     */
    public function clearDeletedFiles()
    {
        if (! $this->_checkAdminRight()) {
            return FALSE;
        }
        
        $this->_addOutputLogWriter();
        
        Tinebase_FileSystem::getInstance()->clearDeletedFiles();
    }
    
    /**
     * repair a table
     * 
     * @param Zend_Console_Getopt $opts
     * 
     * @todo add more tables
     */
    public function repairTable($opts)
    {
        if (! $this->_checkAdminRight()) {
            return FALSE;
        }
        
        $this->_addOutputLogWriter();
        
        $data = $this->_parseArgs($opts, array('table'));
        
        switch ($data['table']) {
            case 'importexport_definition':
                Tinebase_ImportExportDefinition::getInstance()->repairTable();
                $result = 0;
                break;
            default:
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' No repair script found for ' . $data['table']);
                $result = 1;
        }
        
        exit($result);
    }
    
    /**
     * repairs container names
     * 
     * @param Zend_Console_Getopt $opts
     */
    public function repairContainerName($opts)
    {
        if (! $this->_checkAdminRight()) {
            return FALSE;
        }
        $dryrun = $opts->d;
        
        $this->_addOutputLogWriter();
        $args = $this->_parseArgs($opts);
        
        $containersWithBadNames = Tinebase_Container::getInstance()->getContainersWithBadNames();
        
        $locale = Tinebase_Translation::getLocale((isset($args['locale']) ?$args['locale'] : 'auto'));

        if ($dryrun) {
            print_r($containersWithBadNames->toArray());
            echo "Using Locale " . $locale . "\n";
        }
        
        $appContainerNames = array(
            'Calendar' => 'calendar',
            'Tasks'    => 'tasks',
            'Addressbook'    => 'addressbook',
        );
        
        foreach ($containersWithBadNames as $container) {
            if (empty($container->owner_id)) {
                if ($dryrun) {
                    echo "Don't rename shared container " . $container->id . "\n";
                }
                continue;
            }
            $app = Tinebase_Application::getInstance()->getApplicationById($container->application_id);
            $appContainerName = isset($appContainerNames[$app->name]) ? $appContainerNames[$app->name] : "container";
            $translation = Tinebase_Translation::getTranslation($app->name, $locale);
            $account = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $container->owner_id);
            $newName = $newBaseName = sprintf($translation->_("%s's personal " . $appContainerName), $account->accountFullName);
            
            $count = 1;
            do {
                try {
                    Tinebase_Container::getInstance()->getContainerByName($app->name, $newName, Tinebase_Model_Container::TYPE_PERSONAL, $container->owner_id);
                    $found = true;
                    $newName = $newBaseName . ' ' . ++$count;
                } catch (Tinebase_Exception_NotFound $tenf) {
                    $found = false;
                }
                
            } while ($found);
            if ($dryrun) {
                echo "Rename container id " . $container->id . ' to ' . $newName . "\n";
            } else {
                
                $container->name = $newName;
                Tinebase_Container::getInstance()->update($container);
            }
        }
        
        $result = 0;
        exit($result);
    }
    
    /**
     * transfer relations
     * 
     * @param Zend_Console_Getopt $opts
     */
    public function transferRelations($opts)
    {
        if (! $this->_checkAdminRight()) {
            return FALSE;
        }
        
        $this->_addOutputLogWriter();
        
        try {
            $args = $this->_parseArgs($opts, array('oldId', 'newId', 'model'));
        } catch (Tinebase_Exception_InvalidArgument $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Parameters "oldId", "newId" and "model" are required!');
            }
            exit(1);
        }
        
        $skippedEntries = Tinebase_Relations::getInstance()->transferRelations($args['oldId'], $args['newId'], $args['model']);

        if (! empty($skippedEntries) && Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . count($skippedEntries) . ' entries has been skipped:');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' The operation has been terminated successfully.');
        }
    }
}
