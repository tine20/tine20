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
}
