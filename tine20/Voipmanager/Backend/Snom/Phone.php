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
            $phoneId = Voipmanager_Model_SnomPhone::convertSnomPhoneIdToInt($id);
            $where[] = $this->_db->quoteInto('id = ?', $phoneId);
        }

        try {
            $this->_db->beginTransaction();

            // NOTE: cascading delete for lines
            // SECOND NOTE: using array for second argument won't work as delete function joins array items using "AND"
            foreach($where AS $where_atom)
            {
                $this->_db->delete(SQL_TABLE_PREFIX . 'snom_phones', $where_atom);
            }

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
      
}
