<?php
/**
 * Tine 2.0
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the Voipmanager Management application
 *
 * @package     Voipmanager Management
 */
class Voipmanager_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Voipmanager';
    
    /**
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        $recordArray = parent::_recordToJson($_record);
        
        switch (get_class($_record)) {
            case 'Voipmanager_Model_Snom_Phone':
                // add settings
                $recordArray = array_merge($recordArray, $this->getSnomPhoneSettings($_record->getId()));
                
                // add snom templates (no filter + no pagination)
                $recordArray['template_id'] = array(
                    'value'     => $_record->template_id,
                    'records'   => $this->searchSnomTemplates('', '')
                );

                // add snom locations (no filter + no pagination)
                $recordArray['location_id'] = array(
                    'value'     => $_record->location_id,
                    'records'   => $this->searchSnomLocations('', '')
                );
                
                // add names to lines
                foreach ($recordArray['lines'] as &$line) {
                    $line['name'] = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->get($line['asteriskline_id'])->name;
                }
                
                break;

            case 'Voipmanager_Model_Snom_Template':
                // add snom softwares (no filter + no pagination)
                $recordArray['software_id'] = array(
                    'value'     => $recordArray['software_id'],
                    'records'   => $this->searchSnomSoftwares('', '')
                );

                // add snom settings (no filter + no pagination)
                $recordArray['setting_id'] = array(
                    'value'     => $recordArray['setting_id'],
                    'records'   => $this->searchSnomSettings('', '')
                );
                break;
                
            case 'Voipmanager_Model_Snom_Phone':
                // add settings
                $recordArray = array_merge($recordArray, $this->getSnomPhoneSettings($recordArray['id']));
                
                // resolve snom template_id
                $recordArray['template_id'] = array(
                    'value'     => $recordArray['template_id'],
                    'records'   => $this->searchSnomTemplates('', '')
                );

                // resolve snom location_id
                $recordArray['location_id'] = array(
                    'value'     => $recordArray['location_id'],
                    'records'   => $this->searchSnomLocations('', '')
                );
                
                // add names to lines
                foreach ($recordArray['lines'] as &$line) {
                    $line['name'] = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->get($line['asteriskline_id'])->name;
                }
                
                break;

            case 'Voipmanager_Model_Asterisk_SipPeer':
            case 'Voipmanager_Model_Asterisk_Voicemail':
                // resolve context_id
                $recordArray['context_id'] = array(
                    'value'     => $recordArray['context_id'],
                    'records'   => $this->searchAsteriskContexts('', '')
                );
                break;
        }
        
        return $recordArray;
    }

    
/****************************************
 * SNOM PHONE / PHONESETTINGS FUNCTIONS
 *
 * 
 * 
 */
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchSnomPhones($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Voipmanager_Controller_Snom_Phone::getInstance(), 'Voipmanager_Model_Snom_PhoneFilter');
        
        foreach ($result['results'] as &$phone) {
            // resolve location and template names

            if($location = Voipmanager_Controller_Snom_Location::getInstance()->get($phone['location_id'])) {
                $phone['location'] = $location->name;
            }
            
            if($template = Voipmanager_Controller_Snom_Template::getInstance()->get($phone['template_id'])) {
                $phone['template'] = $template->name;
            }                            
        }
        
        return $result;
    }
    
    /**
     * get one phone identified by phoneId
     *
     * @param  int $id
     * @return array
     */
    public function getSnomPhone($id)
    {
        return $this->_get($id, Voipmanager_Controller_Snom_Phone::getInstance());
    }    
    
    /**
     * save one phone
     * -  if $recordData['id'] is empty the phone gets added, otherwise it gets updated
     *
     * @param array $recordData an array of phone properties
     * @return array
     */
    public function saveSnomPhone($recordData)
    {
        // unset if empty
        if (empty($recordData['id'])) {
            unset($recordData['id']);
        }

        // unset some (readonly-)fields 
        $unsetFields = array(
            'settings_loaded_at',
            'firmware_checked_at',
            'last_modified_time',
            'ipaddress',
            'current_software',
        ); 
        foreach ($unsetFields as $field) {
            unset($recordData[$field]);
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recordData, true));
        
        $phone = new Voipmanager_Model_Snom_Phone($recordData);
        
        $phoneSettings = new Voipmanager_Model_Snom_PhoneSettings();
        $phoneSettings->setFromArray($recordData);
        
        $phone->lines = new Tinebase_Record_RecordSet(
            'Voipmanager_Model_Snom_Line', 
            (isset($recordData['lines']) && !empty($recordData['lines'])) ? $recordData['lines'] : array(),
            TRUE
        );
        $phone->rights = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_PhoneRight', 
            (isset($recordData['rights']) && !empty($recordData['rights'])) ? $recordData['rights'] : array()
        );
        $phone->settings = $phoneSettings;
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($phoneSettings->toArray(), true));
        
        if (empty($phone->id)) {
            $phone = Voipmanager_Controller_Snom_Phone::getInstance()->create($phone);
        } else {
            $phone = Voipmanager_Controller_Snom_Phone::getInstance()->update($phone);
        }
        $phone = $this->getSnomPhone($phone->getId());

        return $phone;         
    }     
    
    /**
     * delete multiple phones
     *
     * @param  array $ids list of phoneId's to delete
     * @return array
     */
    public function deleteSnomPhones($ids)
    {
        return $this->_delete($ids, Voipmanager_Controller_Snom_Phone::getInstance());
    }    


    /**
     * send HTTP Client Info to multiple phones
     *
     * @param  array $phoneIds list of phoneId's to send http client info to
     * @return array
     */      
    public function resetHttpClientInfo($phoneIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        Voipmanager_Controller_Snom_Phone::getInstance()->resetHttpClientInfo($phoneIds);
        
        return $result;
    }      
      
      
    /**
     * get one phoneSettings identified by phoneSettingsId
     *
     * @param  int   $id
     * @return array
     */
    public function getSnomPhoneSettings($id)
    {
        return $this->_get($id, Voipmanager_Controller_Snom_PhoneSettings::getInstance());
    }              
    
    /**
     * save one phoneSettings
     *
     * if $phoneSettingsData['id'] is empty the phoneSettings gets added, otherwise it gets updated
     *
     * @param  array $recordData an array of phoneSettings properties
     * @return array
     */
    public function saveSnomPhoneSettings($recordData)
    {
        return $this->_save($recordData, Voipmanager_Controller_Snom_PhoneSettings::getInstance(), 'Snom_PhoneSettings', 'phone_id');       
    }

    /**
     * delete phoneSettings
     *
     * @param  array $ids phoneSettingsId to delete
     * @return array
     */
    public function deleteSnomPhoneSettings($ids)
    {
        return $this->_delete($ids, Voipmanager_Controller_Snom_PhoneSettings::getInstance());
    }
        
      
