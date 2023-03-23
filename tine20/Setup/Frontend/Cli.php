<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        add ext check again
 */

/**
 * cli server
 *
 * This class handles all requests from cli scripts
 *
 * @package     Tinebase
 */
class Setup_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_appname = 'Setup';

    /**
     * @var Tinebase_Application
     */
    protected $_tinebaseApplication;

    public function __construct($tinebaseApplication = null)
    {
        if ($tinebaseApplication === null) {
            $this->_tinebaseApplication = Tinebase_Application::getInstance();
        } else {
            $this->_tinebaseApplication = $tinebaseApplication;
        }
    }

    /**
     * authentication
     *
     * @param string $_username
     * @param string $_password
     * 
     * @return boolean
     */
    public function authenticate($_username, $_password)
    {
        return false;
    }
    
    /**
     * handle request (call -ApplicationName-_Cli.-MethodName- or -ApplicationName-_Cli.getHelp)
     *
     * @param Zend_Console_Getopt $_opts
     * @param boolean $exitAfterHandle
     * @return int
     */
    public function handle(Zend_Console_Getopt $_opts, $exitAfterHandle = true)
    {
        $time_start = microtime(true);

        // always set real setup user if Tinebase is installed
        if (Setup_Controller::getInstance()->isInstalled('Tinebase')) {
            try {
                // TODO remove this if no update occure from < 12.7
                Tinebase_Group_Sql::doJoinXProps(false);
                $setupUser = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
                $setupUser = Tinebase_User::SYSTEM_USER_SETUP;
            }
            Tinebase_Group_Sql::doJoinXProps(true);
            if ($setupUser && ! Setup_Core::getUser() instanceof Tinebase_Model_User) {
                Setup_Core::set(Tinebase_Core::USER, $setupUser);
            }
        } else {
            Setup_Core::set(Setup_Core::USER, Tinebase_User::SYSTEM_USER_SETUP);
        }

        $this->_setLocale($_opts);

        $result = 0;
        if (isset($_opts->install)) {
            $result = $this->_install($_opts);
        } elseif(isset($_opts->update)) {
            $result = $this->_update($_opts);
        } elseif(isset($_opts->update_needed)) {
            $result = $this->_updateNeeded($_opts);
        } elseif(isset($_opts->uninstall)) {
            $this->_uninstall($_opts);
        } elseif(isset($_opts->install_dump)) {
            $this->_installDump($_opts);
        } elseif(isset($_opts->maintenance_mode)) {
            $this->_maintenanceMode($_opts);
        } elseif(isset($_opts->list)) {
            $result = $this->_listInstalled();
        } elseif(isset($_opts->sync_accounts_from_ldap)) {
            $this->_importAccounts($_opts);
        } elseif(isset($_opts->updateAllAccountsWithAccountEmail)) {
            $this->_updateAllAccountsWithAccountEmail($_opts);
        } elseif(isset($_opts->sync_passwords_from_ldap)) {
            $this->_syncPasswords($_opts);
        } elseif(isset($_opts->egw14import)) {
            $this->_egw14Import($_opts);
        } elseif(isset($_opts->check_requirements)) {
            $result = $this->_checkRequirements();
        } elseif(isset($_opts->setconfig)) {
            $this->_setConfig($_opts);
        } elseif(isset($_opts->clear_cache)) {
            $this->_clearCache($_opts);
        } elseif(isset($_opts->clear_cache_dir)) {
            $this->_clearCacheDir($_opts);
        } elseif(isset($_opts->create_admin)) {
            $this->_createAdminUser($_opts);
        } elseif(isset($_opts->getconfig)) {
            $this->_getConfig($_opts);
        } elseif(isset($_opts->reset_demodata)) {
            $this->_resetDemodata($_opts);
        } elseif(isset($_opts->updateAllImportExportDefinitions)) {
            $this->_updateAllImportExportDefinitions($_opts);
        } elseif(isset($_opts->backup)) {
            $this->_backup($_opts);
        } elseif(isset($_opts->restore)) {
            $this->_restore($_opts);
        } elseif(isset($_opts->compare)) {
            $this->_compare($_opts);
        } elseif(isset($_opts->mysql)) {
            $this->_mysqlClient($_opts);
        } elseif(isset($_opts->setpassword)) {
            $this->_setPassword($_opts);
        } elseif(isset($_opts->pgsqlMigration)) {
            $this->_pgsqlMigration($_opts);
        } elseif(isset($_opts->upgradeMysql564)) {
            $this->_upgradeMysql564();
        } elseif(isset($_opts->migrateUtf8mb4)) {
            $this->_migrateUtf8mb4();
        } elseif(isset($_opts->config_from_env)) {
            $this->_configFromEnv();
        } elseif(isset($_opts->is_installed)) {
            $result = $this->_isInstalled();
        } elseif(isset($_opts->add_auth_token)) {
            $this->_addAuthToken($_opts);
        }

        Tinebase_Log::logUsageAndMethod('setup.php', $time_start, 'Setup.' . implode(',', $_opts->getOptions()));
        
        if ($exitAfterHandle) {
            exit($result);
        }

        return $result;
    }

    protected function _setLocale(Zend_Console_Getopt $opts)
    {
        $args = $this->_parseRemainingArgs($opts->getRemainingArgs());
        $lang = $opts->lang ?: ($args['lang'] ?? getenv('LANGUAGE'));

        if ($lang) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' Setting locale to ' . $lang);
            Tinebase_Core::setLocale($lang);
        }
    }
    
    /**
     * install new applications
     *
     * @param Zend_Console_Getopt $_opts
     * @return integer
     */
    protected function _install(Zend_Console_Getopt $_opts)
    {
        $controller = Setup_Controller::getInstance();

        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        if (isset($options['lang'])) {
            Tinebase_Core::setLocale($options['lang']);
        }
        
        if ($_opts->install === true) {
            if (Setup_Controller::getInstance()->isInstalled('Tinebase')) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . ' Already installed ... nothing to do.');
                return 0;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' Installing ...');

            $applications = $controller->getInstallableApplications();
            $applications = array_keys($applications);
        } else {
            $applications = array();
            $applicationNames = explode(',', $_opts->install);
            if (count($applicationNames) === 1 && strtolower($applicationNames[0]) === 'all') {
                $applications = $controller->getInstallableApplications();
                $applications = array_keys($applications);
            } else {
                foreach ($applicationNames as $applicationName) {
                    $applicationName = ucfirst(trim($applicationName));
                    try {
                        $controller->getSetupXml($applicationName);
                        if (Setup_Controller::getInstance()->isInstalled('Tinebase') &&
                            Setup_Controller::getInstance()->isInstalled($applicationName)) {
                            echo "Application $applicationName is already installed.\n";
                        } else {
                            $applications[] = $applicationName;
                        }
                    } catch (Setup_Exception_NotFound $e) {
                        echo "Application $applicationName not found! Skipped...\n";
                    }
                }
            }
        }

        if (isset($options['skipApps'])) {
            if (!is_array($options['skipApps'])) {
                $options['skipApps'] = explode(',', $options['skipApps']);
            }
            $skipApps = $options['skipApps'];
            $applications = array_diff($applications, $skipApps);
        }

        $this->_promptRemainingOptions($applications, $options);
        $appCount = $controller->installApplications($applications, $options);
        
        if ((isset($options['acceptedTermsVersion']) || array_key_exists('acceptedTermsVersion', $options))) {
            Setup_Controller::getInstance()->saveAcceptedTerms($options['acceptedTermsVersion']);
        }
        
        echo "Successfully installed " . $appCount . " applications.\n";
        return 0;
    }

    protected function _pgsqlMigration(Zend_Console_Getopt $_opts)
    {
        // TODO ask for cleanup? make cleanup?
        // TODO check maintenance mode, its needs to be on!
        // TODO check action queue is empty
        // known issues:
        // path!
        // [r?]trim unique keys, if there was something trimmed, remember, second+ time add _ or something

        $noBackupTables = Setup_Controller::getInstance()->getBackupStructureOnlyTables();

        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        if (!isset($options['mysqlConfigFile'])) {
            echo 'option mysqlConfigFile is mandatory';
            return;
        }

        // get pgsql DB:
        if (!($pgsqlDb = Setup_Core::getDb()) instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            echo 'pgsql migration only works for pgsql installations';
            return;
        }

        // reset DB:
        $mysqlConfigFile = $options['mysqlConfigFile'];
        if (!is_file($mysqlConfigFile)) {
            echo $mysqlConfigFile . ' is not a readable file (--mysqlConfigFile option)';
            return;
        }
        if (!($dbConfig = include $mysqlConfigFile) || !is_array($dbConfig)) {
            echo 'bad mysql config file: ' . $mysqlConfigFile;
            return;
        }

        if (isset($dbConfig['password']) && !empty($dbConfig['password'])) {
            Setup_Core::getLogger()->getFormatter()->addReplacement($dbConfig['password']);
        }
        if (!($mysqlDB = Tinebase_Core::createAndConfigureDbAdapter($dbConfig)) instanceof  Zend_Db_Adapter_Pdo_Mysql) {
            $dbConfig['password'] = '*****';
            echo 'provided database config is not a working mysql config: ' . print_r($dbConfig, true);
            return;
        }
        // place table prefix into the concrete adapter
        $mysqlDB->table_prefix = $pgsqlDb->table_prefix;

        // set the mysql DB as our current DB
        Zend_Db_Table_Abstract::setDefaultAdapter($mysqlDB);
        Setup_Core::set(Setup_Core::DB, $mysqlDB);

        // some cache busting
        Setup_Core::set(Setup_Core::CONFIG, null);
        Tinebase_Config::destroyInstance();
        Tinebase_Application::getInstance()->clearCache();
        Tinebase_Application::destroyInstance();
        Tinebase_Container::getInstance()->resetClassCache();
        Tinebase_Container::destroyInstance();
        Setup_Controller::destroyInstance();
        Setup_Backend_Factory::clearCache();
        Tinebase_User::destroyInstance();
        Setup_Core::set(Setup_Core::USER, 'setupuser');
        Addressbook_Backend_Factory::clearCache();
        Addressbook_Controller_Contact::destroyInstance();
        $dbConfig['driver'] = 'pdo_mysql';
        $dbConfig['user']   = $dbConfig['username'];
        Setup_SchemaTool::setDBParams($dbConfig);

        $newOpts = new Zend_Console_Getopt(['install' => []], [
            '--install', '--', 'acceptedTermsVersion=1', 'adminLoginName=a', 'adminPassword=b'
        ]);
        $this->_install($newOpts);

        $blackListedTables = [];
        $mysqlTables = $mysqlDB->query('SHOW TABLES')->fetchAll(Zend_Db::FETCH_COLUMN, 0);
        $pgsqlTables = $pgsqlDb->query('SELECT table_name FROM information_schema.tables WHERE table_schema = '
            . '\'public\' AND table_type= \'BASE TABLE\'')->fetchAll(Zend_Db::FETCH_COLUMN, 0);

        // set foreign key checks off
        $mysqlDB->query('SET foreign_key_checks = 0');
        $mysqlDB->query('SET unique_checks = 0');
        $mysqlDB->query('SET autocommit = 0');

        // truncate
        foreach ($mysqlTables as $table) {
            $mysqlDB->query('TRUNCATE TABLE ' . $mysqlDB->quoteIdentifier($table));
        }

        $mysqlDB->query('COMMIT');
        // set foreign key checks off
        $mysqlDB->query('SET foreign_key_checks = 0');
        $mysqlDB->query('SET unique_checks = 0');
        $mysqlDB->query('SET autocommit = 0');

        foreach (array_diff($mysqlTables, $blackListedTables) as $table) {
            if (in_array($table, $noBackupTables)) {
                continue;
            }
            if (!in_array($table, $pgsqlTables)) {
                continue;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Migrating table ' . $table . ' ...');

            $start = 0;
            $limit = 50;
            $tableDscr = Tinebase_Db_Table::getTableDescriptionFromCache($table);
            $primaries = [];
            $columns = [];
            $selectColumns = [];
            foreach ($tableDscr as $col => $desc) {
                if ($desc['PRIMARY']) {
                    $primaries[] = $col;
                }
                $columns[] = $mysqlDB->quoteIdentifier($col);
                $selectColumns[] = $col;
            }
            $insertQuery = 'INSERT INTO ' . $mysqlDB->quoteIdentifier($table) . ' (' . join(', ', $columns) .
                ') VALUES ';
            $select = $pgsqlDb->select()->from($table, $selectColumns)->order($primaries);

            $rowcount = 0;
            while (true) {
                $select->limit($limit, $start);
                if (empty($data = $select->query()->fetchAll(Zend_Db::FETCH_NUM))) {
                    break;
                }
                $start += $limit;
                $first = true;
                $query = $insertQuery;
                foreach ($data as $idx => $row) {
                    $query .= ($first === true ? '' : '), ') . '(';
                    $firstRow = true;
                    foreach ($row as $value) {
                        $query .= ($firstRow === true ? '' : ', ') .
                            (null === $value ? 'null' : $mysqlDB->quote($value));
                        $firstRow = false;
                    }
                    $first = false;
                    $rowcount++;
                }

                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' ' . $query);

                $mysqlDB->query($query . ')');
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' ... done. Migrated ' . $rowcount . ' rows.');
        }

        $mysqlDB->query('COMMIT');
        $mysqlDB->query('SET foreign_key_checks = 1');
        $mysqlDB->query('SET unique_checks = 1');
    }

    protected function _upgradeMysql564()
    {
        echo 'starting upgrade ...' . PHP_EOL;

        $failures = Setup_Controller::getInstance()->upgradeMysql564();
        if (count($failures) > 0) {
            echo PHP_EOL . 'failures:' . PHP_EOL . join(PHP_EOL, $failures);
        }

        echo PHP_EOL . 'done' . PHP_EOL . PHP_EOL;
    }

    /**
     * prompt remaining options
     * 
     * @param array $_applications
     * @param array $_options
     * @return void
     * 
     * @todo add required version server side
     */
    protected function _promptRemainingOptions($_applications, &$_options)
    {
        if (in_array('Tinebase', $_applications)) {
            
            if (! isset($_options['acceptedTermsVersion'])) {
                fwrite(STDOUT, PHP_EOL . file_get_contents(dirname(dirname(dirname(__FILE__))) . '/LICENSE' ));
                $licenseAnswer = Tinebase_Server_Cli::promptInput('I have read the license agreement and accept it (type "yes" to accept)');
                
                
                fwrite(STDOUT, PHP_EOL . file_get_contents(dirname(dirname(dirname(__FILE__))) . '/PRIVACY' ));
                $privacyAnswer = Tinebase_Server_Cli::promptInput('I have read the privacy agreement and accept it (type "yes" to accept)');
            
                if (! (strtoupper($licenseAnswer) == 'YES' && strtoupper($privacyAnswer) == 'YES')) {
                    echo "error: you need to accept the terms! exiting \n";
                    exit (1);
                }
                
                $_options['acceptedTermsVersion'] = 1;
            }
            
            
            // initial username
            if (! isset($_options['adminLoginName'])) {
                $_options['adminLoginName'] = Tinebase_Server_Cli::promptInput('Inital Admin Users Username');
                if (! $_options['adminLoginName']) {
                    echo "error: username must be given! exiting \n";
                    exit (1);
                }
            }
            
            // initial password / can be empty => will trigger password change dialogue
            if (! array_key_exists('adminPassword', $_options)) {
                $_options['adminPassword'] = $this->_promptPassword();
            }
        }
    }
    
    /**
     * prompt password
     * 
     * @return string
     */
    protected function _promptPassword()
    {
        $password1 = Tinebase_Server_Cli::promptInput('Admin user password', TRUE);
        if (! $password1) {
            echo "Error: Password must not be empty! Exiting ... \n";
            exit (1);
        }
        $password2 = Tinebase_Server_Cli::promptInput('Confirm password', TRUE);
        if ($password1 !== $password2) {
            echo "Error: Passwords do not match! Exiting ... \n";
            exit (1);
        }
        
        return $password1;
    }

    /**
     * set system user password
     *
     * @param Zend_Console_Getopt $_opts
     * @return integer
     */
    protected function _setPassword(Zend_Console_Getopt $_opts)
    {
        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        if (empty($options['username']) || empty($options['password'])) {
            echo "username and password parameters required\n";
            return 2;
        }

        $username = $options['username'];
        $password = $options['password'];
        if (! in_array($username, Tinebase_User::getSystemUsernames(), /* strict */ true)) {
            echo "it's only allowed to set system user passwords here\n";
            return 2;
        }

        $user = Tinebase_User::getInstance()->getUserByLoginName($username);
        Tinebase_User::getInstance()->setPassword($user, $password);
        return 0;
    }

    /**
     * update existing applications
     *
     * @param Zend_Console_Getopt $_opts
     * @return integer
     */
    protected function _update(Zend_Console_Getopt $_opts)
    {
        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        $result = Setup_Controller::getInstance()->updateApplications(null, [
            'strict' => isset($options['strict']) && $options['strict'],
            'skipQueueCheck' => isset($options['skipQueueCheck']) && $options['skipQueueCheck'],
            'rerun' => isset($options['rerun']) ? explode(',', $options['rerun']) : []
        ]);
        echo "Updated " . $result['updated'] . " application(s).\n";
        if ($_opts->v && count($result['updates']) > 0) {
            print_r($result['updates']);
        }
        return 0;
    }

    /**
     * @param Zend_Console_Getopt $_opts
     * @return int
     */
    protected function _updateNeeded(Zend_Console_Getopt $_opts)
    {
        $result = Setup_Controller::getInstance()->updateNeeded();
        if ($result) {
            echo "Update required\n";
            return 1;
        }
        return 0;
    }

    /**
     * uninstall applications
     *
     * @param Zend_Console_Getopt $_opts
     * @return bool
     */
    protected function _uninstall(Zend_Console_Getopt $_opts): bool
    {
        $controller = Setup_Controller::getInstance();
        
        if ($_opts->uninstall === true) {
            $backend = Setup_Backend_Factory::factory();
            if (! $backend->tableExists('applications')) {
                return 0;
            }
            $applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
        } else {
            $applications = new Tinebase_Record_RecordSet('Tinebase_Model_Application');
            $applicationNames = explode(',', $_opts->uninstall);
            foreach($applicationNames as $applicationName) {
                $applicationName = ucfirst(trim($applicationName));
                try {
                    $application = Tinebase_Application::getInstance()->getApplicationByName($applicationName);
                    $applications->addRecord($application);
                } catch (Tinebase_Exception_NotFound $e) {
                }
            }
        }
        
        if (in_array('Tinebase', $applications->name) && $_opts->removemailaccounts) {
            $this->_removemailaccounts();
        }
        
        $uninstallCount = $controller->uninstallApplications($applications->name);
        
        echo "Successfully uninstalled " . $uninstallCount . " applications.\n";
        return 0;
    }

    protected function _removemailaccounts()
    {
        try {
            if (Tinebase_EmailUser::manages(Tinebase_Config::SMTP)) {
                echo "Deleting SMTP mailaccounts...\n";
                $smtpBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
                $smtpBackend->deleteAllEmailUsers();
            }
        } catch (Tinebase_Exception_Backend $e) {
            Tinebase_Exception::log($e);
        }

        try {
            if (Tinebase_EmailUser::manages(Tinebase_Config::IMAP)) {
                echo "Deleting IMAP mailaccounts...\n";
                $imapBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
                $imapBackend->deleteAllEmailUsers();
            }
        } catch (Tinebase_Exception_Backend $e) {
            Tinebase_Exception::log($e);
        }
    }
    
    /**
     * reinstall applications
     * and reset Demodata
     * php setup.php --reset_demodata USERNAME
     * 
     * @param Zend_Console_Getopt $_opts
     */
    protected function _resetDemodata(Zend_Console_Getopt $_opts)
    {
        $controller = Setup_Controller::getInstance();
        $userController = Admin_Controller_User::getInstance();
        $containerController = Tinebase_Container::getInstance();
        $cli = new Tinebase_Frontend_Cli();
        
        //Don't reset this applications
        $fixedApplications = array('Tinebase', 'Admin', 'Addressbook');
        
        //Log in
        $opts = $_opts->getRemainingArgs();
        $username = $opts[0];
        if (empty($username)) {
            echo "Username is missing!\n";
            exit;
        }
        $user = Tinebase_User::getInstance()->getUserByLoginName($username);
        Tinebase_Core::set(Tinebase_Core::USER, $user);
        
        //get all applications and remove some
        $applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
        
        foreach ($applications as $key => &$application) {
            if (in_array($application, $fixedApplications)) {
                unset($applications[$key]);
            }
        }
        
        //get set rights
        $userRoleName = Tinebase_Config::getInstance()->get(Tinebase_Config::DEFAULT_USER_ROLE_NAME);
        $users = Tinebase_Acl_Roles::getInstance()->getRoleByName($userRoleName);
        $rights = Tinebase_Acl_Roles::getInstance()->getRoleRights($users->getId());
        
        //Uninstall Applications
        try {
            $controller->uninstallApplications($applications->name);
            echo "Successfully uninstalled " . count($applications) . " applications.\n";
        } catch (Tinebase_Exception_NotFound $e) {
        } finally {
            Setup_SchemaTool::resetUninstalledTables();
        }
        //Install Applications
        try {
            $controller->installApplications($applications->name);
            echo "Successfully installed " . count($applications) . " applications.\n";
        } catch (Tinebase_Exception_NotFound $e) {
        }
        
        //set rights
        foreach ($applications as $app) {
            $newApplicationId = Tinebase_Application::getInstance()->getApplicationByName($app->name)->getId();
            
            foreach ($rights as &$right) {
                if ($right['application_id'] == $app->id) {
                    $right['application_id'] = $newApplicationId;
                }
            }
        }
        
        Tinebase_Acl_Roles::getInstance()->setRoleRights($users->getId(), $rights);
        echo "Successfully restored user rights.\n";
        
        //Clean up addressbooks
        $internalContacts = $userController->getDefaultInternalAddressbook();
        $containers = $containerController->getAll();
        foreach ($containers as $key => &$container) {
            if ($container->id == $internalContacts) {
                // Do nothing
            } else {
                try {
                    $containerController->deleteContainer($container, true);
                } catch (Exception $e) {
                }
            }
        }
        unset($containers);
        echo "Successfully cleand up containers.\n";
        
        //remove state
        $db = Tinebase_Core::getDb();
        $statement = "TRUNCATE TABLE " . $db->quoteIdentifier(SQL_TABLE_PREFIX . 'state');
        $db->query($statement);
        echo "Successfully truncated state table.\n";
        
        //Get Demodata
        $cli->createAllDemoData($_opts);
        
        //clear Cache
        Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_ALL);
        echo "Successfully cleared Cache.\n";
        
        echo "Every thing done!\n";
    }

    /**
     * Update Import Export Definitions for all applications
     *
     * @param Zend_Console_Getopt $_opts
     */
    protected function _updateAllImportExportDefinitions(Zend_Console_Getopt $_opts)
    {
        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        if (isset($options['onlyDefinitions'])) {
            $onlyDefinitions = true;
        } else {
            $onlyDefinitions = false;
        }
        //get all applications
        $applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
        foreach ($applications as $application) {
            Setup_Controller::getInstance()->createImportExportDefinitions($application, $onlyDefinitions);
            echo "Update definitions for " . $application->name . "...\n";
        }
    }
    
    /**
     * list installed apps
     *
     * TODO add --version command, too
     */
    protected function _listInstalled()
    {
        try {
            $applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
        } catch (Zend_Db_Statement_Exception $e) {
            echo "No applications installed\n";
            return 1;
        }

        echo 'Version: "' . TINE20_CODENAME . '" ' . TINE20_PACKAGESTRING . ' (Build: ' . TINE20_BUILDTYPE . ")\n";
        echo "Currently installed applications:\n";
        $applications->sort('name');
        foreach ($applications as $application) {
            echo "* " . $application->name . " (Version: " . $application->version . ") - " . $application->status . "\n";
        }
        
        return 0;
    }
    
    /**
     * import accounts from ldap
     *
     * @param Zend_Console_Getopt $_opts
     */
    protected function _importAccounts(Zend_Console_Getopt $_opts)
    {
        // disable timelimit during import of user accounts
        Setup_Core::setExecutionLifeTime(0);
        
        // import groups
        if (! $_opts->onlyusers) {
            Tinebase_Group::syncGroups();
        }
        
        // import users
        $options = array('syncContactData' => TRUE);
        if ($_opts->dbmailldap) {
            $options['ldapplugins'] = array(
                new Tinebase_EmailUser_Imap_LdapDbmailSchema(),
                new Tinebase_EmailUser_Smtp_LdapDbmailSchema()
            );
        }

        if ($_opts->syncdeletedusers) {
            $options['deleteUsers'] = true;
        }
        if ($_opts->syncaccountstatus) {
            $options['syncAccountStatus'] = true;
        }
        if ($_opts->syncontactphoto) {
            $options['syncContactPhoto'] = true;
        }

        Tinebase_User::syncUsers($options);
    }

    /**
     * create/update email users with current account
     *  USAGE: php setup.php --updateAllAccountsWithAccountEmail -- [fromInstance=master.mytine20.com createEmail=1 domain=mydomain.org]
     *
     * @param Zend_Console_Getopt $_opts
     * @return int
     */
    protected function _updateAllAccountsWithAccountEmail(Zend_Console_Getopt $_opts)
    {
        $data = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        if (isset($data['fromInstance'])) {
            // fetch all accounts from fromInstance and write to configured instance
            $imap = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
            $imap->copyFromInstance($data['fromInstance']);
        }

        $allowedDomains = Tinebase_EmailUser::getAllowedDomains();
        $userController = Admin_Controller_User::getInstance();
        $emailUser = Tinebase_EmailUser::getInstance();
        /** @var Tinebase_Model_FullUser $user */
        foreach ($userController->searchFullUsers('') as $user) {
            $emailUser->inspectGetUserByProperty($user);

            if (empty($user->accountEmailAddress) && isset($data['createEmail']) && $data['createEmail']) {
                $config = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP)->toArray();
                // TODO allow to set other domains via args?
                if (! empty($config['primarydomain'])) {
                    $mail = $user->accountLoginName . '@' . $config['primarydomain'];
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Setting new email address for user: ' . $mail);
                    $user->accountEmailAddress = $mail;
                }
            }

            if (! empty($user->accountEmailAddress)) {
                list($userPart, $domainPart) = explode('@', $user->accountEmailAddress);
                if (isset($data['domain']) && $domainPart !== $data['domain']) {
                    // skip user because not in given domain
                    continue;
                }
                // TODO allow to skip this?
                if (count($allowedDomains) > 0 && ! in_array($domainPart, $allowedDomains)) {
                    $newEmailAddress = $userPart . '@' . $allowedDomains[0];
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Setting new email address for user to comply with allowed domains: ' . $newEmailAddress);
                    $user->accountEmailAddress = $newEmailAddress;
                }
                try {
                    $userController->update($user);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                        . ' ' . $tenf);
                }

            }
        }

        return 0;
    }

    /**
     * sync ldap passwords
     * 
     * @param Zend_Console_Getopt $_opts
     */
    protected function _syncPasswords(Zend_Console_Getopt $_opts)
    {
        Tinebase_User::syncLdapPasswords();
    }
    
    /**
     * import from egw14
     * 
     * @param Zend_Console_Getopt $_opts
     */
    protected function _egw14Import(Zend_Console_Getopt $_opts)
    {
        $args = $_opts->getRemainingArgs();
        
        if (count($args) < 1 || ! is_readable($args[0])) {
            echo "can not open config file \n";
            echo "see tine20.org/wiki/EGW_Migration_Howto for details \n\n";
            echo "usage: ./setup.php --egw14import /path/to/config.ini (see Tinebase/Setup/Import/Egw14/config.ini)\n\n";
            exit(1);
        }
        
        try {
            $config = new Zend_Config(array(), TRUE);
            $config->merge(new Zend_Config_Ini($args[0]));
            $config = $config->merge($config->all);
        } catch (Zend_Config_Exception $e) {
            fwrite(STDERR, "Error while parsing config file($args[0]) " .  $e->getMessage() . PHP_EOL);
            exit(1);
        }
        
        $writer = new Zend_Log_Writer_Stream('php://output');
        $logger = new Zend_Log($writer);
        
        $filter = new Zend_Log_Filter_Priority((int) $config->loglevel);
        $logger->addFilter($filter);
        
        $importer = new Tinebase_Setup_Import_Egw14($config, $logger);
        $importer->import();
    }
    
    /**
     * do the environment check
     *
     * @return int
     */
    protected function _checkRequirements(): int
    {
        $results = Setup_Controller::getInstance()->checkRequirements();
        if ($results['success']) {
          echo "OK - All requirements are met\n";
        } else {
          echo "ERRORS - The following requirements are not met: \n";
        }
        foreach (['results', 'resultOptionalBinaries'] as $key) {
            foreach ($results[$key] as $result) {
                if (!empty($result['message'])) {
                    echo "- " . strip_tags($result['message']) . "\n";
                }
            }
        }
        return $results['success'] ? 0 : 1;
    }
    
    /**
     * set config
     * USAGE: php setup.php --setconfig -- configkey={{configkey}} configvalue={{configvalue}} [default=1]
     *  (default=1 removes the config from database to return to the default value)
     *
     * @param Zend_Console_Getopt $_opts
     * @return array
     */
    protected function _setConfig(Zend_Console_Getopt $_opts)
    {
        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        $errors = array();

        if (empty($options['configkey'])) {
            $errors[] = 'Missing argument: configkey';
        }

        if (isset($options['default']) && $options['default'] == 1) {
            $configValue = null;
        } else if (! isset($options['configvalue'])) {
            $errors[] = 'Missing argument: configvalue';
        } else {
            $configValue = self::parseConfigValue($options['configvalue']);
        }

        $configKey = (string)$options['configkey'];
        $applicationName = (isset($options['app'])) ? $options['app'] : 'Tinebase';

        if (! Tinebase_Application::getInstance()->isInstalled('Tinebase') || ! Tinebase_Application::getInstance()->isInstalled($applicationName)) {
            $errors[] = $applicationName . ' is not installed';
        }
        
        if (empty($errors)) {
           Setup_Controller::getInstance()->setConfigOption($configKey, $configValue, $applicationName);
           echo "OK - Updated configuration option $configKey for application $applicationName\n";
        } else {
            echo "ERRORS - The following errors occured: \n";
            foreach ($errors as $error) {
                echo "- " . $error . "\n";
            }
        }
    }
    
    /**
     * get config
     *
     */
    protected function _getConfig(Zend_Console_Getopt $_opts)
    {
        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        $applicationName = (isset($options['app'])) ? $options['app'] : 'Tinebase';

        $errors = array();
        if (! Tinebase_Application::getInstance()->isInstalled('Tinebase') || ! Tinebase_Application::getInstance()->isInstalled($applicationName)) {
            $errors[] = $applicationName . ' is not installed';
            $config = null;
        } else {
            $config = Tinebase_Config_Abstract::factory($applicationName);
        }

        if (! isset($options['configkey']) || empty($options['configkey'])) {
            $errors[] = 'Missing argument: configkey';
            if ($config) {
                $errors[] = 'Available config settings:';
                $errors[] = print_r($config::getProperties(), true);
            }
        } else {
            $configKey = (string)$options['configkey'];
        }
        
        if (empty($errors)) {
            $value = $config->get($configKey);
            $value = is_string($value) ? $value : Zend_Json::encode($value);
            echo $value . " \n";
        } else {
            echo "ERRORS - The following errors occured: \n";
            foreach ($errors as $error) {
                echo "- " . $error . "\n";
            }
        }
    }

    /**
     * clears all caches
     *
     * @param Zend_Console_Getopt $_opts
     */
    protected function _clearCache(Zend_Console_Getopt $_opts)
    {
        $cachesCleared = Setup_Controller::getInstance()->clearCache();
        if ($_opts->v) {
            echo "Caches cleared: " . print_r($cachesCleared, true) . "\n";
        } 
    }

    /**
     * clears cache directories
     * 
     * @param Zend_Console_Getopt $_opts
     */
    protected function _clearCacheDir(Zend_Console_Getopt $_opts)
    {
        Setup_Controller::getInstance()->clearCacheDir();
    }

    /**
     * create admin user / activate existing user / allow to reset password
     * 
     * @param Zend_Console_Getopt $_opts
     * 
     * @todo check role by rights and not by name
     * @todo replace echos with stdout logger
     */
    protected function _createAdminUser(Zend_Console_Getopt $_opts)
    {
        if (! Setup_Controller::getInstance()->isInstalled('Tinebase')) {
            die('Install Tinebase first.');
        }

        echo "Please enter a username. An existing user is reactivated and you can reset the password.\n";
        $username = strtolower(Tinebase_Server_Cli::promptInput('Username'));
        $tomorrow = Tinebase_DateTime::now()->addDay(1);
        
        try {
            $user = Tinebase_User::getInstance()->getFullUserByLoginName($username);
            echo "User $username already exists.\n";
            Tinebase_User::getInstance()->setStatus($user->getId(), Tinebase_Model_User::ACCOUNT_STATUS_ENABLED);
            echo "Activated admin user '$username'.\n";
            
            $expire = Tinebase_Server_Cli::promptInput('Should the admin user expire tomorrow (default: "no", "y" or "yes" for expiry)?');
            if ($expire === 'y' or $expire === 'yes') {
                Tinebase_User::getInstance()->setExpiryDate($user->getId(), $tomorrow);
                echo "User expires tomorrow at $tomorrow.\n";
            }
            
            $resetPw = Tinebase_Server_Cli::promptInput('Do you want to reset the password (default: "no", "y" or "yes" for reset)?');
            if ($resetPw === 'y' or $resetPw === 'yes') {
                $password = $this->_promptPassword();
                Tinebase_User::getInstance()->setPassword($user, $password);
                echo "User password has been reset.\n";
            }

            try {
                Tinebase_User::getInstance()->assertAdminGroupMembership($user);
                echo "Added user to default admin group\n";
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
                echo "Could not add user to default admin group: " . $e->getMessage();
            }

            $this->_checkAdminRole($user);
            
        } catch (Tinebase_Exception_NotFound $tenf) {
            // create new admin user that expires tomorrow
            $password = $this->_promptPassword();
            Tinebase_User::createInitialAccounts(array(
                'adminLoginName' => $username,
                'adminPassword'  => $password,
                'expires'        => $tomorrow,
            ), true);
            echo "Created new admin user '$username' that expires tomorrow.\n";
        }
    }


    /**
     * check admin role membership
     * 
     * @param Tinebase_Model_FullUser $user
     */
    protected function _checkAdminRole($user)
    {
        $roleMemberships = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($user->getId());
        $adminRoleFound = FALSE;
        // TODO allow to configure this / pass it as param
        $adminRoleName = 'admin role';

        foreach ($roleMemberships as $roleId) {
            $role = Tinebase_Acl_Roles::getInstance()->getRoleById($roleId);
            if ($role->name === $adminRoleName) {
                $adminRoleFound = TRUE;
                break;
            }
        }

        if (! $adminRoleFound || ! Tinebase_Acl_Roles::getInstance()->hasRight('Admin', $user->getId(), Tinebase_Acl_Rights::ADMIN)) {
            echo "Admin role not found for user " . $user->accountLoginName . ".\n";

            try {
                $adminRole = Tinebase_Acl_Roles::getInstance()->getRoleByName($adminRoleName);
            } catch (Tinebase_Exception_NotFound $tenf) {
                $adminRole = $this->_createNewAdminRoleForAdmin($adminRoleName);
            }

            Tinebase_Acl_Roles::getInstance()->setRoleMembers($adminRole->getId(), array(
                array(
                    'id'    => $user->getId(),
                    'type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, 
                )
            ));
            
            echo "Added user " . $user->accountLoginName . " to role '$adminRoleName''.\n";
            // @todo clear roles/groups cache
        }
    }

    protected function _createNewAdminRoleForAdmin($adminRoleName)
    {
        $adminRole = new Tinebase_Model_Role(array(
            'name'                  => $adminRoleName,
            'description'           => 'admin role for tine. this role has all rights per default.',
        ));

        $adminRole = Tinebase_Acl_Roles::getInstance()->createRole($adminRole);
        // add all rights for all apps
        $enabledApps = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
        $roleRights = array();
        foreach ($enabledApps as $application) {
            $allRights = Tinebase_Application::getInstance()->getAllRights($application->getId());
            foreach ($allRights as $right) {
                $roleRights[] = array(
                    'application_id' => $application->getId(),
                    'right'          => $right,
                );
            }
        }
        Tinebase_Acl_Roles::getInstance()->setRoleRights($adminRole->getId(), $roleRights);

        return $adminRole;
    }

    /**
     * @param Zend_Console_Getopt $_opts
     * @throws Exception
     */
    protected function _backup(Zend_Console_Getopt $_opts)
    {
        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        Setup_Controller::getInstance()->backup($options);
    }

    /**
     * @param Zend_Console_Getopt $_opts
     * @throws Exception
     */
    protected function _restore(Zend_Console_Getopt $_opts)
    {
        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        Setup_Controller::getInstance()->restore($options);
    }

    /**
     * set maintenance mode to state X
     *
     * @param Zend_Console_Getopt $_opts
     */
    protected function _maintenanceMode(Zend_Console_Getopt $_opts)
    {
        $options = $this->_parseArgs($_opts);

        // legacy, remove once ansible .... died?
        if (isset($options['state']) && !isset($options['mode'])) {
            if ('all' === $options['state']) {
                $options['mode'] = Tinebase_Config::MAINTENANCE_MODE_ON;
            } else {
                $options['mode'] = $options['state'];
            }
        }
        
        $modes = [
            Tinebase_Config::MAINTENANCE_MODE_ON,
            Tinebase_Config::MAINTENANCE_MODE_OFF,
        ];
        if (!in_array($options['mode'] ?? null, $modes)) {
            echo PHP_EOL . 'mandatory parameter --mode=[' . implode('|', $modes) . '] missing or not recognized' . PHP_EOL;
            return 1;
        }

        $flags = (array)($options[Tinebase_Config::MAINTENANCE_MODE_FLAGS] ?? []);

        if (!in_array(Tinebase_Config::MAINTENANCE_MODE_FLAG_SKIP_APPS, $flags)) {
            $enabledApplications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
            if (isset($options['apps'])) {
                $apps = (array)$options['apps'];
                $enabledApplications = $enabledApplications->filter(function (Tinebase_Model_Application $app) use ($apps) {
                    return in_array($app->name, $apps);
                });
            }

            $enable = Tinebase_Config::MAINTENANCE_MODE_ON === $options['mode'];
            foreach ($enabledApplications as $application) {
                $app = Tinebase_Core::getApplicationInstance($application->name);
                if (true === $enable) {
                    $app->goIntoMaintenanceMode(/*$flags*/);
                } else {
                    $app->leaveMaintenanceMode(/*$flags*/);
                }
            }

            echo PHP_EOL . 'Apps ' . ($enable ? 'going into' : 'leaving') . ' maintenance mode. waiting...' . PHP_EOL;

            do {
                foreach ($enabledApplications as $application) {
                    $app = Tinebase_Core::getApplicationInstance($application->name);
                    if ($app->isInMaintenanceMode() === $enable) {
                        $enabledApplications->removeById($application->id);
                    }
                }
                if ($enabledApplications->count() > 0) {
                    echo '.';
                    usleep(100000);
                }

            } while ($enabledApplications->count() > 0);
            echo 'done' . PHP_EOL;

            if (isset($options['apps']) || in_array(Tinebase_Config::MAINTENANCE_MODE_FLAG_ONLY_APPS, $flags)) {
                return 0;
            }
        }

        if (Tinebase_Config::MAINTENANCE_MODE_ON === $options['mode']) {
            if (in_array(Tinebase_Config::MAINTENANCE_MODE_FLAG_ALLOW_ADMIN_LOGIN, $flags)) {
                $options['state'] = Tinebase_Config::MAINTENANCE_MODE_NORMAL;
            } else {
                $options['state'] = Tinebase_Config::MAINTENANCE_MODE_ALL;
            }
        }
        if (Setup_Controller::getInstance()->setMaintenanceMode($options)) {
            echo PHP_EOL . 'set maintenance mode to: ' . $options['mode'] . PHP_EOL;
        } else {
            echo PHP_EOL . 'failed to set maintance mode to: ' . $options['mode'] . PHP_EOL;
        }

        return 0;
    }

    /**
     * install tine20 from a dump (local dir or remote dir)
     *
     * @param Zend_Console_Getopt $_opts
     */
    protected function _installDump(Zend_Console_Getopt $_opts)
    {
        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        Setup_Controller::getInstance()->installFromDump($options);

        return 0;
    }

    /**
     * parse options
     * 
     * @param string $_value
     * @return array|string
     */
    public static function parseConfigValue($_value)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_value, TRUE));
        
        // check value is json encoded
        if (Tinebase_Helper::is_json($_value)) {
            return Zend_Json::decode($_value); 
        }
        
        $result = array(
            'active' => 1
        );

        // keep spaces, \: and \,
        $_value = preg_replace(array('/ /', '/\\\:/', '/\\\,/', '/\s*/'), array('§', '@', ';', ''), $_value);
        
        $parts = explode(',', $_value);
        
        foreach ($parts as $part) {
            $part = str_replace(';', ',', $part);
            $part = str_replace('§', ' ', $part);
            $part = str_replace('@', ':', $part);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $part);
            if (strpos($part, '_') !== FALSE) {
                list($key, $sub) = preg_split('/_/', $part, 2);
                if (preg_match('/:/', $sub)) {
                    list($subKey, $value) = explode(':', $sub);
                    $result[$key][$subKey] = $value;
                } else {
                    // might be a '_' in the value
                    if (preg_match('/:/', $part)) {
                        $exploded = explode(':', $part);
                        $key = array_shift($exploded);
                        $result[$key] = implode(':', $exploded);
                    } else {
                        throw new Timetracker_Exception_UnexpectedValue('You have an error in the config syntax (":" expected): ' . $part);
                    }
                }
            } else {
                if (strpos($part, ':') !== FALSE) {
                    list($key, $value) = preg_split('/:/', $part, 2);
                    $result[$key] = $value;
                } else {
                    $result = $part;
                }
            }
        }

        return $result;
    }
    
    /**
     * parse remaining args
     * 
     * @param array $_args
     * @return array
     */
    protected function _parseRemainingArgs($_args)
    {
        $options = array();
        foreach ($_args as $arg) {
            if (strpos($arg, '=') !== FALSE) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $arg);
                list($key, $value) = preg_split('/=/', $arg, 2);
                $options[$key] = $value;
            }
        }
        
        return $options;
    }

    /**
     * compare shema of two tine databases
     *
     * @param Zend_Console_Getopt $_opts
     * @return integer
     * @throws Exception
     */
    protected function _compare(Zend_Console_Getopt $_opts)
    {
        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        $schemaChanges = Setup_Controller::getInstance()->compareSchema($options);
        print_r($schemaChanges);

        if (count($schemaChanges) > 0) {
            echo "Do you want to apply the schema changes? WARNING: this might render your tine20 installation unusable. Always backup your DB before doing this!\n";

            $apply = Tinebase_Server_Cli::promptInput('Do you want to apply the schema changes? (default: "no", "y" or "yes" for apply sql)?');
            if ($apply === 'y' or $apply === 'yes') {
                $db = Setup_Core::getDb();
                foreach ($schemaChanges as $change) {
                    echo "applying sql: " . $change . "\n";
                    try {
                        $db->query($change);
                    } catch (Exception $e) {
                        echo $e;
                        $continue = Tinebase_Server_Cli::promptInput("Do you want to continue?\n");
                        if ($continue !== 'y' && $continue !== 'yes') {
                            return 2;
                        }
                    }
                }
            }
        }

        return 0;
    }

    public function _migrateUtf8mb4()
    {
        $db = Setup_Core::getDb();
        if (!$db instanceof Zend_Db_Adapter_Pdo_Mysql) {
            throw new Tinebase_Exception_Backend_Database('you are not using mysql');
        }

        if (!Setup_Backend_Mysql::dbSupportsVersion($db, 'mariadb > 10.3 | mysql > 8')) {
            if (($ilp = $db->query('SELECT @@innodb_large_prefix')->fetchColumn()) !== '1') {
                throw new Tinebase_Exception_Backend_Database('innodb_large_prefix seems not be turned on: ' . $ilp);
            }
            if (($iff = $db->query('SELECT @@innodb_file_format')->fetchColumn()) !== 'Barracuda') {
                throw new Tinebase_Exception_Backend_Database('innodb_file_format seems not to be Barracuda: ' . $iff);
            }
        }
        if (($ift = $db->query('SELECT @@innodb_file_per_table')->fetchColumn()) !== '1') {
            throw new Tinebase_Exception_Backend_Database('innodb_file_per_table seems not to be turned on: ' . $ift);
        }

        $dbConfig = $db->getConfig();
        try {
            $db->query('ALTER DATABASE ' . $db->quoteIdentifier($dbConfig['dbname']) .
                ' CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci');
        } catch (Zend_Db_Exception $zde) {
            Tinebase_Exception::log($zde);
        }
        
        $tables = $db->query('SELECT DISTINCT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME LIKE "' .
            SQL_TABLE_PREFIX . '%" AND CHARACTER_SET_NAME IS NOT NULL AND CHARACTER_SET_NAME NOT LIKE "utf8mb4%"' .
            ' AND TABLE_SCHEMA = "' . $dbConfig['dbname'] . '"')->fetchAll(Zend_Db::FETCH_COLUMN);

        $db->query('SET foreign_key_checks = 0');
        $db->query('SET unique_checks = 0');
        foreach ($tables as $table) {
            echo "Converting table $table ...";
            if ($db->query('SELECT count(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = "' . $table .
                    '" AND TABLE_SCHEMA = "' . $dbConfig['dbname'] . '" AND ROW_FORMAT <> "Dynamic"')->fetchColumn()) {
                try {
                    $db->query('ALTER TABLE ' . $db->quoteIdentifier($table) . ' ROW_FORMAT = DYNAMIC');
                } catch (Zend_Db_Statement_Exception $e) {
                    if ($db->query('SELECT count(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = "' . $table .
                            '" AND TABLE_SCHEMA = "' . $dbConfig['dbname'] . '" AND ROW_FORMAT <> "Dynamic"')
                            ->fetchColumn()) {
                        throw $e;
                    }
                }
            }

            if ($table === SQL_TABLE_PREFIX . 'tree_nodes') {
                $setupBackend = new Setup_Backend_Mysql();
                $db->query('SET foreign_key_checks = 1');
                try {
                    $setupBackend->dropForeignKey('tree_nodes', 'tree_nodes::parent_id--tree_nodes::id');
                    $db->query('SET foreign_key_checks = 0');
                    $setupBackend->dropIndex('tree_nodes', 'parent_id-name');
                } catch (Zend_Db_Statement_Exception $zdse) {
                    echo $zdse->getMessage() . "\n";
                } finally {
                    $db->query('SET foreign_key_checks = 0');
                }
            }

            $db->query('ALTER TABLE ' . $db->quoteIdentifier($table) .
                ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

            if ($table === SQL_TABLE_PREFIX . 'tree_nodes') {
                $setupBackend = new Setup_Backend_Mysql(true);
                $setupBackend->alterCol('tree_nodes', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>name</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                    <collation>utf8mb4_bin</collation>
                </field>'));
                $setupBackend->addIndex('tree_nodes', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>parent_id-name</name>
                    <unique>true</unique>
                    <field>
                        <name>parent_id</name>
                    </field>
                    <field>
                        <name>name</name>
                    </field>
                    <field>
                        <name>deleted_time</name>
                    </field>
                </index>'));
                $setupBackend->addForeignKey('tree_nodes', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>tree_nodes::parent_id--tree_nodes::id</name>
                    <field>
                        <name>parent_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>tree_nodes</table>
                        <field>id</field>
                        <onupdate>cascade</onupdate>
                        <!-- add ondelete? -->
                    </reference>
                </index>'));
            }

            echo "done\n";
        }

        $db->query('SET foreign_key_checks = 1');
        $db->query('SET unique_checks = 1');

        foreach ($tables as $table) {
            $db->query('REPAIR TABLE ' . $db->quoteIdentifier($table));
            $db->query('OPTIMIZE TABLE ' . $db->quoteIdentifier($table));
        }

        Setup_Controller::getInstance()->clearCache();

        return 0;
    }

    /**
     * loads config values from environment
     */
    private function _configFromEnv()
    {
        $output = [];

        foreach ($_ENV as $env_key => $env_value) {
            $env_key_array = explode('_', $env_key);
            if ($env_key_array[0] != 'TINE20' || ! isset($env_key_array[1]) || $env_key_array[1] != '' || ! isset($env_key_array[2])) {
                //Only accept env vars with format 'TINE20__*'
                continue;
            }

            if (isset($env_key_array[3])) {
                $applicationName = $env_key_array[2];
                $configKey = $env_key_array[3];
            } else {
                $applicationName = 'Tinebase';
                $configKey = $env_key_array[2];
            }

            $configValue = self::parseConfigValue($env_value);

            if (! Tinebase_Application::getInstance()->isInstalled('Tinebase') || ! Tinebase_Application::getInstance()->isInstalled($applicationName)) {
                $output[] = $configKey . " err: " . $applicationName . ' is not installed';
                continue;
            }

            $config = Tinebase_Config_Abstract::factory($applicationName);

            if (null === $config->getDefinition($configKey)) {
                $output[] = $configKey . " err: config property does not exist in " . $applicationName;
                continue;
            }

            if ($config->get($configKey) == $configValue) {
                $output[] = $configKey . " ok";
                continue;
            }

            $config->set($configKey, $configValue);
            $output[] = $configKey . " changed";
        }

        if (! empty($output)) {
            foreach ($output as $lines) {
                echo "- " . $lines . "\n";
            }
        } else {
            echo "Nothing to load\n";
        }
    }

    private function _isInstalled() {
        try {
            if ($this->_tinebaseApplication->isInstalled('Tinebase', true)) {
                return 0;
            }
        } catch (Exception $e) {
            return 1;
        }

        return 1;
    }


    /**
     * Add a new token to table tine20_auth_token
     *
     * @param Zend_Console_Getopt $_opts
     */
    protected function _addAuthToken(Zend_Console_Getopt $_opts)
    {
        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());

        $mandatoryOptions = array(
            'user',
            'id',
            'auth_token',
            'valid_until',
            'channels',
        );

        foreach($mandatoryOptions as $opt) {
            if (!isset($options[$opt])) {
                echo 'option ' . $opt . ' is mandatory' . PHP_EOL;
                return;
            }
        }

        $result = Setup_Controller::getInstance()->addAuthToken($options);

        if (is_array($result)) {
            echo "Auth token created: " . print_r($result, true) . PHP_EOL;
        }
    }

    /**
     * allows to call the mysql-client with the configured db params
     *
     * @param Zend_Console_Getopt $_opts
     * @return int
     *
     * TODO add more platforms?
     * TODO use .my.cnf file? needs to be deleted afterwards (like in backup/restore)
     * TODO use better process control library? i.e. https://symfony.com/doc/current/components/process.html
     */
    protected function _mysqlClient(Zend_Console_Getopt $_opts)
    {
        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        if (! empty($options['platform'])) {
            switch ($options['platform']) {
                case 'docker': // maybe add "alpine"?
                    // install mysql client if not available
                    system('apk add mysql-client');
            }
        }

        $dbConf = Tinebase_Core::getConfig()->database;
        $command ='mysql -h ' . $dbConf->host . ' -p' . $dbConf->password . ' -u ' . $dbConf->username
            . ' ' . $dbConf->dbname;
        $descriptorspec = [
            0 => array("pty"),
            1 => array("pty"),
            2 => array("pty")
        ];
        $process = proc_open($command, $descriptorspec, $pipes);

        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);
        stream_set_blocking(STDIN,0);
        do {
            echo stream_get_contents($pipes[1]);
            echo stream_get_contents($pipes[2]);
            while ($in = fgets(STDIN)) {
                fwrite($pipes[0], $in);
                if ($in[0] === "\004") {
                    // graceful exit (via EOT / CTRL-D)
                    break 2;
                }
            }
        } while(is_resource($process));

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return proc_close($process);
    }
}
