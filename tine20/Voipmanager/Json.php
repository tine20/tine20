<?php
/**
 * Tine 2.0
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        still too much code duplication (in get/search/save/delete functions), remove that (but how?)
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
     * @param string $query
     * @return array
     */
    public function getSnomPhones($sort, $dir, $query)
    {     
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $filter = new Voipmanager_Model_SnomPhoneFilter(array(
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
            if($location_row = Voipmanager_Controller::getInstance()->getSnomLocation($_row['location_id'])) {
                $_location = $location_row->toArray();
                $_rows[$i]['location'] = $_location['name'];
            }
            
            if($template_row = Voipmanager_Controller::getInstance()->getSnomTemplate($_row['template_id'])) {
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
        $controller = Voipmanager_Controller_Snom_Phone::getInstance();       
        $result = $this->_get($controller, $phoneId); 
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
        
        $phone = new Voipmanager_Model_SnomPhone();
        $phone->setFromArray($phoneData);
        
        $phoneSettings = new Voipmanager_Model_SnomPhoneSettings();
        $phoneSettings->setFromArray($phoneData);

        $phone->lines = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomLine', $lineData, true);
        $phone->rights = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomPhoneRight', $rightsData);
        
        if (empty($phone->id)) {
            $phone = Voipmanager_Controller_Snom_Phone::getInstance()->create($phone, $phoneSettings);
        } else {
            $phone = Voipmanager_Controller_Snom_Phone::getInstance()->update($phone, $phoneSettings);
        }
        $phone = $this->getSnomPhone($phone->getId());

/*        foreach($ownerData AS $owner) {
            $owner['phone_id'] = $phone['id'];
            
            $_owner = new Voipmanager_Model_SnomPhoneOwner();   
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
        $result = $this->_delete($controller, $_phoneIds);
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
        $controller = Voipmanager_Controller_Snom_PhoneSettings::getInstance();       
        $result = $this->_get($controller, $phoneSettingsId); 
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
        $controller = Voipmanager_Controller_Snom_PhoneSettings::getInstance();
        $result = $this->_save($controller, $phoneSettingsData, 'Voipmanager_Model_SnomPhoneSettings');
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
        $controller = Voipmanager_Controller_Snom_PhoneSettings::getInstance();
        $result = $this->_delete($controller, $_phoneIds);
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
     * @param string $query
     * @return array
     */
    public function getSnomLocations($sort, $dir, $query)
    {     
        $controller = Voipmanager_Controller_Snom_Location::getInstance();
        $filter = new Voipmanager_Model_SnomLocationFilter(array(
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
        $controller = Voipmanager_Controller_Snom_Location::getInstance();       
        $result = $this->_get($controller, $locationId); 
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
        $controller = Voipmanager_Controller_Snom_Location::getInstance();
        $result = $this->_save($controller, $locationData, 'Voipmanager_Model_SnomLocation');
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
        $controller = Voipmanager_Controller_Snom_Location::getInstance();
        $result = $this->_delete($controller, $_locationIds);
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
     * @param string $query
     * @return array
     */
    public function searchSnomSoftware($sort, $dir, $query)
    {     
        $controller = Voipmanager_Controller_Snom_Software::getInstance();
        $filter = new Voipmanager_Model_SnomSoftwareFilter(array(
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
        $controller = Voipmanager_Controller_Snom_Software::getInstance();       
        $result = $this->_get($controller, $softwareId); 
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
        $controller = Voipmanager_Controller_Snom_Software::getInstance();
        $result = $this->_save($controller, $softwareData, 'Voipmanager_Model_SnomSoftware');
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
        $controller = Voipmanager_Controller_Snom_Software::getInstance();
        $result = $this->_delete($controller, $_softwareIds);
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
     * @param string $query
     * @return array
     */
    public function getSnomTemplates($sort, $dir, $query)
    {     
        $controller = Voipmanager_Controller_Snom_Template::getInstance();
        $filter = new Voipmanager_Model_SnomTemplateFilter(array(
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
        $controller = Voipmanager_Controller_Snom_Template::getInstance();       
        $result = $this->_get($controller, $templateId); 
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
        $controller = Voipmanager_Controller_Snom_Template::getInstance();
        $result = $this->_save($controller, $templateData, 'Voipmanager_Model_SnomTemplate');
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
        $controller = Voipmanager_Controller_Snom_Template::getInstance();
        $result = $this->_delete($controller, $_templateIds);
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
     * @param string $query
     * @return array
     */
    public function getSnomSettings($sort, $dir, $query)
    {     
        $controller = Voipmanager_Controller_Snom_Setting::getInstance();
        $filter = new Voipmanager_Model_SnomSettingFilter(array(
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
        $controller = Voipmanager_Controller_Snom_Setting::getInstance();       
        $result = $this->_get($controller, $settingId); 
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
        $controller = Voipmanager_Controller_Snom_Setting::getInstance();
        $result = $this->_save($controller, $settingData, 'Voipmanager_Model_SnomSetting');
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
        $controller = Voipmanager_Controller_Snom_Setting::getInstance();
        $result = $this->_delete($controller, $_settingIds);
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
     * @param string $query
     * @return array
     */
    public function getAsteriskContexts($sort = NULL, $dir = NULL, $query = NULL)
    {     
        $controller = Voipmanager_Controller_Asterisk_Context::getInstance();
        $filter = new Voipmanager_Model_AsteriskContextFilter(array(
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
        $controller = Voipmanager_Controller_Asterisk_Context::getInstance();       
        $result = $this->_get($controller, $contextId); 
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
        $controller = Voipmanager_Controller_Asterisk_Context::getInstance();
        $result = $this->_save($controller, $contextData, 'Voipmanager_Model_AsteriskContext');
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
        $controller = Voipmanager_Controller_Asterisk_Context::getInstance();
        $result = $this->_delete($controller, $_contextIds);
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
     * @param string $query
     * @return array
     */
    public function getAsteriskMeetmes($sort, $dir, $query)
    {     
        $controller = Voipmanager_Controller_Asterisk_Meetme::getInstance();
        $filter = new Voipmanager_Model_AsteriskMeetmeFilter(array(
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
        $controller = Voipmanager_Controller_Asterisk_Meetme::getInstance();       
        $result = $this->_get($controller, $meetmeId); 
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
        $controller = Voipmanager_Controller_Asterisk_Meetme::getInstance();
        $result = $this->_save($controller, $meetmeData, 'Voipmanager_Model_AsteriskMeetme');
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
        $controller = Voipmanager_Controller_Asterisk_Meetme::getInstance();
        $result = $this->_delete($controller, $_meetmeIds);
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
     * @param string $query
     * @param string $context
     * @return array
     */
    public function getAsteriskSipPeers($sort, $dir, $query, $context)
    {       
        $controller = Voipmanager_Controller_Asterisk_SipPeer::getInstance();
        $filter = new Voipmanager_Model_AsteriskSipPeerFilter(array(
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
        $controller = Voipmanager_Controller_Asterisk_SipPeer::getInstance();       
        $result = $this->_get($controller, $sipPeerId); 
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
        $controller = Voipmanager_Controller_Asterisk_SipPeer::getInstance();
        $result = $this->_save($controller, $sipPeerData, 'Voipmanager_Model_AsteriskSipPeer');
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
        $controller = Voipmanager_Controller_Asterisk_SipPeer::getInstance();
        $result = $this->_delete($controller, $_sipPeerIds);
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
     * @param string $query
     * @param string $context
     * @return array
     */
    public function getAsteriskVoicemails($sort, $dir, $query, $context)
    {     
        $controller = Voipmanager_Controller_Asterisk_Voicemail::getInstance();
        $filter = new Voipmanager_Model_AsteriskVoicemailFilter(array(
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
        $controller = Voipmanager_Controller_Asterisk_Voicemail::getInstance();       
        $result = $this->_get($controller, $voicemailId); 
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
        $controller = Voipmanager_Controller_Asterisk_Voicemail::getInstance();
        $result = $this->_save($controller, $voicemailData, 'Voipmanager_Model_AsteriskVoicemail');
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
        $controller = Voipmanager_Controller_Asterisk_Voicemail::getInstance();
        $result = $this->_delete($controller, $_voicemailIds);
        return $result;
    }     

    /********************* generic get/search/save/delete functions ************************************/
    
    /**
     * generic get function
     *
     * @param Voipmanager_Controller_Interface $_controller
     * @param integer $_id
     */
    protected function _get(Voipmanager_Controller_Interface $_controller, $_id)
    {
        $record = $_controller->get($_id);        
        $result = $record->toArray();      
        return $result;
    }

    /**
     * generic search function
     *
     * @param Voipmanager_Controller_Interface $_controller
     * @param string $_sort
     * @param string $_dir
     * @param Tinebase_Record_Interface $_filter
     * @return array
     */
    protected function _search(Voipmanager_Controller_Interface $_controller, $_sort, $_dir, $_filter)
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
    
    /**
     * generic save function
     *
     * @param Voipmanager_Controller_Interface $_controller
     * @param integer $_id
     */
    protected function _save(Voipmanager_Controller_Interface $_controller, $_data, $_model)
    {
        $data = Zend_Json::decode($_data);
        
        // unset if empty
        if (empty($data['id'])) {
            unset($data['id']);
        }

        $record = new $_model();
        $record->setFromArray($data);

        if (empty($record->id)) {
            $record = $_controller->create($record);
        } else {
            $record = $_controller->update($record);
        }

        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $this->_get($_controller, $record->getId())
        ); 
        
        return $result;
    }

    /**
     * generic delete function
     *
     * @param Voipmanager_Controller_Interface $_controller
     * @param array $_meetmeIDs list of meetmeId's to delete
     * @return array
     */
    protected function _delete(Voipmanager_Controller_Interface $_controller, $_ids)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $ids = Zend_Json::decode($_ids);
        $_controller->delete($ids);
        return $result;
    }
}