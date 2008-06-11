<?php
/**
 * Tine 2.0
 *
 * @package     Asterisk Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Http.php 2477 2008-05-15 09:52:27Z ph_il $
 */

/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the Asterisk Management application
 *
 * @package     Asterisk Management
 */
class Asterisk_Http extends Tinebase_Application_Http_Abstract
{
    protected $_appname = 'Asterisk';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Asterisk/js/Asterisk.js'
        );
    }
    
    /**
     * create edit phone dialog
     *
     * @param int $phoneId
     * @todo catch permission denied exceptions only
     * 
     */
    public function editPhone($phoneId=NULL)
    {
        if (!empty($phoneId)) {
            $phones = Asterisk_Controller::getInstance();
            $phone = $phones->getPhoneById($phoneId);
            $arrayPhone = $phone->toArray();
        } else {

        }

        // encode the phone array
        $encodedPhone = Zend_Json::encode($arrayPhone);                   
        
        $currentAccount = Zend_Registry::get('currentAccount');
                
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();        
        $view->jsExecute = 'Tine.Asterisk.Phones.EditDialog.display(' . $encodedPhone .');';

        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->title="edit phone data";

        $view->isPopup = true;
        
        $includeFiles = Tinebase_Http::getAllIncludeFiles();
        $view->jsIncludeFiles  = $includeFiles['js'];
        $view->cssIncludeFiles = $includeFiles['css'];
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }
    

    /**
     * create edit config dialog
     *
     * @param int $configId
     * @todo catch permission denied exceptions only
     * 
     */
    public function editConfig($configId=NULL)
    {
        if (!empty($configId)) {
            $configs = Asterisk_Controller::getInstance();
            $config = $configs->getConfigById($configId);
            $arrayConfig = $config->toArray();
        } else {

        }

        // encode the config array
        $encodedConfig = Zend_Json::encode($arrayConfig);                   
        
        $currentAccount = Zend_Registry::get('currentAccount');
                
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();        
        $view->jsExecute = 'Tine.Asterisk.Config.EditDialog.display(' . $encodedConfig .');';

        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->title="edit config data";

        $view->isPopup = true;
        
        $includeFiles = Tinebase_Http::getAllIncludeFiles();
        $view->jsIncludeFiles  = $includeFiles['js'];
        $view->cssIncludeFiles = $includeFiles['css'];
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }    
    
    
    /**
     * create edit software dialog
     *
     * @param int $softwareId
     * @todo catch permission denied exceptions only
     * 
     */
    public function editSoftware($softwareId=NULL)
    {
        if (!empty($softwareId)) {
            $softwares = Asterisk_Controller::getInstance();
            $software = $softwares->getSoftwareById($softwareId);
            $arraySoftware = $software->toArray();
        } else {

        }

        // encode the software array
        $encodedSoftware = Zend_Json::encode($arraySoftware);                   
        
        $currentAccount = Zend_Registry::get('currentAccount');
                
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();        
        $view->jsExecute = 'Tine.Asterisk.Software.EditDialog.display(' . $encodedSoftware .');';

        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->title="edit software data";

        $view->isPopup = true;
        
        $includeFiles = Tinebase_Http::getAllIncludeFiles();
        $view->jsIncludeFiles  = $includeFiles['js'];
        $view->cssIncludeFiles = $includeFiles['css'];
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }    
    
     
}