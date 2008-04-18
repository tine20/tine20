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
class Setup_Tables
{
    private $_backend;
    private $_config;

    /**
     * the contructor
     *
     */
    public function __construct()
    {
        $this->_config = Zend_Registry::get('configFile');

        $this->setupLogger();
        $this->setupDatabaseConnection();
        
        #switch ($this->_config->database->database) {
        #    case('mysql'):
                $this->_backend = new Setup_Backend_Mysql();
        #        break;
        #    
        #    default:
        #        echo "you have to define a dbms = yourdbms (like mysql) in your config.ini file";
        #}
    }

    /**
     * initializes the logger
     *
     */
    protected function setupLogger()
    {
        $logger = new Zend_Log();

        if(isset($this->_config->logger)) {
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
        if(isset($this->_config->database)) {
            $dbConfig = $this->_config->database;

            define('SQL_TABLE_PREFIX', $dbConfig->get('tableprefix') ? $dbConfig->get('tableprefix') : 'tine20_');

            echo "setting table prefix to: " . SQL_TABLE_PREFIX . " <hr>";

            $db = Zend_Db::factory('PDO_MYSQL', $dbConfig->toArray());
            Zend_Db_Table_Abstract::setDefaultAdapter($db);

            Zend_Registry::set('dbAdapter', $db);
        } else {
            die ('database section not found in central configuration file');
        }
    }

    /**
     * checks if application is installed at all
     *
     * @param unknown_type $_application
     * @return unknown
     */
    public function applicationExists($_application)
    {
        if($this->tableExists(SQL_TABLE_PREFIX . 'applications')) {
            if($this->applicationVersionQuery($_application) != false) {
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * parses the xml stream and creates the tables if needed
     *
     * @param string $_file path to xml file
     */
    public function parseFile($_file)
    {
        $createdTables = array();
    
        $xml = simplexml_load_file($_file);
        
        if (!$this->applicationExists($xml->name)) {
            // just insert tables
            if(isset($xml->tables)) {
                foreach ($xml->tables[0] as $table) {
                    if (false == $this->tableExists(SQL_TABLE_PREFIX . $table->name)) {
                        $this->_backend->_createTable($table);
                        $createdTables[] = $table;
                    }
                }
            }
            
            
            try {
                $application = Tinebase_Application::getInstance()->getApplicationByName($xml->name);
            } catch (Exception $e) {
                $application = new Tinebase_Model_Application(array(
                    'name'      => $xml->name,
                    'status'    => Tinebase_Application::ENABLED,
                    'order'     => $xml->order ? $xml->order : 99,
                    'version'   => $xml->version
                ));

                $application = Tinebase_Application::getInstance()->addApplication($application);
            }

            foreach($createdTables as $table) {
                $this->addTable($application, SQL_TABLE_PREFIX . $table->name, $table->version);
            }

            if(isset($xml->defaultRecords)) {
                foreach ($xml->defaultRecords[0] as $record) {
                    $this->execInsertStatement($record);
                }
            }    
                
            
            return 'initialLoad';    
        } else {
            $application = Tinebase_Application::getInstance()->getApplicationByName($xml->name);

            switch(version_compare($application->version, $xml->version)) {
                case -1:
                    $this->updateApplication($xml->name, $application->version, $xml->version);
                    
                    echo "<strong>" . $xml->name . " had different specifications. Now up-to-date to version " .  $xml->version . "</strong><br>";
                    break; 
                case 0:
                    echo "<i>" . $xml->name . " no changes " . $xml->version . "</i><br>";
                    break;
                case 1:
                    echo "<span style=color:#ff0000>database schema newer as your new definition - giving up</span>";
                    break;
            }
            return 'update';
        }
    }



    /**
     * check's if a given table exists
     *
     * @param string $_tableSchema
     * @param string $_tableName
     * @return boolean return true if the table exists, otherwise false
     */
    public function tableExists($_tableName)
    {
         $select = Zend_Registry::get('dbAdapter')->select()
          ->from('information_schema.tables')
          ->where('TABLE_SCHEMA = ?', $this->_config->database->dbname)
          ->where('TABLE_NAME = ?', $_tableName);

        $stmt = $select->query();

        $table = $stmt->fetchObject();
 

        if($table === false) {
          return false;
        }

        return true; 
    }
    
    /**
     * check's a given database table version 
     *
     * @param string $_tableName
     * @return boolean return string "version" if the table exists, otherwise false
     */
    
    public function tableVersionQuery($_tableName)
    {
        $select = Zend_Registry::get('dbAdapter')->select()
                ->from( SQL_TABLE_PREFIX . 'application_tables')
                ->where('name = ?', $_tableName);

        $stmt = $select->query();
        $version = $stmt->fetchAll();
        
        return $version[0]['version'];
    }
    
    /**
     * check's a given application version
     *
     * @param string $_application
     * @return boolean return string "version" if the table exists, otherwise false
     */
    public function applicationVersionQuery($_application)
    {    
        $select = Zend_Registry::get('dbAdapter')->select()
                ->from( SQL_TABLE_PREFIX . 'applications')
                ->where('name = ?', $_application);

        $stmt = $select->query();
        $version = $stmt->fetchAll();
        if(empty($version)) {
            return false;
        } else {
            return $version[0]['version'];
        }
    }

    /**
     * compare versions
     *
     * @param string $_tableName (database)
     * @param string $_tableVersion (xml-file)
     * @return 1,0,-1
     */
    public function applicationNeedsUpdate($_applicationName, $_applicationVersion)
    {
        return version_compare($this->applicationVersionQuery($_applicationName),  $_applicationVersion);
    }
    
    
    /**
     * compare versions
     *
     * @param string $_tableName (database)
     * @param string $_tableVersion (xml-file)
     * @return 1,0,-1
     */
    public function tableNeedsUpdate($_tableName, $_tableVersion)
    {
        return version_compare($this->tableVersionQuery($_tableName),  $_tableVersion);
    }

    public function updateApplication($_name, $_updateFrom, $_updateTo)
    {
        echo "Updateing $_name from $_updateFrom to $_updateTo<br>";
        
        list($fromMajorVersion, $fromMinorVersion) = explode('.', $_updateFrom);
        list($toMajorVersion, $toMinorVersion) = explode('.', $_updateTo);

        $minor = $fromMinorVersion;
        
        for($major = $fromMajorVersion; $major <= $toMajorVersion; $major++) {
            $className = ucfirst($_name) . '_Setup_Update_Release' . $major;
            
            $update = new $className($this->_backend);
            
            $classMethods = get_class_methods($update);
            
            // we must do at least one update
            do {
                $functionName = 'update_' . $minor;
                //echo "FUNCTIONNAME: $functionName<br>";
                $update->$functionName();
                $minor++;
            } while(array_search('update_' . $minor, $classMethods) !== false);
            
            //reset minor version to 0
            $minor = 0;
        }
    }
    
    
    public function execInsertStatement($_record)
    {
        $table = new Tinebase_Db_Table(array(
           'name' => SQL_TABLE_PREFIX . $_record->table->name
        ));

        foreach ($_record->field as $field) {
            if(isset($field->value['special'])) {
                switch(strtolower($field->value['special'])) {
                    case 'now':
                    {
                        $value = Zend_Date::now()->getIso();
                        break;
                    }
                    case 'account_id':
                    {
                        break;
                    }
                    case 'application_id':
                    {
                        $application = Tinebase_Application::getInstance()->getApplicationByName($field->value);

                        $value = $application->id;

                        break;
                    }
                    default:
                    {
                        throw new Exception('unsupported special type ' . strtolower($field->value['special']));
                        break;
                    }
                }
            } else {
                $value = $field->value;
            }

            $data[(string)$field->name] = $value;
        }

        $table->insert($data);
    }

    public function addTable(Tinebase_Model_Application $_application, $_name, $_version)
    {
        $applicationTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'application_tables'));

        $applicationData = array(
            'application_id'    => $_application->id,
            'name'              => $_name,
            'version'           => $_version
        );

        $applicationID = $applicationTable->insert($applicationData);

        return $applicationID;
    }
}
