<?php
/**
 * controller for Voipmanager Management application
 * 
 * the main logic of the Voipmanager Management application
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * controller class for Voipmanager Management application
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Controller
{
    /**
     * Voipmanager backend class
     *
     * @var Voipmanager_Backend_Sql
     */
    protected $_backend;
    
    /**
     * the snom phone sql backend
     *
     * @var Voipmanager_Backend_Snom_Phone
     */
    protected $_snomPhoneBackend;
    
    /**
     * the snom phone lines sql backend
     *
     * @var Voipmanager_Backend_Snom_Line
     */
    protected $_snomLineBackend;
    
    /**
     * the snom phone software sql backend
     *
     * @var Voipmanager_Backend_Snom_Software
     */
    protected $_snomSoftwareBackend;
    
    /**
     * the snom phone location sql backend
     *
     * @var Voipmanager_Backend_Snom_Location
     */
    protected $_snomLocationBackend;
    
    /**
     * the snom phone template sql backend
     *
     * @var Voipmanager_Backend_Snom_Template
     */
    protected $_snomTemplateBackend;
    
    /**
     * the asterisk sip peer sql backend
     *
     * @var Voipmanager_Backend_Asterisk_SipPeer
     */
    protected $_asteriskSipPeerBackend;

    /**
     * the asterisk context sql backend
     *
     * @var Voipmanager_Backend_Asterisk_Context
     */
    protected $_asteriskContextBackend;

    /**
     * the asterisk voicemail sql backend
     *
     * @var Voipmanager_Backend_Asterisk_Voicemail
     */
    protected $_asteriskVoicemailBackend;
    
    /**
     * the snom setting sql backend
     *
     * @var Voipmanager_Backend_Snom_Setting
     */
    protected $_snomSettingBackend;    
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_snomPhoneBackend            = new Voipmanager_Backend_Snom_Phone();
        $this->_snomLineBackend             = new Voipmanager_Backend_Snom_Line();
        $this->_snomSoftwareBackend         = new Voipmanager_Backend_Snom_Software();
        $this->_snomLocationBackend         = new Voipmanager_Backend_Snom_Location();
        $this->_snomTemplateBackend         = new Voipmanager_Backend_Snom_Template();      
        $this->_snomSettingBackend          = new Voipmanager_Backend_Snom_Setting();              
        $this->_asteriskSipPeerBackend      = new Voipmanager_Backend_Asterisk_SipPeer();          
        $this->_asteriskContextBackend      = new Voipmanager_Backend_Asterisk_Context();          
        $this->_asteriskVoicemailBackend    = new Voipmanager_Backend_Asterisk_Voicemail();                  
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Voipmanager_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Voipmanager_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Voipmanager_Controller;
        }
        
        return self::$_instance;
    }



/********************************
 * SNOM PHONE FUNCTIONS
 *
 * 
 */


    /**
     * get snom_phone by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomPhone
     */
    public function getSnomPhone($_id)
    {
        $phone = $this->_snomPhoneBackend->get($_id);
        
        $filter = new Voipmanager_Model_SnomLineFilter(array(
            'snomphone_id'  => $phone->id
        ));
        $phone->lines = $this->_snomLineBackend->search($filter);

        return $phone;    
    }


    /**
     * get snom_phones
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomPhone
     */
    public function getSnomPhones($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $filter = new Voipmanager_Model_SnomPhoneFilter(array(
            'query' => $_query
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'  => $_sort,
            'dir'   => $_dir
        ));

        $result = $this->_snomPhoneBackend->search($filter, $pagination);
        
        return $result;    
    }


    /**
     * add one phone
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @return  Voipmanager_Model_SnomPhone
     */
    public function createSnomPhone(Voipmanager_Model_SnomPhone $_phone)
    {        
        $phone = $this->_snomPhoneBackend->create($_phone);
        foreach($_phone->lines as $line) {
            $line->snomphone_id = $phone->getId();
            $addedLine = $this->_snomLineBackend->create($line);
        }
      
        return $this->getSnomPhone($phone);
    }
    

    /**
     * update one phone
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @return  Voipmanager_Model_SnomPhone
     */
    public function updateSnomPhone(Voipmanager_Model_SnomPhone $_phone)
    {
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
        $phone = $this->_snomPhoneBackend->update($_phone);
        
        $this->_snomLineBackend->deletePhoneLines($phone->getId());
        
        foreach($_phone->lines as $line) {
            $line->snomphone_id = $phone->getId();
            error_log(print_r($line->toArray(), true));
            $addedLine = $this->_snomLineBackend->create($line);
        }
      
        return $this->getSnomPhone($phone);
    }    
    
    
    /**
     * Deletes a set of phones.
     * 
     * If one of the phones could not be deleted, no phone is deleted
     * 
     * @throws Exception
     * @param array array of phone identifiers
     * @return void
     */
    public function deleteSnomPhones($_identifiers)
    {
        $this->_snomPhoneBackend->delete($_identifiers);
    }



