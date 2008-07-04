<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     
 *
 */


/**
 * Asterisk meetme sql backend
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Asterisk_Meetme
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
        $this->_db = Zend_Registry::get('dbAdapter');
    }
    
  
	/**
	 * search meetme
	 * 
     * @param Voipmanager_Model_AsteriskMeetmeFilter $_filter
     * @param Tinebase_Model_Pagination $_pagination
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskMeetme
	 */
    public function search(Voipmanager_Model_AsteriskMeetmeFilter $_filter, Tinebase_Model_Pagination $_pagination)
    {	
        $where = array();
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'asterisk_meetme');
            
        $_pagination->appendPagination($select);

        if(!empty($_filter->query)) {
            $select->where($this->_db->quoteInto('(name LIKE ? OR description LIKE ? )', '%' . $_filter->query . '%'));
        } else {
            // handle the other fields separately
        }
       
        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_AsteriskMeetme', $rows);
		
        return $result;
	}  
  
      
	/**
	 * get meetme by id
	 * 
     * @param string $_id
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskMeetme
	 */
    public function get($_id)
    {	
        $meetmeId = Voipmanager_Model_AsteriskMeetme::convertAsteriskMeetmeIdToInt($_id);
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'asterisk_meetme')->where($this->_db->quoteInto('id = ?', $meetmeId));
        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new UnderflowException('meetme not found');
        }
#       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_AsteriskMeetme', $row);
        $result = new Voipmanager_Model_AsteriskMeetme($row);
        return $result;
	}
	   
    /**
     * add new meetme
     *
     * @param Voipmanager_Model_AsteriskMeetme $_meetme the meetme data
     * @return Voipmanager_Model_AsteriskMeetme
     */
    public function create(Voipmanager_Model_AsteriskMeetme $_meetme)
    {
        if (! $_meetme->isValid()) {
            throw new Exception('invalid meetme');
        }

        if ( empty($_meetme->id) ) {
            $_meetme->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $meetme = $_meetme->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'asterisk_meetme', $meetme);

        return $this->get($_meetme->getId());
    }
    
    /**
     * update an existing meetme
     *
     * @param Voipmanager_Model_AsteriskMeetme $_meetme the meetme data
     * @return Voipmanager_Model_AsteriskMeetme
     */
    public function update(Voipmanager_Model_AsteriskMeetme $_meetme)
    {
        if (! $_meetme->isValid()) {
            throw new Exception('invalid meetme');
        }
        $meetmeId = $_meetme->getId();
        $meetmeData = $_meetme->toArray();
        unset($meetmeData['id']);

        $where = array($this->_db->quoteInto('id = ?', $meetmeId));
        $this->_db->update(SQL_TABLE_PREFIX . 'asterisk_meetme', $meetmeData, $where);
        
        return $this->get($meetmeId);
    }    


    /**
     * delete meetme(s) identified by meetme id
     *
     * @param string|array|Tinebase_Record_RecordSet $_id
     * @return void
     */
    public function delete($_id)
    {
        foreach ((array)$_id as $id) {
            $meetmeId = Voipmanager_Model_AsteriskMeetme::convertAsteriskMeetmeIdToInt($id);
            $where[] = $this->_db->quoteInto('id = ?', $meetmeId);
        }

        try {
            $this->_db->beginTransaction();

            // NOTE: using array for second argument won't work as delete function joins array items using "AND"
            foreach($where AS $where_atom)
            {
                $this->_db->delete(SQL_TABLE_PREFIX . 'asterisk_meetme', $where_atom);
            }

            $this->_db->commit();
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }  
}
