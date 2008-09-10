<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * the class provides functions to handle applications
 * 
 * @package     Tinebase
 * @subpackage  Config
 */
class Tinebase_Config
{
    /**
     * the table object for the SQL_TABLE_PREFIX . config table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $_configTable;

    /**
     * the table object for the SQL_TABLE_PREFIX . user config table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $_configUserTable;
    
    /**
     * the db adapter
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db = NULL;
    
    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __construct() 
    {
        $this->_configTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'config'));
        $this->_configUserTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'config_user'));
        $this->_db = $this->_configTable->getAdapter();
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }

    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Config;
        }
        
        return self::$_instance;
    }
    
    
    /**
     * returns one config value identified by config name and application id
     * 
     * @param   int     $_applicationId application id
     * @param   string  $_name config name/key
     * @return  Tinebase_Model_Config  the config record
     */
    public function getConfig($_applicationId, $_name)
    {
        $select = $this->_configTable->select();
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('application_id') . ' = ?', $_applicationId))
               ->where($this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_name));
        
        if (!$row = $this->_configTable->fetchRow($select)) {
            throw new Exception("application config setting with name $_name not found!");
        }
        
        $result = new Tinebase_Model_Config($row->toArray());
        
        return $result;
    }

    /**
     * returns one preference value identified by user id, config name and application id
     * 
     * @param   int     $_userId 
     * @param   string  $_name config name/key
     * @param   int     $_applicationId application id (if NULL -> use Tinebase application)
     * @param   bool    $_checkDefault
     * @return  Tinebase_Model_Config  the config record
     * 
     * @todo get preference from config table if it doesn't exist in config_user
     */
    public function getPreference($_userId, $_name, $_applicationId = NULL, $_checkDefault = TRUE)
    {
        $applicationId = ($_applicationId !== NULL ) ? $_applicationId : Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId();
        
        $select = $this->_configUserTable->select();
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('application_id') . ' = ?', $applicationId))
               ->where($this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_name))
               ->where($this->_db->quoteInto($this->_db->quoteIdentifier('user_id') . ' = ?', $_userId));
        
        if (!$row = $this->_configTable->fetchRow($select)) {
            if ($_checkDefault) {
                $result = $this->getConfig($applicationId, $_name);
            } else {
                throw new Exception("user preference with name $_name not found!");
            }
        } else {
            $result = new Tinebase_Model_Config($row->toArray());    
        }
        
        return $result;
    }
    
    /**
     * returns all config settings for one application
     * 
     * @param   string     $_applicationName application name
     * @return  array with config name => value pairs
     */
    public function getConfigForApplication($_applicationName)
    {
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName($_applicationName)->getId();
        
        $select = $this->_db->select();
        $select->from(SQL_TABLE_PREFIX . 'config')
               ->where($this->_db->quoteIdentifier('application_id') . ' = ?', $applicationId);
        $rows = $this->_db->fetchAssoc($select);
        //$result = new Tinebase_Record_RecordSet('Tinebase_Model_Config', $rows, true);

        if (empty($rows)) {
            throw new Exception("application $_applicationName config settings not found!");
        }
        
        $result = array();
        foreach ( $rows as $row ) {
            $result[$row['name']] = $row['value'];
        }

        return $result;
    }
    
    /**
     * sets one config value identified by config name and application id
     * 
     * @param   Tinebase_Model_Config $_config record to set
     * @return  Tinebase_Model_Config
     */
    public function setConfig(Tinebase_Model_Config $_config)
    {
        // check if already in
        try {
            $config = $this->getConfig($_config->application_id, $_config->name);
            $config->value = $_config->value;

            // update
            $this->_configTable->update($config->toArray(), $this->_db->quoteInto('id = ?', $config->getId()));             
            
        } catch ( Exception $e ) {
            $newId = $_config->generateUID();
            $_config->setId($newId);
            
            // create new
            $this->_configTable->insert($_config->toArray()); 
        }

        $config = $this->getConfig($_config->application_id, $_config->name);
        
        return $config;
    }     

    /**
     * sets one config value identified by config name and application id
     * 
     * @param   int     $_userId 
     * @param   Tinebase_Model_Config $_config record to set
     * @return  Tinebase_Model_Config
     */
    public function setPreference($_userId, Tinebase_Model_Config $_config)
    {
        // check if already in
        try {
            $config = $this->getPreference($_userId, $_config->name, $_config->application_id, FALSE);
            $config->value = $_config->value;

            // update
            $this->_configUserTable->update($config->toArray(), $this->_db->quoteInto('id = ?', $config->getId()));             
            
        } catch ( Exception $e ) {
            $newId = $_config->generateUID();
            $_config->setId($newId);
            
            $configArray = $_config->toArray();
            $configArray['user_id'] = $_userId;
            
            // create new
            $this->_configUserTable->insert($configArray); 
        }

        $config =  $this->getPreference($_userId, $_config->name, $_config->application_id);
        
        return $config;
    }     
    
    /**
     * deletes one config setting
     * 
     * @param   Tinebase_Model_Config $_config record to delete
     */
    public function deleteConfig(Tinebase_Model_Config $_config)
    {
        $this->_configTable->delete($this->_db->quoteInto('id = ?', $_config->getId()));
    }
    
}
