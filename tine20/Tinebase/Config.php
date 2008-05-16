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
     * the db adapter
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db = '';
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __construct() {
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
     * holdes the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $instance = NULL;
    
    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Config;
        }
        
        return self::$instance;
    }
    
    
    /**
     * returns one config value identified by config name and application id
     * 
     * @param   int     $_applicationId application id
     * @param   string  $_name config name/key
     * @return  string  the config value
     * @todo    implement
     */
    public function getConfig($_applicationId, $_name)
    {
        $result = '';        
        return $result;
    }

    /**
     * returns all config settings for one application
     * 
     * @param   int     $_applicationId application id
     * @return  array
     * @todo    implement
     */
    public function getConfigForApplication($_applicationId)
    {
        $result = array();        
        return $result;
    }
    
    /**
     * sets one config value identified by config name and application id
     * 
     * @param   int     $_applicationId application id
     * @param   string  $_name config name/key
     * @param   string  $_value config value
     * @todo    implement
     */
    public function setConfig($_applicationId, $_name, $_value)
    {
    }            
}