/********************************
 * SNOM LOCATION FUNCTIONS
 *
 * 
 */
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchSnomLocations($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Voipmanager_Controller_Snom_Location::getInstance(), 'Voipmanager_Model_Snom_LocationFilter');
        return $result;
    }
    
    /**
     * get one location identified by locationId
     *
     * @param  int $id
     * @return array
     */
    public function getSnomLocation($id)
    {
        return $this->_get($id, Voipmanager_Controller_Snom_Location::getInstance());
    }      


    /**
     * save one location
     *
     * if $locationData['id'] is empty the location gets added, otherwise it gets updated
     *
     * @param  array $recordData an array of location properties
     * @return array
     */
    public function saveSnomLocation($recordData)
    {
        return $this->_save($recordData, Voipmanager_Controller_Snom_Location::getInstance(), 'Snom_Location');              
    }
     
        
    /**
     * delete multiple locations
     *
     * @param  array $ids list of locationId's to delete
     * @return array
     */
    public function deleteSnomLocations($ids)
    {
        return $this->_delete($ids, Voipmanager_Controller_Snom_Location::getInstance());
    }        
        
      
      
/********************************
 * SNOM SOFTWARE FUNCTIONS
 *
 * 
 */
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchSnomSoftwares($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Voipmanager_Controller_Snom_Software::getInstance(), 'Voipmanager_Model_Snom_SoftwareFilter');
        return $result;
    }
    
   /**
     * get one software identified by softwareId
     *
     * @param  int $id
     * @return array
     */
    public function getSnomSoftware($id)
    {
        return $this->_get($id, Voipmanager_Controller_Snom_Software::getInstance());
    }         


    /**
     * add/update software
     *
     * if $softwareData['id'] is empty the software gets added, otherwise it gets updated
     *
     * @param  array $recordData an array of software properties
     * @return array
     */
    public function saveSnomSoftware($recordData)
    {
        return $this->_save($recordData, Voipmanager_Controller_Snom_Software::getInstance(), 'Snom_Software');
    }     
      
      
    /**
     * delete multiple softwareversion entries
     *
     * @param  array $ids list of softwareId's to delete
     * @return array
     */
    public function deleteSnomSoftwares($ids)
    {
        return $this->_delete($ids, Voipmanager_Controller_Snom_Software::getInstance());
    }
    
    
    
