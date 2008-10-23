<?php
/**
 * Tine 2.0
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        replace old controller with controllers from Voipmanager_Controller_*
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
        
        $rows = Voipmanager_Controller::getInstance()->getSnomPhones($filter, $pagination);
        
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
        $result = array(
            'success'   => true
        );

        $phone = Voipmanager_Controller::getInstance()->getSnomPhone($phoneId);
        
        $result = $phone->toArray();    

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
            $phone = Voipmanager_Controller::getInstance()->createSnomPhone($phone, $phoneSettings);
        } else {
            $phone = Voipmanager_Controller::getInstance()->updateSnomPhone($phone, $phoneSettings);
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
        $result = array(
            'success'   => TRUE
        );
        
        $phoneIds = Zend_Json::decode($_phoneIds);
        
        Voipmanager_Controller::getInstance()->deleteSnomPhones($phoneIds);
        
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
        
        Voipmanager_Controller::getInstance()->resetHttpClientInfo($phoneIds);
        
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
     * @param string $query
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
     * @param string $query
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
     * @param string $query
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