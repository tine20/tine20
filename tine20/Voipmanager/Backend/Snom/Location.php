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
 * backend to handle Snom location
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_Location
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;    

	/**
	 * the constructor
	 */
    public function __construct($_db = NULL)
    {
        if($_db instanceof Zend_Db_Adapter_Abstract) {
            $this->_db = $_db;
        } else {
            $this->_db = Zend_Registry::get('dbAdapter');
        }
    }
        
	/**
	 * search locations
	 * 
     * @param Voipmanager_Model_SnomLocationFilter $_filter
     * @param Tinebase_Model_Pagination $_pagination
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomLocation
	 */
    public function search(Voipmanager_Model_SnomLocationFilter $_filter, Tinebase_Model_Pagination $_pagination)
    {	
        $where = array();
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_location');
            
        $_pagination->appendPagination($select);

        if(!empty($_filter->query)) {
            
            
            
            $select->where($this->_db->quoteInto('(firmware_interval LIKE ? OR firmware_status LIKE ? OR update_policy LIKE ? OR setting_server LIKE ? OR admin_mode LIKE ? OR ntp_server LIKE ? OR http_user LIKE ? OR description LIKE ? OR name LIKE ?)', '%' . $_filter->query . '%'));
        } else {
            // handle the other fields separately
        }
       
        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomLocation', $rows);
		
        return $result;
	}    
    
    
	/**
	 * get Location by id
	 * 
     * @param string $_id
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomLocation
	 */
    public function get($_id)
    {	
        $locationId = Voipmanager_Model_SnomLocation::convertSnomLocationIdToInt($_id);

        
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'snom_location')->where($this->_db->quoteInto('id = ?', $locationId));
        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new UnderflowException('location not found');
        }
#       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomLocation', $row);
        $result = new Voipmanager_Model_SnomLocation($row);
        return $result;
	}    
    
    
   
     /**
     * insert new location
     *
     * @param Voipmanager_Model_SnomLocation $_location the location data
     * @return Voipmanager_Model_SnomLocation
     */
    public function create(Voipmanager_Model_SnomLocation $_location)
    {
        if (! $_location->isValid()) {
            throw new Exception('invalid location');
        }
        
        if ( empty($_location->id) ) {
        	$_location->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $locationData = $_location->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'snom_location', $locationData);

        return $this->get($_location->id);
    }
    
    
    /**
     * update an existing location
     *
     * @param Voipmanager_Model_Location $_location the locationdata
     * @return Voipmanager_Model_Location
     */
    public function update(Voipmanager_Model_SnomLocation $_location)
    {
        if (! $_location->isValid()) {
            throw new Exception('invalid location');
        }
        $locationId = $_location->getId();
        $locationData = $_location->toArray();
        unset($locationData['id']);

        $where = array($this->_db->quoteInto('id = ?', $locationId));
        $this->_db->update(SQL_TABLE_PREFIX . 'snom_location', $locationData, $where);
        
        return $this->get($locationId);
    }    
     
    
    
    /**
     * delete location identified by location id
     *
     * @param int|array $_id location id
     * 
     */
    public function delete($_id)
    {
        foreach ((array)$_id as $id) {
            $locationId = Voipmanager_Model_SnomLocation::convertSnomLocationIdToInt($id);            
            $where[] = $this->_db->quoteInto('id = ?', $locationId);
        }
                
        
        try {
            $this->_db->beginTransaction();

            // NOTE: using array for second argument won't work as delete function joins array items using "AND"
            foreach($where AS $where_atom)
            {
                $this->_db->delete(SQL_TABLE_PREFIX . 'snom_location', $where_atom);
            }

            $this->_db->commit();
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
       
    }    
    
}
