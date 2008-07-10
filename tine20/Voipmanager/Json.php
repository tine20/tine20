<?php
/**
 * Tine 2.0
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the Voipmanager Management application
 *
 * @package     Voipmanager Management
 */
class Voipmanager_Json extends Tinebase_Application_Json_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_appname = 'Voipmanager';



/****************************************
 * SNOM PHONE / PHONESETTINGS FUNCTIONS
 *
 * 
 */
    
    /**
     * get snom phones
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
    public function getSnomPhones($sort, $dir, $query)
    {     
  
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Voipmanager_Controller::getInstance()->getSnomPhones($sort, $dir, $query)) {
        
            $_rows = $rows->toArray();

            $i = 0; 
                  
            foreach($_rows AS $_row)
            {
                if($location_row = Voipmanager_Controller::getInstance()->getSnomLocation($_row['location_id']))
                {
                    $_location = $location_row->toArray();
                    $_rows[$i]['location'] = $_location['name'];
                }
                
                if($template_row = Voipmanager_Controller::getInstance()->getSnomTemplate($_row['template_id']))
                {
                    $_template = $template_row->toArray();                                        
                    $_rows[$i]['template'] = $_template['name'];
                }                
                
                $i = $i + 1;
            }         
        
            $result['results']      = $_rows;
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    }
    
    
   /**
     * get one phone identified by phoneId
     *
     * @param int $phoneId
     * @return array
     */
    public function getSnomPhone($phoneId)
    {
        $result = array(
            'success'   => true
        );

        $phone = Voipmanager_Controller::getInstance()->getSnomPhone($phoneId);
        
        $result = $phone->toArray();      
          
        return $result;
    }    
    
   
    
    /**
     * save one phone
     *
     * if $phoneData['id'] is empty the phone gets added, otherwise it gets updated
     *
     * @param string $phoneData a JSON encoded array of phone properties
     * @return array
     */
    public function saveSnomPhone($phoneData, $lineData)
    {

        $phoneData = Zend_Json::decode($phoneData);
        $lineData = Zend_Json::decode($lineData);

        // unset if empty
        if (empty($phoneData['id'])) {
            unset($phoneData['id']);
        }

        //Zend_Registry::get('logger')->debug(print_r($phoneData,true));
        $phone = new Voipmanager_Model_SnomPhone();
        $phone->setFromArray($phoneData);
        
        $phoneSettings = new Voipmanager_Model_SnomPhoneSettings();
        $phoneSettings->setFromArray($phoneData);

        $phone->lines = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomLine', $lineData, true);
        
        if (empty($phone->id)) {
            $phone = Voipmanager_Controller::getInstance()->createSnomPhone($phone, $phoneSettings);
        } else {
            $phone = Voipmanager_Controller::getInstance()->updateSnomPhone($phone, $phoneSettings);
        }
        $phone = $this->getSnomPhone($phone->getId());

        $result = array('success'           => true,
            'welcomeMessage'    => 'Entry updated',
            'updatedData'       => $phone
        );
        
        return $result;         
    }     
    
   
    /**
     * delete multiple phones
     *
     * @param array $_phoneIDs list of phoneId's to delete
     * @return array
     */
    public function deleteSnomPhones($_phoneIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $phoneIds = Zend_Json::decode($_phoneIds);
        
        Voipmanager_Controller::getInstance()->deleteSnomPhones($phoneIds);
        
        

        return $result;
    }    
      
      
      
      
   /**
     * get one phoneSettings identified by phoneSettingsId
     *
     * @param int $phoneSettingsId
     * @return array
     */
    public function getSnomPhoneSettings($phoneSettingsId)
    {
        $result = array(
            'success'   => true
        );

        $phoneSettings = Voipmanager_Controller::getInstance()->getSnomPhoneSettings($phoneSettingsId);
        
        $result = $phoneSettings->toArray();      
          
        return $result;
    }              
        
        
    /**
     * save one phoneSettings
     *
     * if $phoneSettingsData['id'] is empty the phoneSettings gets added, otherwise it gets updated
     *
     * @param string $phoneSettingsData a JSON encoded array of phoneSettings properties
     * @return array
     */
    public function saveSnomPhoneSettings($phoneSettingsData)
    {
        $phoneSettingsData = Zend_Json::decode($phoneSettingsData);
        
        // unset if empty
        if (empty($phoneSettingsData['setting_id'])) {
            unset($phoneSettingsData['setting_id']);
        }

        //Zend_Registry::get('logger')->debug(print_r($phoneSettingsData,true));
        $phoneSettings = new Voipmanager_Model_SnomPhoneSettings();
        $phoneSettings->setFromArray($phoneSettingsData);


        if (empty($phoneSettings->setting_id)) {
            $phoneSettings = Voipmanager_Controller::getInstance()->createSnomPhoneSettings($phoneSettings);
        } else {
            $phoneSettings = Voipmanager_Controller::getInstance()->updateSnomPhoneSettings($phoneSettings);
        }
        $phoneSettings = $this->getSnomPhoneSettings($phoneSettings->getId());

        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $phoneSettings
        ); //$phoneSettings->toArray());
        
        
        return $result;
         
    }     
    
   
    /**
     * delete phoneSettings
     *
     * @param array $_phoneSettingsID phoneSettingsId to delete
     * @return array
     */
    public function deleteSnomPhoneSettings($_phoneSettingsId)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $phoneSettingsId = Zend_Json::decode($_phoneSettingsId);
        
        Voipmanager_Controller::getInstance()->deleteSnomPhoneSettings($phoneSettingsId);

        return $result;
    }          
        
      
