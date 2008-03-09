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
    private $_prefix;
    private $_config;

    public function __construct()
    {
        try {
            $this->_config = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');
        } catch (Zend_Config_Exception $e) {
            die ('central configuration file ' . $_SERVER['DOCUMENT_ROOT'] . '/../config.ini not found');
        }

        $this->setupDatabaseConnection();

        $this->_backend = new Setup_Backend_Mysql($this->_prefix);
    }

    /**
     * initializes the database connection
     *
     */
    protected function setupDatabaseConnection()
    {
        if(isset($this->_config->database)) {
            $dbConfig = $this->_config->database;

            $this->_prefix = $dbConfig->get('tableprefix') ? $dbConfig->get('tableprefix') : 'tine20_';

            echo "setting table prefix to: {$this->_prefix} <hr>";

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
        $xml = simplexml_load_file($_file);
        
        foreach ($xml->tables[0] as $table) {
              $tableName = $this->_prefix . $table['name'];
              if(!$this->_backend->tableExists($this->_config->database->dbname, $tableName)) {
                $this->_backend->createTable($table);
              } else {
                echo "skipped table {$tableName}. Table exists already.<br>";
              }
        }
    }
}
