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
     * imap conf name
     * 
     * @var string
     */
    const IMAP = 'imap';
    
    /**
     * smtp conf name
     * 
     * @var string
     */
    const SMTP = 'smtp';

    /**
     * sieve conf name
     * 
     * @var string
     */
    const SIEVE = 'sieve';

    /**
     * authentication backend config
     * 
     * @var string
     */
    const AUTHENTICATIONBACKEND = 'Tinebase_Authentication_BackendConfiguration';
    
    /**
     * authentication backend type config
     * 
     * @var string
     */
    const AUTHENTICATIONBACKENDTYPE = 'Tinebase_Authentication_BackendType';
    
    /**
     * save automatic alarms when creating new record
     * 
     * @var string
     */
    const AUTOMATICALARM = 'automaticalarm';
    
    /**
     * user backend config
     * 
     * @var string
     */
    const USERBACKEND = 'Tinebase_User_BackendConfiguration';
    
    /**
     * user backend type config
     * 
     * @var string
     */
    const USERBACKENDTYPE = 'Tinebase_User_BackendType';
    
    /**
     * cronjob user id
     * 
     * @var string
     */
    const CRONUSERID = 'cronuserid';
    
    /**
     * user defined page title postfix for browser page title
     * 
     * @var string
     */
    const PAGETITLEPOSTFIX = 'pagetitlepostfix';

    /**
     * logout redirect url
     * 
     * @var string
     */
    const REDIRECTURL = 'redirectUrl';
    
    /**
     * redirect always
     * 
     * @var string
     */
    const REDIRECTALWAYS = 'redirectAlways';
    
    /**
     * Config key for Setting "Redirect to referring site if exists?"
     * 
     * @var string
     */
    const REDIRECTTOREFERRER = 'redirectToReferrer';
    
    /**
     * Config key for acceptedTermsVersion
     * @var string
     */
    const ACCEPTEDTERMSVERSION = 'acceptedTermsVersion';
    
    /**
     * Config key for map panel in addressbook / include geoext code
     * @var string
     */
    const MAPPANEL = 'mapPanel';
    
    /**
     * Config key for session ip validation -> if this is set to FALSE no Zend_Session_Validator_IpAddress is registered
     * 
     * @var string
     */
    const SESSIONIPVALIDATION = 'sessionIpValidation';
    
    /**
     * Config key for session user agent validation -> if this is set to FALSE no Zend_Session_Validator_HttpUserAgent is registered
     * 
     * @var string
     */
    const SESSIONUSERAGENTVALIDATION = 'sessionUserAgentValidation';
    
    /**
     * filestore directory
     * 
     * @var string
     */
    const FILESDIR = 'filesdir';
    
    /**
     * xls export config
     * 
     * @deprecated move to app config
     * @var string
     */
    const XLSEXPORTCONFIG = 'xlsexportconfig';
    
    /**
     * app defaults
     * 
     * @deprecated move to app and split
     * @var string
     */
    const APPDEFAULTS = 'appdefaults';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::IMAP => array(
                                   //_('System IMAP')
            'label'                 => 'System IMAP',
                                   //_('System IMAP server configuration.')
            'description'           => 'System IMAP server configuration.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::SMTP => array(
                                   //_('System SMTP')
            'label'                 => 'System SMTP',
                                   //_('System SMTP server configuration.')
            'description'           => 'System SMTP server configuration.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::SIEVE => array(
                                   //_('System SIEVE')
            'label'                 => 'System SIEVE',
                                   //_('System SIEVE server configuration.')
            'description'           => 'System SIEVE server configuration.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::AUTHENTICATIONBACKENDTYPE => array(
                                   //_('Authentication Backend')
            'label'                 => 'Authentication Backend',
                                   //_('Backend adapter for user authentication.')
            'description'           => 'Backend adapter for user authentication.',
            'type'                  => 'string',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::AUTHENTICATIONBACKEND => array(
                                   //_('Authentication Configuration')
            'label'                 => 'Authentication Configuration',
                                   //_('Authentication backend configuration.')
            'description'           => 'Authentication backend configuration.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::USERBACKENDTYPE => array(
                                   //_('User Backend')
            'label'                 => 'User Backend',
                                   //_('Backend adapter for user data.')
            'description'           => 'Backend adapter for user data.',
            'type'                  => 'string',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::USERBACKEND => array(
                                   //_('User Configuration')
            'label'                 => 'User Configuration',
                                   //_('User backend configuration.')
            'description'           => 'User backend configuration.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::CRONUSERID => array(
                                   //_('Cronuser ID')
            'label'                 => 'Cronuser ID',
                                   //_('User ID of the cron user.')
            'description'           => 'User ID of the cron user.',
            'type'                  => 'string',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => TRUE,
        ),
        self::PAGETITLEPOSTFIX => array(
                                   //_('Title Postfix')
            'label'                 => 'Title Postfix',
                                   //_('Postfix string appended to the title of this installation.')
            'description'           => 'Postfix string appended to the title of this installation.',
            'type'                  => 'string',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => TRUE,
        ),
        self::REDIRECTURL => array(
                                   //_('Redirect URL')
            'label'                 => 'Redirect URL',
                                   //_('Redirect to this URL after logout.')
            'description'           => 'Redirect to this URL after logout.',
            'type'                  => 'string',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::REDIRECTTOREFERRER => array(
                                   //_('Redirect to Referrer')
            'label'                 => 'Redirect to Referrer',
                                   //_('Redirect to referrer after logout.')
            'description'           => 'Redirect to referrer after logout.',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::REDIRECTALWAYS => array(
                                   //_('Redirect Always')
            'label'                 => 'Redirect Always',
                                   //_('Redirect to configured redirect URL also for login.')
            'description'           => 'Redirect to configured redirect URL also for login.',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::ACCEPTEDTERMSVERSION => array(
                                   //_('Accepted Terms Version')
            'label'                 => 'Accepted Terms Version',
                                   //_('Accepted version number of the terms and conditions document.')
            'description'           => 'Accepted version number of the terms and conditions document.',
            'type'                  => 'int',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
        ),
        self::MAPPANEL => array(
                                   //_('Use Geolocation Services')
            'label'                 => 'Use Geolocation Services',
                                   //_('Use of external Geolocation services is allowed.')
            'description'           => 'Use of external Geolocation services is allowed.',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::SESSIONIPVALIDATION => array(
                                   //_('IP Session Validator')
            'label'                 => 'IP Session Validator',
                                   //_('Destroy session if the users IP changes.')
            'description'           => 'Destroy session if the users IP changes.',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::SESSIONUSERAGENTVALIDATION => array(
                                   //_('UA Session Validator')
            'label'                 => 'UA Session Validator',
                                   //_('Destroy session if the users user agent string changes.')
            'description'           => 'Destroy session if the users user agent string changes.',
            'type'                  => 'bool',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::FILESDIR => array(
                                   //_('Files Directory')
            'label'                 => 'Files Directory',
                                   //_('Directory with web server write access for user files.')
            'description'           => 'Directory with web server write access for user files.',
            'type'                  => 'string',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
    );
    
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
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
    
    /**
     * get config for client registry
     * 
     * @return Tinebase_Config_Struct
     */
    public function getClientRegistryConfig()
    {
        // get all config names to be included in registry
        $clientProperties = new Tinebase_Config_Struct(array());
        $filters = array();
        $userApplications = Tinebase_Core::getUser()->getApplications(TRUE);
        foreach ($userApplications as $application) {
            $config = $this->{$application->name};
            if ($config instanceof Tinebase_Config_Abstract) {
                $clientProperties[$application->name] = new Tinebase_Config_Struct(array());
                $properties = $config->getProperties();
                foreach( (array) $properties as $name => $definition) {
                    if (array_key_exists('clientRegistryInclude', $definition) && $definition['clientRegistryInclude'] === TRUE) {
                        // might not be too bad as we have a cache
                        $clientProperties[$application->name][$name] = new Tinebase_Config_Struct(array(
                            'value'         => $config->{$name},
                            // add definition here till we have a better palce
                            'definition'    => new Tinebase_Config_Struct($definition),
                        ));
//                        $clientProperties[$application->name][$name] = $definition;
                    }
                }
                
//                if (isset($clientProperties[$application->name])) {
//                    $filters[] = array('condition' => 'AND', 'filters' => array(
//                        array('field' => 'application_id', 'operator' => 'equals', 'value' => $application->getId()),
//                        array('field' => 'name',           'operator' => 'in',     'value' => array_keys((array) $clientProperties[$application->name])),
//                    ));
//                }
            }
        }
        
//        // get all configs at once
//        $clientRecords = $this->_getBackend()->search(new Tinebase_Model_ConfigFilter(array(array('condition' => 'OR', 'filters' => $filters))));
//        $clientRecords->addIndices(array('application_id', 'name'));
//        
//        // data to config
//        foreach($clientProperties as $appName => $properties) {
//            $config = $this->{$appName};
//            $appClientRecords = $clientRecords->filter('application_id', Tinebase_Model_Application::convertApplicationIdToInt($appName));
//            foreach($properties as $name => $definition) {
//                $configRecord = $appClientRecords->filter('name', $name)->getFirstRecord();
//                $configData = Tinebase_Model_Config::NOTSET;
//                
//                if ($configRecord) {
//                    // @todo JSON encode all config data via update script!
//                    $configData = json_decode($configRecord->value, TRUE);
//                    $configData = $configData ? $configData : $configRecord->value;
//                }
//                
//                // CRAP we need to have a public method in $config!!!
//                $clientProperties[$appName][$name] = $config->_rawToConfig($configData, $name);
//            }
//        }
        
        return $clientProperties;
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
     * @deprecated use set / __set (if $_applicationName !== 'Tinebase' this needs a $_applicationName_Config class)
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
