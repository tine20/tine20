<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * controller abstract for applications
 *
 * @package     Tinebase
 * @subpackage  Application
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
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = '';
    
    /**
     * the current account
     * 
     * @var Tinebase_Model_User
     */
    protected $_currentAccount = NULL;
    
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
     */    
    public function checkRight($_right, $_throwException = TRUE, $_includeTinebaseAdmin = TRUE) {
        
        if (empty($this->_applicationName)) {
            throw new Tinebase_Exception_UnexpectedValue('No application name defined!');
        }
                
        $right = strtoupper($_right);
        
        $cache = Tinebase_Core::get(Tinebase_Core::CACHE);
        $cacheId = convertCacheId('checkRight' . $this->_currentAccount->getId() . $_right . $this->_applicationName);
        $result = $cache->load($cacheId);
        
        if (!$result) {        
            $applicationRightsClass = $this->_applicationName . '_Acl_Rights';
            
            // array with the rights that should be checked, ADMIN is in it per default
            $rightsToCheck = ($_includeTinebaseAdmin) ? array(Tinebase_Acl_Rights::ADMIN) : array();
            
            if (preg_match("/MANAGE_/", $right)) {
                $rightsToCheck[] = constant($applicationRightsClass. '::' . $right);
            }
    
            if (preg_match("/VIEW_([A-Z_]*)/", $right, $matches)) {
                $rightsToCheck[] = constant($applicationRightsClass. '::' . $right);
                // manage right includes view right
                $rightsToCheck[] = constant($applicationRightsClass. '::MANAGE_' . $matches[1]);
            }
            
            $result = FALSE;
            
            foreach ($rightsToCheck as $rightToCheck) {
                //echo "check right: " . $rightToCheck;
                if (Tinebase_Acl_Roles::getInstance()->hasRight($this->_applicationName, $this->_currentAccount->getId(), $rightToCheck)) {
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
     * @return  array settings data
     */
    public function getSettings()
    {
        return Tinebase_Config::getInstance()->getConfigAsArray(
            Tinebase_Model_Config::APPDEFAULTS, 
            $this->_applicationName, 
            $this->_defaultsSettings
        );
    }
    
    /**
     * save settings
     * 
     * @param array $_settings
     * @return void
     */
    public function saveSettings($_settings)
    {
        Tinebase_Config::getInstance()->setConfigForApplication(
            Tinebase_Model_Config::APPDEFAULTS, 
            Zend_Json::encode($_settings), 
            $this->_applicationName
        );
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
            throw new Exception("Controller" . $_controllerName . "not implements Tinebase_Controller_Interface.");
        }
        
        return call_user_func(array($_controllerName, 'getInstance')); 
    }
}
