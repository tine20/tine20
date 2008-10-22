<?php
/**
 * controller for Voipmanager Management application
 * 
 * the main logic of the Voipmanager Management application
 *
 * @package     Voipmanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * controller class for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 */
class Voipmanager_Controller_SnomPhone extends Tinebase_Application_Controller_Abstract
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
     * the asterisk meetme sql backend
     *
     * @var Voipmanager_Backend_Asterisk_Meetme
     */
    protected $_asteriskMeetmeBackend;	
	
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
        $this->_asteriskContextBackend      = new Voipmanager_Backend_Asterisk_Context($this->_dbBbackend);          
        $this->_asteriskVoicemailBackend    = new Voipmanager_Backend_Asterisk_Voicemail($this->_dbBbackend);  
		$this->_asteriskMeetmeBackend		= new Voipmanager_Backend_Asterisk_Meetme($this->_dbBbackend);

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


}
