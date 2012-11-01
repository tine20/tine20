<?php
/**
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * base for config classes
 * 
 * @package     Tinebase
 * @subpackage  Config
 * 
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
     * application config class cache (name => config record)
     * 
     * @var array
     */
    protected $_cachedApplicationConfig = NULL;
    
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
     * get config object for application
     * 
     * @param string $applicationName
     * @return Tinebase_Config_Abstract
     */
    public static function factory($applicationName)
    {
        if ($applicationName === 'Tinebase') {
            $config = Tinebase_Core::getConfig();
            // NOTE: this is a Zend_Config object in the Setup
            if ($config instanceof Tinebase_Config_Abstract) {
                return $config;
            }
        }
        
        $configClassName = $applicationName . '_Config';
        if (@class_exists($configClassName)) {
            $config = call_user_func(array($configClassName, 'getInstance'));
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Application ' . $applicationName . ' has no config class.');
            $config = NULL;
        }
        
        return $config;
    }
    
    /**
     * retrieve a value and return $default if there is no element set.
     *
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function get($name, $default = NULL)
    {
        // NOTE: we check config file data here to prevent db lookup when db is not yet setup
        $configFileSection = $this->_getConfigFileSection($name);
        if ($configFileSection) {
            return $this->_rawToConfig($configFileSection[$name], $name);
        }
        
        if (Tinebase_Core::getDb() && $config = $this->_loadConfig($name)) {
            $decodedConfigData = json_decode($config->value, TRUE);
            // @todo JSON encode all config data via update script!
            return $this->_rawToConfig(($decodedConfigData || is_array($decodedConfigData)) ? $decodedConfigData : $config->value, $name);
        }
        
        return $default;
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
            $this->clearCache();
        }
    }
    
    /**
     * delete all config for a application
     *
     * @param  string   $_applicationId
     * @return integer  number of deleted configs
     * 
     * @todo remove param as this should be known?
     */
    public function deleteConfigByApplicationId($_applicationId)
    {
        $count = $this->_getBackend()->deleteByProperty($_applicationId, 'application_id');
        $this->clearCache();
        
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
     * @return Tinebase_Model_Config|NULL
     */
    protected function _loadConfig($name)
    {
        if ($this->_cachedApplicationConfig === NULL) {
            $this->_loadAllAppConfigsInCache();
        }
        $result = (isset($this->_cachedApplicationConfig[$name])) ? $this->_cachedApplicationConfig[$name] :  NULL;
        
        return $result;
    }

    /**
    * fill class cache with all config records for this app
    */
    protected function _loadAllAppConfigsInCache()
    {
        if (empty($this->_appName)) {
            if (Setup_Core::isLogLevel(Zend_Log::WARN)) Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' appName not set');
            $this->_cachedApplicationConfig = array();
        }
        
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($this->_appName);
        
        if (Setup_Core::isLogLevel(Zend_Log::DEBUG)) Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Loading all configs for app ' . $this->_appName);
    
        $filter = new Tinebase_Model_ConfigFilter(array(
            array('field' => 'application_id', 'operator' => 'equals', 'value' => $applicationId),
        ));
        $allConfigs = $this->_getBackend()->search($filter);
        
        if (Setup_Core::isLogLevel(Zend_Log::DEBUG)) Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Found ' . count($allConfigs) . ' configs.');
        if (Setup_Core::isLogLevel(Zend_Log::TRACE)) Setup_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($allConfigs->toArray(), TRUE));
        
        foreach ($allConfigs as $config) {
            $this->_cachedApplicationConfig[$config->name] = $config;
        }
    }
    
    /**
     * store a config record in database
     * 
     * @param   Tinebase_Model_Config $_config record to save
     * @return  Tinebase_Model_Config
     * 
     * @todo only allow to save records for this app ($this->_appName)
     */
    protected function _saveConfig(Tinebase_Model_Config $_config)
    {
        if (Setup_Core::isLogLevel(Zend_Log::DEBUG)) Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting config ' . $_config->name);
        
        $config = $this->_loadConfig($_config->name);
        
        if ($config) {
            $config->value = $_config->value;
            try {
                $result = $this->_getBackend()->update($config);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // config might be deleted but cache has not been cleaned
                $result = $this->_getBackend()->create($_config);
            }
        } else {
            $result = $this->_getBackend()->create($_config);
        }
        
        $this->clearCache();
        
        return $result;
    }
    
    /**
     * clear the cache
     */
    public function clearCache()
    {
        if (Setup_Core::isLogLevel(Zend_Log::DEBUG)) Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Clearing config cache');
        $this->_cachedApplicationConfig = NULL;
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
