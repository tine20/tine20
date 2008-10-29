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
 * @todo        extend Tinebase_Application_Backend_Sql_Abstract
 */


/**
 * Asterisk voicemail sql backend
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Asterisk_Voicemail
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
	 * search voicemail
	 * 
     * @param Voipmanager_Model_AsteriskVoicemailFilter $_filter
     * @param Tinebase_Model_Pagination $_pagination
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskVoicemail
	 */
    public function search(Voipmanager_Model_AsteriskVoicemailFilter $_filter, Tinebase_Model_Pagination $_pagination = NULL)
    {	
        $where = array();
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'asterisk_voicemail');
            
        if($_pagination instanceof Tinebase_Model_Pagination) {
            $_pagination->appendPagination($select);
        }

        if(!empty($_filter->query)) {
            $select->where($this->_db->quoteInto('(context LIKE ? OR mailbox LIKE ? OR fullname LIKE ? OR email LIKE ? OR pager LIKE ? )', '%' . $_filter->query . '%'));
        } else {
            // handle the other fields separately
        }
        
        if(!empty($_filter->context)) {
            $select->where($this->_db->quoteInto('context = ?', $_filter->context));
        }
       
        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_AsteriskVoicemail', $rows);
		
        return $result;
	}  
  
      
	/**
	 * get voicemail by id
	 * 
     * @param string $_id
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskVoicemail
	 * @throws Voipmanager_Exception_NotFound
	 */
    public function get($_id)
    {	
        $voicemailId = Voipmanager_Model_AsteriskVoicemail::convertAsteriskVoicemailIdToInt($_id);
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'asterisk_voicemail')->where($this->_db->quoteInto('id = ?', $voicemailId));
        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new Voipmanager_Exception_NotFound('voicemail not found');
        }
#       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_AsteriskVoicemail', $row);
        $result = new Voipmanager_Model_AsteriskVoicemail($row);
        return $result;
	}
	   
    /**
     * add new voicemail
     *
     * @param Voipmanager_Model_AsteriskVoicemail $_voicemail the voicemail data
     * @return Voipmanager_Model_AsteriskVoicemail
     * @throws  Voipmanager_Exception_Validation
     */
    public function create(Voipmanager_Model_AsteriskVoicemail $_voicemail)
    {
        if (! $_voicemail->isValid()) {
            throw new Voipmanager_Exception_Validation('invalid voicemail');
        }

        if ( empty($_voicemail->id) ) {
            $_voicemail->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $voicemail = $_voicemail->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'asterisk_voicemail', $voicemail);

        return $this->get($_voicemail->getId());
    }
    
    /**
     * update an existing voicemail
     *
     * @param Voipmanager_Model_AsteriskVoicemail $_voicemail the voicemail data
     * @return Voipmanager_Model_AsteriskVoicemail
     * @throws  Voipmanager_Exception_Validation
     */
    public function update(Voipmanager_Model_AsteriskVoicemail $_voicemail)
    {
        if (! $_voicemail->isValid()) {
            throw new Voipmanager_Exception_Validation('invalid voicemail');
        }
        $voicemailId = $_voicemail->getId();
        $voicemailData = $_voicemail->toArray();
        unset($voicemailData['id']);

        $where = array($this->_db->quoteInto('id = ?', $voicemailId));
        $this->_db->update(SQL_TABLE_PREFIX . 'asterisk_voicemail', $voicemailData, $where);
        
        return $this->get($voicemailId);
    }    


    /**
     * delete voicemail(s) identified by voicemail id
     *
     * @param string|array|Tinebase_Record_RecordSet $_id
     * @return void
     * @throws  Voipmanager_Exception_Backend
     */
    public function delete($_id)
    {
        foreach ((array)$_id as $id) {
            $voicemailId = Voipmanager_Model_AsteriskVoicemail::convertAsteriskVoicemailIdToInt($id);
            $where[] = $this->_db->quoteInto('id = ?', $voicemailId);
        }

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

            // NOTE: using array for second argument won't work as delete function joins array items using "AND"
            foreach($where AS $where_atom)
            {
                $this->_db->delete(SQL_TABLE_PREFIX . 'asterisk_voicemail', $where_atom);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw new Voipmanager_Exception_Backend($e->getMessage());
        }
    }  
}
