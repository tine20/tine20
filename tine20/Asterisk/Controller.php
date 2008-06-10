<?php
/**
 * controller for Asterisk Management application
 * 
 * the main logic of the Asterisk Management application
 *
 * @package     Asterisk Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
 *
 */

/**
 * controller class for Asterisk Management application
 * 
 * @package     Asterisk Management
 */
class Asterisk_Controller
{
    /**
     * Asterisk backend class
     *
     * @var Asterisk_Backend_Sql
     */
    protected $_backend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend = Asterisk_Backend_Phone_Factory::factory(Asterisk_Backend_Phone_Factory::SQL);
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Asterisk_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Asterisk_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Asterisk_Controller;
        }
        
        return self::$_instance;
    }

    /**
     * get snom_phone by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Asterisk_Model_Phone
     */
    public function getPhoneById($_id)
    {
        $result = $this->_backend->getPhoneById($_id);

        return $result;    
    }

    /**
     * add one phone
     *
     * @param Asterisk_Model_Phone $_phone
     * @return  Asterisk_Model_Phone
     */
    public function addPhone(Asterisk_Model_Phone $_phone)
    {        
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
        $phone = $this->_backend->addPhone($_phone);
      
        return $phone;
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
    public function deletePhones($_identifiers)
    {
      /*  foreach ($_identifiers as $identifier) {
            $Phone = $this->getPhoneById($identifier);
            if (!$this->_currentAccount->hasGrant($Phone->container_id, Tinebase_Container::GRANT_DELETE)) {
                throw new Exception('Not allowed!');
            }
        }
       */ 
        $this->_backend->deletePhones($_identifiers);
    }


    /**
     * Deletes a set of configs.
     * 
     * If one of the configs could not be deleted, no config is deleted
     * 
     * @throws Exception
     * @param array array of config identifiers
     * @return void
     */
    public function deleteConfigs($_identifiers)
    {
      /*  foreach ($_identifiers as $identifier) {
            $Config = $this->getConfigById($identifier);
            if (!$this->_currentAccount->hasGrant($Config->container_id, Tinebase_Container::GRANT_DELETE)) {
                throw new Exception('Not allowed!');
            }
        }
       */ 
        $this->_backend->deleteConfigs($_identifiers);
    }


    /**
     * update one phone
     *
     * @param Asterisk_Model_Phone $_phone
     * @return  Asterisk_Model_Phone
     */
    public function updatePhone(Asterisk_Model_Phone $_phone)
    {
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
       
        $phone = $this->_backend->updatePhone($_phone);
        
        return $phone;
    }    
    

    /**
     * get snom_phones
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Asterisk_Model_Phone
     */
    public function getPhones($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $result = $this->_backend->getPhones($_sort, $_dir, $_query);

        return $result;    
    }


    /**
     * get snom_config
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Asterisk_Model_Config
     */
    public function getConfig($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {        
        $result = $this->_backend->getConfig($_sort, $_dir, $_query);

        return $result;    
    }
    
    /**
     * get snom_config by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Asterisk_Model_Config
     */
    public function getConfigById($_id)
    {
        $result = $this->_backend->getConfigById($_id);

        return $result;    
    }    
    
    /**
     * add one config
     *
     * @param Asterisk_Model_Config $_config
     * @return  Asterisk_Model_Config
     */
    public function addConfig(Asterisk_Model_Config $_config)
    {        
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_config->owner, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to config in container ' . $_config->owner . ' denied');
        }
        */
        $config = $this->_backend->addConfig($_config);
      
        return $config;
    }    
    
    /**
     * update one config
     *
     * @param Asterisk_Model_Config $_config
     * @return  Asterisk_Model_Config
     */
    public function updateConfig(Asterisk_Model_Config $_config)
    {
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_config->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to config in container ' . $_config->owner . ' denied');
        }
        */
       
        $config = $this->_backend->updateConfig($_config);
        
        return $config;
    }
        
    
    /**
     * get snom_software
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Asterisk_Model_Software
     */
    public function getSoftware($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {        
        $result = $this->_backend->getSoftware($_sort, $_dir, $_query);

        return $result;    
    }  
    
    /**
     * get snom_software by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Asterisk_Model_Software
     */
    public function getSoftwareById($_id)
    {
        $result = $this->_backend->getSoftwareById($_id);

        return $result;    
    }    

    /**
     * add new software
     *
     * @param Asterisk_Model_Software $_software
     * @return  Asterisk_Model_Software
     */
    public function addSoftware(Asterisk_Model_Software $_software)
    {        
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
        $software = $this->_backend->addSoftware($_software);
      
        return $software;
    }
    
    /**
     * update existing software
     *
     * @param Asterisk_Model_Software $_software
     * @return  Asterisk_Model_Software
     */
    public function updateSoftware(Asterisk_Model_Software $_software)
    {
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
       
        $software = $this->_backend->updateSoftware($_software);
        
        return $software;
    }    
    
    
    /**
     * Deletes a set of softwareversion entriews.
     * 
     * If one of the software entries could not be deleted, no software is deleted
     * 
     * @throws Exception
     * @param array array of software identifiers
     * @return void
     */
    public function deleteSoftwares($_identifiers)
    {
/*        foreach ($_identifiers as $identifier) {
            $Software = $this->getSoftwareById($identifier);
            if (!$this->_currentAccount->hasGrant($Software->container_id, Tinebase_Container::GRANT_DELETE)) {
                throw new Exception('Not allowed!');
            }
        }
       */ 
        $this->_backend->deleteSoftwares($_identifiers);
    }    
    
}