<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * backend to handle phone lines
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_Line
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
            $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
        }
    }
        
	/**
	 * search phone lines
	 * 
     * @param Voipmanager_Model_SnomLineFilter $_filter
     * @param Tinebase_Model_Pagination $_pagination
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomLine
	 */
    public function search(Voipmanager_Model_SnomLineFilter $_filter, $_pagination = NULL)
    {	
        $where = array();
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_lines');
            
        if($_pagination instanceof Tinebase_Model_Pagination) {
            $_pagination->appendPagination($select);
        }

        if(!empty($_filter->snomphone_id)) {
            $select->where($this->_db->quoteInto('snomphone_id = ?', $_filter->snomphone_id));
        }
       
        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomLine', $rows);
		
        return $result;
	}
    
	/**
	 * get one snom phone line identified by id
	 * 
     * @param string|Voipmanager_Model_SnomLine $_id
	 * @return Voipmanager_Model_SnomLine the line
	 */
    public function get($_id)
    {	
        $phoneId = Voipmanager_Model_SnomLine::convertSnomLineIdToInt($_id);
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_lines')
            ->where($this->_db->quoteInto('id = ?', $phoneId));

        $row = $this->_db->fetchRow($select);
        if (!$row) {
            throw new UnderflowException('line not found');
        }

        $result = new Voipmanager_Model_SnomLine($row);
        
        return $result;
	}     
    
    /**
     * insert new phone line into database
     *
     * @param Voipmanager_Model_SnomLine $_line the linedata
     * @return Voipmanager_Model_SnomLine
     */
    public function create(Voipmanager_Model_SnomLine $_line)
    {
        if (!$_line->isValid()) {
            throw new Exception('invalid line');
        }
        
        if (empty($_line->id)) {
        	$_line->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $lineData = $_line->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'snom_lines', $lineData);

        return $this->get($_line);
    }
    
    
    /**
     * update an existing line
     *
     * @param Voipmanager_Model_SnomLine $_line the linedata
     * @return Voipmanager_Model_SnomLine
     */
    public function update(Voipmanager_Model_SnomLine $_line)
    {
        if (!$_line->isValid()) {
            throw new Exception('invalid line');
        }
        $lineId = $_line->getId();
        $lineData = $_line->toArray();
        unset($lineData['id']);

        $where = array($this->_db->quoteInto('id = ?', $lineId));
        $this->_db->update(SQL_TABLE_PREFIX . 'snom_lines', $lineData, $where);
        
        return $this->get($_line);
    }        
    
    /**
     * delete lines(s) identified by line id
     *
     * @param string|array|Tinebase_Record_RecordSet $_id
     * @return void
     */
    public function delete($_id)
    {
        foreach ((array)$_id as $id) {
            $lineId = Voipmanager_Model_SnomLine::convertSnomLineIdToInt($id);
            $where[] = $this->_db->quoteInto('id = ?', $lineId);
        }

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

            // NOTE: using array for second argument won't work as delete function joins array items using "AND"
            foreach($where AS $where_atom)
            {
                $this->_db->delete(SQL_TABLE_PREFIX . 'snom_lines', $where_atom);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }    

    /**
     * delete lines(s) identified by phone id
     *
     * @param string|Voipmanager_Model_SnomPhone $_id
     * @return void
     */
    public function deletePhoneLines($_id)
    {
        $phoneId = Voipmanager_Model_SnomPhone::convertSnomPhoneIdToInt($_id);
        $where[] = $this->_db->quoteInto('snomphone_id = ?', $phoneId);

        $this->_db->delete(SQL_TABLE_PREFIX . 'snom_lines', $where);
    }    
}