/********************************
 * SNOM TEMPLATE FUNCTIONS
 *
 * 
 */
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchSnomTemplates($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Voipmanager_Controller_Snom_Template::getInstance(), 'Voipmanager_Model_Snom_TemplateFilter');
        return $result;
    }
    
   /**
     * get one template identified by templateId
     *
     * @param  int $id
     * @return array
     */
    public function getSnomTemplate($id)
    {
        return $this->_get($id, Voipmanager_Controller_Snom_Template::getInstance());
    }
    
    /**
     * add/update template
     *
     * if $templateData['id'] is empty the template gets added, otherwise it gets updated
     *
     * @param  array $recordData an array of template properties
     * @return array
     */
    public function saveSnomTemplate($recordData)
    {
        return $this->_save($recordData, Voipmanager_Controller_Snom_Template::getInstance(), 'Snom_Template');               
    }     
    
    /**
     * delete multiple template entries
     *
     * @param  array $ids list of templateId's to delete
     * @return array
     */
    public function deleteSnomTemplates($ids)
    {
        return $this->_delete($ids, Voipmanager_Controller_Snom_Template::getInstance());
    }     

/********************************
 * SNOM SETTING FUNCTIONS
 *
 * 
 */        
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchSnomSettings($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Voipmanager_Controller_Snom_Setting::getInstance(), 'Voipmanager_Model_Snom_SettingFilter');
        return $result;
    }
    
   /**
     * get one setting identified by settingId
     *
     * @param  int $id
     * @return array
     */
    public function getSnomSetting($id)
    {
        return $this->_get($id, Voipmanager_Controller_Snom_Setting::getInstance());
    }    
    
    /**
     * save one setting
     *
     * if $settingData['id'] is empty the setting gets added, otherwise it gets updated
     *
     * @param  array $recordData an array of setting properties
     * @return array
     */
    public function saveSnomSetting($recordData)
    {
        return $this->_save($recordData, Voipmanager_Controller_Snom_Setting::getInstance(), 'Snom_Setting');
    }
    
   
    /**
     * delete multiple settings
     *
     * @param  array $ids list of settingId's to delete
     * @return array
     */
    public function deleteSnomSettings($ids)
    {
        return $this->_delete($ids, Voipmanager_Controller_Snom_Setting::getInstance());
    }         

    
/********************************
 * ASTERISK CONTEXT FUNCTIONS
 *
 * 
 */    

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchAsteriskContexts($filter, $paging)
    {
        return $this->_search($filter, $paging, Voipmanager_Controller_Asterisk_Context::getInstance(), 'Voipmanager_Model_Asterisk_ContextFilter');
    }
    
   /**
     * get one context identified by contextId
     *
     * @param  int $id
     * @return array
     */
    public function getAsteriskContext($id)
    {
        return $this->_get($id, Voipmanager_Controller_Asterisk_Context::getInstance());
    }    
    
    
    /**
     * save one context
     *
     * if $contextData['id'] is empty the context gets added, otherwise it gets updated
     *
     * @param  array $recordData an array of context properties
     * @return array
     */
    public function saveAsteriskContext($recordData)
    {
        return $this->_save($recordData, Voipmanager_Controller_Asterisk_Context::getInstance(), 'Asterisk_Context');      
    }     
    
    
     /**
     * delete multiple contexts
     *
     * @param  array $ids list of contextId's to delete
     * @return array
     */
    public function deleteAsteriskContexts($ids)
    {
        return $this->_delete($ids, Voipmanager_Controller_Asterisk_Context::getInstance());
    }    
       
