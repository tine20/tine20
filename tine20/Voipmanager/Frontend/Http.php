<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Http.php 5090 2008-10-24 10:30:05Z p.schuele@metaways.de $
 * 
 */

/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the Voipmanager Management application
 *
 * @package     Voipmanager Management
 */
class Voipmanager_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    protected $_applicationName = 'Voipmanager';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
        	'Voipmanager/js/Models.js',
            'Voipmanager/js/widgets.js',
            'Voipmanager/js/Voipmanager.js',
            'Voipmanager/js/CallForwardPanel.js',
            'Voipmanager/js/Snom/LineGridPanel.js',
            'Voipmanager/js/Snom/SoftwareGridPanel.js',
            'Voipmanager/js/Snom/SoftwareEditDialog.js',
        	'Voipmanager/js/Snom/TemplateGridPanel.js',
            'Voipmanager/js/Snom/TemplateEditDialog.js',
        	'Voipmanager/js/Snom/PhoneGridPanel.js',
            'Voipmanager/js/Snom/PhoneEditDialog.js',
			'Voipmanager/js/Snom/LocationGridPanel.js',
            'Voipmanager/js/Snom/LocationEditDialog.js',        
        	'Voipmanager/js/Snom/SettingGridPanel.js',
            'Voipmanager/js/Snom/SettingEditDialog.js',
        	'Voipmanager/js/Asterisk/SipPeerGridPanel.js',
            'Voipmanager/js/Asterisk/SipPeerEditDialog.js',
            'Voipmanager/js/Asterisk/ContextGridPanel.js',
            'Voipmanager/js/Asterisk/ContextEditDialog.js',
        	'Voipmanager/js/Asterisk/VoicemailGridPanel.js',
            'Voipmanager/js/Asterisk/VoicemailEditDialog.js',
        	'Voipmanager/js/Asterisk/MeetmeGridPanel.js',
            'Voipmanager/js/Asterisk/MeetmeEditDialog.js',
        	'Voipmanager/js/MainScreen.js'
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
        $snomTemplates = Voipmanager_Controller_Snom_Template::getInstance()->search();
        $snomLocations = Voipmanager_Controller_Snom_Location::getInstance()->search();
        
        $pagination = new Tinebase_Model_Pagination(array(
            'sort' => 'name'
        ));
        $asteriskSipPeers = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->search(NULL, $pagination);
        $encodedAsteriskSipPeers = Zend_Json::encode($asteriskSipPeers->toArray());    
        
        if (!empty($phoneId)) {
            $snomPhone = Voipmanager_Controller_Snom_Phone::getInstance()->get($phoneId);
            $_phoneData = $snomPhone->toArray();             
            $_phoneSettingsData = Voipmanager_Controller_Snom_PhoneSettings::getInstance()->get($_phoneData['id'])->toArray();
            
            $_templateData = Voipmanager_Controller_Snom_Template::getInstance()->get($_phoneData['template_id'])->toArray();
            $_settingsData = Voipmanager_Controller_Snom_Setting::getInstance()->get($_templateData['setting_id'])->toArray();            

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
            $sipPeer = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->get($sipPeerId);
        } else {
            $sipPeer = new Voipmanager_Model_Asterisk_SipPeer(array(
                'type'  => 'user'
            )); 
        }

        // encode the asterisk sip peer array
        $encodedSipPeer = Zend_Json::encode($sipPeer->toArray());                   
        
        $encodedContexts = Zend_Json::encode(Voipmanager_Controller_Asterisk_Context::getInstance()->search()->toArray());
        
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
            $context = Voipmanager_Controller_Asterisk_Context::getInstance()->get($contextId);
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
            $voicemail = Voipmanager_Controller_Asterisk_Voicemail::getInstance()->get($voicemailId);
            $encodedVoicemail = Zend_Json::encode($voicemail->toArray());                   
        } else {
            $encodedVoicemail = '{}';
        }

        $encodedContexts = Zend_Json::encode(Voipmanager_Controller_Asterisk_Context::getInstance()->search()->toArray());
        
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
            $location = Voipmanager_Controller_Snom_Location::getInstance()->get($locationId);
        } else {
            $location = new Voipmanager_Model_Snom_Location(array(
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
            $software = Voipmanager_Controller_Snom_Software::getInstance()->get($softwareId);
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
            $setting = Voipmanager_Controller_Snom_Setting::getInstance()->get($settingId);
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
        if (!empty($templateId)) {
            $template = Voipmanager_Controller_Snom_Template::getInstance()->get($templateId);
            // encode the template array
            $encodedTemplate = Zend_Json::encode($template->toArray()); 
        } else {
            $encodedTemplate = '{}';
        }
        
        // software data
        $software = Voipmanager_Controller_Snom_Software::getInstance()->search();
        $encodedSoftware = Zend_Json::encode($software->toArray());
        
        // keylayout data
  //      $keylayout = $controller->getKeylayout();
        $encodedKeylayout = Zend_Json::encode('[]');
        
        // settings data
        $settings = Voipmanager_Controller_Snom_Setting::getInstance()->search();
        $encodedSettings = Zend_Json::encode($settings->toArray());
        
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $view->title="edit snom template data";
        $view->jsExecute = '
            Tine.Tinebase.registry.add("softwareVersions", ' . Voipmanager_Controller_Snom_Software::getInstance()->search()->toArray() .' );
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
            $meetme = Voipmanager_Controller_Asterisk_Meetme::getInstance()->get($meetmeId);
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