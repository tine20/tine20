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
 * @todo        remove asterisk sippeer
 * @todo        remove asterisk voicemail
 * @todo        remove snom config
 * @todo        remove snom location
 * @todo        remove snom phone
 * @todo        remove snom setting
 * @todo        remove snom software
 * @todo        remove snom template
 * @todo        remove myphone
 * @todo        replace by Voipmanager_Controller_*
 * @deprecated 
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
     * the snom phone settings sql backend
     *
     * @var Voipmanager_Backend_Snom_PhoneSettings
     */	
    protected $_snomPhoneSettingsBackend;
    
    /**
     * the central caching object
     *
     * @var Zend_Cache_Core
     */
    protected $_cache;
    
    const PDO_MYSQL = 'Pdo_Mysql';
    
    const PDO_OCI = 'Pdo_Oci';
    
    /**
     * the database backend for the backend classes
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_dbBackend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        if(isset(Zend_Registry::get('configFile')->voipmanager) && isset(Zend_Registry::get('configFile')->voipmanager->database)) {
            $this->_dbBbackend = $this->_getDatabaseBackend(Zend_Registry::get('configFile')->voipmanager->database);
        } else {
            $this->_dbBbackend = Zend_Registry::get('dbAdapter');
        }
        
        $this->_snomPhoneBackend            = new Voipmanager_Backend_Snom_Phone($this->_dbBbackend);
        $this->_snomPhoneSettingsBackend    = new Voipmanager_Backend_Snom_PhoneSettings($this->_dbBbackend);        
        $this->_snomLineBackend             = new Voipmanager_Backend_Snom_Line($this->_dbBbackend);
        $this->_snomSoftwareBackend         = new Voipmanager_Backend_Snom_Software($this->_dbBbackend);
        $this->_snomLocationBackend         = new Voipmanager_Backend_Snom_Location($this->_dbBbackend);
        $this->_snomTemplateBackend         = new Voipmanager_Backend_Snom_Template($this->_dbBbackend);      
        $this->_snomSettingBackend          = new Voipmanager_Backend_Snom_Setting($this->_dbBbackend);              
        $this->_asteriskSipPeerBackend      = new Voipmanager_Backend_Asterisk_SipPeer($this->_dbBbackend);          
        $this->_asteriskVoicemailBackend    = new Voipmanager_Backend_Asterisk_Voicemail($this->_dbBbackend);  

		$this->_cache = Zend_Registry::get('cache');
    }
    
    /**
     * return instance of the current database backend
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDBInstance()
    {
        return $this->_dbBbackend;
    }
    
    /**
     * initialize the optional database backend
     *
     * @param unknown_type $_dbConfig
     * @return Zend_Db_Adapter_Abstract
     */
    protected function _getDatabaseBackend($_dbConfig) 
    {
        $dbBackend = constant('self::' . strtoupper($_dbConfig->get('backend', self::PDO_MYSQL)));
        
        switch($dbBackend) {
            case self::PDO_MYSQL:
                $db = Zend_Db::factory('Pdo_Mysql', $_dbConfig->toArray());
                break;
            case self::PDO_OCI:
                $db = Zend_Db::factory('Pdo_Oci', $_dbConfig->toArray());
                break;
            default:
                throw new Exception('Invalid database backend type defined. Please set backend to ' . self::PDO_MYSQL . ' or ' . self::PDO_OCI . ' in config.ini.');
                break;
        }
        
        return $db;
    }
    
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



