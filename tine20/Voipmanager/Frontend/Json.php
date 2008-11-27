<?php
/**
 * Tine 2.0
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        still too much code duplication (in get/search/save/delete functions), remove that
 *              -> extend Tinebase_Application_Frontend_Json_Abstract and use __call interceptor
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the Voipmanager Management application
 *
 * @package     Voipmanager Management
 */
class Voipmanager_Frontend_Json extends Tinebase_Application_Frontend_Json_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Voipmanager';



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
     * @param string $query
     * @return array
     */
    public function getSnomPhones($sort, $dir, $query)
    {     
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $filter = new Voipmanager_Model_Snom_PhoneFilter(array(
            'query' => $query
        ));
        
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'  => $sort,
            'dir'   => $dir
        ));
        
        $rows = Voipmanager_Controller_Snom_Phone::getInstance()->search($filter, $pagination);
        
        $_rows = $rows->toArray();

        $i = 0; 
              
        foreach($_rows AS $_row) {
            if($location_row = Voipmanager_Controller_Snom_Location::getInstance()->get($_row['location_id'])) {
                $_location = $location_row->toArray();
                $_rows[$i]['location'] = $_location['name'];
            }
            
            if($template_row = Voipmanager_Controller_Snom_Template::getInstance()->get($_row['template_id'])) {
                $_template = $template_row->toArray();                                        
                $_rows[$i]['template'] = $_template['name'];
            }                
            
            $i = $i + 1;
        }         
    
        $result['results']      = $_rows;
        $result['totalcount']   = count($result['results']);

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
        $record = Voipmanager_Controller_Snom_Phone::getInstance()->get($phoneId);        
        $result = $record->toArray();      
        return $result;        
    }    
        
    /**
     * save one phone
     * -  if $phoneData['id'] is empty the phone gets added, otherwise it gets updated
     *
     * @param string $phoneData a JSON encoded array of phone properties
     * @param string $lineData
     * @param string $rightsData
     * @return array
     */
    public function saveSnomPhone($phoneData, $lineData, $rightsData)
    {

        $phoneData  = Zend_Json::decode($phoneData);
        $lineData   = Zend_Json::decode($lineData);
        $rightsData = Zend_Json::decode($rightsData);

        // unset if empty
        if (empty($phoneData['id'])) {
            unset($phoneData['id']);
        }

        //Zend_Registry::get('logger')->debug(print_r($phoneData,true));
        Zend_Registry::get('logger')->debug(print_r($rightsData,true));
        
        $phone = new Voipmanager_Model_Snom_Phone();
        $phone->setFromArray($phoneData);
        
        $phoneSettings = new Voipmanager_Model_Snom_PhoneSettings();
        $phoneSettings->setFromArray($phoneData);

        $phone->lines = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_Line', $lineData, true);
        $phone->rights = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_PhoneRight', $rightsData);
        
        if (empty($phone->id)) {
            $phone = Voipmanager_Controller_Snom_Phone::getInstance()->create($phone, $phoneSettings);
        } else {
            $phone = Voipmanager_Controller_Snom_Phone::getInstance()->update($phone, $phoneSettings);
        }
        $phone = $this->getSnomPhone($phone->getId());

/*        foreach($ownerData AS $owner) {
            $owner['phone_id'] = $phone['id'];
            
            $_owner = new Voipmanager_Model_Snom_PhoneOwner();   
            $_owner->setFromArray($owner);
            
            $_ownerData[] = $_owner;
        } */

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
        $controller = Voipmanager_Controller_Snom_Phone::getInstance();
        
        $result = array(
            'success'   => TRUE
        );
        
        $ids = Zend_Json::decode($_phoneIds);
        Voipmanager_Controller_Snom_Phone::getInstance()->delete($ids);
        
        return $result;
    }    


    /**
     * send HTTP Client Info to multiple phones
     *
     * @param array $_phoneIDs list of phoneId's to send http client info to
     * @return array
     */      
    public function resetHttpClientInfo($_phoneIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $phoneIds = Zend_Json::decode($_phoneIds);        
        
        Voipmanager_Controller_Snom_Phone::getInstance()->resetHttpClientInfo($phoneIds);
        
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
        return $this->_get($phoneSettingsId, Voipmanager_Controller_Snom_PhoneSettings::getInstance());
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
        return $this->_save($phoneSettingsData, Voipmanager_Controller_Snom_PhoneSettings::getInstance(), 'Snom_PhoneSettings', 'phone_id');       
    }

    /**
     * delete phoneSettings
     *
     * @param array $_phoneSettingsID phoneSettingsId to delete
     * @return array
     */
    public function deleteSnomPhoneSettings($_phoneSettingsId)
    {
        return $this->_delete($_phoneSettingsId, Voipmanager_Controller_Snom_PhoneSettings::getInstance());
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
     * @param string $query
     * @return array
     */
    public function getSnomLocations($sort, $dir, $query)
    {     
        $controller = Voipmanager_Controller_Snom_Location::getInstance();
        $filter = new Voipmanager_Model_Snom_LocationFilter(array(
            'query'     => $query
        ));
                
        $result = $this->_search($controller, $sort, $dir, $filter);
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
        return $this->_get($locationId, Voipmanager_Controller_Snom_Location::getInstance());
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
        return $this->_save($locationData, Voipmanager_Controller_Snom_Location::getInstance(), 'Snom_Location');              
    }
     
        
    /**
     * delete multiple locations
     *
     * @param array $_locationIDs list of locationId's to delete
     * @return array
     */
    public function deleteSnomLocations($_locationIds)
    {
        return $this->_delete($_locationIds, Voipmanager_Controller_Snom_Location::getInstance());
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
     * @param string $query
     * @return array
     */
    public function searchSnomSoftware($sort, $dir, $query)
    {     
        $controller = Voipmanager_Controller_Snom_Software::getInstance();
        $filter = new Voipmanager_Model_Snom_SoftwareFilter(array(
            'query'     => $query
        ));
                
        $result = $this->_search($controller, $sort, $dir, $filter);
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
        return $this->_get($softwareId, Voipmanager_Controller_Snom_Software::getInstance());
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
        return $this->_save($softwareData, Voipmanager_Controller_Snom_Software::getInstance(), 'Snom_Software');
    }     
      
      
    /**
     * delete multiple softwareversion entries
     *
     * @param array $_softwareIDs list of softwareId's to delete
     * @return array
     */
    public function deleteSnomSoftware($_softwareIds)
    {
        return $this->_delete($_softwareIds, Voipmanager_Controller_Snom_Software::getInstance());
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
     * @param string $query
     * @return array
     */
    public function getSnomTemplates($sort, $dir, $query)
    {     
        $controller = Voipmanager_Controller_Snom_Template::getInstance();
        $filter = new Voipmanager_Model_Snom_TemplateFilter(array(
            'query'     => $query
        ));
                
        $result = $this->_search($controller, $sort, $dir, $filter);
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
        return $this->_get($templateId, Voipmanager_Controller_Snom_Template::getInstance());
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
        return $this->_save($templateData, Voipmanager_Controller_Snom_Template::getInstance(), 'Snom_Template');               
    }     
    
    /**
     * delete multiple template entries
     *
     * @param array $_templateIDs list of templateId's to delete
     * @return array
     */
    public function deleteSnomTemplates($_templateIds)
    {
        return $this->_delete($_templateIds, Voipmanager_Controller_Snom_Template::getInstance());
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
     * @param string $query
     * @return array
     */
    public function getSnomSettings($sort, $dir, $query)
    {     
        $controller = Voipmanager_Controller_Snom_Setting::getInstance();
        $filter = new Voipmanager_Model_Snom_SettingFilter(array(
            'query'     => $query
        ));
                
        $result = $this->_search($controller, $sort, $dir, $filter);
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
        return $this->_get($settingId, Voipmanager_Controller_Snom_Setting::getInstance());
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
        return $this->_save($settingData, Voipmanager_Controller_Snom_Setting::getInstance(), 'Snom_Setting');
    }
    
   
    /**
     * delete multiple settings
     *
     * @param array $_settingIDs list of settingId's to delete
     * @return array
     */
    public function deleteSnomSettings($_settingIds)
    {
        return $this->_delete($_settingIds, Voipmanager_Controller_Snom_Setting::getInstance());
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
     * @param string $query
     * @return array
     */
    public function getAsteriskContexts($sort = NULL, $dir = NULL, $query = NULL)
    {     
        $controller = Voipmanager_Controller_Asterisk_Context::getInstance();
        $filter = new Voipmanager_Model_Asterisk_ContextFilter(array(
            'query'     => $query
        ));
                
        $result = $this->_search($controller, $sort, $dir, $filter);
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
        return $this->_get($contextId, Voipmanager_Controller_Asterisk_Context::getInstance());
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
        return $this->_save($contextData, Voipmanager_Controller_Asterisk_Context::getInstance(), 'Asterisk_Context');      
    }     
    
    
     /**
     * delete multiple contexts
     *
     * @param array $_contextIDs list of contextId's to delete
     * @return array
     */
    public function deleteAsteriskContexts($_contextIds)
    {
        return $this->_delete($_contextIds, Voipmanager_Controller_Asterisk_Context::getInstance());
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
     * @param string $query
     * @return array
     */
    public function getAsteriskMeetmes($sort, $dir, $query)
    {     
        $controller = Voipmanager_Controller_Asterisk_Meetme::getInstance();
        $filter = new Voipmanager_Model_Asterisk_MeetmeFilter(array(
            'query'     => $query
        ));
                
        $result = $this->_search($controller, $sort, $dir, $filter);
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
        return $this->_get($meetmeId, Voipmanager_Controller_Asterisk_Meetme::getInstance());
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
        return $this->_save($meetmeData, Voipmanager_Controller_Asterisk_Meetme::getInstance(), 'Asterisk_Meetme');
    }     
    
    /**
     * delete multiple meetmes
     *
     * @param array $_meetmeIDs list of meetmeId's to delete
     * @return array
     */
    public function deleteAsteriskMeetmes($_meetmeIds)
    {
        return $this->_delete($_meetmeIds, Voipmanager_Controller_Asterisk_Meetme::getInstance());
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
     * @param string $query
     * @param string $context
     * @return array
     */
    public function getAsteriskSipPeers($sort, $dir, $query, $context)
    {       
        $controller = Voipmanager_Controller_Asterisk_SipPeer::getInstance();
        $filter = new Voipmanager_Model_Asterisk_SipPeerFilter(array(
            'query'     => $query,
            'context'   => $context
        ));
                
        $result = $this->_search($controller, $sort, $dir, $filter);
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
        return $this->_get($sipPeerId, Voipmanager_Controller_Asterisk_SipPeer::getInstance());       
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
        return $this->_save($sipPeerData, Voipmanager_Controller_Asterisk_SipPeer::getInstance(), 'Asterisk_SipPeer');       
    }     
    

    /**
     * delete multiple asterisk sip peers
     *
     * @param array $_sipPeerIDs list of sipPeerId's to delete
     * @return array
     */
    public function deleteAsteriskSipPeers($_sipPeerIds)
    {
        return $this->_delete($_sipPeerIds, Voipmanager_Controller_Asterisk_SipPeer::getInstance());
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
     * @param string $query
     * @param string $context
     * @return array
     */
    public function getAsteriskVoicemails($sort, $dir, $query, $context)
    {     
        $controller = Voipmanager_Controller_Asterisk_Voicemail::getInstance();
        $filter = new Voipmanager_Model_Asterisk_VoicemailFilter(array(
            'query'     => $query,
            'context'   => $context
        ));
                
        $result = $this->_search($controller, $sort, $dir, $filter);
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
        return $this->_get($voicemailId, Voipmanager_Controller_Asterisk_Voicemail::getInstance());
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
        return $this->_save($voicemailData, Voipmanager_Controller_Asterisk_Voicemail::getInstance(), 'Asterisk_Voicemail');
    }     
    
   
    /**
     * delete multiple voicemails
     *
     * @param array $_voicemailIDs list of voicemailId's to delete
     * @return array
     */
    public function deleteAsteriskVoicemails($_voicemailIds)
    {
        return $this->_delete($_voicemailIds, Voipmanager_Controller_Asterisk_Voicemail::getInstance());
    }     
    
    /********************* generic get/search/save/delete functions ************************************/
    
    
    /**
     * generic search function
     *
     * @param $_controller
     * @param string $_sort
     * @param string $_dir
     * @param Tinebase_Record_Interface $_filter
     * @return array
     * 
     * @deprecated remove that when frontend gets refactored
     */
    protected function _search($_controller, $_sort, $_dir, $_filter)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'  => $_sort,
            'dir'   => $_dir
        ));
        
        if($rows = $_controller->search($_filter, $pagination)) {
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }
        
        return $result;    
    }
}
