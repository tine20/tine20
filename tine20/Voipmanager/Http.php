<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Http.php 2477 2008-05-15 09:52:27Z ph_il $
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
            'Voipmanager/js/Line.js'
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
            $phone = $controller->getPhoneById($phoneId);
            $lines = $controller->getLines('name');

            // encode the phone array
            $encodedPhone = Zend_Json::encode($phone->toArray());
            $encodedLines = Zend_Json::encode($lines->toArray());              
        } else {
            //$phone = new Voipmanager_Model_Phone();
            //$lines = new Tinebase_Record_RecordSet('Voipmanager_Model_Line');
            
            $encodedPhone = '{}';
            $encodedLines = '{}';
        }

        $encodedTemplates = Zend_Json::encode($controller->getTemplates()->toArray());
        $encodedLocations = Zend_Json::encode($controller->getLocation()->toArray());
        
        $currentAccount = Zend_Registry::get('currentAccount');
                
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->jsExecute = 'Tine.Voipmanager.Phones.EditDialog.display(' . $encodedPhone . ', ' . $encodedLines . ', ' . $encodedTemplates . ', ' . $encodedLocations . ');';

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
            $lines = Voipmanager_Controller::getInstance();
            $line  = $lines->getLineById($lineId);
            $arrayLine = $line->toArray();
        } else {

        }

        // encode the asterisk line array
        $encodedLine = Zend_Json::encode($arrayLine);                   
        
        $currentAccount = Zend_Registry::get('currentAccount');
                
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();        
        $view->jsExecute = 'Tine.Voipmanager.Lines.EditDialog.display(' . $encodedLine .');';

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
     * create edit location dialog
     *
     * @param int $locationId
     * @todo catch permission denied exceptions only
     * 
     */
    public function editLocation($locationId=NULL)
    {
        if (!empty($locationId)) {
            $locations = Voipmanager_Controller::getInstance();
            $location = $locations->getLocationById($locationId);
            $arrayLocation = $location->toArray();
        } else {

        }

        // encode the location array
        $encodedLocation = Zend_Json::encode($arrayLocation);                   
        
        $currentAccount = Zend_Registry::get('currentAccount');
                
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
            $softwares = Voipmanager_Controller::getInstance();
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
            $template = $controller->getTemplateById($templateId);
        } else {
            $template = new Voipmanager_Model_Template(array(
                'model' => 'snom320'
            ), true);
        }

        // encode the template array
        $encodedTemplate = Zend_Json::encode($template->toArray());                   
        
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();        
        $view->jsExecute = 'Tine.Voipmanager.Templates.EditDialog.display(' . $encodedTemplate .');';

        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray(),
            'softwareVersions' => $controller->getSoftware('id', 'ASC', $template->model)->toArray() 
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