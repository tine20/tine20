<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * backend to handle phones
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_Phone
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;    

	/**
	 * the constructor
	 */
    public function __construct()
    {
        $this->_db      = Zend_Db_Table_Abstract::getDefaultAdapter();
    }
    
	/**
	 * search phones
	 * 
     * @param Voipmanager_Model_SnomPhoneFilter $_filter
     * @param Tinebase_Model_Pagination $_pagination
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomPhone
	 */
    public function search(Voipmanager_Model_SnomPhoneFilter $_filter, Tinebase_Model_Pagination $_pagination)
    {	
        $where = array();
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_phones');
            
        $_pagination->appendPagination($select);

        if(!empty($_filter->query)) {
            $select->where($this->_db->quoteInto('(macaddress LIKE ? OR ipaddress LIKE ? OR description LIKE ?)', '%' . $_filter->query . '%'));
        } else {
            // handle the other fields separately
        }
       
        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomPhone', $rows);
		
        return $result;
	}
    
	/**
	 * get one phone identified by id
	 * 
     * @param string|Voipmanager_Model_SnomPhone $_id
	 * @return Voipmanager_Model_SnomPhone the phone
	 */
    public function get($_id)
    {	
        $phoneId = Voipmanager_Model_SnomPhone::convertPhoneIdToInt($_id);
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_phones')
            ->where($this->_db->quoteInto('id = ?', $phoneId));

        $row = $this->_db->fetchRow($select);
        if (!$row) {
            throw new UnderflowException('phone not found');
        }

        $result = new Voipmanager_Model_SnomPhone($row);
        
        return $result;
	}
	     
    /**
     * get one phone identified by id
     * 
     * @param string $_macAddress the macaddress of the phone
     * @return Voipmanager_Model_SnomPhone the phone
     */
    public function getByMacAddress($_macAddress)
    {   
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_phones')
            ->where($this->_db->quoteInto('macaddress = ?', $_macAddress));

        $row = $this->_db->fetchRow($select);
        if (!$row) {
            throw new UnderflowException('phone not found');
        }

        $result = new Voipmanager_Model_SnomPhone($row);
        
        return $result;
    }     
	
    /**
     * insert new phone into database
     *
     * @param Voipmanager_Model_SnomPhone $_phone the phonedata
     * @return Voipmanager_Model_SnomPhone
     */
    public function create(Voipmanager_Model_SnomPhone $_phone)
    {
        if (!$_phone->isValid()) {
            throw new Exception('invalid phone');
        }
        
        if (empty($_phone->id)) {
        	$_phone->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $phoneData = $_phone->toArray();
        unset($phoneData['lines']);
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'snom_phones', $phoneData);

        return $this->get($_phone);
    }
    
    /**
     * update an existing phone
     *
     * @param Voipmanager_Model_SnomPhone $_phone the phonedata
     * @return Voipmanager_Model_SnomPhone
     */
    public function update(Voipmanager_Model_SnomPhone $_phone)
    {
        if (! $_phone->isValid()) {
            throw new Exception('invalid phone');
        }
        $phoneId = $_phone->getId();
        $phoneData = $_phone->toArray();
        unset($phoneData['id']);
        unset($phoneData['lines']);

        $where = array($this->_db->quoteInto('id = ?', $phoneId));
        $this->_db->update(SQL_TABLE_PREFIX . 'snom_phones', $phoneData, $where);
        
        return $this->get($_phone);
    }        
    
    /**
     * delete phone(s) identified by phone id
     *
     * @param string|array|Tinebase_Record_RecordSet $_id
     * @return void
     */
    public function delete($_id)
    {
        foreach ((array)$_id as $id) {
            $phoneId = Voipmanager_Model_SnomPhone::convertPhoneIdToInt($id);
            $where[] = $this->_db->quoteInto('id = ?', $phoneId);
        }

        try {
            $this->_db->beginTransaction();

            // NOTE: cascading delete for lines
            $this->_db->delete(SQL_TABLE_PREFIX . 'snom_phones', $where);

            $this->_db->commit();
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
        
    /**
     * update an existing phone
     *
     * @param Voipmanager_Model_SnomPhone $_phone the phonedata
     * @return Voipmanager_Model_SnomPhone
     */
    public function updateStatus(Voipmanager_Model_SnomPhone $_phone)
    {
        if (! $_phone->isValid()) {
            throw new Exception('invalid phone');
        }
        $phoneId = $_phone->getId();
        $phoneData = $_phone->toArray();
        $statusData = array(
            'ipaddress'             => $_phone->ipaddress,
            'current_software'      => $_phone->current_software,
            'current_model'         => $_phone->current_model,
            'settings_loaded_at'    => $phoneData['settings_loaded_at'],
            'firmware_checked_at'   => $phoneData['firmware_checked_at']
        );

        $where = array($this->_db->quoteInto('id = ?', $phoneId));
        $this->_db->update(SQL_TABLE_PREFIX . 'snom_phones', $statusData, $where);
        
        return $this->get($_phone);
    }      
    
    
	/**
	 * get softwareImage identified by softwareImage id
	 * 
     * @param string|Voipmanager_Model_SoftwareImage $_id
	 * @return Voipmanager_Model_SoftwareImage the softwareImages
	 */
    public function getSoftwareImageById($_id)
    {	
        $softwareImageId = Voipmanager_Model_SoftwareImage::convertSoftwareImageIdToInt($_id);
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_software_softwareimage')
            ->where($this->_db->quoteInto('software_id = ?', $softwareImageId));

        $stmt = $select->query();
        
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);


       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_SoftwareImage', $rows);
       
        return $result;
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
        $where = array();
        if(!empty($_filter)) {
            $_fields = "firmware_interval,firmware_status,update_policy,setting_server,admin_mode,ntp_server,http_user,description";            
            $where = $this->_getSearchFilter($_filter, $_fields);
        }
        
        $select = $this->_db->select()
            ->from(array('location' => SQL_TABLE_PREFIX . 'snom_location'));

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
     * @param Voipmanager_Model_Location $_location the location data
     * @return Voipmanager_Model_Location
     */
    public function addLocation (Voipmanager_Model_Location $_location)
    {
        if (! $_location->isValid()) {
            throw new Exception('invalid location');
        }
        
        if ( empty($_location->id) ) {
        	$_location->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $locationData = $_location->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'snom_location', $locationData);

        return $this->getLocationById($_location->id);
    }
    
    
    /**
     * update an existing location
     *
     * @param Voipmanager_Model_Location $_location the locationdata
     * @return Voipmanager_Model_Location
     */
    public function updateLocation (Voipmanager_Model_Location $_location)
    {
        if (! $_location->isValid()) {
            throw new Exception('invalid location');
        }
        $locationId = $_location->getId();
        $locationData = $_location->toArray();
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
            $_fields = "description";            
            $where = $this->_getSearchFilter($_filter, $_fields);
        }
        
        $select = $this->_db->select()
            ->from(array('location' => SQL_TABLE_PREFIX . 'snom_software'), array(
                'id',
                'description')
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
     * @param Voipmanager_Model_Software $_software the softwaredata
     * @return Voipmanager_Model_Software
     */
    public function addSoftware (Voipmanager_Model_Software  $_software)
    {
        if (! $_software->isValid()) {
            throw new Exception('invalid software');
        }

        if ( empty($_software->id) ) {
            $_software->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $softwareData = $_software->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'snom_software', $softwareData);

        return $this->getSoftwareById($_software->getId());
    }
    
    /**
     * update an existing software
     *
     * @param Voipmanager_Model_Software $_software the softwaredata
     * @return Voipmanager_Model_Software
     */
    public function updateSoftware (Voipmanager_Model_Software $_software)
    {
        if (! $_software->isValid()) {
            throw new Exception('invalid software');
        }
        $softwareId = $_software->getId();
        $softwareData = $_software->toArray();
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
     * add new template
     *
     * @param Voipmanager_Model_Template $_template the template data
     * @return Voipmanager_Model_Template
     */
    public function addTemplate (Voipmanager_Model_Template $_template)
    {
        if (! $_template->isValid()) {
            throw new Exception('invalid template');
        }

        if ( empty($_template->id) ) {
            $_template->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $template = $_template->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'snom_templates', $template);

        return $this->getTemplateById($_template->getId());
    }
    
    /**
     * update an existing template
     *
     * @param Voipmanager_Model_Template $_template the template data
     * @return Voipmanager_Model_Template
     */
    public function updateTemplate (Voipmanager_Model_Template $_template)
    {
        if (! $_template->isValid()) {
            throw new Exception('invalid template');
        }
        $templateId = $_template->getId();
        $templateData = $_template->toArray();
        unset($templateData['id']);

        $where = array($this->_db->quoteInto('id = ?', $templateId));
        $this->_db->update(SQL_TABLE_PREFIX . 'snom_templates', $templateData, $where);
        
        return $this->getTemplateById($templateId);
    }    
	        
      
}
