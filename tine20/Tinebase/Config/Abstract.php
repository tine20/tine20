<?php
abstract class Tinebase_Config_Abstract
{
        
//    /**
//     * entry is readable part of registry
//     * @var int
//     */
//    const SCOPE_REGISTRY = 1;
//    /**
//     * entry is get- setable in admin module
//     * @var int
//     */
//    const SCOPE_ADMIN = 2;
//    /**
//     * entry is get- setable in setup
//     * @var int
//     */
//    const SCOPE_SETUP = 4;
    
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
    private static $_configFileData = NULL;
    
    /**
     * config database backend
     * 
     * @var Tinebase_Backend_Sql
     */
    private static $_backend;
    
    
//    /**
//     * Array of property definition arrays
//     * 
//     * @staticvar array
//     */
//    abstract protected static $_properties = array();
//    
//    /**
//     * get properties definitions 
//     * 
//     * NOTE: as static late binding is not possible in PHP < 5.3 
//     *       this function has to be implemented in each subclass
//     * 
//     * @return array
//     */
//    abstract public static function getProperties();
    
//    /**
//     * the constructor
//     *
//     * don't use the constructor. use the singleton 
//     */    
//    protected function __construct() 
//    {
//        $this->_configFileData = $this->_getConfigFileData();
//    }
    
    
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
        
        // appName section overwrites global section in config file
        $configFileData = $this->_getConfigFileData();
        $configFileSection = array_key_exists($this->_appName, $configFileData) && array_key_exists($_name, $configFileData[$this->_appName]) ? $configFileData[$this->_appName] :
                            (array_key_exists($_name, $configFileData) ? $configFileData : NULL);
        
        // config file overwrites db
        if ($configFileSection) {
            $configData = $configFileSection[$_name];
        } else {
            $configRecord = $this->_loadConfig($_name, $this->_appName);
            $configData = $configRecord ? $configRecord->value : $_default;
        }
        
        // @todo JSON encode all config data via update script!
        // auto JSON decode
        if ($configData && is_scalar($configData)) {
            $decodedData = json_decode($configData, TRUE);
            if ($decodedData) {
                $configData = $decodedData;
            }
        }
        
        // convert array data to struct as long as we don't know better
        return is_array($configData) ? new Tinebase_Config_Struct($configData) : $configData;
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
        $value = $this->get($_name, '###NOTSET###');
        
        return $value !== '###NOTSET###';
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
     * load a config record from database
     * 
     * @param  string                   $_name
     * @param  mixed                    $_application
     * @return Tinebase_Model_Config
     */
    protected function _loadConfig($_name, $_application = null)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_application ? $_application : $this->_appName);
        
        $cacheId = '_loadConfig_' . sha1($applicationId . $_name);
        
        if (Tinebase_Core::getCache()->test($cacheId)) {
            $result = Tinebase_Core::getCache()->load($cacheId);
            return $result;
        }
        
        $filter = new Tinebase_Model_ConfigFilter(array(
            array('field' => 'application_id', 'operator' => 'equals', 'value' => $applicationId),
            array('field' => 'name',           'operator' => 'equals', 'value' => $_name        ),
        ));
        
        $result = $this->_getBackend()->search($filter)->getFirstRecord();
        
        Tinebase_Core::getCache()->save($result, $cacheId, array('config'));
        
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
        Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting config ' . $_config->name);
        
        $config = $this->_loadConfig($_config->name, $_config->application_id);
        if ($config) {
            // update
            $config->value = $_config->value;
            $result = $this->_getBackend()->update($config);
            
        } else {
            // create new
            $result = $this->_getBackend()->create($_config);
        }
        
        Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('config'));
        
        return $result;
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
}