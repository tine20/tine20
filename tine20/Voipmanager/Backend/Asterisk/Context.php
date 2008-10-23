<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
 *
 */


/**
 * Asterisk context sql backend
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Asterisk_Context
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
	 * search context
	 * 
     * @param Voipmanager_Model_AsteriskContextFilter $_filter
     * @param Tinebase_Model_Pagination $_pagination
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskContext
	 */
    public function search(Voipmanager_Model_AsteriskContextFilter $_filter, Tinebase_Model_Pagination $_pagination = NULL)
    {	
        $where = array();
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'asterisk_context');

        if($_pagination instanceof Tinebase_Model_Pagination) {            
            $_pagination->appendPagination($select);
        }

        if(!empty($_filter->query)) {
            $select->where($this->_db->quoteInto('(name LIKE ? OR description LIKE ? )', '%' . $_filter->query . '%'));
        } else {
            // handle the other fields separately
        }
       
        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_AsteriskContext', $rows);
		
        return $result;
	}  
  
      
	/**
	 * get context by id
	 * 
     * @param string $_id
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskContext
	 */
    public function get($_id)
    {	
        $contextId = Voipmanager_Model_AsteriskContext::convertAsteriskContextIdToInt($_id);
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'asterisk_context')->where($this->_db->quoteInto('id = ?', $contextId));
        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new UnderflowException('context not found');
        }
#       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_AsteriskContext', $row);
        $result = new Voipmanager_Model_AsteriskContext($row);
        return $result;
	}
	   
    /**
     * add new context
     *
     * @param Voipmanager_Model_AsteriskContext $_context the context data
     * @return Voipmanager_Model_AsteriskContext
     */
    public function create(Voipmanager_Model_AsteriskContext $_context)
    {
        if (! $_context->isValid()) {
            throw new Exception('invalid context');
        }

        if ( empty($_context->id) ) {
            $_context->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $context = $_context->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'asterisk_context', $context);

        return $this->get($_context->getId());
    }
    
    /**
     * update an existing context
     *
     * @param Voipmanager_Model_AsteriskContext $_context the context data
     * @return Voipmanager_Model_AsteriskContext
     */
    public function update(Voipmanager_Model_AsteriskContext $_context)
    {
        if (! $_context->isValid()) {
            throw new Exception('invalid context');
        }
        $contextId = $_context->getId();
        $contextData = $_context->toArray();
        unset($contextData['id']);

        $where = array($this->_db->quoteInto('id = ?', $contextId));
        $this->_db->update(SQL_TABLE_PREFIX . 'asterisk_context', $contextData, $where);
        
        return $this->get($contextId);
    }    


    /**
     * delete context(s) identified by context id
     *
     * @param string|array|Tinebase_Record_RecordSet $_id
     * @return void
     */
    public function delete($_id)
    {
        foreach ((array)$_id as $id) {
            $contextId = Voipmanager_Model_AsteriskContext::convertAsteriskContextIdToInt($id);
            $where[] = $this->_db->quoteInto('id = ?', $contextId);
        }

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

            // NOTE: using array for second argument won't work as delete function joins array items using "AND"
            foreach($where AS $where_atom)
            {
                $this->_db->delete(SQL_TABLE_PREFIX . 'asterisk_context', $where_atom);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }  
}
