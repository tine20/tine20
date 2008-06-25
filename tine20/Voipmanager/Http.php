<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the Voipmanager Management application
 *
 * @package     Voipmanager Management
 */
class Voipmanager_Http extends Tinebase_Application_Http_Abstract
{
    protected $_appname = 'Voipmanager';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Voipmanager/js/Voipmanager.js',
            'Voipmanager/js/Model.js',
            'Voipmanager/js/Software.js',
            'Voipmanager/js/Templates.js',
            'Voipmanager/js/Phone.js',
            'Voipmanager/js/Location.js',
            'Voipmanager/js/Line.js',
            'Voipmanager/js/Context.js'
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
        $controller = Voipmanager_Controller::getInstance();
        
        if (!empty($phoneId)) {
            $snomPhone = $controller->getSnomPhone($phoneId);
            $snomLines = $snomPhone->lines;
            unset($phone->lines);
            $asteriskLines = $controller->searchAsteriskPeers('name');

            // encode the phone array
            $encodedSnomPhone = Zend_Json::encode($snomPhone->toArray());
            $encodedSnomLines = Zend_Json::encode($snomLines->toArray());
            $encodedAsteriskLines = Zend_Json::encode($asteriskLines->toArray());              
        } else {
            //$phone = new Voipmanager_Model_SnomPhone();
            //$lines = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomLine');
            
            $encodedSnomPhone = '{}';
            $encodedSnomLines = '[]';
            $encodedAsteriskLines = '{}';
        }

        $encodedTemplates = Zend_Json::encode($controller->getSnomTemplates()->toArray());
        $encodedLocations = Zend_Json::encode($controller->getSnomLocations()->toArray());
        
        $currentAccount = Zend_Registry::get('currentAccount');
                
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->jsExecute = 'Tine.Voipmanager.Phones.EditDialog.display(' . $encodedSnomPhone . ', ' . $encodedSnomLines . ', ' . $encodedAsteriskLines . ', ' . $encodedTemplates . ', ' . $encodedLocations . ');';

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
     * create edit asterisk line dialog
     *
     * @param int $lineId
     * 
     */
    public function editLine($lineId=NULL)
    {
        if (!empty($lineId)) {
            $line = Voipmanager_Controller::getInstance()->getAsteriskPeer($lineId);
        } else {
            $line = new Voipmanager_Model_AsteriskPeer(array(
                'type'  => 'user'
            )); 
        }

        // encode the asterisk line array
        $encodedLine = Zend_Json::encode($line->toArray());                   
        
        $encodedContexts = Zend_Json::encode(Voipmanager_Controller::getInstance()->getAsteriskContexts()->toArray());
        
        $currentAccount = Zend_Registry::get('currentAccount');
                
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();        
        $view->jsExecute = 'Tine.Voipmanager.Lines.EditDialog.display(' . $encodedLine .','. $encodedContexts .');';

        $view->locationData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->title="edit line data";

        $view->isPopup = true;
        
        $includeFiles = Tinebase_Http::getAllIncludeFiles();
        $view->jsIncludeFiles  = $includeFiles['js'];
        $view->cssIncludeFiles = $includeFiles['css'];
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }       
    

    /**
     * create edit asterisk context dialog
     *
     * @param int $lineId
     * 
     */
    public function editContext($contextId=NULL)
    {
        if (!empty($contextId)) {
            $context = Voipmanager_Controller::getInstance()->getAsteriskContext($contextId);
            $encodedContext = Zend_Json::encode($context->toArray());                   
        } else {
            $encodedContext = '{}';
        }

        
        $currentAccount = Zend_Registry::get('currentAccount');
                
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();        
        $view->jsExecute = 'Tine.Voipmanager.Context.EditDialog.display(' . $encodedContext .');';

        $view->locationData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->title="edit context data";

        $view->isPopup = true;
        
        $includeFiles = Tinebase_Http::getAllIncludeFiles();
        $view->jsIncludeFiles  = $includeFiles['js'];
        $view->cssIncludeFiles = $includeFiles['css'];
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }


    /**
     * create edit location dialog
     *
     * @param int $locationId
     * @todo catch permission denied exceptions only
     * 
     */
    public function editLocation($locationId=NULL)
    {
        if (!empty($locationId)) {
            $location = Voipmanager_Controller::getInstance()->getSnomLocation($locationId);
        } else {
            $location = new Voipmanager_Model_SnomLocation(array(
                'webserver_type'    => 'http',
                'http_port'         => 80,
                'https_port'        => 443
            ));
        }
        
        // encode the location array
        $encodedLocation = Zend_Json::encode($location->toArray());                   
        
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();        
        $view->jsExecute = 'Tine.Voipmanager.Location.EditDialog.display(' . $encodedLocation .');';

        $view->locationData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->title="edit location data";

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
            $software = Voipmanager_Controller::getInstance()->getSnomSoftware($softwareId);
            $encodedSoftware = Zend_Json::encode($software->toArray());
        } else {
            $encodedSoftware = '{}';
        }

        $currentAccount = Zend_Registry::get('currentAccount');
                
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();        
        $view->jsExecute = 'Tine.Voipmanager.Software.EditDialog.display(' . $encodedSoftware .');';

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

    /**
     * create edit template dialog
     *
     * @param int $templateId
     * @todo catch permission denied exceptions only
     * 
     */
    public function editTemplate($templateId=NULL)
    {
        $controller = Voipmanager_Controller::getInstance();

        if (!empty($templateId)) {
            $template = $controller->getSnomTemplate($templateId);
        } else {
            $template = new Voipmanager_Model_SnomTemplate(array(
                'model' => 'snom320'
            ), true);
        }

        // encode the template array
        $encodedTemplate = Zend_Json::encode($template->toArray());                 
        
        
        
        // software data
        $software = $controller->searchSnomSoftware();
        $encodedSoftware = Zend_Json::encode($software->toArray());
        
        // keylayout data
  //      $keylayout = $controller->getKeylayout();
        $encodedKeylayout = Zend_Json::encode('[]');
        
        // settings data
//        $settings = $controller->getSettings();
        $encodedSettings = Zend_Json::encode('[]');
        
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();        
        $view->jsExecute = 'Tine.Voipmanager.Templates.EditDialog.display(' . $encodedTemplate .','.$encodedSoftware.','.$encodedKeylayout.','.$encodedSettings.');';

        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray(),
            'softwareVersions' => $controller->searchSnomSoftware('id', 'ASC', $template->model)->toArray() 
        );
        
        $view->title="edit template data";

        $view->isPopup = true;
        
        $includeFiles = Tinebase_Http::getAllIncludeFiles();
        $view->jsIncludeFiles  = $includeFiles['js'];
        $view->cssIncludeFiles = $includeFiles['css'];
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }        
     
}