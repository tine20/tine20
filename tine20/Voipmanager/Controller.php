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
 * @version     $Id:  $
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
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend = Voipmanager_Backend_Phone_Factory::factory(Voipmanager_Backend_Phone_Factory::SQL);
        $this->_snomPhoneBackend = new Voipmanager_Backend_Snom_Phone();
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

    /**
     * get snom_phone by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Phone
     */
    public function getPhoneById($_id)
    {
        $result = $this->_snomPhoneBackend->get($_id);

        return $result;    
    }

    /**
     * get snom_phones
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Phone
     */
    public function getPhones($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $filter = new Voipmanager_Model_PhoneFilter(array(
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
     * @param Voipmanager_Model_Phone $_phone
     * @return  Voipmanager_Model_Phone
     */
    public function addPhone(Voipmanager_Model_Phone $_phone)
    {        
        $phone = $this->_snomPhoneBackend->create($_phone);
      
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
        $this->_snomPhoneBackend->delete($_identifiers);
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
    public function deleteLocations($_identifiers)
    {
      /*  foreach ($_identifiers as $identifier) {
            $Config = $this->getLocationById($identifier);
            if (!$this->_currentAccount->hasGrant($Location->container_id, Tinebase_Container::GRANT_DELETE)) {
                throw new Exception('Not allowed!');
            }
        }
       */ 
        $this->_backend->deleteLocations($_identifiers);
    }


    /**
     * update one phone
     *
     * @param Voipmanager_Model_Phone $_phone
     * @return  Voipmanager_Model_Phone
     */
    public function updatePhone(Voipmanager_Model_Phone $_phone)
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
     * get snom_location
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Location
     */
    public function getLocation($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {        
        $result = $this->_backend->getLocation($_sort, $_dir, $_query);

        return $result;    
    }
    
    /**
     * get snom_location by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Location
     */
    public function getLocationById($_id)
    {
        $result = $this->_backend->getLocationById($_id);

        return $result;    
    }    
    
    /**
     * add one location
     *
     * @param Voipmanager_Model_Location $_location
     * @return  Voipmanager_Model_Location
     */
    public function addLocation(Voipmanager_Model_Location $_location)
    {        
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_location->owner, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to location in container ' . $_location->owner . ' denied');
        }
        */
        $location = $this->_backend->addLocation($_location);
      
        return $location;
    }    
    
    /**
     * update one location
     *
     * @param Voipmanager_Model_Location $_location
     * @return  Voipmanager_Model_Location
     */
    public function updateLocation(Voipmanager_Model_Location $_location)
    {
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_location->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to location in container ' . $_location->owner . ' denied');
        }
        */
       
        $location = $this->_backend->updateLocation($_location);
        
        return $location;
    }
        
    
    /**
     * get snom_software
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Software
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
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Software
     */
    public function getSoftwareById($_id)
    {
        $result = $this->_backend->getSoftwareById($_id);

        return $result;    
    }    

    /**
     * add new software
     *
     * @param Voipmanager_Model_Software $_software
     * @return  Voipmanager_Model_Software
     */
    public function addSoftware(Voipmanager_Model_Software $_software)
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
     * @param Voipmanager_Model_Software $_software
     * @return  Voipmanager_Model_Software
     */
    public function updateSoftware(Voipmanager_Model_Software $_software)
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
    
    
    /**
     * get snom_template by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Template
     */
    public function getTemplateById($_id)
    {
        $result = $this->_backend->getTemplateById($_id);

        return $result;    
    }

    /**
     * get snom_templates
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Template
     */
    public function getTemplates($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $result = $this->_backend->getTemplates($_sort, $_dir, $_query);

        return $result;    
    }
    
    /**
     * add new template
     *
     * @param Voipmanager_Model_Template $_template
     * @return  Voipmanager_Model_Template
     */
    public function addTemplate(Voipmanager_Model_Template $_template)
    {        
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
        $template = $this->_backend->addTemplate($_template);
      
        return $template;
    }
    
    /**
     * update existing template
     *
     * @param Voipmanager_Model_Template $_template
     * @return  Voipmanager_Model_Template
     */
    public function updateTemplate(Voipmanager_Model_Template $_template)
    {
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
       
        $template = $this->_backend->updateTemplate($_template);
        
        return $template;
    }   
    
    
    /**
     * get snom_line by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Line
     */
    public function getLineById($_id)
    {
        $result = $this->_backend->getLineById($_id);

        return $result;    
    }

    /**
     * get snom_lines
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Line
     */
    public function getLines($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $result = $this->_backend->getLines($_sort, $_dir, $_query);

        return $result;    
    }
    
    /**
     * add new line
     *
     * @param Voipmanager_Model_Line $_line
     * @return  Voipmanager_Model_Line
     */
    public function addLine(Voipmanager_Model_Line $_line)
    {        
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
        $line = $this->_backend->addLine($_line);
      
        return $line;
    }
    
    /**
     * update existing line
     *
     * @param Voipmanager_Model_Line $_line
     * @return  Voipmanager_Model_Line
     */
    public function updateLine(Voipmanager_Model_Line $_line)
    {
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
       
        $line = $this->_backend->updateLine($_line);
        
        return $line;
    }       
    
     
}