/**********************************************
 * SNOM PHONE / SNOM PHONESETTINGS FUNCTIONS
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
        $phone->lines  = $this->_snomLineBackend->search($filter);
        $phone->rights = $this->_snomPhoneBackend->getPhoneRights($phone->id);
        
        // add accountDisplayName
        foreach ($phone->rights as &$right) {
            $user = Tinebase_User::getInstance()->getUserById($right->account_id);
            $right->accountDisplayName = $user->accountDisplayName;
        }
        
        return $phone;    
    }
    
    /**
     * get snom_phone_line by id
     *
     * @param string $_id the id of the line
     * @return Voipmanager_Model_SnomLine
     */
    public function getSnomPhoneLine($_id)
    {
        $id = Voipmanager_Model_SnomLine::convertSnomLineIdToInt($_id);
        if (($result = $this->_cache->load('snomPhoneLine_' . $id)) === false) {
            $result = $this->_snomLineBackend->get($id);
            $this->_cache->save($result, 'snomPhoneLine_' . $id, array('SnomPhoneLine'), 5);
        }
        
        return $result;    
    }
    
    /**
     * get snom_phone by macAddress
     *
     * @param string $_macAddress
     * @return Voipmanager_Model_SnomPhone
     */
    public function getSnomPhoneByMacAddress($_macAddress)
    {
        $phone = $this->_snomPhoneBackend->getByMacAddress($_macAddress);
        
        $filter = new Voipmanager_Model_SnomLineFilter(array(
            'snomphone_id'  => $phone->id
        ));
        $phone->lines  = $this->_snomLineBackend->search($filter);
        $phone->rights = $this->_snomPhoneBackend->getPhoneRights($phone->id);
        
        // add accountDisplayName
        foreach ($phone->rights as &$right) {
            $user = Tinebase_User::getInstance()->getUserById($right->account_id);
            $right->accountDisplayName = $user->accountDisplayName;
        }
        
        return $phone;    
    }
        
    
    /**
     * get snom_phones
     *
     * @param Voipmanager_Model_SnomPhoneFilter $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomPhone
     */
    public function getSnomPhones(Voipmanager_Model_SnomPhoneFilter $_filter, $_pagination = NULL)
    {
        $result = $this->_snomPhoneBackend->search($_filter, $_pagination);
        
        return $result;    
    }


    /**
     * get My Phones
     *
     * @param string $_sort
     * @param string $_dir
     * @param string $_query
     * @param string $_accountId
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomPhone
     */
    public function getMyPhones($_sort, $_dir, $_query, $_accountId)
    {       
        if(empty($_accountId)) 
        {
            throw new UnderflowException('no accountId set');
        }    
        
        $filter = new Voipmanager_Model_SnomPhoneFilter(array(
            'query' => $_query,
            'accountId' => $_accountId
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'  => $_sort,
            'dir'   => $_dir
        ));

        $result = $this->_snomPhoneBackend->search($filter, $pagination);
    
        return $result;
    }


    /**
     * get myPhone by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomPhone
     */
    public function getMyPhone($_id, $_accountId)
    {
        if(empty($_accountId)) 
        {
            throw new UnderflowException('no accountId set');
        }   
        
        
        $phone = $this->_snomPhoneBackend->getMyPhone($_id, $_accountId);
        
        $filter = new Voipmanager_Model_SnomLineFilter(array(
            'snomphone_id'  => $phone->id
        ));
        $phone->lines = $this->_snomLineBackend->search($filter);

        return $phone;    
    }


    /**
     * add one phone
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @return  Voipmanager_Model_SnomPhone
     */
    public function createSnomPhone(Voipmanager_Model_SnomPhone $_phone, Voipmanager_Model_SnomPhoneSettings $_phoneSettings)
    {
        // auto generate random http client username and password        
        // limit length because of Snom phone limitations
        $_phone->http_client_user = Tinebase_Record_Abstract::generateUID(30);
        $_phone->http_client_pass = Tinebase_Record_Abstract::generateUID(20);
        $_phone->http_client_info_sent = false;
        
        unset($_phone->settings_loaded_at);
        unset($_phone->firmware_checked_at);
        unset($_phone->last_modified_time);
        unset($_phone->ipaddress);
        unset($_phone->current_software);
        
        $phone = $this->_snomPhoneBackend->create($_phone);
        
        // force the right phone_id
        $_phoneSettings->setId($phone->getId());

        // set all settings which are equal to the default settings to NULL
        $template = $this->getSnomTemplate($phone->template_id);
        $settingDefaults = $this->getSnomSetting($template->setting_id);

        foreach($_phoneSettings AS $key => $value) {
            if($key == 'phone_id') {
                continue;
            }
            if($_phoneSettings->$key == $settingDefaults->$key) {
                $_phoneSettings->$key = NULL;
            }    
        }
                
        $phoneSettings = $this->_snomPhoneSettingsBackend->create($_phoneSettings);
        
        foreach($_phone->lines as $line) {
            $line->snomphone_id = $phone->getId();
            $addedLine = $this->_snomLineBackend->create($line);
        }
        
        // save phone rights
        if (isset($phone->rights)) {
            $this->_snomPhoneBackend->setPhoneRights($phone);
        }        
      
        return $this->getSnomPhone($phone);
    }
    
    /**
     * set redirect settings only
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     */
    public function updateSnomPhoneRedirect(Voipmanager_Model_SnomPhone $_phone)
    {
        $this->_snomPhoneBackend->updateRedirect($_phone);
    }
    
    /**
     * update one phone
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @param Voipmanager_Model_SnomPhoneSettings|optional $_phoneSettings
     * @return  Voipmanager_Model_SnomPhone
     */
    public function updateSnomPhone(Voipmanager_Model_SnomPhone $_phone, $_phoneSettings = NULL)
    {
        unset($_phone->settings_loaded_at);
        unset($_phone->firmware_checked_at);
        unset($_phone->last_modified_time);
        unset($_phone->ipaddress);
        unset($_phone->current_software);
        
        $phone = $this->_snomPhoneBackend->update($_phone);
        
        if($_phoneSettings instanceof Voipmanager_Model_SnomPhoneSettings) {
        
            // force the right phone_id
            $_phoneSettings->setId($phone->getId());
    
            // set all settings which are equal to the default settings to NULL
            $template = $this->getSnomTemplate($phone->template_id);
            $settingDefaults = $this->getSnomSetting($template->setting_id);
    
            foreach($_phoneSettings AS $key => $value) {
                if($key == 'phone_id') {
                    continue;
                }
                if($_phoneSettings->$key == $settingDefaults->$key) {
                    $_phoneSettings->$key = NULL;
                }    
            }
            
            if($this->_snomPhoneSettingsBackend->get($phone->getId())) {
                $phoneSettings = $this->_snomPhoneSettingsBackend->update($_phoneSettings);
            } else {
                $phoneSettings = $this->_snomPhoneSettingsBackend->create($_phoneSettings);            
            }
        
        }
        
        $this->_snomLineBackend->deletePhoneLines($phone->getId());
        
        foreach($_phone->lines as $line) {
            $line->snomphone_id = $phone->getId();
            //error_log(print_r($line->toArray(), true));
            $addedLine = $this->_snomLineBackend->create($line);
        }
        
        // save phone rights
        if (isset($_phone->rights)) {
            $this->_snomPhoneBackend->setPhoneRights($_phone);
        }
              
        return $this->getSnomPhone($phone);
    }    
    
    
   /**
     * update one myPhone
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @return  Voipmanager_Model_SnomPhone
     */
    public function updateMyPhone(Voipmanager_Model_MyPhone $_phone, Voipmanager_Model_SnomPhoneSettings $_phoneSettings, $_accountId)
    {
       
        if(empty($_accountId)) 
        {
            throw new UnderflowException('no accountId set');
        }        
       
        $phone = $this->_snomPhoneBackend->updateMyPhone($_phone, $_accountId);
        
        // force the right phone_id
        $_phoneSettings->setId($phone->getId());

        // set all settings which are equal to the default settings to NULL
        $template = $this->getSnomTemplate($phone->template_id);
        $settingDefaults = $this->getSnomSetting($template->setting_id);

        foreach($_phoneSettings AS $key => $value) {
            if($key == 'phone_id') {
                continue;
            }
            if($_phoneSettings->$key == $settingDefaults->$key) {
                $_phoneSettings->$key = NULL;
            }    
        }
        
        if($this->_snomPhoneSettingsBackend->get($phone->getId())) {
            $phoneSettings = $this->_snomPhoneSettingsBackend->update($_phoneSettings);
        } else {
            $phoneSettings = $this->_snomPhoneSettingsBackend->create($_phoneSettings);            
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


    /**
     * send http client info to a set of phones.
     * 
     * @param array array of phone identifiers
     * @return void
     */
    public function resetHttpClientInfo($_identifiers)
    {
        if(!is_array($_identifiers) || !is_object($_identifiers)) {
            $_identifiers = (array)$_identifiers;
        }
        foreach ($_identifiers as $id) {
            $phone = $this->getSnomPhone($id);
            $phone->http_client_user = Tinebase_Record_Abstract::generateUID(30);
            $phone->http_client_pass = Tinebase_Record_Abstract::generateUID(20);
            $phone->http_client_info_sent = false;
            
            $phone = $this->_snomPhoneBackend->update($phone);
        }
    }

    /**
     * get snom_phoneSettings by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomPhoneSettings
     */
    public function getSnomPhoneSettings($_id)
    {
        $phoneSettings = $this->_snomPhoneSettingsBackend->get($_id);
        return $phoneSettings;    
    }


    /**
     * add one phoneSetting
     *
     * @param Voipmanager_Model_SnomPhoneSettings $_phone
     * @return  Voipmanager_Model_SnomPhoneSettings
     */
    public function createSnomPhoneSettings(Voipmanager_Model_SnomPhoneSettings $_phoneSettings)
    {       
        $phoneSettings = $this->_snomPhoneSettingsBackend->create($_phoneSettings);
        return $this->getSnomPhoneSettings($phoneSettings);
    }
    

    /**
     * update one phoneSettings
     *
     * @param Voipmanager_Model_SnomPhoneSettings $_phoneSettings
     * @return  Voipmanager_Model_SnomPhoneSettings
     */
    public function updateSnomPhoneSettings(Voipmanager_Model_SnomPhoneSettings $_phoneSettings)
    {
        $phoneSettings = $this->_snomPhoneSettingsBackend->update($_phoneSettings);
        return $this->getSnomPhoneSettings($phoneSettings);
    }    
    
    
    /**
     * Deletes phoneSettings.
     * 
     * 
     * 
     * @throws Exception
     * @param array array of phone identifiers
     * @return void
     */
    public function deleteSnomPhoneSettings($_identifiers)
    {
        $this->_snomPhoneSettingsBackend->delete($_identifiers);
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
     * @param string|Voipmanager_Model_SnomLocation $_id
     * @return Voipmanager_Model_Location
     */
    public function getSnomLocation($_id)
    {
        $id = Voipmanager_Model_SnomLocation::convertSnomLocationIdToInt($_id);
        if (($result = $this->_cache->load('snomLocation_' . $id)) === false) {
            $result = $this->_snomLocationBackend->get($id);
            $this->_cache->save($result, 'snomLocation_' . $id, array('SnomLocation'), 5);
        }
        
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
        if (!Zend_Registry::get('currentAccount')->hasGrant($_location->owner, Tinebase_Model_Container::GRANT_ADD)) {
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
        if (!Zend_Registry::get('currentAccount')->hasGrant($_location->owner, Tinebase_Model_Container::GRANT_EDIT)) {
            throw new Exception('edit access to location in container ' . $_location->owner . ' denied');
        }
        */
       
        $location = $this->_snomLocationBackend->update($_location);
        
        $this->_cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('SnomLocation'));
        
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
            if (!$this->_currentAccount->hasGrant($Location->container_id, Tinebase_Model_Container::GRANT_DELETE)) {
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
     * @param string|Voipmanager_Model_SnomTemplate $_id
     * @return Voipmanager_Model_SnomTemplate
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
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Model_Container::GRANT_ADD)) {
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
        $id = Voipmanager_Model_AsteriskSipPeer::convertAsteriskSipPeerIdToInt($_id);
        if (($result = $this->_cache->load('asteriskSipPeer_' . $id)) === false) {
            $result = $this->_asteriskSipPeerBackend->get($id);
            $this->_cache->save($result, 'asteriskSipPeer_' . $id, array('asteriskSipPeer'), 5);
        }
        
        return $result;    
    }
   
    /**
     * get list of asterisk sip peers
     *
     * @param Voipmanager_Model_AsteriskSipPeerFilter|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskSipPeer
     */
    public function searchAsteriskSipPeers($_filter = NULL, $_pagination = NULL)
    {
        $result = $this->_asteriskSipPeerBackend->search($_filter, $_pagination);
        
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
    public function getAsteriskVoicemails($_sort = 'id', $_dir = 'ASC', $_query = NULL, $_context = NULL)
    {
        $filter = new Voipmanager_Model_AsteriskVoicemailFilter(array(
            'query' => $_query
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'  => $_sort,
            'dir'   => $_dir
        ));

        $result = $this->_asteriskVoicemailBackend->search($filter, $pagination, $_context);
        
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
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->container_id, Tinebase_Model_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->container_id . ' denied');
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
