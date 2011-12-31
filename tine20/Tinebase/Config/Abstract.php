<?php
/**
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * base for config classes
 * 
 * @package     Tinebase
 * @subpackage  Config
 * @todo support protected function interceptor for get property: _get<PropertyName>(&$data)
 * @todo support protected function interceptor for set property: _set<PropertyName>(&$data)
 * @todo update db to json encode all configs
 * @todo support array collections definitions
 */
abstract class Tinebase_Config_Abstract
{
    /**
     * object config type
     * 
     * @var string
     */
    const TYPE_OBJECT = 'object';

    /**
     * integer config type
     * 
     * @var string
     */
    const TYPE_INT = 'int';
    
    /**
     * string config type
     * 
     * @var string
     */
    const TYPE_STRING = 'string';
    
    /**
     * float config type
     * 
     * @var string
     */
    const TYPE_FLOAT = 'float';
    
    /**
     * dateTime config type
     * 
     * @var string
     */
    const TYPE_DATETIME = 'dateTime';
    
    /**
     * keyFieldConfig config type
     * 
     * @var string
     */
    const TYPE_KEYFIELD = 'keyFieldConfig';
    
    /**
     * application name this config belongs to
     *
     * @var string
     */
    protected $_appName;
    
    /**
     * config file data.
     * 
     * @var array
     */
    private static $_configFileData;
    
    /**
     * config database backend
     * 
     * @var Tinebase_Backend_Sql
     */
    private static $_backend;
    
    /**
     * get properties definitions 
     * 
     * NOTE: as static late binding is not possible in PHP < 5.3 
     *       this function has to be implemented in each subclass
     *       and can not even be declared here
     * 
     * @return array
     */
//    abstract public static function getProperties();
    
    /**
     * retrieve a value and return $default if there is no element set.
     *
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function get($_name, $_default = null)
    {
        // return app /spechial config classes
        $configClassName = ucfirst($_name) . '_Config';
        if (@class_exists($configClassName)) {
            return call_user_func(array($configClassName, 'getInstance'));
        }      
        
        // NOTE: we check config file data here to prevent db lookup when db is not yet setup
        $configFileSection = $this->_getConfigFileSection($_name);
        if ($configFileSection) {
            return $this->_rawToConfig($configFileSection[$_name], $_name);
        }
        
        if (Tinebase_Core::getDb() && $configRecord = $this->_loadConfig($_name, $this->_appName)) {
            $configData = json_decode($configRecord->value, TRUE);
            // @todo JSON encode all config data via update script!
            return $this->_rawToConfig($configData ? $configData : $configRecord->value, $_name);
        } else {
            return $_default;
        }
    }
    
    /**
     * store a config value
     *
     * @param  string   $_name      config name
     * @param  mixed    $_value     config value
     * @return void
     */
    public function set($_name, $_value)
    {
        $configRecord = new Tinebase_Model_Config(array(
            "application_id"    => Tinebase_Application::getInstance()->getApplicationByName($this->_appName)->getId(),
            "name"              => $_name,
            "value"             => json_encode($_value),              
        ));
        
        $this->_saveConfig($configRecord);
    }
    
