<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * controller abstract for applications
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
abstract class Tinebase_Controller_Abstract implements Tinebase_Controller_Interface
{
    /**
     * default settings
     * 
     * @var array
     */
    protected $_defaultsSettings = array();
    
    
    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = NULL;
    
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = '';
    
    /**
     * disable events on demand
     * 
     * @var mixed   false => no events filtered, true => all events filtered, array => disable only specific events
     */
    protected $_disabledEvents = false;
    
    /**
     * generic check admin rights function
     * rules: 
     * - ADMIN right includes all other rights
     * - MANAGE_* right includes VIEW_* right 
     * - results are cached if caching is active (with cache tag 'rights')
     * 
     * @param   string  $_right to check
     * @param   boolean $_throwException [optional]
     * @param   boolean $_includeTinebaseAdmin [optional]
     * @return  boolean
     * @throws  Tinebase_Exception_UnexpectedValue
     * @throws  Tinebase_Exception_AccessDenied
     * 
     * @todo move that to *_Acl_Rights
     * @todo include Tinebase admin? atm only the application admin right is checked
     */
    public function checkRight($_right, $_throwException = TRUE, $_includeTinebaseAdmin = TRUE) 
    {
        if (empty($this->_applicationName)) {
            throw new Tinebase_Exception_UnexpectedValue('No application name defined!');
        }
        
        $right = strtoupper($_right);
        
        $cache = Tinebase_Core::get(Tinebase_Core::CACHE);
        $cacheId = convertCacheId('checkRight' . Tinebase_Core::getUser()->getId() . $right . $this->_applicationName);
        $result = $cache->load($cacheId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $cacheId);
        
        if (!$result) {
            $applicationRightsClass = $this->_applicationName . '_Acl_Rights';
            
            // array with the rights that should be checked, ADMIN is in it per default
            $rightsToCheck = ($_includeTinebaseAdmin) ? array(Tinebase_Acl_Rights::ADMIN) : array();
            
            if (preg_match("/VIEW_([A-Z_]*)/", $right, $matches)) {
                // manage right includes view right
                $rightsToCheck[] = constant($applicationRightsClass. '::MANAGE_' . $matches[1]);
            } 
            
            $rightsToCheck[] = constant($applicationRightsClass. '::' . $right);
            
            $result = FALSE;
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Checking rights: ' . print_r($rightsToCheck, TRUE));
            
            foreach ($rightsToCheck as $rightToCheck) {
                if (Tinebase_Acl_Roles::getInstance()->hasRight($this->_applicationName, Tinebase_Core::getUser()->getId(), $rightToCheck)) {
                    $result = TRUE;
                    break;
                }
            }

            $cache->save($result, $cacheId, array('rights'), 120);
        }
        
        if (!$result && $_throwException) {
            throw new Tinebase_Exception_AccessDenied("You are not allowed to $right in application $this->_applicationName !");
        }

        return $result;
    }
    
    /**
     * Returns default settings for app
     *
     * @param boolean $_resolve if some values should be resolved
     * @return  array settings data
     */
    public function getConfigSettings($_resolve = FALSE)
    {
        $appConfig = Tinebase_Config::getAppConfig($this->_applicationName);
        if ($appConfig != NULL) {
            $settings = $appConfig::getInstance()->get(
                Tinebase_Config::APPDEFAULTS, 
                new Tinebase_Config_Struct($this->_defaultsSettings)
            )->toArray();
        } else { 
            $settings = $this->_defaultsSettings;
        }
        return ($_resolve) ? $this->_resolveConfigSettings($settings) : $settings;
    }
    
    /**
     * resolve some settings
     * 
     * @param array $_settings
     */
    protected function _resolveConfigSettings($_settings)
    {
        return $_settings;
    }
    
    /**
     * save settings
     * 
     * @param array $_settings
     * @return void
     */
    public function saveConfigSettings($_settings)
    {
        // only admins are allowed to do this
        $this->checkRight(Tinebase_Acl_Rights::ADMIN);
        
        $appConfig = Tinebase_Config::getAppConfig($this->_applicationName);
        
        if ($appConfig !== NULL) {
            $appConfig::getInstance()->set(Tinebase_Config::APPDEFAULTS, $_settings);
        }
    }
    
    /**
     * returns the default model of this application
     * @return string
     */
    public static function getDefaultModel()
    {
        return static::$_defaultModel;
    }
    
    /**
     * returns controller instance for given $_controllerName
     * 
     * @param string $_controllerName
     * @return Tinebase_Controller
     */
    public static function getController($_controllerName)
    {
        if (! class_exists($_controllerName)) {
            throw new Exception("Controller" . $_controllerName . "not found.");
        }
        
        if (!in_array('Tinebase_Controller_Interface', class_implements($_controllerName))) {
            throw new Exception("Controller $_controllerName does not implement Tinebase_Controller_Interface.");
        }
        
        return call_user_func(array($_controllerName, 'getInstance'));
    }
}
