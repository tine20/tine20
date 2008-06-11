<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
 *
 */

/**
 * 
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Phone_Sql implements Voipmanager_Backend_Phone_Interface
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;    
	/**
	* Instance of Voipmanager_Backend_Phone_Sql_Phones
	*
	* @var Voipmanager_Backend_Sql_Phones
	*/
    protected $phoneTable;
    
	/**
	* the constructor
	*
	*/
    public function __construct()
    {
        $this->_db = Zend_Registry::get('dbAdapter');
        $this->phoneTable      		= new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'snom_phones'));
    }
    
	/**
	 * get Phones
	 * 
     * @param string $_sort
     * @param string $_dir
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Phone
	 */
    public function getPhones($_sort = 'id', $_dir = 'ASC', $_filter = NULL)
    {	
        $where = array();
        
        if(!empty($_filter)) {
            $_fields = "voipmanager.macaddress,voipmanager.ipaddress,voipmanager.description,location.description,templates.name";            
            $where = $this->_getSearchFilter($_filter, $_fields);
        }
        
        
        $select = $this->_db->select()
            ->from(array('voipmanager' => SQL_TABLE_PREFIX . 'snom_phones'), array(
                'id',
                'macaddress',
                'location_id',
                'template_id',
                'ipaddress',
                'last_modified_time',
                'description')
            );
            
        $select->join(array('location' => SQL_TABLE_PREFIX . 'snom_location'),
				'voipmanager.location_id = location.id', array( 'location' => 'description') );            

        $select->join(array('templates' => SQL_TABLE_PREFIX . 'snom_templates'),
				'voipmanager.template_id = templates.id', array( 'template' => 'name') );            


        $select->order($_sort.' '.$_dir);

        foreach($where as $whereStatement) {
            $select->where($whereStatement);
        }               
 //        error_log($select->__toString());
       
        $stmt = $this->_db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_Phone', $rows);
		
        return $result;
	}
    
    
	/**
	 * get Phone by id
	 * 
     * @param string $_id
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Phone
	 */
    public function getPhoneById($_phoneId)
    {	
        $phoneId = Voipmanager_Model_Phone::convertPhoneIdToInt($_phoneId);
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'snom_phones')->where($this->_db->quoteInto('id = ?', $phoneId));
        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new UnderflowException('phone not found');
        }
#       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_Phone', $row);
        $result = new Voipmanager_Model_Phone($row);
        return $result;
	}     
    
    
     /**
     * add a phone
     *
     * @param Voipmanager_Model_Phone $_phoneData the phonedata
     * @return Voipmanager_Model_Phone
     */
    public function addPhone (Voipmanager_Model_Phone $_phoneData)
    {
        if (! $_phoneData->isValid()) {
            throw new Exception('invalid phone');
        }
        
        if ( empty($_phoneData->id) ) {
        	$_phoneData->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $phoneData = $_phoneData->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'snom_phones', $phoneData);
        $id = $this->_db->lastInsertId(SQL_TABLE_PREFIX . 'snom_phones', 'id');
        // if we insert a phone without an id, we need to get back one
        if (empty($_phoneData->id) && $id == 0) {
            throw new Exception("returned phone id is 0");
        }
        // if the phone had no phoneId set, set the id now
        if (empty($_phoneData->id)) {
            $_phoneData->id = $id;
        }
        return $this->getPhoneById($_phoneData->id);
    }
    
    
    /**
     * update an existing phone
     *
     * @param Voipmanager_Model_Phone $_phoneData the phonedata
     * @return Voipmanager_Model_Phone
     */
    public function updatePhone (Voipmanager_Model_Phone $_phoneData)
    {
        if (! $_phoneData->isValid()) {
            throw new Exception('invalid phone');
        }
        $phoneId = Voipmanager_Model_Phone::convertPhoneIdToInt($_phoneData);
        $phoneData = $_phoneData->toArray();
        unset($phoneData['id']);

        $where = array($this->_db->quoteInto('id = ?', $phoneId));
        $this->_db->update(SQL_TABLE_PREFIX . 'snom_phones', $phoneData, $where);
        return $this->getPhoneById($phoneId);
    }    
    
    
    /**
     * delete phone identified by phone id
     *
     * @param int $_phoneId phone id
     * @return int the number of row deleted
     */
    public function deletePhone ($_phoneId)
    {
        $phoneId = Voipmanager_Model_Phone::convertPhoneIdToInt($_phoneId);
        $where = array($this->_db->quoteInto('id = ?', $phoneId) , $this->_db->quoteInto('id = ?', $phoneId));
        $result = $this->_db->delete(SQL_TABLE_PREFIX . 'snom_phones', $where);
        return $result;
    }    
    
    
    /**
     * Deletes a set of phones.
     * 
     * If one of the phones could not be deleted, no phone is deleted
     * 
     * @throws Exception
     * @param array array of strings (phone ids)
     * @return void
     */
    public function deletePhones($_ids)
    {
        try {
            $this->_db->beginTransaction();
            foreach ($_ids as $id) {
                $this->deletePhone($id);
            }
            $this->_db->commit();
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }    
    
    
    
   
    
    
    
	/**
	 * get Location
	 * 
     * @param string $_sort
     * @param string $_dir
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Location
	 */
    public function getLocation($_sort = 'id', $_dir = 'ASC', $_filter = NULL)
    {	
        if(!empty($_filter)) {
            $_fields = "firmware_interval,firmware_status,update_policy,setting_server,admin_mode,ntp_server,http_user,description";            
            $where = $this->_getSearchFilter($_filter, $_fields);
        }
        
        
        $select = $this->_db->select()
            ->from(array('location' => SQL_TABLE_PREFIX . 'snom_location'), array(
                'firmware_interval',
                'firmware_status',
                'update_policy',
                'setting_server',
                'admin_mode',
                'admin_mode_password',
                'ntp_server',
                'webserver_type',
                'https_port',
                'http_user',
                'http_pass',
                'id',
                'description',
                'filter_registrar',
                'callpickup_dialoginfo',
                'pickup_indication')
            );

        $select->order($_sort.' '.$_dir);

         foreach($where as $whereStatement) {
              $select->where($whereStatement);
         }               
       //echo  $select->__toString();
       
        $stmt = $this->_db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_Location', $rows);
		
        return $result;
	}
    
	/**
	 * get Location by id
	 * 
     * @param string $_id
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Location
	 */
    public function getLocationById($_locationId)
    {	
        $locationId = Voipmanager_Model_Location::convertLocationIdToInt($_locationId);
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'snom_location')->where($this->_db->quoteInto('id = ?', $locationId));
        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new UnderflowException('location not found');
        }