/********************************
 * SNOM LOCATION FUNCTIONS
 *
 * 
 */


   /**
     * get snom_location
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Location
     */
    public function getSnomLocations($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {        
        $filter = new Voipmanager_Model_SnomLocationFilter(array(
            'query' => $_query
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'  => $_sort,
            'dir'   => $_dir
        ));

        $result = $this->_snomLocationBackend->search($filter, $pagination);    
    
        return $result;    
    }
    
    
    /**
     * get snom_location by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Location
     */
    public function getSnomLocation($_id)
    {
        $result = $this->_snomLocationBackend->get($_id);

        return $result;    
    }    
    
    
    /**
     * add one location
     *
     * @param Voipmanager_Model_Location $_location
     * @return  Voipmanager_Model_Location
     */
    public function createSnomLocation(Voipmanager_Model_SnomLocation $_location)
    {        
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_location->owner, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to location in container ' . $_location->owner . ' denied');
        }
        */
        $location = $this->_snomLocationBackend->create($_location);
      
        return $location;
    }    
    
    
    /**
     * update one location
     *
     * @param Voipmanager_Model_Location $_location
     * @return  Voipmanager_Model_Location
     */
    public function updateSnomLocation(Voipmanager_Model_SnomLocation $_location)
    {
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_location->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to location in container ' . $_location->owner . ' denied');
        }
        */
       
        $location = $this->_snomLocationBackend->update($_location);
        
        return $location;
    }
      
      
    /**
     * Deletes a set of locations.
     * 
     * If one of the locations could not be deleted, no location is deleted
     * 
     * @throws Exception
     * @param array array of location identifiers
     * @return void
     */
    public function deleteSnomLocations($_identifiers)
    {
      /*  foreach ($_identifiers as $identifier) {
            $Config = $this->getLocationById($identifier);
            if (!$this->_currentAccount->hasGrant($Location->container_id, Tinebase_Container::GRANT_DELETE)) {
                throw new Exception('Not allowed!');
            }
        }
       */
      
        $this->_snomLocationBackend->delete($_identifiers);
    }



/********************************
 * SNOM TEMPLATE FUNCTIONS
 *
 * 
 */

    
    /**
     * get snom_template by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Template
     */
    public function getSnomTemplate($_id)
    {
        $result = $this->_snomTemplateBackend->get($_id);

        return $result;  
    }


    /**
     * get snom_templates
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Template
     */
    public function getSnomTemplates($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $filter = new Voipmanager_Model_SnomTemplateFilter(array(
            'query' => $_query
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'  => $_sort,
            'dir'   => $_dir
        ));

        $result = $this->_snomTemplateBackend->search($filter, $pagination);        

        return $result;    
    }
    
    
    /**
     * add new template
     *
     * @param Voipmanager_Model_Template $_template
     * @return  Voipmanager_Model_Template
     */
    public function createSnomTemplate(Voipmanager_Model_SnomTemplate $_template)
    {        
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
        $template = $this->_snomTemplateBackend->create($_template);
      
        return $template;
    }
    
    
    /**
     * update existing template
     *
     * @param Voipmanager_Model_Template $_template
     * @return  Voipmanager_Model_Template
     */
    public function updateSnomTemplate(Voipmanager_Model_SnomTemplate $_template)
    {
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
       
        $template = $this->_snomTemplateBackend->update($_template);
        
        return $template;
    }   


   /**
     * Deletes a set of templates.
     * 
     * If one of the templates could not be deleted, no template will be deleted
     * 
     * @throws Exception
     * @param array array of template identifiers
     * @return void
     */
    public function deleteSnomTemplates($_identifiers)
    {
      
        $this->_snomTemplateBackend->delete($_identifiers);
    }
    


