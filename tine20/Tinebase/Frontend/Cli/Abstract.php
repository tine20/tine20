<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * abstract cli server
 *
 * This class handles cli requests
 *
 * @package     Tinebase
 * @subpackage  Frontend
 */
class Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Tinebase';

    /**
     * import demodata default definitions
     *
     * @var array
     */
    protected $_defaultDemoDataDefinition = [
    ];

    /**
     * help array with function names and param descriptions
     */
    protected $_help = array();

    /**
     * echos usage information
     *
     */
    public function getHelp()
    {
        foreach ($this->_help as $functionHelp) {
            echo $functionHelp['description']."\n";
            echo "parameters:\n";
            foreach ($functionHelp['params'] as $param => $description) {
                echo "$param \t $description \n";
            }
        }
    }
    
    /**
     * update or create import/export definition
     * 
     * @param Zend_Console_Getopt $_opts
     * @return int
     */
    public function updateImportExportDefinition(Zend_Console_Getopt $_opts)
    {
        $this->_checkAdminRight();
        
        $defs = $_opts->getRemainingArgs();
        if (empty($defs)) {
            echo "No definition given.\n";
            return 1;
        }
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        
        foreach ($defs as $definitionFilename) {
            Tinebase_ImportExportDefinition::getInstance()->updateOrCreateFromFilename($definitionFilename, $application);
            echo "Imported " . $definitionFilename . " successfully.\n";
        }
        
        return 0;
    }

    /**
     * add container
     *
     * example usages:
     * (1) $ php tine20.php --method=Calendar.createContainer name=TEST type=shared owner=
     *
     * @param Zend_Console_Getopt $_opts
     * @return boolean
     */
    public function createContainer(Zend_Console_Getopt $_opts)
    {
        $this->_checkAdminRight();

        $data = $this->_parseArgs($_opts, array('name', 'type', 'model'), array('owner', 'color'));

        if ($data['type'] !== 'shared') {
            die('only shared containers supported');
        }

        $app = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);

        $container = new Tinebase_Model_Container(array(
            'name'              => $data['name'],
            'type'              => $data['type'],
            'model'             => $data['model'],
            'application_id'    => $app->getId(),
            'backend'           => 'Sql'
        ));

        Tinebase_Container::getInstance()->addContainer($container);
    }

    /**
     * set container grants
     * 
     * example usages: 
     * (1) $ php tine20.php --method=Calendar.setContainerGrants containerId=3339 accountId=15 accountType=group grants=readGrant
     * (2) $ php tine20.php --method=Timetracker.setContainerGrants namefilter="timeaccount name" accountId=15,30 accountType=group grants=book_own,manage_billable overwrite=1
     * 
     * @param Zend_Console_Getopt $_opts
     * @return boolean
     */
    public function setContainerGrants(Zend_Console_Getopt $_opts)
    {
        $this->_checkAdminRight();
        
        $data = $this->_parseArgs($_opts, array('accountId', 'grants'));
        
        $containers = $this->_getContainers($data);
        if (count($containers) == 0) {
            echo "No matching containers found.\n";
        } else {
            Admin_Controller_Container::getInstance()->setGrantsForContainers(
                $containers, 
                $data['grants'],
                $data['accountId'], 
                ((isset($data['accountType']) || array_key_exists('accountType', $data))) ? $data['accountType'] : Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                ((isset($data['overwrite']) || array_key_exists('overwrite', $data)) && $data['overwrite'] == '1')
            );
            
            echo "Updated " . count($containers) . " container(s).\n";
        }
        
        return TRUE;
    }

    /**
     * create demo data
     * 
     * example usages: 
     * (1) $ php tine20.php --method=Calendar.createDemoData --username=admin --password=xyz locale=de users=pwulf,rwright // Creates demo events for pwulf and rwright with the locale de, just de and en is supported at the moment
     * (2) $ php tine20.php --method=Calendar.createDemoData --username=admin --password=xyz models=Calendar,Event sharedonly // Creates shared calendars and events with the default locale en                
     * (3) $ php tine20.php --method=Calendar.createDemoData --username=admin --password=xyz // Creates all demo calendars and events for all users
     * (4) $ php tine20.php --method=Calendar.createDemoData --username=admin --password=xyz full // Creates much more demo data than (3)
     * (5) $ php tine20.php --method=Calendar.createDemoData --username=admin --password=xyz -- demodata=csv // import demodata from csv files
     * (6) $ php tine20.php --method=Admin.createDemoData --username=admin --password=xyz -- demodata=set set=default.yml // import demodata defined by default.yml
     *
     * @param Zend_Console_Getopt $_opts
     * @param boolean $checkDependencies
     * @return boolean
     */
    public function createDemoData($_opts = NULL, $checkDependencies = TRUE)
    {
        // just admins can perform this action
        $this->_checkAdminRight();

        $data = $this->_parseArgs($_opts);
        if (! isset($data['demodata'])) {
            $data['demodata'] = '';
        }

        switch ($data['demodata']){
            case "php":
                $this->_createPhpDemoData($_opts, $checkDependencies);
                break;
            case "csv":
                $this->_createImportDemoData();
                break;
            case "set":
                $set = isset($data['set']) ? $data['set'] : null;
                $this->_createImportDemoDataFromSet($set);
                break;
            case "all":
            case "":
            default:
                $this->_createPhpDemoData($_opts, $checkDependencies);
                $this->_createImportDemoData();
        }
        return true;
    }

    /**
     * checks for APP_Setup_DemoData and executes the code
     *
     * @param $_opts
     * @param $checkDependencies
     */
    protected function _createPhpDemoData($_opts, $checkDependencies)
    {
        $className = $this->_applicationName . '_Setup_DemoData';

        if (! class_exists($className)) {
            return;
        }
        if ($checkDependencies) {
            foreach($className::getRequiredApplications() as $appName) {
                if (Tinebase_Application::getInstance()->isInstalled($appName)) {
                    $cname = $appName . '_Setup_DemoData';
                    if (class_exists($cname)) {
                        if (! $cname::hasBeenRun()) {
                            $className2 = $appName . '_Frontend_Cli';
                            if (class_exists($className2)) {
                                echo 'Creating required DemoData of application "' . $appName . '"...' . PHP_EOL;
                                $class = new $className2();
                                $class->createDemoData($_opts, TRUE);
                            }
                        }
                    }
                }
            }
        }

        $options = array('createUsers' => TRUE, 'createShared' => TRUE, 'models' => NULL, 'locale' => 'de', 'password' => '');

        if ($_opts) {
            $args = $this->_parseArgs($_opts, array());

            if ((isset($args['other']) || array_key_exists('other', $args))) {
                $options['createUsers']  = in_array('sharedonly', $args['other']) ? FALSE : TRUE;
                $options['createShared'] = in_array('noshared',   $args['other']) ? FALSE : TRUE;
                $options['full']         = in_array('full',       $args['other']) ? FALSE : TRUE;
            }

            // locale defaults to de
            if (isset($args['locale'])) {
                $options['locale'] = $args['locale'];
            }

            // password defaults to empty password
            if (isset($args['password'])) {
                $options['password'] = $args['password'];
            }

            if (isset($args['users'])) {
                $options['users'] = is_array($args['users']) ? $args['users'] : array($args['users']);
            }

            if (isset($args['models'])) {
                $options['models'] = is_array($args['models']) ? $args['models'] : array($args['models']);
            }
        }


        $setupDemoData = $className::getInstance();
        if ($setupDemoData->createDemoData($options)) {
            echo 'Demo Data was created successfully' . chr(10) . chr(10);
            if (method_exists($setupDemoData, 'unsetInstance')) {
                $setupDemoData->unsetInstance();
            }
        } else {
            echo 'No Demo Data has been created' . chr(10) . chr(10);
        }
    }

    /**
     * try to import demodata files from APP/Setup/DemoData/import
     */
    protected function _createImportDemoData()
    {
        // get all app models and try to find import files for them
        $application = Setup_Core::getApplicationInstance($this->_applicationName, '', true);
        foreach ($application->getModels() as $model) {
            $options = isset($this->_defaultDemoDataDefinition[$model])
                ? [
                    'definition' => $this->_defaultDemoDataDefinition[$model]
                ] : [];
            $importer = new Tinebase_Setup_DemoData_Import($model, $options);
            try {
                $importer->importDemodata();
                echo 'Csv Demo Data was created successfully' . chr(10) . chr(10);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // model has no import files
            }
        }
    }

    /**
     * try to import demodata files from APP/Setup/DemoData/demodata_set_file.yml
     *
     * @param string $setFile
     */
    protected function _createImportDemoDataFromSet($setFile = null)
    {
        if (! extension_loaded('yaml')) {
            throw new Tinebase_Exception_SystemGeneric('php yaml extension needed');
        }

        $importer = new Tinebase_Setup_DemoData_ImportSet($this->_applicationName, [
            'files' => [$setFile]]
        );
        try {
            $importer->importDemodata();
            echo 'Set Demo Data was created successfully' . chr(10) . chr(10);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // model has no import files
        }
    }

    /**
     * get container for setContainerGrants
     * 
     * @param array $_params
     * @return Tinebase_Record_RecordSet
     * @throws Timetracker_Exception_UnexpectedValue
     */
    protected function _getContainers($_params)
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        $containerFilterData = array(
            array('field' => 'application_id', 'operator' => 'equals', 'value' => $application->getId()),
        );
        
        if ((isset($_params['containerId']) || array_key_exists('containerId', $_params))) {
            $containerFilterData[] = array('field' => 'id', 'operator' => 'equals', 'value' => $_params['containerId']);
        } else if ((isset($_params['namefilter']) || array_key_exists('namefilter', $_params))) {
            $containerFilterData[] = array('field' => 'name', 'operator' => 'contains', 'value' => $_params['namefilter']);
        } else {
            throw new Timetracker_Exception_UnexpectedValue('Parameter containerId or namefilter missing!');
        }
        
        $containers = Tinebase_Container::getInstance()->search(new Tinebase_Model_ContainerFilter($containerFilterData));
        
        return $containers;
    }
    
    /**
     * parses arguments (key1=value1 key2=value2 key3=subvalue1,subvalue2 ...)
     * 
     * @param Zend_Console_Getopt $_opts
     * @param array $_requiredKeys
     * @param string $_otherKey use this key for arguments without '='
     * @param boolean $_splitSubArgs
     * @throws Tinebase_Exception_InvalidArgument
     * @return array
     *
     * @todo remove $_splitSubArgs and detect, if it is a json encoded value
     */
    protected function _parseArgs(Zend_Console_Getopt $_opts, $_requiredKeys = array(), $_otherKey = 'other', $_splitSubArgs = true)
    {
        $args = $_opts->getRemainingArgs();
        
        $result = array();
        foreach ($args as $idx => $arg) {
            if (strpos($arg, '=') !== false) {
                list($key, $value) = explode('=', $arg);
                if ($_splitSubArgs) {
                    if (strpos($value, ',') !== false) {
                        $value = explode(',', $value);
                    }
                    $value = str_replace('"', '', $value);
                }
                $result[$key] = $value;
            } else {
                $result[$_otherKey][] = $arg;
            }
        }
        
        if (! empty($_requiredKeys)) {
            foreach ($_requiredKeys as $requiredKey) {
                if (! (isset($result[$requiredKey]) || array_key_exists($requiredKey, $result))) {
                    throw new Tinebase_Exception_InvalidArgument('Required parameter not found: ' . $requiredKey);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * check admin right of application
     * 
     * @param boolean $exitOnNoPermission
     * @return boolean
     */
    protected function _checkAdminRight($exitOnNoPermission = true)
    {
        // check if admin for app
        if (! Tinebase_Core::getUser()->hasRight($this->_applicationName, Tinebase_Acl_Rights::ADMIN)) {
            echo "No admin right for application " . $this->_applicationName . "\n";
            if ($exitOnNoPermission) {
                // 126 = Command invoked cannot execute / Permission problem or command is not an executable
                // @see http://tldp.org/LDP/abs/html/exitcodes.html
                exit(126);
            } else {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * import records
     *
     * @param Zend_Console_Getopt   $_opts
     * @return array import result
     */
    protected function _import($_opts)
    {
        $args = $this->_parseArgs($_opts, array(), 'filename');
        
        if ($_opts->d) {
            $args['dryrun'] = 1;
            if ($_opts->v) {
                echo "Doing dry run.\n";
            }
        }

        if ((isset($args['definition']) || array_key_exists('definition', $args)))  {
            if (preg_match("/\.xml/", $args['definition'])) {
                $definition = Tinebase_ImportExportDefinition::getInstance()->getFromFile(
                    $args['definition'],
                    Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId()
                );
            } else {
                $definition = Tinebase_ImportExportDefinition::getInstance()->getByName($args['definition']);
            }
            // If old Admin Import plugin is given use the new one!
            if ($definition->plugin == 'Admin_Import_Csv') {
                $definition->plugin = 'Admin_Import_User_Csv';
            }
            $importer = call_user_func($definition->plugin . '::createFromDefinition', $definition, $args);
        } else if ((isset($args['plugin']) || array_key_exists('plugin', $args))) {
            $importer =  new $args['plugin']($args);
        } else if (isset($args['model'])) {
            // use generic import
            $definition = Tinebase_ImportExportDefinition::getInstance()->getGenericImport($args['model']);
            $importer = call_user_func($definition->plugin . '::createFromDefinition', $definition, $args);
        } else {
            echo "You need to define a plugin OR a definition OR a model at least! \n";
            exit;
        }
        
        // loop files in argv
        $result = array();
        foreach ((array) $args['filename'] as $filename) {
            // read file
            if ($_opts->v) {
                echo "reading file $filename ...";
            }
            try {
                $result[$filename] = $importer->importFile($filename);
                if ($_opts->v) {
                    echo "done.\n";
                }
            } catch (Exception $e) {
                if ($_opts->v) {
                    echo "failed (". $e->getMessage() . ").\n";
                } else {
                    echo $e->getMessage() . "\n";
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                continue;
            }

            $this->_echoImportResult($result[$filename]);

            // import (check if dry run)
            if ($_opts->d && $_opts->v) {
                print_r($result[$filename]['results']->toArray());
                
                if ($result[$filename]['failcount'] > 0) {
                    print_r($result[$filename]['exceptions']->toArray());
                }
            } 
        }
        
        return $result;
    }

    /**
     * echos import result
     *
     * @param array $result
     */
    protected function _echoImportResult($result)
    {
        // TODO use a loop here
        if (isset($result['totalcount']) && ! empty($result['totalcount'])) {
            echo "Imported " . $result['totalcount'] . " records.\n";
        }
        if (isset($result['failcount']) && ! empty($result['failcount'])) {
            echo "Import failed for " . $result['failcount'] . " records.\n";
        }
        if (isset($result['duplicatecount']) && ! empty($result['duplicatecount'])) {
            echo "Found " . $result['duplicatecount'] . " duplicates.\n";
        }
        if (isset($result['updatecount']) && ! empty($result['updatecount'])) {
            echo "Updated " . $result['updatecount'] . " records.\n";
        }
    }

    /**
     * search for duplicates
     * 
     * @param Tinebase_Controller_Record_Interface $_controller
     * @param  Tinebase_Model_Filter_FilterGroup
     * @param string $_field
     * @return array with ids / field
     * 
     * @todo add more options (like soundex, what do do with duplicates/delete/merge them, ...)
     */
    protected function _searchDuplicates(Tinebase_Controller_Record_Abstract $_controller, $_filter, $_field)
    {
        $pagination = new Tinebase_Model_Pagination(array(
            'start' => 0,
            'limit' => 100,
        ));
        $results = array();
        $allRecords = array();
        $totalCount = $_controller->searchCount($_filter);
        echo 'Searching ' . $totalCount . " record(s) for duplicates\n";
        while ($pagination->start < $totalCount) {
            $records = $_controller->search($_filter, $pagination);
            foreach ($records as $record) {
                if (in_array($record->{$_field}, $allRecords)) {
                    $allRecordsFlipped = array_flip($allRecords);
                    $duplicateId = $allRecordsFlipped[$record->{$_field}];
                    $results[] = array('id' => $duplicateId, 'value' => $record->{$_field});
                    $results[] = array('id' => $record->getId(), 'value' => $record->{$_field});
                }
                
                $allRecords[$record->getId()] = $record->{$_field};
            }
            $pagination->start += 100;
        }
        
        return $results;
    }
    
    /**
     * add log writer for php://output
     * 
     * @param integer $priority
     */
    protected function _addOutputLogWriter($priority = 5)
    {
        $writer = new Zend_Log_Writer_Stream('php://output');
        $writer->addFilter(new Zend_Log_Filter_Priority($priority));
        Tinebase_Core::getLogger()->addWriter($writer);
    }
    
    /**
     * import from egroupware
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function importegw14($_opts)
    {
        $args = $_opts->getRemainingArgs();
        
        if (count($args) < 1 || ! is_readable($args[0])) {
            echo "can not open config file \n";
            echo "see tine20.org/wiki/EGW_Migration_Howto for details \n\n";
            echo "usage: ./tine20.php --method=Appname.importegw14 /path/to/config.ini  (see Tinebase/Setup/Import/Egw14/config.ini)\n\n";
            exit(1);
        }
        
        try {
            $config = new Zend_Config_Ini($args[0]);
            if ($config->{strtolower($this->_applicationName)}) {
                $config = $config->{strtolower($this->_applicationName)};
            }
        } catch (Zend_Config_Exception $e) {
            fwrite(STDERR, "Error while parsing config file($args[0]) " .  $e->getMessage() . PHP_EOL);
            exit(1);
        }
        
        $writer = new Zend_Log_Writer_Stream('php://output');
        $logger = new Zend_Log($writer);
        
        $filter = new Zend_Log_Filter_Priority((int) $config->loglevel);
        $logger->addFilter($filter);
        
        $class_name = $this->_applicationName . '_Setup_Import_Egw14';
        if (! class_exists($class_name)) {
            $logger->ERR(__METHOD__ . '::' . __LINE__ . " no import for {$this->_applicationName} available");
            exit(1);
        }
        
        try {
            $importer = new $class_name($config, $logger);
            $importer->import();
        } catch (Exception $e) {
            $logger->ERR(__METHOD__ . '::' . __LINE__ . " import for {$this->_applicationName} failed ". $e->getMessage());
        }
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
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Setting user with id ' . $cronuserId . ' as cronuser.');
            $cronuser = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $cronuserId, 'Tinebase_Model_FullUser');
            try {
                Tinebase_User::getInstance()->assertAdminGroupMembership($cronuser);
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' ' . $tenf->getMessage());

            $cronuser = Tinebase_User::createSystemUser(Tinebase_User::SYSTEM_USER_CRON);
            if ($cronuser) {
                Tinebase_Config::getInstance()->set(Tinebase_Config::CRONUSERID, $cronuser->getId());
            }
        }

        return $cronuser;
    }

    /**
     * exports containers as ICS, VCF, ...
     *
     * @param Zend_Console_Getopt $_opts
     * @param string $_model
     * @param string $_exportClass
     * @return boolean
     */
    protected function _exportVObject(Zend_Console_Getopt $_opts, $_model, $_exportClass)
    {
        $args = $this->_parseArgs($_opts);

        // @todo implement
        //   - allow to export all shared containers

        if (isset($args['type']) && $args['type'] === 'personal') {
            // get all containers of given type
            $containers = Tinebase_Container::getInstance()->getPersonalContainer(
                Tinebase_Core::getUser(),
                $_model,
                Tinebase_Core::getUser()
            )->getArrayOfIds();
        } else if (isset($args['container_id'])) {
            $containers = explode(',', $args['container_id']);
        }

        foreach ($containers as $containerId) {
            $this->_exportContainerAsVObject($containerId, $args, $_model, $_exportClass);
        }

        return 0;
    }

    /**
     * @param Tinebase_Model_Container $container
     * @param array $args
     * @param string $extension
     * @return string
     *
     * @todo add container name (need to strip spaces, special chars, ...)?
     * @todo create subdir for each user?
     */
    protected function _getVObjectExportFilename($container, $args, $extension)
    {
        $path = isset($args['path']) ? $args['path'] : Tinebase_Core::getTempDir();
        return $path . DIRECTORY_SEPARATOR . Tinebase_Core::getUser()->accountLoginName
            // . '_' . $container->name
            . '_' . substr($container->getId(), 0, 8) . '.' . $extension;
    }

    /**
     * @param $containerId
     * @param $options
     * @param $model
     * @param $exportClass
     *
     * TODO add more filters as param
     */
    protected function _exportContainerAsVObject($containerId, $options, $model, $exportClass)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Exporting calendar ' . $containerId);
        }

        if (! isset($options['stdout']) || $options['stdout'] != 1) {
            if (! isset($options['filename'])) {
                $container = Tinebase_Container::getInstance()->getContainerById($containerId);
                $extension = $exportClass === Addressbook_Export_VCard::class ? 'vcf' : 'ics';
                $options['filename'] = $this->_getVObjectExportFilename($container, $options, $extension);
            }
        } else {
            unset($options['filename']);
        }

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($model, [
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $containerId],
        ]);
        $export = new $exportClass($filter, null, $options);
        $filename = $export->generate();
        if (! $filename) {
            // TODO refactor function signature - write does not write content to file but to stdout/browser
            $export->write();
        } else {
            echo 'Exported container ' . $containerId . ' into file ' . $filename . "\n";
        }
    }
}
