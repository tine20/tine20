<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        replace Zend_Db_Table_Abstract with Zend_Db_Adapter_Abstract
 */

/**
 * the class provides functions to handle config options
 * 
 * @package     Tinebase
 * @subpackage  Config
 */
class Tinebase_Config
{
    /**************************** application config *****************/
    
    /**
     * default user group const
     *
     */
    const DEFAULT_USER_GROUP = 'Default User Group';
    
    /**
     * default admin group const
     *
     */
    const DEFAULT_ADMIN_GROUP = 'Default Admin Group';
    
    /**************************** protected vars *********************/
    
    /**
     * the table object for the SQL_TABLE_PREFIX . config table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $_configTable;

    /**
     * the db adapter
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db = NULL;
    
    /**
     * holds the instance of the singleton
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
     * @param   string          $_name config name/key
     * @param   string          $_applicationId application id
     * @param   mixed|optional  $_default the default value
     * @return  Tinebase_Model_Config  the config record
     * @throws  Tinebase_Exception_NotFound
     */
    public function getConfig($_name, $_applicationId = NULL, $_default = NULL)
    {
        $applicationId = ($_applicationId !== NULL ) 
            ? Tinebase_Model_Application::convertApplicationIdToInt($_applicationId) 
            : Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId();
                
        $select = $this->_configTable->select();
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('application_id') . ' = ?', $applicationId))
               ->where($this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_name));
        
        if (!$row = $this->_configTable->fetchRow($select)) {
            if ($_default === NULL) {
                throw new Tinebase_Exception_NotFound("Application config setting with name $_name not found and no default value given!");
            } else {
                $result = new Tinebase_Model_Config(array(
                    'application_id'    => $applicationId,
                    'name'              => $_name,
                    'value'             => $_default
                ));
            }
        } else {
            $result = new Tinebase_Model_Config($row->toArray());
        }
        
        return $result;
    }

    /**
     * returns all config settings for one application
     * 
     * @param   string $_applicationId application id
     * @return  array with config name => value pairs
     * //@throws  Tinebase_Exception_NotFound
     */
    public function getConfigForApplication($_applicationId)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);

        $select = $this->_db->select();
        $select->from(SQL_TABLE_PREFIX . 'config')
               ->where($this->_db->quoteIdentifier('application_id') . ' = ?', $applicationId);
        $rows = $this->_db->fetchAssoc($select);
        //$result = new Tinebase_Record_RecordSet('Tinebase_Model_Config', $rows, true);

        //if (empty($rows)) {
        //    throw new Tinebase_Exception_NotFound("application $_applicationName config settings not found!");
        //}
        
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
            $config = $this->getConfig($_config->name, $_config->application_id);
            $config->value = $_config->value;

            // update
            $this->_configTable->update($config->toArray(), $this->_db->quoteInto('id = ?', $config->getId()));             
            
        } catch (Tinebase_Exception_NotFound $e) {
            $newId = $_config->generateUID();
            $_config->setId($newId);
            
            // create new
            $this->_configTable->insert($_config->toArray()); 
        }

        $config = $this->getConfig($_config->name, $_config->application_id);
        
        return $config;
    }
    
    /**
     * set config for application (simplified setConfig())
     *
     * @param string $_name
     * @param string $_value
     * @param string $_applicationName [optional]
     * @return Tinebase_Model_Config
     */
    public function setConfigForApplication($_name, $_value, $_applicationName = 'Tinebase')
    {
        $configRecord = new Tinebase_Model_Config(array(
            "application_id"    => Tinebase_Application::getInstance()->getApplicationByName($_applicationName)->getId(),
            "name"              => $_name,
            "value"             => $_value,              
        ));
        
        return $this->setConfig($configRecord);
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