#       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_Location', $row);
        $result = new Voipmanager_Model_Location($row);
        return $result;
	}    
    
    
   
     /**
     * add a location
     *
     * @param Voipmanager_Model_Location $_locationData the location data
     * @return Voipmanager_Model_Location
     */
    public function addLocation (Voipmanager_Model_Location $_locationData)
    {
        if (! $_locationData->isValid()) {
            throw new Exception('invalid location');
        }
        
        if ( empty($_locationData->id) ) {
        	$_locationData->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $locationData = $_locationData->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'snom_location', $locationData);

        return $this->getLocationById($_locationData->id);
    }
    
    
    /**
     * update an existing location
     *
     * @param Voipmanager_Model_Location $_locationData the locationdata
     * @return Voipmanager_Model_Location
     */
    public function updateLocation (Voipmanager_Model_Location $_locationData)
    {
        if (! $_locationData->isValid()) {
            throw new Exception('invalid location');
        }
        $locationId = Voipmanager_Model_Location::convertLocationIdToInt($_locationData);
        $locationData = $_locationData->toArray();
        unset($locationData['id']);

        $where = array($this->_db->quoteInto('id = ?', $locationId));
        $this->_db->update(SQL_TABLE_PREFIX . 'snom_location', $locationData, $where);
        return $this->getLocationById($locationId);
    }    
     
    
    
    /**
     * delete location identified by location id
     *
     * @param int $_locationId location id
     * @return int the number of row deleted
     */
    public function deleteLocation ($_locationId)
    {
        $locationId = Voipmanager_Model_Location::convertLocationIdToInt($_locationId);
        $where = array($this->_db->quoteInto('id = ?', $locationId) , $this->_db->quoteInto('id = ?', $locationId));
        $result = $this->_db->delete(SQL_TABLE_PREFIX . 'snom_location', $where);
        return $result;
    }    
    
    
    /**
     * Deletes a set of locations.
     * 
     * If one of the locations could not be deleted, no location is deleted
     * 
     * @throws Exception
     * @param array array of strings (location ids)
     * @return void
     */
    public function deleteLocations($_ids)
    {
        try {
            $this->_db->beginTransaction();
            foreach ($_ids as $id) {
                $this->deleteLocation($id);
            }
            $this->_db->commit();
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    } 
    
    
    
	/**
	 * get Software
	 * 
     * @param string $_sort
     * @param string $_dir
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Software
	 */
    public function getSoftware($_sort = 'id', $_dir = 'ASC', $_filter = NULL)
    {	
        $where = array();
        
        if(!empty($_filter)) {
            $_fields = "description,model,softwareimage";            
            $where = $this->_getSearchFilter($_filter, $_fields);
        }
        
        $select = $this->_db->select()
            ->from(array('location' => SQL_TABLE_PREFIX . 'snom_software'), array(
                'id',
                'description',
                'model',
                'softwareimage')
            );

        $select->order($_sort.' '.$_dir);

         foreach($where as $whereStatement) {
              $select->where($whereStatement);
         }               
       //echo  $select->__toString();
       
        $stmt = $this->_db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_Software', $rows);
		
        return $result;
	}    
    
	/**
	 * get Software by id
	 * 
     * @param string $_id
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Software
	 */
    public function getSoftwareById($_softwareId)
    {	
        //$softwareId = Voipmanager_Model_Software::convertSoftwareIdToInt($_softwareId);
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_software')
            ->where($this->_db->quoteInto('id = ?', $_softwareId));
            
        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new UnderflowException('software not found');
        }
#       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_Software', $row);
        $result = new Voipmanager_Model_Software($row);
        return $result;
	}      
    
     /**
     * add new software
     *
     * @param Voipmanager_Model_Software $_softwareData the softwaredata
     * @return Voipmanager_Model_Software
     */
    public function addSoftware (Voipmanager_Model_Software  $_softwareData)
    {
        if (! $_softwareData->isValid()) {
            throw new Exception('invalid software');
        }

        if ( empty($_softwareData->id) ) {
            $_softwareData->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $softwareData = $_softwareData->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'snom_software', $softwareData);

        return $this->getSoftwareById($_softwareData->getId());
    }
    
    /**
     * update an existing software
     *
     * @param Voipmanager_Model_Software $_softwareData the softwaredata
     * @return Voipmanager_Model_Phone
     */
    public function updateSoftware (Voipmanager_Model_Software $_softwareData)
    {
        if (! $_softwareData->isValid()) {
            throw new Exception('invalid software');
        }
        $softwareId = $_softwareData->getId();
        $softwareData = $_softwareData->toArray();
        unset($softwareData['id']);

        $where = array($this->_db->quoteInto('id = ?', $softwareId));
        $this->_db->update(SQL_TABLE_PREFIX . 'snom_software', $softwareData, $where);
        
        return $this->getSoftwareById($softwareId);
    }    
    

    /**
     * delete software identified by software id
     *
     * @param int $_softwareId software id
     * @return int the number of row deleted
     */
    public function deleteSoftware ($_softwareId)
    {
        $softwareId = Voipmanager_Model_Software::convertSoftwareIdToInt($_softwareId);
        $where = array($this->_db->quoteInto('id = ?', $softwareId) , $this->_db->quoteInto('id = ?', $softwareId));
        $result = $this->_db->delete(SQL_TABLE_PREFIX . 'snom_software', $where);
        return $result;
    }    
    
    
    /**
     * Deletes a set of software entries ids.
     * 
     * If one of the software entries could not be deleted, no software is deleted
     * 
     * @throws Exception
     * @param array array of strings (software ids)
     * @return void
     */
    public function deleteSoftwares($_ids)
    {
        try {
            $this->_db->beginTransaction();
            foreach ($_ids as $id) {
                $this->deleteSoftware($id);
            }
            $this->_db->commit();
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
    
  
	/**
	 * get Templates
	 * 
     * @param string $_sort
     * @param string $_dir
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Template
	 */
    public function getTemplates($_sort = 'id', $_dir = 'ASC', $_filter = NULL)
    {	
        $where = array();
        
        if(!empty($_filter)) {
            $_fields = "model,description";            
            $where = $this->_getSearchFilter($_filter, $_fields);
        }
        
        
        $select = $this->_db->select()
            ->from(array('voipmanager' => SQL_TABLE_PREFIX . 'snom_templates'), array(
                'id',
                'name',
                'description',
                'model',
                'keylayout_id',
                'setting_id',
                'software_id')
            );

        $select->order($_sort.' '.$_dir);

        foreach($where as $whereStatement) {
            $select->where($whereStatement);
        }               
        //echo  $select->__toString();
       
        $stmt = $this->_db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_Template', $rows);
		
        return $result;
	}
    
    
	/**
	 * get Template by id
	 * 
     * @param string $_id
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Template
	 */
    public function getTemplateById($_templateId)
    {	
        $templateId = Voipmanager_Model_Template::convertTemplateIdToInt($_templateId);
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'snom_templates')->where($this->_db->quoteInto('id = ?', $templateId));
        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new UnderflowException('template not found');
        }
#       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_Template', $row);
        $result = new Voipmanager_Model_Template($row);
        return $result;
	}   
  
  
    
    
    
   /**
     * create search filter
     *
     * @param string $_filter
     * @param int $_leadstate
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return array
     */
    protected function _getSearchFilter($_filter, $_fields)
    {
        $where = array();
        if(!empty($_filter)) {
            $search_values = explode(" ", $_filter);
            
            $search_fields = explode(",", $_fields);
            foreach($search_fields AS $search_field) {
                $fields .= " OR " . $search_field . " LIKE ?";    
            }
            $fields = substr($fields,3);
        
            foreach($search_values AS $search_value) {
                $where[] = Zend_Registry::get('dbAdapter')->quoteInto('('.$fields.')', '%' . $search_value . '%');                            
            }
        }
        return $where;
    }    
   
}
