<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * SyncState class
 * @package     ActiveSync
 */
class ActiveSync_Backend_SyncState 
{
    /**
     * the database object of this class
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    public function __construct()
    {
        $this->_db = Tinebase_Core::getDb();
    }
    
    /**
     * read syncstate from database 
     *
     * @param ActiceSync_Model_SyncState $_syncState
     * @return ActiceSync_Model_SyncState
     */
    public function get(ActiveSync_Model_SyncState $_syncState)
    {
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'acsync_synckey')
            ->where('device_id = ?', $_syncState->device_id)
            ->where('type = ?', $_syncState->type)
            ->order('counter DESC')
            ->limit(1);
            
        if(!empty($_syncState->counter)) {
            $select->where('counter = ?', $_syncState->counter);
        }

        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new ActiveSync_Exception_SyncStateNotFound('syncState not found: ' . $select);
        }

        $result = new ActiveSync_Model_SyncState($row);
        
        return $result;
    }
    
    public function create(ActiveSync_Model_SyncState $_syncState)
    {
        $newData = $_syncState->toArray();
        
        $result = $this->_db->insert(SQL_TABLE_PREFIX . 'acsync_synckey', $newData);
        
        return $result;
    }
    
    public function update(ActiveSync_Model_SyncState $_syncState)
    {
        $where = array(
            $this->_db->quoteInto('device_id = ?', $_syncState->device_id), 
            $this->_db->quoteInto('type = ?',      $_syncState->type),
            $this->_db->quoteInto('counter = ?',   $_syncState->counter)
        );
        
        $newData = array(
            'lastsync' => $_syncState->lastsync
        );
        
        $result = $this->_db->update(SQL_TABLE_PREFIX . 'acsync_synckey', $newData, $where);
        
        return $result;
    }
    
    public function delete(ActiveSync_Model_SyncState $_syncState)
    {
        $where = array(
            $this->_db->quoteInto('device_id = ?', $_syncState->device_id), 
            $this->_db->quoteInto('type = ?', $_syncState->type)
        );
        
        $result = $this->_db->delete(SQL_TABLE_PREFIX . 'acsync_synckey', $where);
        
        return $result;
    }
    
    public function deleteOther(ActiveSync_Model_SyncState $_syncState)
    {
        // remove all other synckeys
        $where = array(
            $this->_db->quoteInto('device_id = ?', $_syncState->device_id),
            $this->_db->quoteInto('type = ?',      $_syncState->type),
            $this->_db->quoteInto('counter != ?',  $_syncState->counter)
        );
        
        $this->_db->delete(SQL_TABLE_PREFIX . 'acsync_synckey', $where);
        
        return true;
        
    }
    
}
