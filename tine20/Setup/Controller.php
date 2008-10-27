<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class to handle setup of Tine 2.0
 *
 * @package     Setup
 */
class Setup_Controller
{
    /**
     * setup backend
     *
     * @var Setup_Backend_Interface
     */
    private $_backend;
    
    /**
     * Enter description here...
     *
     * @var Zend_Config
     */
    private $_config;
    
    /**
     * get set to true, if Tinebase got installed (not updated!!!)
     *
     * @var bool
     */
    private $_doInitialLoad = false;
    
    /**
     * the constructor
     *
     */
    public function __construct($_init = TRUE)
    {
        $this->_config = Zend_Registry::get('configFile');

        if ($_init) {
            $this->_setupLogger();
            $this->setupDatabaseConnection();
        }
    }
              
    /**
     * initializes the logger
     *
     */
    protected function _setupLogger()
    {
        $logger = new Zend_Log();

        if (isset($this->_config->logger)) {
            $loggerConfig = $this->_config->logger;

            $filename = $loggerConfig->filename;
            $priority = (int)$loggerConfig->priority;

            $writer = new Zend_Log_Writer_Stream($filename);
            $logger->addWriter($writer);

            $filter = new Zend_Log_Filter_Priority($priority);
            $logger->addFilter($filter);

        } else {
            $writer = new Zend_Log_Writer_Null;
            $logger->addWriter($writer);
        }

        Zend_Registry::set('logger', $logger);

        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' logger initialized');
    }

    /**
     * initializes the database connection
     *
     */
    protected function setupDatabaseConnection()
    {
        if (isset($this->_config->database)) {
            $dbConfig = $this->_config->database;
            define('SQL_TABLE_PREFIX', $dbConfig->get('tableprefix') ? $dbConfig->get('tableprefix') : 'tine20_');

            echo "<pre><hr>setting table prefix to: " . SQL_TABLE_PREFIX . " <hr>";
            
            $dbBackend = constant('Tinebase_Core::' . strtoupper($dbConfig->get('backend', Tinebase_Core::PDO_MYSQL)));
            
            switch($dbBackend) {
                case Tinebase_Core::PDO_MYSQL:
                    $db = Zend_Db::factory('PDO_MYSQL', $dbConfig->toArray());
                    $this->_backend = Setup_Backend_Factory::factory('Mysql');
                    break;
                case Tinebase_Core::PDO_OCI:
                    //$db = Zend_Db::factory('Pdo_Oci', $dbConfig->toArray());
                    $db = Zend_Db::factory('Oracle', $dbConfig->toArray());
                    $this->_backend = Setup_Backend_Factory::factory('Oracle');
                    break;
                default:
                    throw new Exception('Invalid database backend type defined. Please set backend to ' . Tinebase_Core::PDO_MYSQL . ' or ' . Tinebase_Core::PDO_OCI . ' in config.ini.');
                    break;
            }
                        
            Zend_Db_Table_Abstract::setDefaultAdapter($db);

            Zend_Registry::set('dbAdapter', $db);
        } else {
            die ('database section not found in central configuration file');
        }
    }

    /**
     * updates installed applications. does nothing if no applications are installed
     * 
     * @param Tinebase_Record_RecordSet $_applications
     * @param boolean                   $_dryRun checks only if update is needed if true 
     */
    public function updateApplications(Tinebase_Record_RecordSet $_applications, $_dryRun = FALSE)
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

        for($majorVersion = $smallestMajorVersion; $majorVersion <= $biggestMajorVersion; $majorVersion++) {
            foreach($_applications as $application) {
                if($application->getMajorVersion() <= $majorVersion) {
                    $this->updateApplication($application, $majorVersion, $_dryRun);
                }
            }
        }        
    }
    
    /**
     * setup the Database - read from the XML-files
     *
     */
    public function installApplications($_tinebaseFile, $_setupFilesPath )
    {    
        if(file_exists($_tinebaseFile)) {
           $this->parseFile($_tinebaseFile);
        }
        
        foreach ( new DirectoryIterator('./') as $item ) {
            if($item->isDir() && $item->getFileName() != 'Tinebase') {
                $fileName = $item->getFileName() . $_setupFilesPath ;
                if(file_exists($fileName)) {
                    $this->parseFile($fileName);
                }
            }
        }
    }
     
    /**
     * returns true if we need to load initial data
     *
     * @return bool
     */
    public function initialLoadRequired()
    {
        return $this->_doInitialLoad;
    }
    
    /**
     * add an application to database
     * register to tine
     * insert default records
     *
     * @return void
     */    
    public function addApplication(SimpleXMLElement $_xml)
    {
        // just insert tables
       ///*
        $createdTables = array();
        if (isset($_xml->tables)) {
            foreach ($_xml->tables[0] as $tableXML) {
                $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableXML);

                if (false == $this->_backend->tableExists($table->name)) {
                    try {
                        $this->_backend->createTable($table);
                        $createdTables[] = $table;
                        //exit();
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }
                }
            }
        }

