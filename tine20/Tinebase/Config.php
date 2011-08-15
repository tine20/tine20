<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * the class provides functions to handle config options
 * 
 * @package     Tinebase
 * @subpackage  Config
 */
class Tinebase_Config extends Tinebase_Config_Abstract
{
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Tinebase';
    
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
    private function __construct() {}
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __clone() {}
    
    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Config();
        }
        
        return self::$_instance;
    }
    

    
    /**
     * returns one config value identified by config name and application id
     * -> value in config.inc.php overwrites value in db if $_fromFile is TRUE
     * 
     * @deprecated
     * @param   string      $_name config name/key
     * @param   string      $_applicationId application id [optional]
     * @param   mixed       $_default the default value [optional]
     * @param   boolean     $_fromFile get from config.inc.php [optional]
     * @return  Tinebase_Model_Config  the config record
     * @throws  Tinebase_Exception_NotFound
     */
    public function getConfig($_name, $_applicationId = NULL, $_default = NULL, $_fromFile = TRUE)
    {
        $applicationId = ($_applicationId !== NULL ) 
            ? Tinebase_Model_Application::convertApplicationIdToInt($_applicationId) 
            : Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId();
            
        
        $result = $this->_loadConfig($_name, $applicationId);
        if (! $result) {
            $result = new Tinebase_Model_Config(array(
                'application_id'    => $applicationId,
                'name'              => $_name,
                'value'             => $_default,
            ), TRUE);
        }
            
        // check config.inc.php and get value from there
        $configFileData = $this->_getConfigFileData();
        if ($_fromFile && array_key_exists($_name, $configFileData)) {
            Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Overwriting config setting "' . $_name . '" with value from config.inc.php.');
            $result->value = $configFileData[$_name];
        } 
        
        return $result;
    }

    /**
     * returns one config value identified by config name and application name as array (it was json encoded in db)
     * 
     * @deprecated 
     * @param   string      $_name config name/key
     * @param   string      $_applicationName
     * @param   array       $_default the default value
     * @return  array       the config array
     * @throws Tinebase_Exception_NotFound
     */
    public function getConfigAsArray($_name, $_applicationName = 'Tinebase', $_default = array())
    {
        $config = $this->getConfig($_name, Tinebase_Application::getInstance()->getApplicationByName($_applicationName)->getId(), $_default);
        
        if (! is_object($config)) {
            throw new Tinebase_Exception_NotFound('Config object ' . $_name . ' not found or is not an object!');
        }
        
        $result = (is_array($config->value)) ? $config->value : Zend_Json::decode($config->value);
        
        return $result;
    }
    
    /**
     * set config for application
     *
     * @deprecated use set / __set
     * @param string $_name
     * @param string $_value
     * @param string $_applicationName [optional]
     * @return Tinebase_Model_Config
     */
    public function setConfigForApplication($_name, $_value, $_applicationName = 'Tinebase')
    {
        $value = (is_array($_value)) ? Zend_Json::encode($_value) : $_value;
        
        $configRecord = new Tinebase_Model_Config(array(
            "application_id"    => Tinebase_Application::getInstance()->getApplicationByName($_applicationName)->getId(),
            "name"              => $_name,
            "value"             => $value,              
        ));
        
        return $this->_saveConfig($configRecord);
    }

    /**
     * deletes one config setting
     * 
     * @deprecated
     * @param   Tinebase_Model_Config $_config record to delete
     * @return void
     */
    public function deleteConfig(Tinebase_Model_Config $_config)
    {
        $this->_getBackend()->delete($_config->getId());
        
        Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('config'));
    }
    
    /**
     * Delete config for application (simplified deleteConfig())
     *
     * @deprecated
     * @param string $_name
     * @param string $_applicationName [optional]
     * @return void
     */
    public function deleteConfigForApplication($_name, $_applicationName = 'Tinebase')
    {
        try {
            $configRecord = $this->getConfig($_name, Tinebase_Application::getInstance()->getApplicationByName($_applicationName)->getId());
            $this->deleteConfig($configRecord);
        } catch (Tinebase_Exception_NotFound $e) {
            //no config found => nothing to delete
        }
    }
    
    /**
     * get option setting string
     * 
     * @deprecated
     * @param Tinebase_Record_Interface $_record
     * @param string $_id
     * @param string $_label
     * @return string
     */
    public static function getOptionString($_record, $_label)
    {
        $controller = Tinebase_Core::getApplicationInstance($_record->getApplication());
        $settings = $controller->getConfigSettings();
        $idField = $_label . '_id';
        
        $option = $settings->getOptionById($_record->{$idField}, $_label . 's');
        
        $result = (isset($option[$_label])) ? $option[$_label] : '';
        
        return $result;
    }    
}
