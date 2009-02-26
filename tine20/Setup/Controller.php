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
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_baseDir = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
        $this->_db = Tinebase_Core::getDb();
        
        switch(get_class($this->_db)) {
            case 'Zend_Db_Adapter_Pdo_Mysql':
                $this->_backend = Setup_Backend_Factory::factory('Mysql');
                break;
                
            case 'Zend_Db_Adapter_Pdo_Oci':
                $this->_backend = Setup_Backend_Factory::factory('Oracle');
                break;
                
            default:
                throw new InvalidArgumentException('Invalid database backend type defined.');
                break;
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
        // @todo add dependencies to xml files
        $applications = array('Tinebase' => $this->getSetupXml('Tinebase'));
        
        foreach (new DirectoryIterator($this->_baseDir) as $item) {
            if($item->isDir() && $item->getFileName() != 'Tinebase') {
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

        for($majorVersion = $smallestMajorVersion; $majorVersion <= $biggestMajorVersion; $majorVersion++) {
            foreach($_applications as $application) {
                if($application->getMajorVersion() <= $majorVersion) {
                    $this->updateApplication($application, $majorVersion);
                }
            }
        }        
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
                        echo $e->getMessage();
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
     * @param   string    $_name application name
     * @param   string    $_updateTo version to update to (example: 1.17)
     * @throws  Setup_Exception if current app version is too high
     */
    public function updateApplication(Tinebase_Model_Application $_application, $_majorVersion)
    {
        $setupXml = $this->getSetupXml($_application->name);
        
        switch(version_compare($_application->version, $setupXml->version)) {
            case -1:
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
                        
                        try {
                            $db = Tinebase_Core::getDb();
                            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
                        
                            $update->$functionName();
                        
                            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                
                        } catch (Exception $e) {
                            Tinebase_TransactionManager::getInstance()->rollBack();
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                            throw $e;
                        }
                            
                        $minor++;
                    } while(array_search('update_' . $minor, $classMethods) !== false);
                }
                
                echo "<strong> Updated " . $_application->name . " successfully to " .  $_majorVersion . '.' . $minor . "</strong><br>";
                
                break; 
                
            case 0:
                break;
                
            case 1:
                throw new Setup_Exception('Current application version is higher than version from setup.xml');
                break;
        }        
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
     * checks if setup is required
     *
     * @return boolean
     */
    public function setupRequired()
    {
        $result = FALSE;
        
        // check if applications table exists
        try {
            // get list of applications, sorted by id. Tinebase should have the smallest id because it got installed first.
            $applicationTable = Tinebase_Core::getDb()->describeTable(SQL_TABLE_PREFIX . 'applications');
        } catch (Zend_Db_Statement_Exception $e) {
            $result = TRUE;
        }            
        return $result;
    }
    
    /**
     * delete list of applications
     *
     * @param array $_applications list of application names
     * @todo resolve order of applications
     */
    public function uninstallApplications($_applications)
    {
        foreach($_applications as $application) {
            $this->_uninstallApplication($application);
        }
    }

    /**
     * do php.ini environment check
     *
     * @return array
     */
    public function environmentCheck()
    {
        $success = TRUE;
        $message = '';
        
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
                    $message = "Sorry, your environment is not supported. You need to set $variable equal or greater than $required (now: $set).";
                    $success = FALSE;
                }

            } elseif ($oldValue != $newValue) {
                if (ini_set($variable, $newValue) === false) {
                    $message = "Sorry, your environment is not supported. You need to set $variable from $oldValue to $newValue.";
                    $success = FALSE;
                }
            }
        }
        
        return array(
            'result'        => $success,
            'message'       => $message
        );
    }
    
    /**
     * uninstall app
     *
     * @param Tinebase_Model_Application $_application
     */
    protected function _uninstallApplication(Tinebase_Model_Application $_application)
    {
        #echo "Uninstall $_application\n";
        $applicationTables = Tinebase_Application::getInstance()->getApplicationTables($_application);
        
        do {
            $oldCount = count($applicationTables);
            
            foreach($applicationTables as $key => $table) {
                #echo "Remove table: $table\n";
                try {
                    $this->_backend->dropTable($table);
                    if($_application != 'Tinebase') {
                        Tinebase_Application::getInstance()->removeApplicationTable($_application, $table);
                    }
                    unset($applicationTables[$key]);
                } catch(Zend_Db_Statement_Exception $e) {
                    // we need to catch exceptions here, as we don't want to break here, as a table
                    // migth still have some foreign keys
                    #echo $e->getMessage() . "\n";
                }
                
            }
            
            if($oldCount > 0 && count($applicationTables) == $oldCount) {
                throw new Setup_Exception('dead lock detected oldCount: ' . $oldCount);
            }
        } while(count($applicationTables) > 0);
                
        if($_application != 'Tinebase') {
            // remove application from table of installed applications
            $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_application);
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('application_id') . '= ?', $applicationId)
            );
            
            $this->_db->delete(SQL_TABLE_PREFIX . 'role_rights', $where);        
            $this->_db->delete(SQL_TABLE_PREFIX . 'container', $where);
                    
            Tinebase_Application::getInstance()->deleteApplication($_application);
        }
    }

    /**
     * install list of applications
     *
     * @param array $_applications list of application names
     * @todo resolve order of applications
     */
    public function installApplications($_applications)
    {
        foreach($_applications as $application) {
            $xml = $this->getSetupXml($application);
            $this->_installApplication($xml);
        }
    }
    
    /**
     * install given application
     *
     * @param  SimpleXMLElement $_xml
     * @return void
     */
    protected function _installApplication($_xml)
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
            'name'      => $_xml->name,
            'status'    => $_xml->status ? $_xml->status : Tinebase_Application::ENABLED,
            'order'     => $_xml->order ? $_xml->order : 99,
            'version'   => $_xml->version
        ));
        
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
    }
}