/********************************
 * SNOM LOCATION FUNCTIONS
 *
 * 
 */
        
    /**
     * get snom location
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
    public function getSnomLocations($sort, $dir, $query)
    {     
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Voipmanager_Controller::getInstance()->getSnomLocations($sort, $dir, $query)) {
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    }        
    
    
   /**
     * get one location identified by locationId
     *
     * @param int $locationId
     * @return array
     */
    public function getSnomLocation($locationId)
    {
        $result = array(
            'success'   => true
        );

        $location = Voipmanager_Controller::getInstance()->getSnomLocation($locationId);
        
        $result = $location->toArray();        
        return $result;
    }      


    /**
     * save one location
     *
     * if $locationData['id'] is empty the location gets added, otherwise it gets updated
     *
     * @param string $locationData a JSON encoded array of location properties
     * @return array
     */
    public function saveSnomLocation($locationData)
    {
        $locationData = Zend_Json::decode($locationData);
        Zend_Registry::get('logger')->debug(print_r($locationData,true));
        
        // unset if empty
        if (empty($locationData['id'])) {
            unset($locationData['id']);
        }

        $location = new Voipmanager_Model_SnomLocation();
        $location->setFromArray($locationData);
        
        if (empty($location->id)) {
            $location = Voipmanager_Controller::getInstance()->createSnomLocation($location);
        } else {
            $location = Voipmanager_Controller::getInstance()->updateSnomLocation($location);
        }
        $location = $this->getSnomLocation($location->getId());
        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $location
        ); //$location->toArray());
        
        
        return $result;
         
    }     
     
        
    /**
     * delete multiple locations
     *
     * @param array $_locationIDs list of locationId's to delete
     * @return array
     */
    public function deleteSnomLocations($_locationIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $locationIds = Zend_Json::decode($_locationIds);
        
        Voipmanager_Controller::getInstance()->deleteSnomLocations($locationIds);

        return $result;
    }        
        
      
      
/********************************
 * SNOM SOFTWARE FUNCTIONS
 *
 * 
 */       
  
    /**
     * get snom software
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
    public function searchSnomSoftware($sort, $dir, $query)
    {     
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $rows = Voipmanager_Controller::getInstance()->searchSnomSoftware($sort, $dir, $query);
        $result['results']      = $rows->toArray();
        $result['totalcount']   = count($result['results']);

        return $result;        
    }        
    
    
   /**
     * get one software identified by softwareId
     *
     * @param int $softwareId
     * @return array
     */
    public function getSnomSoftware($softwareId)
    {
        $result = array(
            'success'   => true
        );

        $software = Voipmanager_Controller::getInstance()->getSnomSoftware($softwareId);
        
        $result = $software->toArray();        
        return $result;
    }         


    /**
     * add/update software
     *
     * if $softwareData['id'] is empty the software gets added, otherwise it gets updated
     *
     * @param string $phoneData a JSON encoded array of software properties
     * @return array
     */
    public function saveSnomSoftware($softwareData)
    {
        $softwareData = Zend_Json::decode($softwareData);
        Zend_Registry::get('logger')->debug(print_r($softwareData,true));
        
        // unset if empty
        if (empty($softwareData['id'])) {
            unset($softwareData['id']);
        }

        //Zend_Registry::get('logger')->debug(print_r($phoneData,true));
        $software = new Voipmanager_Model_SnomSoftware();
        $software->setFromArray($softwareData);
        
        if ( empty($software->id) ) {
            $software = Voipmanager_Controller::getInstance()->createSnomSoftware($software);
        } else {
            $software = Voipmanager_Controller::getInstance()->updateSnomSoftware($software);
        }
        //$software = $this->getSoftware($software->getId());
        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $software->toArray()
        ); //$phone->toArray());
        
        
        return $result;
         
    }     
      
      
    /**
     * delete multiple softwareversion entries
     *
     * @param array $_softwareIDs list of softwareId's to delete
     * @return array
     */
    public function deleteSnomSoftware($_softwareIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $softwareIds = Zend_Json::decode($_softwareIds);
        
        Voipmanager_Controller::getInstance()->deleteSnomSoftware($softwareIds);

        return $result;
    }       
        
        
        
