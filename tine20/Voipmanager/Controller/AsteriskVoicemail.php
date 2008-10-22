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
class Voipmanager_Controller extends Tinebase_Application_Controller_Abstract
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
   
   
   
/********************************
 * ASTERISK MEETME FUNCTIONS
 *
 * 
 */

    
    /**
     * get asterisk_meetme by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskMeetme
     */
    public function getAsteriskMeetme($_id)
    {
        $meetme = $this->_asteriskMeetmeBackend->get($_id);
        
        return $meetme;    
    }


    /**
     * get asterisk_meetmes
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskMeetme
     */
    public function getAsteriskMeetmes($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $filter = new Voipmanager_Model_AsteriskMeetmeFilter(array(
            'query' => $_query
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'  => $_sort,
            'dir'   => $_dir
        ));

        $result = $this->_asteriskMeetmeBackend->search($filter, $pagination);
        
        return $result;    
    }


    /**
     * add one meetme
     *
     * @param Voipmanager_Model_AsteriskMeetme $_meetme
     * @return  Voipmanager_Model_AsteriskMeetme
     */
    public function createAsteriskMeetme(Voipmanager_Model_AsteriskMeetme $_meetme)
    {        
        $meetme = $this->_asteriskMeetmeBackend->create($_meetme);
      
        return $meetme;
    }
    

    /**
     * update one meetme
     *
     * @param Voipmanager_Model_AsteriskMeetme $_meetme
     * @return  Voipmanager_Model_AsteriskMeetme
     */
    public function updateAsteriskMeetme(Voipmanager_Model_AsteriskMeetme $_meetme)
    {
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->container_id, Tinebase_Model_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->container_id . ' denied');
        }
        */
        $meetme = $this->_asteriskMeetmeBackend->update($_meetme);
        
        return $this->getAsteriskMeetme($meetme);
    }    
    
  
    /**
     * Deletes a set of meetmes.
     * 
     * If one of the meetmes could not be deleted, no meetme is deleted
     * 
     * @throws Exception
     * @param array array of meetme identifiers
     * @return void
     */
    public function deleteAsteriskMeetmes($_identifiers)
    {
        $this->_asteriskMeetmeBackend->delete($_identifiers);
    }    
    
    
     
   
    
}