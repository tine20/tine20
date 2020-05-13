<?php
/**
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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
abstract class Tinebase_Config_Abstract implements Tinebase_Config_Interface
{
    const CLASSNAME             = 'class';
    const CLIENTREGISTRYINCLUDE = 'clientRegistryInclude';
    const CONTENT               = 'content';
    const DEFAULT_STR           = 'default';
    const DESCRIPTION           = 'description';
    const LABEL                 = 'label';
    const OPTIONS               = 'options';
    const SETBYADMINMODULE      = 'setByAdminModule';
    const SETBYSETUPMODULE      = 'setBySetupModule';
    const TYPE                  = 'type';

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
     * boolean config type
     * 
     * @var string
     */
    const TYPE_BOOL = 'bool';
    
    /**
     * string config type
     * 
     * @var string
     */
    const TYPE_STRING = 'string';

    /**
     * record config type
     * behaves like a string, only when sent to the client it will be resolved to the actual record
     *
     * @var string
     */
    const TYPE_RECORD = 'record';

    const APPLICATION_NAME = 'appName';
    const MODEL_NAME = 'modelName';

    // @TODO: remove TYPE_RECORD_CONTROLLER and derive it from modelName!
    /**
     * @deprecated
     */
    const TYPE_RECORD_CONTROLLER = 'recordController';

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
     * keyField config type
     *
     * @var string
     */
    const TYPE_KEYFIELD = 'keyField';

    /**
     * array config type
     *
     * @var string
     */
    const TYPE_ARRAY = 'array';

    /**
     * keyFieldConfig config type
     * 
     * @var string
     */
    const TYPE_KEYFIELD_CONFIG = 'keyFieldConfig';
    
    /**
     * config key for enabled features / feature switch
     *
     * @var string
     */
    const ENABLED_FEATURES = 'features';
    
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

    protected $_mergedConfigCache = array();

    protected $_isAppDefaultConfigMerged = false;

    /**
     * server classes
     *
     * @var array
     */
    protected static $_serverPlugins = array();
    
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
     * @param string $val
     * @return string
     */
    public static function uncertainJsonDecode($val)
    {
        if (!is_string($val)) return $val;
        
        $result = json_decode($val, TRUE);
        if (null === $result && strtolower($val) !== '{null}') $result = $val;

        return $result;
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
        if (isset($this->_mergedConfigCache[$name]) || array_key_exists($name, $this->_mergedConfigCache)) {
            if (!isset($this->_mergedConfigCache[$name]) && null !== $default) {
                return $default;
            }
            return $this->_mergedConfigCache[$name];
        }

        $fileConfigArray = null;
        $dbConfigArray = null;
        $dbAvailable = 'logger' === $name ? false :
            (('database' === $name || 'caching' === $name) ? Tinebase_Core::hasDb() : true);

        // NOTE: we return here (or in the default handling) if db is not available. That is to prevent db lookup when db is not yet setup
        $configFileSection = $this->getConfigFileSection($name);
        if (null !== $configFileSection) {
            $fileConfigArray = $configFileSection[$name];
            if (false === $dbAvailable) {
                return $this->_rawToConfig($fileConfigArray, $name);
            }
        }
        
        if (true === $dbAvailable && null !== ($config = $this->_loadConfig($name))) {
            $dbConfigArray = static::uncertainJsonDecode($config->value);
        }

        $data = null;
        if (null === $fileConfigArray && null === $dbConfigArray) {
            if ($default !== null) {
                return $default;
            }

            $data = $this->_getDefault($name);
        } else {

            if (null === $fileConfigArray) {
                $fileConfigArray = array();
            } elseif(!is_array($fileConfigArray)) {
                $data = $fileConfigArray;
            }

            if (null === $dbConfigArray) {
                $dbConfigArray = array();
            } elseif(!is_array($dbConfigArray) && null === $data) {
                if (count($fileConfigArray) > 0) {
                    $dbConfigArray = array();
                } else {
                    $data = $dbConfigArray;
                }
            }

            if (null === $data) {
                $data = array_replace_recursive($dbConfigArray, $fileConfigArray);
            }
        }

        $this->_mergedConfigCache[$name] = $this->_rawToConfig($data, $name);

        return $this->_mergedConfigCache[$name];
    }
    
    /**
     * get config default
     * - checks if application config.inc.php is available for defaults first
     * - checks definition default second
     * 
     * @param string $name
     * @return mixed
     */
    protected function _getDefault($name)
    {
        $default = null;
        $definition = self::getDefinition($name);

        if (null !== $definition) {
            if (isset($definition['default']) || array_key_exists('default', $definition)) {
                $default = $definition['default'];
            } elseif (isset($definition['type']) && isset($definition['class']) && $definition['type'] === self::TYPE_OBJECT) {
                $default = array();
            }
        }

        return $default;
    }
    
    /**
     * store a config value
     *
     * if you store a value here, it will be valid for the current process and be persisted in the db, BUT
     * it maybe overwritten by a config file entry. So other process that merge the config from db and config
     * file again may not get the value you set here.
     *
     * @TODO validate config (rawToString?)
     *
     * @param  string   $_name      config name
     * @param  mixed    $_value     config value
     * @return void
     */
    public function set($_name, $_value)
    {
        if (null === $_value) {
            $this->delete($_name);
            return;
        }

        try {
            if (is_object($_value) && $_value instanceof Tinebase_Record_Interface) {
                $_value = $_value->toArray(true);
            }

            $configRecord = new Tinebase_Model_Config(array(
                "application_id"    => Tinebase_Application::getInstance()->getApplicationByName($this->_appName)->getId(),
                "name"              => $_name,
                "value"             => json_encode($_value),
            ));

            $this->_saveConfig($configRecord);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // during installation we may not have access to the application yet.. dangerous terrain
        }

        $this->_mergedConfigCache[$_name] = $this->_rawToConfig($_value, $_name);
    }

    public function setInMemory($_name, $_value)
    {
        $this->_mergedConfigCache[$_name] = $this->_rawToConfig($_value, $_name);
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
            $this->clearCache(null, true);
            if (isset($this->_mergedConfigCache[$_name]) || array_key_exists($_name, $this->_mergedConfigCache)) {
                unset($this->_mergedConfigCache[$_name]);
            }
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
        $this->clearCache(array("id" => $_applicationId));
        
        return $count;
    }
    
    /**
     * Magic function so that $obj->value will work.
     *
     * @param string $_name
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
     * public wrapper for _getConfigFileData, only use it from outside of the config classes!
     *
     * @return array
     */
    public function getConfigFileData()
    {
        return $this->_getConfigFileData();
    }

    /**
     * returns data from central config.inc.php file
     * 
     * @return array
     */
    protected function _getConfigFileData()
    {
        if (! self::$_configFileData) {
            /** @noinspection PhpIncludeInspection */
            self::$_configFileData = include('config.inc.php');
            
            if (self::$_configFileData === false) {
                die('Central configuration file config.inc.php not found in includepath: ' . get_include_path() . "\n");
            }
            
            if (isset(self::$_configFileData['confdfolder'])) {
                $tmpDir = Tinebase_Core::guessTempDir(self::$_configFileData);
                $cachedConfigFile = $tmpDir . DIRECTORY_SEPARATOR . 'cachedConfig.inc.php';

                if (file_exists($cachedConfigFile)) {
                    try {
                        /** @noinspection PhpIncludeInspection */
                        $cachedConfigData = include($cachedConfigFile);
                    } catch (Throwable $e) {
                        unlink($cachedConfigFile);
                        $cachedConfigData = false;
                        Tinebase_Exception::log($e);
                    }
                } else {
                    $cachedConfigData = false;
                }
                
                if ($this->_doCreateCachedConfig($cachedConfigData)) {
                    $this->_createCachedConfig($tmpDir);
                } else {
                    self::$_configFileData = $cachedConfigData;
                }
            }
        }
        
        return self::$_configFileData;
    }

    /**
     * returns true if a new cached config file should be created
     *
     * @param $cachedConfigData
     * @return bool
     */
    protected function _doCreateCachedConfig($cachedConfigData)
    {
        return
            false === $cachedConfigData ||
            !isset($cachedConfigData['ttlstamp']) ||
            $cachedConfigData['ttlstamp'] < time() ||
            (defined('TINE20_BUILDTYPE') && (TINE20_BUILDTYPE === 'DEVELOPMENT' || TINE20_BUILDTYPE === 'DEBUG'));
    }
    
    /**
     * composes config files from conf.d and saves array to tmp file
     *
     * @param string $tmpDir
     */
    protected function _createCachedConfig($tmpDir)
    {
        $confdFolder = self::$_configFileData['confdfolder'];

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Creating new cached config file in ' . $tmpDir);

        if (! is_readable($confdFolder)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' can\'t open conf.d folder "' . $confdFolder . '"');
            return;
        }

        $dh = opendir($confdFolder);

        if ($dh === false) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' opendir() failed on folder "' . $confdFolder . '"');
            return;
        }

        while (false !== ($direntry = readdir($dh))) {
            if (strpos($direntry, '.inc.php') === (strlen($direntry) - 8)) {
                $tmpArray = $this->_getConfdFileData($confdFolder . DIRECTORY_SEPARATOR . $direntry);
                if (false !== $tmpArray && is_array($tmpArray)) {
                    foreach ($tmpArray as $key => $value) {
                        self::$_configFileData[$key] = $value;
                    }
                }
            }
        }
        closedir($dh);

        $this->_mergedConfigCache = [];
        // reset logger as the new available config from conf.d mail contain different logger configuration
        Tinebase_Core::unsetLogger();
        // magic to prevent ->logger recurion, see also clearCache() for a detailed explanation
        Tinebase_Core::isLogLevel(Zend_Log::WARN);

        $ttl = 60;
        $ttlErrorMsg = null;
        if (isset(self::$_configFileData['composeConfigTTL'])) {
            $ttl = (int) self::$_configFileData['composeConfigTTL'];
            if ($ttl < 1) {
                $ttlErrorMsg = ' composeConfigTTL needs to be an integer > 0, current value: "'
                    . print_r(self::$_configFileData['composeConfigTTL'],true) . '"';
                $ttl = 60;
            }
        }
        self::$_configFileData['ttlstamp'] = time() + $ttl;
        if (null !== $ttlErrorMsg) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . $ttlErrorMsg);
        }
        
        $filename = $tmpDir . DIRECTORY_SEPARATOR . 'cachedConfig.inc.php';
        $filenameTmp = $filename . uniqid('tine20', true);
        $fh = fopen($filenameTmp, 'w');
        if (false === $fh) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' can\'t create cached composed config file "' .$filename );
        } else {
            fputs($fh, "<?php\n\nreturn ");
            fputs($fh, var_export(self::$_configFileData, true));
            fputs($fh, ';');
            fclose($fh);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Wrote config to file ' . $filenameTmp);
            
            if (false === rename($filenameTmp, $filename) ) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' can\'t rename "' . $filenameTmp . '" to "' . $filename . '"' );
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Renamed to file ' . $filename);
        }
    }

    /**
     * returns conf.d file data
     * - lint file
     * - check PHP opening tag
     *
     * @param $filename
     * @return array|boolean
     */
    protected function _getConfdFileData($filename)
    {
        $result = @shell_exec("php -l $filename");
        if (preg_match('/parse error/i', $result)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' PHP syntax check failed for ' . $filename . ': ' . $result);
            return false;
        }

        // check first chars to prevent leading spaces
        $content = file_get_contents($filename, false, null, 0, 200);
        if (strpos($content, '<?php') !== 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Could not find leading PHP open tag in ' . $filename);
            return false;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Including config file ' . $filename);

        /** @noinspection PhpIncludeInspection */
        return include($filename);
    }

    /**
     * returns data from application specific config.inc.php file
     *
     * @return array
     */
    protected function _getAppDefaultsConfigFileData()
    {
        $configFilename = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . $this->_appName .
            DIRECTORY_SEPARATOR . 'config.inc.php';

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Looking for defaults config.inc.php at ' . $configFilename);
        if (file_exists($configFilename)) {
            /** @noinspection PhpIncludeInspection */
            $configData = include($configFilename);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
                . __LINE__ . ' Found default config.inc.php for app ' . $this->_appName);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::'
                . __LINE__ . ' ' . print_r($configData, true));
        } else {
            $configData = array();
        }
        
        return $configData;
    }
    
    /**
     * get config file section where config identified by name is in
     * 
     * @param  string $name
     * @return array
     */
    public function getConfigFileSection($name)
    {
        $configFileData = $this->_getConfigFileData();
        
        // appName section overwrites global section in config file
        // TODO: this needs improvement -> it is currently not allowed to have configs with the same names in
        //       an Application and Tinebase as this leads to strange/unpredictable results here ...
        return (isset($configFileData[$this->_appName]) || array_key_exists($this->_appName, $configFileData)) && (isset($configFileData[$this->_appName][$name]) || array_key_exists($name, $configFileData[$this->_appName])) ? $configFileData[$this->_appName] :
              ((isset($configFileData[$name]) || array_key_exists($name, $configFileData)) ? $configFileData : NULL);
    }
    
    /**
     * load a config record from database
     * 
     * @param  string                   $_name
     * @return Tinebase_Model_Config|NULL
     */
    protected function _loadConfig($_name)
    {
        if ($this->_cachedApplicationConfig === NULL || empty($this->_cachedApplicationConfig)) {
            $this->_loadAllAppConfigsInCache();
        }
        $result = (isset($this->_cachedApplicationConfig[$_name])) ? $this->_cachedApplicationConfig[$_name] :  NULL;
        
        return $result;
    }

    /**
    * fill class cache with all config records for this app
    */
    protected function _loadAllAppConfigsInCache()
    {
        if (empty($this->_appName)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__ . ' appName not set');
            $this->_cachedApplicationConfig = array();
        }

        if (! Tinebase_Application::getInstance()->getInstance()->isInstalled('Tinebase')) {
            $this->_cachedApplicationConfig = array();
            return;
        }
        
        $cache = Tinebase_Core::getCache();
        if (!is_object($cache)) {
           Tinebase_Core::setupCache();
           $cache = Tinebase_Core::getCache();
        }
        
        if (Tinebase_Core::get(Tinebase_Core::SHAREDCACHE)) {
            if ($cachedApplicationConfig = $cache->load('cachedAppConfig_' . $this->_appName)) {
                $this->_cachedApplicationConfig = $cachedApplicationConfig;
                return;
            }
        }
        
        try {
            $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($this->_appName);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Loading all configs for app ' . $this->_appName);
            $filter = new Tinebase_Model_ConfigFilter(array(
                array('field' => 'application_id', 'operator' => 'equals', 'value' => $applicationId),
            ));
            $allConfigs = $this->_getBackend()->search($filter);
        } catch (Zend_Db_Exception $zdae) {
            // DB might not exist or tables are not created, yet
            Tinebase_Exception::log($zdae);
            $this->_cachedApplicationConfig = array();
            return;
        } catch (Tinebase_Exception_NotFound $tenf) {
            // application might not yet exist
            Tinebase_Exception::log($tenf);
            $this->_cachedApplicationConfig = array();
            return;
        }

        foreach ($allConfigs as $config) {
            $this->_cachedApplicationConfig[$config->name] = $config;
        }
        
        if (Tinebase_Core::get(Tinebase_Core::SHAREDCACHE)) {
            $cache->save($this->_cachedApplicationConfig, 'cachedAppConfig_' . $this->_appName);
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
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Setting config ' . $_config->name);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($_config->value, true));
        
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
        
        $this->clearCache(null, true);
        
        return $result;
    }

    /**
     * clears the inMemory class cache
     *
     * @param string|null $key
     */
    public function clearMemoryCache($key = null)
    {
        if (null === $key) {
            $this->_mergedConfigCache = [];
        } else {
            unset($this->_mergedConfigCache[$key]);
        }
    }

    /**
     * clear the cache
     * @param   array $appFilter
     */
    public function clearCache($appFilter = null, $keepMemoryCache = false)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Clearing config cache');

        if (Tinebase_Core::get(Tinebase_Core::SHAREDCACHE)) {
            if (isset($appFilter)) {
                list($key, $value) = each($appFilter);
                $appName = $key === 'name' ? $value : Tinebase_Application::getInstance()->getApplicationById($value)->name;
            } else {
                $appName = $this->_appName;
            }
            Tinebase_Core::getCache()->remove('cachedAppConfig_' . $appName);
        }

        Tinebase_Cache_PerRequest::getInstance()->reset('Tinebase_Config_Abstract');

        $cachedConfigFile = Tinebase_Core::guessTempDir() . DIRECTORY_SEPARATOR . 'cachedConfig.inc.php';
        if (file_exists($cachedConfigFile)) {
            unlink($cachedConfigFile);
        }

        // reset class caches last because they would be filled again by Tinebase_Core::guessTempDir()
        self::$_configFileData = null;
        $this->_cachedApplicationConfig = null;
        if (!$keepMemoryCache) {
            $this->_mergedConfigCache = array();
        }

        // sadly we need to do some magic here:
        // after clearCache a call to Tinebase_Core::getConfig()->logger would cause a __get('logger') recursion
        // this is a PHP quirk (not an endless loop, just a simple one depth recursion, but PHP doesn't like __get
        // recursions)
        // see also _createCachedConfig, it's being done there too
        // so: unset the logger
        Tinebase_Core::unsetLogger();
        // initialize the logger data again
        Tinebase_Core::isLogLevel(Zend_Log::WARN);
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
     * @param   mixed     $_rawData
     * @param   string    $_name
     * @return  mixed
     */
    protected function _rawToConfig($_rawData, $_name)
    {
        return static::rawToConfig($_rawData, $this, $_name, self::getDefinition($_name), $this->_appName);
    }


    protected static function _destroyBackend()
    {
        self::$_backend = null;
    }

    /**
     * converts raw data to config values of defined type
     * 
     * @TODO support array contents conversion
     * @TODO support interceptors
     * 
     * @param   mixed     $_rawData
     * @param   object    $parent
     * @param   string    $parentKey
     * @param   array     $definition
     * @param   string    $appName
     * @return  mixed
     */
    public static function rawToConfig($_rawData, $parent, $parentKey, $definition, $appName)
    {
        if (null === $_rawData) {
            return $_rawData;
        }

        // TODO make definition mandatory => should be an error
        if (!is_array($definition) || !isset($definition['type'])) {
            return is_array($_rawData) ? new Tinebase_Config_Struct($_rawData) : $_rawData;
        }
        if ($definition['type'] === self::TYPE_OBJECT && isset($definition['class']) && @class_exists($definition['class'])) {
            if (is_object($_rawData) && $_rawData instanceof $definition['class']) {
                return $_rawData;
            }
            if (isset($definition['content']) && isset($definition['default']) && is_array($definition['default'])) {
                foreach ($definition['default'] as $key => $default) {
                    if (isset($definition['content'][$key]) && !isset($definition['content'][$key]['default'])) {
                        $definition['content'][$key]['default'] = $default;
                    }
                }
            }
            return new $definition['class'](is_array($_rawData) ? $_rawData : array(), $parent, $parentKey,
                isset($definition['content']) ? $definition['content'] : null, $appName);
        }

        switch ($definition['type']) {
            case self::TYPE_INT:        return (int) $_rawData;
            case self::TYPE_BOOL:       return $_rawData === "true" || (bool) (int) $_rawData;
            case self::TYPE_RECORD:
            case self::TYPE_STRING:     return (string) $_rawData;
            case self::TYPE_FLOAT:      return (float) $_rawData;
            case self::TYPE_ARRAY:      return (array) $_rawData;
            case self::TYPE_DATETIME:   return new DateTime($_rawData);
            case self::TYPE_KEYFIELD_CONFIG:
                if (is_object($_rawData) && $_rawData instanceof Tinebase_Config_KeyField) {
                    return $_rawData;
                }
                $options = isset($definition['options']) ? (array) $definition['options'] : array();
                $options['appName'] = $appName;
                return Tinebase_Config_KeyField::create($_rawData, $options);

            // TODO this should be an error
            default:                    return is_array($_rawData) ? new Tinebase_Config_Struct($_rawData, $parent, $parentKey) : $_rawData;
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
        if (!$this->_isAppDefaultConfigMerged) {
            foreach ($this->_getAppDefaultsConfigFileData() as $key => $val) {
                $this->_mergeAppDefaultsConfigData(static::$_properties, $key, $val);
            }
            $this->_isAppDefaultConfigMerged = true;
        }
        $properties = static::getProperties();
        
        return (isset($properties[$_name]) || array_key_exists($_name, $properties)) ? $properties[$_name] : NULL;
    }

    /**
     * @param $target
     * @param $key
     * @param $val
     */
    protected function _mergeAppDefaultsConfigData(&$target, $key, $val)
    {
        if (!isset($target[$key])) {
            return;
        }
        if (is_array($val)) {
            if (   $target[$key][self::TYPE] === self::TYPE_OBJECT
                && $target[$key][self::CLASSNAME] === Tinebase_Config_Struct::class
                && isset($target[$key][self::CONTENT])
            ) {
                foreach ($val as $k => $v) {
                    if (!isset($target[$key][self::CONTENT][$k])) {
                        continue;
                    }
                    $this->_mergeAppDefaultsConfigData($target[$key][self::CONTENT], $k, $v);
                }
            } elseif (in_array($target[$key][self::TYPE], [self::TYPE_ARRAY, self::TYPE_KEYFIELD_CONFIG])) {
                $target[$key][self::DEFAULT_STR] = $val;
            }
        } elseif (!isset($target[$key][self::TYPE])
            || ($target[$key][self::TYPE] !== self::TYPE_OBJECT &&
                $target[$key][self::TYPE] !== self::TYPE_ARRAY)
        ) {
            $target[$key][self::DEFAULT_STR] = $val;
        }
    }
    
    /**
     * Get list of server classes
     *
     * @return array
     */
    public static function getServerPlugins()
    {
        return static::$_serverPlugins;
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

    /**
     * returns true if a certain feature is enabled
     *
     * @param string $featureName
     * @return boolean
     * @throws Setup_Exception
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function featureEnabled($featureName)
    {
        $cacheId = $this->_appName;
        try {
            $features = Tinebase_Cache_PerRequest::getInstance()->load(__CLASS__, __METHOD__, $cacheId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $features = $this->get(self::ENABLED_FEATURES);
            if ('Tinebase' === $this->_appName && (!Setup_Backend_Factory::factory()->supports('mysql >= 5.6.4 | mariadb >= 10.0.5')
                    || !$features->{Tinebase_Config::FEATURE_FULLTEXT_INDEX})) {
                $features->{Tinebase_Config::FEATURE_SEARCH_PATH} = false;
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Features config of app ' . $this->_appName . ': '
                . print_r($features->toArray(), true));
            Tinebase_Cache_PerRequest::getInstance()->save(__CLASS__, __METHOD__, $cacheId, $features);
        }

        if (isset($features->{$featureName})) {
            return $features->{$featureName};
        }

        return false;
    }
}