/********************************
 * SNOM TEMPLATE FUNCTIONS
 *
 * 
 */
        
    /**
     * get snom templates
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
    public function getSnomTemplates($sort, $dir, $query)
    {     
  
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Voipmanager_Controller::getInstance()->getSnomTemplates($sort, $dir, $query)) {
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    }
    
    
   /**
     * get one template identified by templateId
     *
     * @param int $templateId
     * @return array
     */
    public function getSnomTemplate($templateId)
    {
        $result = array(
            'success'   => true
        );

        $template = Voipmanager_Controller::getInstance()->getTemplateById($templateId);
        
        $result = $template->toArray();        
        return $result;
    }
             
    /**
     * add/update template
     *
     * if $templateData['id'] is empty the template gets added, otherwise it gets updated
     *
     * @param string $templateData a JSON encoded array of template properties
     * @return array
     */
    public function saveSnomTemplate($templateData)
    {
        $templateData = Zend_Json::decode($templateData);
        
        // unset if empty
        if (empty($templateData['id'])) {
            unset($templateData['id']);
        }

        $template = new Voipmanager_Model_SnomTemplate();
        $template->setFromArray($templateData);
        
        if ( empty($template->id) ) {
            $template = Voipmanager_Controller::getInstance()->createSnomTemplate($template);
        } else {
            $template = Voipmanager_Controller::getInstance()->updateSnomTemplate($template);
        }

        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $template->toArray()
        );
        
        
        return $result;
         
    }     
    
    
    /**
     * delete multiple template entries
     *
     * @param array $_templateIDs list of templateId's to delete
     * @return array
     */
    public function deleteSnomTemplates($_templateIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $templateIds = Zend_Json::decode($_templateIds);
        
        Voipmanager_Controller::getInstance()->deleteSnomTemplates($templateIds);

        return $result;
    }     
    
    
    
/********************************
 * ASTERISK SIP PEER FUNCTIONS
 *
 * 
 */    
 
    /**
     * get asterisk sip peers
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
    public function getAsteriskSipPeers($sort, $dir, $query, $context)
    {     
  
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Voipmanager_Controller::getInstance()->searchAsteriskSipPeers($sort, $dir, $query, $context)) {
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    }
    
    
   /**
     * get one asterisk sip peer identified by sipPeerId
     *
     * @param int $sipPeerId
     * @return array
     */
    public function getAsteriskSipPeer($sipPeerId)
    {
        $result = array(
            'success'   => true
        );

        $sipPeer = Voipmanager_Controller::getInstance()->getAsteriskSipPeer($sipPeerId);
        
        $result = $sipPeer->toArray();        
        return $result;
    }
          
             
    /**
     * add/update asterisk sip peer
     *
     * if $sipPeerData['id'] is empty the sip peer gets added, otherwise it gets updated
     *
     * @param string $sipPeerData a JSON encoded array of sipPeer properties
     * @return array
     */
    public function saveAsteriskSipPeer($sipPeerData)
    {
        $sipPeerData = Zend_Json::decode($sipPeerData);
        
        // unset if empty
        if (empty($sipPeerData['id'])) {
            unset($sipPeerData['id']);
        }

        $sipPeer = new Voipmanager_Model_AsteriskSipPeer();
        $sipPeer->setFromArray($sipPeerData);
        
        if ( empty($sipPeer->id) ) {
            $sipPeer = Voipmanager_Controller::getInstance()->createAsteriskSipPeer($sipPeer);
        } else {
            $sipPeer = Voipmanager_Controller::getInstance()->updateAsteriskSipPeer($sipPeer);
        }

        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $sipPeer->toArray()
        );
        
        
        return $result;
         
    }     
    

    /**
     * delete multiple asterisk sip peers
     *
     * @param array $_sipPeerIDs list of sipPeerId's to delete
     * @return array
     */
    public function deleteAsteriskSipPeers($_sipPeerIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $sipPeerIds = Zend_Json::decode($_sipPeerIds);
     
        Voipmanager_Controller::getInstance()->deleteAsteriskSipPeers($sipPeerIds);

        return $result;
    }     
    
    
    