/********************************
 * ASTERISK MEETME FUNCTIONS
 *
 * 
 */        
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchAsteriskMeetmes($filter, $paging)
    {
        return $this->_search($filter, $paging, Voipmanager_Controller_Asterisk_Meetme::getInstance(), 'Voipmanager_Model_Asterisk_MeetmeFilter');
    }
    
   /**
     * get one meetme identified by meetmeId
     *
     * @param  int $id
     * @return array
     */
    public function getAsteriskMeetme($id)
    {
        return $this->_get($id, Voipmanager_Controller_Asterisk_Meetme::getInstance());
    }    
    
    
    /**
     * save one meetme
     *
     * if $meetmeData['id'] is empty the meetme gets added, otherwise it gets updated
     *
     * @param  array $recordData an array of meetme properties
     * @return array
     */
    public function saveAsteriskMeetme($recordData)
    {
        return $this->_save($recordData, Voipmanager_Controller_Asterisk_Meetme::getInstance(), 'Asterisk_Meetme');
    }     
    
    /**
     * delete multiple meetmes
     *
     * @param  array $ids list of meetmeId's to delete
     * @return array
     */
    public function deleteAsteriskMeetmes($ids)
    {
        return $this->_delete($ids, Voipmanager_Controller_Asterisk_Meetme::getInstance());
    }     
    
/********************************
 * ASTERISK SIP PEER FUNCTIONS
 *
 * 
 */    

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchAsteriskSipPeers($filter, $paging)
    {
        return $this->_search($filter, $paging, Voipmanager_Controller_Asterisk_SipPeer::getInstance(), 'Voipmanager_Model_Asterisk_SipPeerFilter');
    }
    
   /**
     * get one asterisk sip peer identified by sipPeerId
     *
     * @param  int $id
     * @return array
     */
    public function getAsteriskSipPeer($id)
    {
        return $this->_get($id, Voipmanager_Controller_Asterisk_SipPeer::getInstance());       
    }
          
             
    /**
     * add/update asterisk sip peer
     *
     * if $sipPeerData['id'] is empty the sip peer gets added, otherwise it gets updated
     *
     * @param  array $recordData an array of sipPeer properties
     * @return array
     */
    public function saveAsteriskSipPeer($recordData)
    {
        return $this->_save($recordData, Voipmanager_Controller_Asterisk_SipPeer::getInstance(), 'Asterisk_SipPeer');       
    }
    
    /**
     * update multiple records
     *
     * @param  string $id record id
     * @param  array $data key/value pairs to update 
     * @return updated record
     */
    public function updatePropertiesAsteriskSipPeer($id, $data)
    {
        return $this->_updateProperties($id, $data, Voipmanager_Controller_Asterisk_SipPeer::getInstance());
    }
    
    /**
     * delete multiple asterisk sip peers
     *
     * @param  array $ids list of sipPeerId's to delete
     * @return array
     */
    public function deleteAsteriskSipPeers($ids)
    {
        return $this->_delete($ids, Voipmanager_Controller_Asterisk_SipPeer::getInstance());
    }     
    
/********************************
 * ASTERISK VOICEMAIL FUNCTIONS
 *
 * 
 */        
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchAsteriskVoicemails($filter, $paging)
    {
        return $this->_search($filter, $paging, Voipmanager_Controller_Asterisk_Voicemail::getInstance(), 'Voipmanager_Model_Asterisk_VoicemailFilter');
    }
    
   /**
     * get one voicemail identified by voicemailId
     *
     * @param  int $id
     * @return array
     */
    public function getAsteriskVoicemail($id)
    {     
        return $this->_get($id, Voipmanager_Controller_Asterisk_Voicemail::getInstance());
    }        
    
    /**
     * save one voicemail
     *
     * if $voicemailData['id'] is empty the voicemail gets added, otherwise it gets updated
     *
     * @param  array $recordData an array of voicemail properties
     * @return array
     */
    public function saveAsteriskVoicemail($recordData)
    {
        return $this->_save($recordData, Voipmanager_Controller_Asterisk_Voicemail::getInstance(), 'Asterisk_Voicemail');
    }     
    
   
    /**
     * delete multiple voicemails
     *
     * @param  array $ids list of voicemailId's to delete
     * @return array
     */
    public function deleteAsteriskVoicemails($ids)
    {
        return $this->_delete($ids, Voipmanager_Controller_Asterisk_Voicemail::getInstance());
    }
}