    /**
     * delete a config from database
     * 
     * @param  string   $_name
     * @return void
     */
    public function delete($_name)
    {
        $config = $this->_loadConfig($_name);
        if ($config) {
            $this->_getBackend()->delete($config->getId());
            
            Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('config'));
        }
    }
    
    /**
     * delete all config for a application
     *
     * @param  string   $_applicationId
     * @return integer  number of deleted configs
     */
    public function deleteConfigByApplicationId($_applicationId)
    {
        $count = $this->_getBackend()->deleteByProperty($_applicationId, 'application_id');
        
        Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('config'));
        
        return $count;
    }
    
    /**
     * Magic function so that $obj->value will work.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($_name)
    {
        return $this->get($_name);
    }
    
    /**
     * Magic function so that $obj->configName = configValue will work.
     *
     * @param  string   $_name      config name
     * @param  mixed    $_value     config value
     * @return void
     */
    public function __set($_name, $_value)
    {
        $this->set($_name, $_value);
    }
    
    /**
     * checks if a config name is set
     * isset means that the config key is present either in config file or in db
     * 
     * @param  string $_name
     * @return bool
     */
    public function __isset($_name)
    {
        // NOTE: we can't test more precise here due to cacheing
        $value = $this->get($_name, Tinebase_Model_Config::NOTSET);
        
        return $value !== Tinebase_Model_Config::NOTSET;
    }
    
    /**
     * returns data from central config.inc.php file
     * 
     * @return array
     */
    protected function _getConfigFileData()
    {
        if (! self::$_configFileData) {
            $configData = include('config.inc.php');
            
            if($configData === false) {
                die ('central configuration file config.inc.php not found in includepath: ' . get_include_path());
            }
            
            self::$_configFileData = $configData;
        }
        
        return self::$_configFileData;
    }
    
    /**
     * get config file section where config identified by name is in
     * 
     * @param  string $_name
     * @return array
     */
    protected function _getConfigFileSection($_name)
    {
        $configFileData = $this->_getConfigFileData();
        
        // appName section overwrites global section in config file
        return array_key_exists($this->_appName, $configFileData) && array_key_exists($_name, $configFileData[$this->_appName]) ? $configFileData[$this->_appName] :
              (array_key_exists($_name, $configFileData) ? $configFileData : NULL);
    }
    
    /**
     * load a config record from database
     * 
     * @param  string                   $_name
     * @param  mixed                    $_application
     * @return Tinebase_Model_Config
     */
    protected function _loadConfig($_name, $_application = null)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_application ? $_application : $this->_appName);
        
        $cache = Tinebase_Core::getCache();
        $cacheId = '_loadConfig_' . sha1($applicationId . $_name);
        
        if ($cache && $cache->test($cacheId)) {
            $result = $cache->load($cacheId);
            if (is_object($result)) {
                return $result;
            }
        }
        
        $filter = new Tinebase_Model_ConfigFilter(array(
            array('field' => 'application_id', 'operator' => 'equals', 'value' => $applicationId),
            array('field' => 'name',           'operator' => 'equals', 'value' => $_name        ),
        ));
        
        $result = $this->_getBackend()->search($filter)->getFirstRecord();
        
        if ($cache) $cache->save($result, $cacheId, array('config'), 60);
        
        return $result;
    }
    
    /**
     * store a config record in database
     * 
     * @param   Tinebase_Model_Config $_config record to save
     * @return  Tinebase_Model_Config
     */
    protected function _saveConfig(Tinebase_Model_Config $_config)
    {
        if (Setup_Core::isLogLevel(Zend_Log::DEBUG)) Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' setting config ' . $_config->name);
        
        $config = $this->_loadConfig($_config->name, $_config->application_id);
        if ($config) {
            $config->value = $_config->value;
            $result = $this->_getBackend()->update($config);
            
        } else {
            $result = $this->_getBackend()->create($_config);
        }
        
        $this->_clearCache();
        
        return $result;
    }
    
    /**
     * clear the cache
     */
    protected function _clearCache()
    {
        if (Setup_Core::isLogLevel(Zend_Log::DEBUG)) Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' clearing cache ... ');
        
        Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('config'));
    }
    
    /**
     * returns config database backend
     * 
     * @return Tinebase_Backend_Sql
     */
    protected function _getBackend()
    {
        if (! self::$_backend) {
            self::$_backend = new Tinebase_Backend_Sql(array(
                'modelName' => 'Tinebase_Model_Config', 
                'tableName' => 'config',
            ));
        }
        
        return self::$_backend;
    }
    
    /**
     * converts raw data to config values of defined type
     * 
     * @TODO support array contents conversion
     * @TODO support interceptors
     * @TODO find a place for defaults of definition
     * 
     * @param   mixed     $_rawData
     * @param   string    $_name
     * @return  mixed
     */
    protected function _rawToConfig($_rawData, $_name)
    {
        $definition = self::getDefinition($_name);
        
//        // config.inc.php overwrites all other rawData
//        $configFileSection = $this->_getConfigFileSection($_name);
//        if ($configFileSection) {
//            $_rawData = $configFileSection[$_name];
//        }
        
        if (! $definition) {
            return is_array($_rawData) ? new Tinebase_Config_Struct($_rawData) : $_rawData;
        }
        
//        // get default from definition if needed
//        if (is_string($_rawData) && $_rawData == Tinebase_Model_Config::NOTSET) {
//            if (array_key_exists('default', $definition)) {
//                $_rawData = $definition['default'];
//            }
//        }
        
        if ($definition['type'] === self::TYPE_OBJECT && isset($definition['class']) && @class_exists($definition['class'])) {
            return new $definition['class']($_rawData);
        }
        
        switch ($definition['type']) {
            case self::TYPE_INT:        return (int) $_rawData;
            case self::TYPE_STRING:     return (string) $_rawData;
            case self::TYPE_FLOAT:      return (float) $_rawData;
            case self::TYPE_DATETIME:   return new DateTime($_rawData);
            case self::TYPE_KEYFIELD:   return Tinebase_Config_KeyField::create($_rawData, array_key_exists('options', $definition) ? (array) $definition['options'] : array());
            default:                    return is_array($_rawData) ? new Tinebase_Config_Struct($_rawData) : $_rawData;
        }
    }
    
    /**
     * get definition of given property
     * 
     * @param   string  $_name
     * @return  array
     */
    public function getDefinition($_name)
    {
        // NOTE we can't call statecally here (static late binding again)
        $properties = $this->getProperties();
        
        return array_key_exists($_name, $properties) ? $properties[$_name] : NULL;
    }
    
    /**
     * check if config system is ready
     * 
     * @todo check db setup
     * @return bool
     */
    public static function isReady()
    {
        $configFile = @file_get_contents('config.inc.php', FILE_USE_INCLUDE_PATH);
        
        return !! $configFile;
    }
}