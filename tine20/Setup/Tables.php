<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
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

    public function __construct()
    {
        try {
            $this->_config = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');
        } catch (Zend_Config_Exception $e) {
            die ('central configuration file ' . $_SERVER['DOCUMENT_ROOT'] . '/../config.ini not found');
        }

        $this->setupDatabaseConnection();

        $this->_backend = new Setup_Backend_Mysql();
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
     * parses the xml stream and creates the tables if needed
     *
     * @param string $_file path to xml file
     */
    public function parseFile($_file)
    {
        $createdTables = array();
        
        $xml = simplexml_load_file($_file);
        
        if(isset($xml->tables)) {
            foreach ($xml->tables[0] as $table) {
                  $tableName = SQL_TABLE_PREFIX . $table->name;
                  if(!$this->_backend->tableExists($this->_config->database->dbname, $tableName)) {
                    $this->_backend->createTable($table);
                    $createdTables[] = $table;
                  } else {
                    echo "skipped table {$tableName}. Table exists already.<br>";
                  }
            }
        }
        
        if(isset($xml->defaultRecords)) 
        {
            foreach ($xml->defaultRecords[0] as $record) 
            {
                $this->_backend->execInsertStatement($record);
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
