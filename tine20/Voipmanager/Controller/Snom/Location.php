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
   
}