/********************************
 * ASTERISK CONTEXT FUNCTIONS
 *
 * 
 */    
     
    /**
     * get asterisk contexts
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
    public function getAsteriskContexts($sort = NULL, $dir = NULL, $query = NULL)
    {     
  
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Voipmanager_Controller::getInstance()->getAsteriskContexts($sort, $dir, $query)) {
        
            $_rows = $rows->toArray();

            $i = 0; 
        
            $result['results']      = $_rows;
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    }
    
    
   /**
     * get one context identified by contextId
     *
     * @param int $contextId
     * @return array
     */
    public function getAsteriskContext($contextId)
    {
        $result = array(
            'success'   => true
        );

        $context = Voipmanager_Controller::getInstance()->getAsteriskContext($contextId);
        
        $result = $context->toArray();      
          
        return $result;
    }    
    
    
    /**
     * save one context
     *
     * if $contextData['id'] is empty the context gets added, otherwise it gets updated
     *
     * @param string $contextData a JSON encoded array of context properties
     * @return array
     */
    public function saveAsteriskContext($contextData)
    {
        $contextData = Zend_Json::decode($contextData);
        
        // unset if empty
        if (empty($contextData['id'])) {
            unset($contextData['id']);
        }

        //Zend_Registry::get('logger')->debug(print_r($contextData,true));
        $context = new Voipmanager_Model_AsteriskContext();
        $context->setFromArray($contextData);

        
        if (empty($context->id)) {
            $context = Voipmanager_Controller::getInstance()->createAsteriskContext($context);
        } else {
            $context = Voipmanager_Controller::getInstance()->updateAsteriskContext($context);
        }
        $context = $this->getAsteriskContext($context->getId());
        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $context
        ); //$context->toArray());
        
        
        return $result;
         
    }     
    
    
     /**
     * delete multiple contexts
     *
     * @param array $_contextIDs list of contextId's to delete
     * @return array
     */
    public function deleteAsteriskContexts($_contextIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $contextIds = Zend_Json::decode($_contextIds);
        
        Voipmanager_Controller::getInstance()->deleteAsteriskContexts($contextIds);

        return $result;
    }    
       
    
    
/********************************
 * ASTERISK VOICEMAIL FUNCTIONS
 *
 * 
 */        
    
    /**
     * get asterisk voicemails
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
    public function getAsteriskVoicemails($sort, $dir, $query, $context)
    {     
  
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Voipmanager_Controller::getInstance()->getAsteriskVoicemails($sort, $dir, $query, $context)) {
        
            $_rows = $rows->toArray();

            $i = 0; 
        
            $result['results']      = $_rows;
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    }
    
    
   /**
     * get one voicemail identified by voicemailId
     *
     * @param int $voicemailId
     * @return array
     */
    public function getAsteriskVoicemail($voicemailId)
    {
        $result = array(
            'success'   => true
        );

        $voicemail = Voipmanager_Controller::getInstance()->getAsteriskVoicemail($voicemailId);
        
        $result = $voicemail->toArray();      
          
        return $result;
    }    
    
    
    /**
     * save one voicemail
     *
     * if $voicemailData['id'] is empty the voicemail gets added, otherwise it gets updated
     *
     * @param string $voicemailData a JSON encoded array of voicemail properties
     * @return array
     */
    public function saveAsteriskVoicemail($voicemailData)
    {
        $voicemailData = Zend_Json::decode($voicemailData);
        
        // unset if empty
        if (empty($voicemailData['id'])) {
            unset($voicemailData['id']);
        }

        //Zend_Registry::get('logger')->debug(print_r($voicemailData,true));
        $voicemail = new Voipmanager_Model_AsteriskVoicemail();
        $voicemail->setFromArray($voicemailData);

        
        if (empty($voicemail->id)) {
            $voicemail = Voipmanager_Controller::getInstance()->createAsteriskVoicemail($voicemail);
        } else {
            $voicemail = Voipmanager_Controller::getInstance()->updateAsteriskVoicemail($voicemail);
        }
        $voicemail = $this->getAsteriskVoicemail($voicemail->getId());
        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $voicemail
        ); //$voicemail->toArray());
        
        
        return $result;
         
    }     
    
   
    /**
     * delete multiple voicemails
     *
     * @param array $_voicemailIDs list of voicemailId's to delete
     * @return array
     */
    public function deleteAsteriskVoicemails($_voicemailIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $voicemailIds = Zend_Json::decode($_voicemailIds);
        
        Voipmanager_Controller::getInstance()->deleteAsteriskVoicemails($voicemailIds);

        return $result;
    }     



