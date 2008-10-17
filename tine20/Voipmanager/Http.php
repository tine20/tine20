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
            'Voipmanager/js/Snom/Software.js',
            'Voipmanager/js/Snom/Templates.js',
            'Voipmanager/js/Snom/Phone.js',
            'Voipmanager/js/Snom/Location.js',
            'Voipmanager/js/Snom/Settings.js',
            'Voipmanager/js/Asterisk/SipPeer.js',
            'Voipmanager/js/Asterisk/Context.js',
            'Voipmanager/js/Asterisk/Voicemail.js',
			'Voipmanager/js/Asterisk/Meetme.js',
			'Voipmanager/js/widgets.js',
			'Voipmanager/js/MyPhones.js'
        );
    }
    
    /**
     * create edit phone dialog
     *
     * @param int $phoneId
     * @todo catch permission denied exceptions only
     * 
     */
    public function editSnomPhone($phoneId=NULL)
    {
        $controller = Voipmanager_Controller::getInstance();
        $snomTemplates = $controller->getSnomTemplates();
        $snomLocations = $controller->getSnomLocations();
        
        $asteriskSipPeers = $controller->searchAsteriskSipPeers('name');
        $encodedAsteriskSipPeers = Zend_Json::encode($asteriskSipPeers->toArray());    
        
        if (!empty($phoneId)) {
            $snomPhone = $controller->getSnomPhone($phoneId);
            //$snomLines = $snomPhone->lines;
            //unset($phone->lines);

            $_phoneData = $snomPhone->toArray();

            //$_phoneOwner = $controller->getPhoneOwner($_phoneData['id']);   
             
            $_phoneSettingsData = $controller->getSnomPhoneSettings($_phoneData['id'])->toArray();
            
            $_templateData = $controller->getSnomTemplate($_phoneData['template_id'])->toArray();
            $_settingsData = $controller->getSnomSetting($_templateData['setting_id'])->toArray();
            

            $_writableFields = array('web_language','language','display_method','mwi_notification','mwi_dialtone','headset_device','message_led_other','global_missed_counter','scroll_outgoing','show_local_line','show_call_status','call_waiting');

            foreach($_writableFields AS $wField)
            {               
                $_fieldRW = $wField.'_writable';
                
                 if($_settingsData[$_fieldRW] == '0')
                 {
                     $_phoneSettingsData[$wField] = $_settingsData[$wField];
                     $_notWritable[$wField] = 'true';
                 } else {
                     if(empty($_phoneSettingsData[$wField])) {
                         $_phoneSettingsData[$wField] = $_settingsData[$wField];                    
                     }
                     $_notWritable[$wField] = '';    
                 }
            }

            $encodedWritable = Zend_Json::encode($_notWritable);
                
            $_phoneData = array_merge($_phoneSettingsData,$_phoneData);
            
            // encode the data arrays
            $encodedSnomPhone = Zend_Json::encode($_phoneData);
            $encodedSnomLines = Zend_Json::encode($snomPhone->lines->toArray());
            
        } else {
            $encodedWritable = '{}';
            $encodedSnomPhone = "{current_model:'snom320',redirect_event:'none'}";
            $encodedSnomLines = '[]';

            //$encodedSettings = '{}';
        }

        $encodedTemplates = Zend_Json::encode($snomTemplates->toArray());
        $encodedLocations = Zend_Json::encode($snomLocations->toArray());        
                        
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $view->title="edit snom phone data";
        $view->jsExecute = 'Tine.Voipmanager.Snom.Phones.EditDialog.display(' . $encodedSnomPhone . ', ' . $encodedSnomLines . ', ' . $encodedAsteriskSipPeers . ', ' . $encodedTemplates . ', ' . $encodedLocations . ', '. $encodedWritable .');';
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }
    
    /**
     * create edit asterisk line dialog
     *
     * @param int $lineId
     * 
     */
    public function editAsteriskSipPeer($sipPeerId=NULL)
    {
        if (!empty($sipPeerId)) {
            $sipPeer = Voipmanager_Controller::getInstance()->getAsteriskSipPeer($sipPeerId);
        } else {
            $sipPeer = new Voipmanager_Model_AsteriskSipPeer(array(
                'type'  => 'user'
            )); 
        }

        // encode the asterisk sip peer array
        $encodedSipPeer = Zend_Json::encode($sipPeer->toArray());                   
        
        $encodedContexts = Zend_Json::encode(Voipmanager_Controller::getInstance()->getAsteriskContexts()->toArray());
        
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $view->title="edit asterisk sip peer data";
        $view->jsExecute = 'Tine.Voipmanager.Asterisk.SipPeers.EditDialog.display(' . $encodedSipPeer .','. $encodedContexts .');';
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }       
    

    /**
     * create edit asterisk context dialog
     *
     * @param int $lineId
     * 
     */
    public function editAsteriskContext($contextId=NULL)
    {
        if (!empty($contextId)) {
            $context = Voipmanager_Controller::getInstance()->getAsteriskContext($contextId);
            $encodedContext = Zend_Json::encode($context->toArray());                   
        } else {
            $encodedContext = '{}';
        }
        
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $view->title="edit asterisk context data";
        $view->jsExecute = 'Tine.Voipmanager.Asterisk.Context.EditDialog.display(' . $encodedContext .');';
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }


    /**
     * create edit asterisk context dialog
     *
     * @param int $lineId
     * 
     */
    public function editAsteriskVoicemail($voicemailId=NULL)
    {
        if (!empty($voicemailId)) {
            $voicemail = Voipmanager_Controller::getInstance()->getAsteriskVoicemail($voicemailId);
            $encodedVoicemail = Zend_Json::encode($voicemail->toArray());                   
        } else {
            $encodedVoicemail = '{}';
        }

        $encodedContexts = Zend_Json::encode(Voipmanager_Controller::getInstance()->getAsteriskContexts()->toArray());
        
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $view->title="edit asterisk voicemail data";
        $view->jsExecute = 'Tine.Voipmanager.Asterisk.Voicemail.EditDialog.display(' . $encodedVoicemail .','.$encodedContexts.');';

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
    public function editSnomLocation($locationId=NULL)
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
       
        $view->title="edit snom location data";
        $view->jsExecute = 'Tine.Voipmanager.Snom.Location.EditDialog.display(' . $encodedLocation .');';
        
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
    public function editSnomSoftware($softwareId=NULL)
    {
        if (!empty($softwareId)) {
            $software = Voipmanager_Controller::getInstance()->getSnomSoftware($softwareId);
            $encodedSoftware = Zend_Json::encode($software->toArray());
        } else {
            $encodedSoftware = '{}';
        }

        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $view->title="edit snom software data";
        $view->jsExecute = 'Tine.Voipmanager.Snom.Software.EditDialog.display(' . $encodedSoftware .');';
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }    


   /**
     * create edit snom setting dialog
     *
     * @param int $settingId
     * @todo catch permission denied exceptions only
     * 
     */
    public function editSnomSetting($settingId=NULL)
    {
        if (!empty($settingId)) {
            $setting = Voipmanager_Controller::getInstance()->getSnomSetting($settingId);
            $encodedSetting = Zend_Json::encode($setting->toArray());
        } else {
            $encodedSetting = '{}';
        }

        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $view->title="edit snom setting data";
        $view->jsExecute = 'Tine.Voipmanager.Snom.Settings.EditDialog.display(' . $encodedSetting .');';

        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }  


    /**
     * create edit template dialog
     *
     * @param int $templateId
     * @todo catch permission denied exceptions only
     * @todo move stuff out of registry. Registry is reserved for Tinebase!!!
     * 
     */
    public function editSnomTemplate($templateId=NULL)
    {
        $controller = Voipmanager_Controller::getInstance();

        if (!empty($templateId)) {
            $template = $controller->getSnomTemplate($templateId);
            // encode the template array
            $encodedTemplate = Zend_Json::encode($template->toArray()); 
        } else {
            $encodedTemplate = '{}';
        }
        
        // software data
        $software = $controller->searchSnomSoftware();
        $encodedSoftware = Zend_Json::encode($software->toArray());
        
        // keylayout data
  //      $keylayout = $controller->getKeylayout();
        $encodedKeylayout = Zend_Json::encode('[]');
        
        // settings data
        $settings = $controller->getSnomSettings();
        $encodedSettings = Zend_Json::encode($settings->toArray());
        
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $view->title="edit snom template data";
        $view->jsExecute = '
            Tine.Tinebase.Registry.add("softwareVersions", ' . $controller->searchSnomSoftware()->toArray() .' );
            Tine.Voipmanager.Snom.Templates.EditDialog.display(' . $encodedTemplate .','.$encodedSoftware.','.$encodedKeylayout.','.$encodedSettings.');
        ';
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }        
     

    /**
     * create edit asterisk meetme dialog
     *
     * @param int $lineId
     * 
     */
    public function editAsteriskMeetme($meetmeId=NULL)
    {
        if (!empty($meetmeId)) {
            $meetme = Voipmanager_Controller::getInstance()->getAsteriskMeetme($meetmeId);
            $encodedMeetme = Zend_Json::encode($meetme->toArray());                   
        } else {
            $encodedMeetme = '{}';
        }

        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $view->title="edit asterisk meetme data";
        $view->jsExecute = 'Tine.Voipmanager.Asterisk.Meetme.EditDialog.display(' . $encodedMeetme .');';
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }	 
	 
}