/********************************
 * SNOM SOFTWARE FUNCTIONS
 *
 * 
 */
   
    
    /**
     * get snom_software
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomSoftware
     */
    public function searchSnomSoftware($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {        
        $filter = new Voipmanager_Model_SnomSoftwareFilter(array(
            'query' => $_query
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'  => $_sort,
            'dir'   => $_dir
        ));

        $result = $this->_snomSoftwareBackend->search($filter, $pagination);
        
        return $result;    
    }  
    
    
    /**
     * get snom_software by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomSoftware
     */
    public function getSnomSoftware($_id)
    {
        $result = $this->_snomSoftwareBackend->get($_id);

        return $result;    
    }    


    /**
     * add new software
     *
     * @param Voipmanager_Model_Software $_software
     * @return  Voipmanager_Model_Software
     */
    public function createSnomSoftware(Voipmanager_Model_SnomSoftware $_software)
    {        
        unset($_software->current_software);
        unset($_software->settings_loaded_at);
        unset($_software->firmware_checked_at);
        unset($_software->ipaddress);
        
        $software = $this->_snomSoftwareBackend->create($_software);
      
        return $software;
    }
    
    
    /**
     * update existing software
     *
     * @param Voipmanager_Model_SnomSoftware $_software
     * @return  Voipmanager_Model_SnomSoftware
     */
    public function updateSnomSoftware(Voipmanager_Model_SnomSoftware $_software)
    {
        unset($_software->current_software);
        unset($_software->settings_loaded_at);
        unset($_software->firmware_checked_at);
        unset($_software->ipaddress);
        
        $software = $this->_snomSoftwareBackend->update($_software);
        
        return $software;
    }    
    
    
    /**
     * Deletes a set of softwareversion entriews.
     * 
     * If one of the software entries could not be deleted, no software is deleted
     * 
     * @throws Exception
     * @param string|array|Tinebase_Record_RecordSet $_identifiers list of software identifiers
     * @return void
     */
    public function deleteSnomSoftware($_identifiers)
    {
        $this->_snomSoftwareBackend->delete($_identifiers);
    }    
    

    
/********************************
 * ASTERISK SIP PEER FUNCTIONS
 *
 * 
 */

    
    /**
     * get asterisk sip peer by id
     *
     * @param string $_id the id of the peer
     * @return Voipmanager_Model_AsteriskSipPeer
     */
    public function getAsteriskSipPeer($_id)
    {
        $result = $this->_asteriskSipPeerBackend->get($_id);

        return $result;    
    }


    /**
     * get list of asterisk sip peers
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskSipPeer
     */
    public function searchAsteriskSipPeers($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $result = $this->_asteriskSipPeerBackend->search($_sort, $_dir, $_query);

        return $result;    
    }
   
   
    /**
     * add new asterisk sip peer
     *
     * @param Voipmanager_Model_AsteriskSipPeer $_sipPeer
     * @return  Voipmanager_Model_AsteriskSipPeer
     */
    public function createAsteriskSipPeer(Voipmanager_Model_AsteriskSipPeer $_sipPeer)
    {        
        $sipPeer = $this->_asteriskSipPeerBackend->create($_sipPeer);
      
        return $sipPeer;
    }
    
    
    /**
     * update existing asterisk sip peer
     *
     * @param Voipmanager_Model_AsteriskSipPeer $_sipPeer
     * @return  Voipmanager_Model_AsteriskSipPeer
     */
    public function updateAsteriskSipPeer(Voipmanager_Model_AsteriskSipPeer $_sipPeer)
    {
        $sipPeer = $this->_asteriskSipPeerBackend->update($_sipPeer);
        
        return $sipPeer;
    }       
    
    
    /**
     * Deletes a set of asterisk sip peers.
     * 
     * If one of the asterisk sip peer could not be deleted, no asterisk sip peer is deleted
     * 
     * @throws Exception
     * @param array array of asterisk sip peer identifiers
     * @return void
     */
    public function deleteAsteriskSipPeers($_identifiers)
    {
        $this->_asteriskSipPeerBackend->delete($_identifiers);
    }     
    
    
    
/********************************
 * SNOM XML CONFIG FUNCTIONS
 *
 * 
 */    
 
 
    /**
     * get xml configurationfile for snom phones
     *
     * @param string $_macAddress the mac address of the phone
     * @return string the xml formated configuration file
     */
    public function getSnomConfig($_macAddress)
    {
        $xmlBackend = new Voipmanager_Backend_Snom_Xml();
        
        $xml = $xmlBackend->getConfig($_macAddress);
        
        return $xml;
    }
    
    
    
/********************************
 * ASTERISK CONTEXT FUNCTIONS
 *
 * 
 */

    
    /**
     * get asterisk_context by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskContext
     */
    public function getAsteriskContext($_id)
    {
        $context = $this->_asteriskContextBackend->get($_id);
        
        return $context;    
    }


    /**
     * get asterisk_contexts
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskContext
     */
    public function getAsteriskContexts($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $filter = new Voipmanager_Model_AsteriskContextFilter(array(
            'query' => $_query
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'  => $_sort,
            'dir'   => $_dir
        ));

        $result = $this->_asteriskContextBackend->search($filter, $pagination);
        
        return $result;    
    }


    /**
     * add one context
     *
     * @param Voipmanager_Model_AsteriskContext $_context
     * @return  Voipmanager_Model_AsteriskContext
     */
    public function createAsteriskContext(Voipmanager_Model_AsteriskContext $_context)
    {        
        $context = $this->_asteriskContextBackend->create($_context);
      
        return $this->getAsteriskContext($context);
    }
    

    /**
     * update one context
     *
     * @param Voipmanager_Model_AsteriskContext $_context
     * @return  Voipmanager_Model_AsteriskContext
     */
    public function updateAsteriskContext(Voipmanager_Model_AsteriskContext $_context)
    {
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
        $context = $this->_asteriskContextBackend->update($_context);
        
        return $this->getAsteriskContext($context);
    }    
    
  
    /**
     * Deletes a set of contexts.
     * 
     * If one of the contexts could not be deleted, no context is deleted
     * 
     * @throws Exception
     * @param array array of context identifiers
     * @return void
     */
    public function deleteAsteriskContexts($_identifiers)
    {
        $this->_asteriskContextBackend->delete($_identifiers);
    }
    
    
    
/********************************
 * ASTERISK VOICEMAIL FUNCTIONS
 *
 * 
 */

    
    /**
     * get asterisk_voicemail by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskVoicemail
     */
    public function getAsteriskVoicemail($_id)
    {
        $voicemail = $this->_asteriskVoicemailBackend->get($_id);
        
        return $voicemail;    
    }


    /**
     * get asterisk_voicemails
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskVoicemail
     */
    public function getAsteriskVoicemails($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $filter = new Voipmanager_Model_AsteriskVoicemailFilter(array(
            'query' => $_query
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'  => $_sort,
            'dir'   => $_dir
        ));

        $result = $this->_asteriskVoicemailBackend->search($filter, $pagination);
        
        return $result;    
    }


    /**
     * add one voicemail
     *
     * @param Voipmanager_Model_AsteriskVoicemail $_voicemail
     * @return  Voipmanager_Model_AsteriskVoicemail
     */
    public function createAsteriskVoicemail(Voipmanager_Model_AsteriskVoicemail $_voicemail)
    {        
        $voicemail = $this->_asteriskVoicemailBackend->create($_voicemail);
      
        return $this->getAsteriskVoicemail($voicemail);
    }
    

    /**
     * update one voicemail
     *
     * @param Voipmanager_Model_AsteriskVoicemail $_voicemail
     * @return  Voipmanager_Model_AsteriskVoicemail
     */
    public function updateAsteriskVoicemail(Voipmanager_Model_AsteriskVoicemail $_voicemail)
    {
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
        $voicemail = $this->_asteriskVoicemailBackend->update($_voicemail);
        
        return $this->getAsteriskVoicemail($voicemail);
    }    
    
  
    /**
     * Deletes a set of voicemails.
     * 
     * If one of the voicemails could not be deleted, no voicemail is deleted
     * 
     * @throws Exception
     * @param array array of voicemail identifiers
     * @return void
     */
    public function deleteAsteriskVoicemails($_identifiers)
    {
        $this->_asteriskVoicemailBackend->delete($_identifiers);
    }    
    
    
    
    
/********************************
 * SNOM SETTINGS FUNCTIONS
 *
 * 
 */

    
    /**
     * get snom_setting by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomSetting
     */
    public function getSnomSetting($_id)
    {
        $setting = $this->_snomSettingBackend->get($_id);
        
        return $setting;    
    }


    /**
     * get snom settings
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomSetting
     */
    public function getSnomSettings($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $filter = new Voipmanager_Model_SnomSettingFilter(array(
            'query' => $_query
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'  => $_sort,
            'dir'   => $_dir
        ));

        $result = $this->_snomSettingBackend->search($filter, $pagination);
        
        return $result;    
    }


    /**
     * add one setting
     *
     * @param Voipmanager_Model_SnomSetting $_voicemail
     * @return  Voipmanager_Model_SnomSetting
     */
    public function createSnomSetting(Voipmanager_Model_SnomSetting $_setting)
    {        
        $setting = $this->_snomSettingBackend->create($_setting);
      
        return $this->getSnomSetting($setting);
    }
    

    /**
     * update one setting
     *
     * @param Voipmanager_Model_SnomSetting $_setting
     * @return  Voipmanager_Model_SnomSetting
     */
    public function updateSnomSetting(Voipmanager_Model_SnomSetting $_setting)
    {
        $setting = $this->_snomSettingBackend->update($_setting);
        return $this->getSnomSetting($setting);
    }    
    
  
    /**
     * Deletes a set of settings.
     * 
     * If one of the settings could not be deleted, no setting is deleted
     * 
     * @throws Exception
     * @param array array of setting identifiers
     * @return void
     */
    public function deleteSnomSettings($_identifiers)
    {
        $this->_snomSettingBackend->delete($_identifiers);
    }     
    
}