/********************************
 * SNOM SETTING FUNCTIONS
 *
 * 
 */        
    
    /**
     * get snom settings
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
    public function getSnomSettings($sort, $dir, $query)
    {     
  
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Voipmanager_Controller::getInstance()->getSnomSettings($sort, $dir, $query)) {
        
            $_rows = $rows->toArray();

            $i = 0; 
        
            $result['results']      = $_rows;
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    }
    
    
   /**
     * get one setting identified by settingId
     *
     * @param int $settingId
     * @return array
     */
    public function getSnomSetting($settingId)
    {
        $result = array(
            'success'   => true
        );

        $setting = Voipmanager_Controller::getInstance()->getSnomSetting($settingId);
        
        $result = $setting->toArray();      
          
        return $result;
    }    
    
    
    /**
     * save one setting
     *
     * if $settingData['id'] is empty the setting gets added, otherwise it gets updated
     *
     * @param string $settingData a JSON encoded array of setting properties
     * @return array
     */
    public function saveSnomSetting($settingData)
    {
        $settingData = Zend_Json::decode($settingData);
        
        // unset if empty
        if (empty($settingData['id'])) {
            unset($settingData['id']);
        }


        //Zend_Registry::get('logger')->debug(print_r($settingData,true));
        $setting = new Voipmanager_Model_SnomSetting();
        $setting->setFromArray($settingData);
        
        if (empty($setting->id)) {
            $setting = Voipmanager_Controller::getInstance()->createSnomSetting($setting);
        } else {
            $setting = Voipmanager_Controller::getInstance()->updateSnomSetting($setting);
        }
        $setting = $this->getSnomSetting($setting->getId());
        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $setting
        ); //$setting->toArray());
        
        
        return $result;
         
    }     
    
   
    /**
     * delete multiple settings
     *
     * @param array $_settingIDs list of settingId's to delete
     * @return array
     */
    public function deleteSnomSettings($_settingIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $settingIds = Zend_Json::decode($_settingIds);
        
        Voipmanager_Controller::getInstance()->deleteSnomSettings($settingIds);

        return $result;
    }      
   
   
   
/********************************
 * ASTERISK MEETME FUNCTIONS
 *
 * 
 */        
    
    /**
     * get asterisk meetmes
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
    public function getAsteriskMeetmes($sort, $dir, $query)
    {     
  
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Voipmanager_Controller::getInstance()->getAsteriskMeetmes($sort, $dir, $query)) {
        
            $_rows = $rows->toArray();

            $i = 0; 
        
            $result['results']      = $_rows;
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    }
    
    
   /**
     * get one meetme identified by meetmeId
     *
     * @param int $meetmeId
     * @return array
     */
    public function getAsteriskMeetme($meetmeId)
    {
        $result = array(
            'success'   => true
        );

        $meetme = Voipmanager_Controller::getInstance()->getAsteriskMeetme($meetmeId);
        
        $result = $meetme->toArray();      
          
        return $result;
    }    
    
    
    /**
     * save one meetme
     *
     * if $meetmeData['id'] is empty the meetme gets added, otherwise it gets updated
     *
     * @param string $meetmeData a JSON encoded array of meetme properties
     * @return array
     */
    public function saveAsteriskMeetme($meetmeData)
    {
        $meetmeData = Zend_Json::decode($meetmeData);
        
        // unset if empty
        if (empty($meetmeData['id'])) {
            unset($meetmeData['id']);
        }

        //Zend_Registry::get('logger')->debug(print_r($meetmeData,true));
        $meetme = new Voipmanager_Model_AsteriskMeetme();
        $meetme->setFromArray($meetmeData);

        
        if (empty($meetme->id)) {
            $meetme = Voipmanager_Controller::getInstance()->createAsteriskMeetme($meetme);
        } else {
            $meetme = Voipmanager_Controller::getInstance()->updateAsteriskMeetme($meetme);
        }
        $meetme = $this->getAsteriskMeetme($meetme->getId());
        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $meetme
        ); //$meetme->toArray());
        
        
        return $result;
         
    }     
    
   
    /**
     * delete multiple meetmes
     *
     * @param array $_meetmeIDs list of meetmeId's to delete
     * @return array
     */
    public function deleteAsteriskMeetmes($_meetmeIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $meetmeIds = Zend_Json::decode($_meetmeIds);
        
        Voipmanager_Controller::getInstance()->deleteAsteriskMeetmes($meetmeIds);

        return $result;
    }     

    
}