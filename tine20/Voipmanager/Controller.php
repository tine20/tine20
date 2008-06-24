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
     * the asterisk peer sql backend
     *
     * @var Voipmanager_Backend_Asterisk_Peer
     */
    protected $_asteriskPeerBackend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_snomPhoneBackend    = new Voipmanager_Backend_Snom_Phone();
        $this->_snomLineBackend     = new Voipmanager_Backend_Snom_Line();
        $this->_snomSoftwareBackend = new Voipmanager_Backend_Snom_Software();
        $this->_snomLocationBackend = new Voipmanager_Backend_Snom_Location();
        $this->_snomTemplateBackend = new Voipmanager_Backend_Snom_Template();      
        $this->_asteriskPeerBackend = new Voipmanager_Backend_Asterisk_Peer();          
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
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
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
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
       
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
     * get asterisk peer by id
     *
     * @param string $_id the id of the peer
     * @return Voipmanager_Model_AsteriskPeer
     */
    public function getAsteriskPeer($_id)
    {
        $result = $this->_asteriskPeerBackend->get($_id);

        return $result;    
    }

    /**
     * get list of asterisk peers
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskPeer
     */
    public function searchAsteriskPeers($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $result = $this->_asteriskPeerBackend->search($_sort, $_dir, $_query);

        return $result;    
    }
    
    /**
     * add new asterisk peer
     *
     * @param Voipmanager_Model_AsteriskPeer $_peer
     * @return  Voipmanager_Model_AsteriskPeer
     */
    public function createAsteriskPeer(Voipmanager_Model_AsteriskPeer $_peer)
    {        
        $peer = $this->_asteriskPeerBackend->create($_peer);
      
        return $peer;
    }
    
    /**
     * update existing asterisk peer
     *
     * @param Voipmanager_Model_AsteriskPeer $_peer
     * @return  Voipmanager_Model_AsteriskPeer
     */
    public function updateAsteriskPeer(Voipmanager_Model_AsteriskPeer $_peer)
    {
        $peer = $this->_asteriskPeerBackend->update($_peer);
        
        return $peer;
    }       
    
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
}