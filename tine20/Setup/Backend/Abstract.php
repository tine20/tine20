<?php

/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @copyright   Copyright (c); 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de);
 * @version     $Id: Abstract.php 1735 2008-04-05 20:08:37Z lkneschke $
 *
 */

/**
 * interface for backend class
 * 
 * @package     Setup
 */
abstract class Setup_Backend_Abstract implements Setup_Backend_Interface
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db = NULL;
    
    /**
     * config object
     *
     * @var Zend_Config
     */
    protected $_config = NULL;
    
    /**
     * constructor
     *
     */
    public function __construct()
    {
        $this->_config = Tinebase_Core::getConfig();
        $this->_db = Tinebase_Core::getDb();
    }
    
    /**
     * checks if application is installed at all
     *
     * @param unknown_type $_application
     * @return boolean
     */
    public function applicationExists($_application)
    {
        if ($this->tableExists('applications')) {
            if ($this->applicationVersionQuery($_application) != false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * check's a given database table version 
     *
     * @param string $_tableName
     * @return boolean return string "version" if the table exists, otherwise false
     */
    public function tableVersionQuery($_tableName)
    {
        $select = $this->_db->select()
            ->from( SQL_TABLE_PREFIX . 'application_tables')
            ->where($this->_db->quoteIdentifier('name') . ' = ?', SQL_TABLE_PREFIX . $_tableName);

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
        $select = $this->_db->select()
            ->from( SQL_TABLE_PREFIX . 'applications')
            ->where($this->_db->quoteIdentifier('name') . ' = ?', $_application);

        $stmt = $select->query();
        $version = $stmt->fetchAll();
        
        if (empty($version)) {
            return false;
        } else {
            return $version[0]['version'];
        }
    }
    
    /**
     * execute insert statement for default values (records)
     * handles some special fields, which can't contain static values
     * 
     * @param   SimpleXMLElement $_record
     * @throws  Setup_Exception
     */
    public function execInsertStatement(SimpleXMLElement $_record)
    {
        $data = array();
        
        foreach ($_record->field as $field) {
            if (isset($field->value['special'])) {
                switch(strtolower($field->value['special'])) {
                    case 'now':
                        $value = Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
                        break;
                    
                    case 'account_id':
                        break;
                    
                    case 'application_id':
                        $application = Tinebase_Application::getInstance()->getApplicationByName($field->value);
                        $value = $application->id;
                        break;
                    
                    case 'uid':
                        $value = Tinebase_Record_Abstract::generateUID();
                        break;
                        
                    default:
                        throw new Setup_Exception('Unsupported special type ' . strtolower($field->value['special']));                    
                    }
            } else {
                $value = $field->value;
            }
            // buffer for insert statement
            $data[(string)$field->name] = (string)$value;
        }
        
        #$table = new Tinebase_Db_Table(array(
        #   'name' => SQL_TABLE_PREFIX . $_record->table->name
        #));

        #// final insert process
        #$table->insert($data);
        
        #var_dump($data);
        #var_dump(SQL_TABLE_PREFIX . $_record->table->name);
        $this->_db->insert(SQL_TABLE_PREFIX . $_record->table->name, $data);
    }

    /**
     * execute statement without return values
     * 
     * @param string statement
     */    
    public function execQueryVoid($_statement)
    {
        $stmt = $this->_db->query($_statement);
    }
    
    /**
     * execute statement  return values
     * 
     * @param string statement
     * @return stdClass object
     */       
    public function execQuery($_statement)
    {
        $stmt = $this->_db->query($_statement);
        
        return $stmt->fetchAll();
    }
    
    /**
     * removes table from database
     * 
     * @param string tableName
     */
    public function dropTable($_tableName)
    {
    	Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Dropping table ' . $_tableName);
        $statement = "DROP TABLE " . $this->_db->quoteTableAs(SQL_TABLE_PREFIX . $_tableName);
        $this->execQueryVoid($statement);
    }
}