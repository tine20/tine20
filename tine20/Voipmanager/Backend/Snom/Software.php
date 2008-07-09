<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * backend to handle Snom software
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_Software
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
	 * search softwares
	 * 
     * @param Voipmanager_Model_SnomSoftwareFilter $_filter
     * @param Tinebase_Model_Pagination $_pagination
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomSoftware
	 */
    public function search(Voipmanager_Model_SnomSoftwareFilter $_filter, Tinebase_Model_Pagination $_pagination)
    {	
        $where = array();
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_software');
            
        $_pagination->appendPagination($select);

        if(!empty($_filter->query)) {
            $select->where($this->_db->quoteInto('(description LIKE ? OR name LIKE ?)', '%' . $_filter->query . '%'));
        } else {
            // handle the other fields separately
        }
       
        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomSoftware', $rows);
		
        return $result;
	}
    
	/**
	 * get one phone identified by id
	 * 
     * @param string|Voipmanager_Model_SnomSoftware $_id
	 * @return Voipmanager_Model_SnomSoftware the software
	 */
    public function get($_id)
    {	
        $softwareId = Voipmanager_Model_SnomSoftware::convertSoftwareIdToInt($_id);
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_software')
            ->where($this->_db->quoteInto('id = ?', $softwareId));

        $row = $this->_db->fetchRow($select);
        if (!$row) {
            throw new UnderflowException('software not found');
        }

        $result = new Voipmanager_Model_SnomSoftware($row);
        
        return $result;
	}

	/**
     * insert new phone into database
     *
     * @param Voipmanager_Model_SnomSoftware $_software the software to add
     * @return Voipmanager_Model_SnomSoftware
     */
    public function create(Voipmanager_Model_SnomSoftware $_software)
    {
        if (!$_software->isValid()) {
            throw new Exception('invalid software');
        }
        
        if (empty($_software->id)) {
        	$_software->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $softwareData = $_software->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'snom_software', $softwareData);

        return $this->get($_software);
    }
    
    /**
     * update an existing software
     *
     * @param Voipmanager_Model_SnomSoftware $_software the software to update
     * @return Voipmanager_Model_SnomSoftware
     */
    public function update(Voipmanager_Model_SnomSoftware $_software)
    {
        if (!$_software->isValid()) {
            throw new Exception('invalid software');
        }
        
        $softwareId = $_software->getId();
        $softwareData = $_software->toArray();
        unset($softwareData['id']);

        $where = array($this->_db->quoteInto('id = ?', $softwareId));
        $this->_db->update(SQL_TABLE_PREFIX . 'snom_software', $softwareData, $where);
        
        return $this->get($_software);        
    }        
    
    /**
     * delete software(s) identified by software id
     *
     * @param string|array|Tinebase_Record_RecordSet $_id
     * @return void
     */
    public function delete($_id)
    {
        foreach ((array)$_id as $id) {
            $softwareId = Voipmanager_Model_SnomSoftware::convertSoftwareIdToInt($id);
            $where[] = $this->_db->quoteInto('id = ?', $softwareId);
        }

        try {
            $this->_db->beginTransaction();

            // NOTE: using array for second argument won't work as delete function joins array items using "AND"
            foreach($where AS $where_atom)
            {
                $this->_db->delete(SQL_TABLE_PREFIX . 'snom_software', $where_atom);
            }

            $this->_db->commit();
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
}