//*/

        // register to tine
        try {
            $application = Tinebase_Application::getInstance()->getApplicationByName($_xml->name);
        } catch (Exception $e) {
            $application = new Tinebase_Model_Application(array(
                'name'      => $_xml->name,
                'status'    => $_xml->status ? $_xml->status : Tinebase_Application::ENABLED,
                'order'     => $_xml->order ? $_xml->order : 99,
                'version'   => $_xml->version
            ));
        
           $application = Tinebase_Application::getInstance()->addApplication($application);
        
        }
        
        // insert in database
        foreach ($createdTables as $table) {
            $this->_backend->addTable($application, (string) $table->name, (int) $table->version);
       }


        // insert default records
        if (isset($_xml->defaultRecords)) {
            foreach ($_xml->defaultRecords[0] as $record) {
                $this->_backend->execInsertStatement($record);
            }
        }
    }
    
    /**
     * parses the xml stream and creates the tables if needed
     *
     * @param string $_file path to xml file
     */
    public function parseFile($_file)
    {
        $xml = simplexml_load_file($_file);
        
        if (!$this->_backend->applicationExists($xml->name)) {
            $this->addApplication($xml);
            if ($xml->name == 'Tinebase') {
                $this->_doInitialLoad = true;
            }
        }
    }
    
    /**
     * load the setup.xml file and returns a simplexml object
     *
     * @param string $_applicationName name of the application
     */
    public function getSetupXml($_applicationName)
    {
    
        $setupXML = dirname(__FILE__) . '/../' . ucfirst($_applicationName) . '/Setup/setup.xml';
      
        if (!file_exists($setupXML)) {
            throw new Exception(ucfirst($_applicationName) . '/Setup/setup.xml not found. If application got renamed or deleted, re-run setup.php.');
        }
        
        $xml = simplexml_load_file($setupXML);
       
        return $xml;
    }
    
    /**
     * check update
     *
     * @param Tinebase_Model_Application $_application
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
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }
                } else {
                    throw new Exception ('Table ' . $table->name . ' for application' . $_application->name . " does not exists. \n<strong>Update broken</strong>");
                }
            }
        }
    }
    
    /**
     * update installed application
     *
     * @param string    $_name application name
     * @param string    $_updateTo version to update to (example: 1.17)
     * @param boolean   $_dryRun checks only if update is needed if true
     * @throws Setup_Exception_SetupRequired if update is needed and dry run is activated
     */
    public function updateApplication(Tinebase_Model_Application $_application, $_majorVersion, $_dryRun = FALSE)
    {
        $setupXml = $this->getSetupXml($_application->name);
        
        switch(version_compare($_application->version, $setupXml->version)) {
            case -1:
                if ($_dryRun) {
                    throw new Setup_Exception_SetupRequired();
                }
                
                echo "Executing updates for " . $_application->name . " (starting at " . $_application->version . ")<br>";

                list($fromMajorVersion, $fromMinorVersion) = explode('.', $_application->version);
        
                $minor = $fromMinorVersion;
               
                if(file_exists(ucfirst($_application->name) . '/Setup/Update/Release' . $_majorVersion . '.php')) {
                    $className = ucfirst($_application->name) . '_Setup_Update_Release' . $_majorVersion;
                
                    $update = new $className($this->_backend);
                
                    $classMethods = get_class_methods($update);
              
                    // we must do at least one update
                    do {
                        $functionName = 'update_' . $minor;
                        //echo "FUNCTIONNAME: $functionName<br>";
                        $update->$functionName();
                        $minor++;
                    } while(array_search('update_' . $minor, $classMethods) !== false);
                }
                
                echo "<strong> Updated " . $_application->name . " successfully to " .  $_majorVersion . '.' . $minor . "</strong><br>";
                
                break; 
                
            case 0:
                if (!$_dryRun) {
                    echo "<i>" . $_application->name . " is up to date (Version: " . $_application->version . ")</i><br>\n\n";
                }
                break;
                
            case 1:
                if (!$_dryRun) {
                    echo "<span style=color:#ff0000>Something went wrong!!! Current application version is higher than version from setup.xml.</span>";
                }
                throw new Exception('Current application version is higher than version from setup.xml');
                break;
        }        
    }

    /**
     * checks if setup/update is required
     *
     * @return boolean
     * 
     * @todo check database/tables or add another function for that
     */
    public function setupRequired()
    {
        $result = FALSE;
        
        //-- check if database exists
        
        //-- check if application table exists
        
        // check if applications are up-to-date
        $applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
        try {
            $result = $this->updateApplications($applications, TRUE);
        } catch (Setup_Exception_SetupRequired $ure) {
            $result = TRUE;
        }
        
        return $result;
    }
}
