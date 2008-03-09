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
    private $tineExtDb;
    private $prefix;
    private $_config;

    public function __construct()
    {
        try {
            $this->_config = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');
        } catch (Zend_Config_Exception $e) {
            die ('central configuration file ' . $_SERVER['DOCUMENT_ROOT'] . '/../config.ini not found');
        }

        $this->setupDatabaseConnection();
    }

    /**
     * initializes the database connection
     *
     */
    protected function setupDatabaseConnection()
    {
        if(isset($this->_config->database)) {
            $dbConfig = $this->_config->database;

            $this->prefix = $dbConfig->get('tableprefix') ? $dbConfig->get('tableprefix') : 'tine20_';

            echo "setting table prefix to: {$this->prefix} <hr>";

            $db = Zend_Db::factory('PDO_MYSQL', $dbConfig->toArray());
            Zend_Db_Table_Abstract::setDefaultAdapter($db);

            Zend_Registry::set('dbAdapter', $db);
        } else {
            die ('database section not found in central configuration file');
        }
    }

    public function createTable($_table)
    {
        $statement = "CREATE TABLE IF NOT EXISTS `" . $this->prefix . $_table['name'] . "` (\n";

        foreach ($_table->fields[0] as $field) {
            if($field['name'] != '') {
                $statement .= "`" . $field['name'] . "` " . $field['type'] . " " . $field['NULL'];
                if (isset($field['extra'])) {
                    $statement .= " " . $field['extra'];
                }
                $statement .=",\n";
            }
        }

        foreach ($_table->keys[0] as $key) {
            $statement .= " " . $key['type'] . " `" . $this->prefix . $key['name'] . "` (" ;

            foreach ($key->keyfield as $keyfield) {
                $statement .= "`"  . (string)$keyfield . "`,";
            }
            	
            $statement = substr($statement, 0, (strlen($statement)-1)) . "),";
        }

        $statement = substr($statement, 0, (strlen($statement)-1)) ;
        $statement .= ")";

        $statement .= 	"\n ENGINE=" . $_table->engine . " DEFAULT CHARSET=" . $_table->charset;

        //echo $statement . "<hr>";

        Zend_Registry::get('dbAdapter')->query($statement);
    }



    public function parseFile($_file)
    {
        $xml = simplexml_load_file($_file);

        foreach ($xml->applications[0] as $application) {
            foreach ($application->table as $table) {
                $this->createTable($table);
            }
        }
    }
}
