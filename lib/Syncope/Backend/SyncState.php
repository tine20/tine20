<?php
/**
 * Tine 2.0
 *
 * @package     Syncope
 * @subpackage  Backend
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Syncope module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * sql backend class for the folder state
 *
 * @package     Syncope
 * @subpackage  Backend
 */
class Syncope_Backend_SyncState implements Syncope_Backend_ISyncState
{
    /**
     * the database adapter
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    public function __construct(Zend_Db_Adapter_Abstract $_db)
    {
        $this->_db = $_db;
    }
    
    /**
     * create new sync state
     *
     * @param Syncope_Model_ISyncState $_syncState
     * @return Syncope_Model_ISyncState
     */
    public function create(Syncope_Model_ISyncState $_syncState, $_keepPreviousSyncState = true)
    {
        $id = sha1(mt_rand(). microtime());
        $deviceId = $_syncState->device_id instanceof Syncope_Model_IDevice ? $_syncState->device_id->id : $_syncState->device_id;
    
        $this->_db->insert('syncope_synckeys', array(
        	'id'          => $id, 
        	'device_id'   => $deviceId,
        	'type'        => $_syncState->type,
        	'counter'     => $_syncState->counter,
        	'lastsync'    => $_syncState->lastsync->format('Y-m-d H:i:s'),
        	'pendingdata' => isset($_syncState->pendingdata) && is_array($_syncState->pendingdata) ? Zend_Json::encode($_syncState->pendingdata) : null
        ));

        $state = $this->get($id);
        
        if ($_keepPreviousSyncState !== true) {
            // remove all other synckeys
            $this->_deleteOtherStates($state);
        }
        
        return $state;
    }
    
    protected function _deleteOtherStates(Syncope_Model_ISyncState $_state)
    {
        // remove all other synckeys
        $where = array(
            'device_id = ?' => $_state->device_id,
            'type = ?'      => $_state->type,
            'counter != ?'  => $_state->counter
        );
    
        $this->_db->delete('syncope_synckeys', $where);
    
        return true;
    
    }
    
    /**
     * @param string  $_id
     * @throws Syncope_Exception_NotFound
     * @return Syncope_Model_ISyncState
     */
    public function get($_id)
    {
        $select = $this->_db->select()
            ->from('syncope_synckeys')
            ->where('id = ?', $_id);
    
        $stmt = $this->_db->query($select);
        $state = $stmt->fetchObject('Syncope_Model_SyncState');
        
        if (! $state instanceof Syncope_Model_ISyncState) {
            throw new Syncope_Exception_NotFound('id not found');
        }
        
        if (!empty($state->lastsync)) {
            $state->lastsync = new DateTime($state->lastsync, new DateTimeZone('utc'));
        }
        if (!empty($state->pendingdata)) {
            $state->pendingdata = Zend_Json::encode($state->pendingdata);
        }
        
        return $state;
    }
    
    /**
     * delete all stored folderId's for given device
     *
     * @param Syncope_Model_Device|string $_deviceId
     * @param string $_class
     */
    public function resetState($_deviceId, $_type)
    {
        $deviceId = $_deviceId instanceof Syncope_Model_IDevice ? $_deviceId->id : $_deviceId;
         
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('type') . ' = ?',      $_type)
        );
    
        $this->_db->delete('syncope_synckeys', $where);
    }
    
    public function update(Syncope_Model_ISyncState $_syncState)
    {
        $deviceId = $_syncState->device_id instanceof Syncope_Model_IDevice ? $_syncState->device_id->id : $_syncState->device_id;
        
        $this->_db->update('syncope_synckeys', array(
        	'counter'     => $_syncState->counter,
        	'lastsync'    => $_syncState->lastsync->format('Y-m-d H:i:s'),
        	'pendingdata' => isset($_syncState->pendingdata) ? $_syncState->pendingdata : null
        ), array(
        	'id = ?' => $_syncState->id
        ));
        
        return $this->get($_syncState->id);
    }
    
    /**
     * get array of ids which got send to the client for a given class
     *
     * @param Syncope_Model_Device|string $_deviceId
     * @param string $_class
     * @return Syncope_Model_ISyncState
     */
    public function validate($_deviceId, $_syncKey, $_class, $_collectionId = NULL)
    {
        $deviceId = $_deviceId instanceof Syncope_Model_IDevice ? $_deviceId->id : $_deviceId;
        $type     = $_collectionId !== NULL ? $_class . '-' . $_collectionId : $_class;
        
        $select = $this->_db->select()
            ->from('syncope_synckeys')
            ->where($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId)
            ->where($this->_db->quoteIdentifier('counter') . ' = ?', $_syncKey)
            ->where($this->_db->quoteIdentifier('type') . ' = ?', $type);
        
        $stmt = $this->_db->query($select);
        $state = $stmt->fetchObject('Syncope_Model_SyncState');
        
        if (! $state instanceof Syncope_Model_ISyncState) {
            return false;
        }

        if (!empty($state->lastsync)) {
            $state->lastsync = new DateTime($state->lastsync, new DateTimeZone('utc'));
        }
        if (!empty($state->pendingdata)) {
            $state->pendingdata = Zend_Json::encode($state->pendingdata);
        }
        
        // check if this was the latest syncKey
        $select = $this->_db->select()
            ->from('syncope_synckeys')
            ->where($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId)
            ->where($this->_db->quoteIdentifier('counter') . ' = ?', $_syncKey + 1)
            ->where($this->_db->quoteIdentifier('type') . ' = ?', $type);
        
        $stmt = $this->_db->query($select);
        $moreRecentState = $stmt->fetchObject('Syncope_Model_SyncState');
        
        // found more recent synckey => the last sync repsone got not received by the client
        if ($moreRecentState instanceof Syncope_Model_ISyncState) {
            // undelete entries marked as deleted in syncope_contentstates table
            $this->_db->update('syncope_contentstates', array(
            	'is_deleted'  => 0,
            ), array(
            	'device_id = ?'    => $deviceId,
            	'class = ?'        => $_class,
            	'collectionid = ?' => $_collectionId,
            	'is_deleted = ?'   => 1
            ));
            
            // remove entries added during latest sync in syncope_contentstates table
            $this->_db->delete('syncope_contentstates', array(
            	'device_id = ?'     => $deviceId,
            	'class = ?'         => $_class,
            	'collectionid = ?'  => $_collectionId,
            	'creation_time > ?' => $state->lastsync->format('Y-m-d H:i:s'),
            ));
            
        } else {
            // finaly delete all entries marked for removal in syncope_contentstates table    
            $this->_db->delete('syncope_contentstates', array(
            	'device_id = ?'     => $deviceId,
            	'class = ?'         => $_class,
            	'collectionid = ?'  => $_collectionId,
            	'is_deleted = ?'    => 1
            ));
            
        }
        
        // remove all other synckeys
        $this->_deleteOtherStates($state);
        
        return $state;
    }
}
