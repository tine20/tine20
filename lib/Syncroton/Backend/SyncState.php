<?php
/**
 * Tine 2.0
 *
 * @package     Syncroton
 * @subpackage  Backend
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * sql backend class for the folder state
 *
 * @package     Syncroton
 * @subpackage  Backend
 */
class Syncroton_Backend_SyncState implements Syncroton_Backend_ISyncState
{
    /**
     * the database adapter
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    protected $_tablePrefix;
    
    public function __construct(Zend_Db_Adapter_Abstract $_db, $_tablePrefix = 'Syncroton_')
    {
        $this->_db          = $_db;
        $this->_tablePrefix = $_tablePrefix;
    }
    
    /**
     * create new sync state
     *
     * @param Syncroton_Model_ISyncState $_syncState
     * @return Syncroton_Model_SyncState
     */
    public function create(Syncroton_Model_ISyncState $_syncState, $_keepPreviousSyncState = true)
    {
        $id = sha1(mt_rand(). microtime());
        $deviceId = $_syncState->device_id instanceof Syncroton_Model_IDevice ? $_syncState->device_id->id : $_syncState->device_id;
    
        $this->_db->insert($this->_tablePrefix . 'synckey', array(
            'id'          => $id, 
            'device_id'   => $deviceId,
            'type'        => $_syncState->type instanceof Syncroton_Model_IFolder ? $_syncState->type->id : $_syncState->type,
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
    
    protected function _deleteOtherStates(Syncroton_Model_ISyncState $_state)
    {
        // remove all other synckeys
        $where = array(
            'device_id = ?' => $_state->device_id,
            'type = ?'      => $_state->type,
            'counter != ?'  => $_state->counter
        );
    
        $this->_db->delete($this->_tablePrefix . 'synckey', $where);
    
        return true;
    
    }
    
    /**
     * @param string  $_id
     * @throws Syncroton_Exception_NotFound
     * @return Syncroton_Model_SyncState
     */
    public function get($_id)
    {
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'synckey')
            ->where('id = ?', $_id);
    
        $stmt = $this->_db->query($select);
        $state = $stmt->fetchObject('Syncroton_Model_SyncState');
        $stmt = null; # see https://bugs.php.net/bug.php?id=44081
        
        if (! $state instanceof Syncroton_Model_ISyncState) {
            throw new Syncroton_Exception_NotFound('id not found');
        }
        
        $this->_convertFields($state);
        
        return $state;
    }
    
    protected function _convertFields(Syncroton_Model_SyncState $state)
    {
        if (!empty($state->lastsync)) {
            $state->lastsync = new DateTime($state->lastsync, new DateTimeZone('utc'));
        }
        if ($state->pendingdata !== NULL) {
            $state->pendingdata = Zend_Json::decode($state->pendingdata);
        }
    }
    
    /**
     * always returns the latest syncstate
     * 
     * @param  Syncroton_Model_IDevice|string  $_deviceId
     * @param  Syncroton_Model_IFolder|string  $_folderId
     * @return Syncroton_Model_SyncState
     */
    public function getSyncState($_deviceId, $_folderId)
    {
        $deviceId = $_deviceId instanceof Syncroton_Model_IDevice ? $_deviceId->id : $_deviceId;
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->id : $_folderId;
    
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'synckey')
            ->where($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId)
            ->where($this->_db->quoteIdentifier('type')      . ' = ?', $folderId)
            ->order('counter DESC')
            ->limit(1);
        
        $stmt = $this->_db->query($select);
        $state = $stmt->fetchObject('Syncroton_Model_SyncState');
        $stmt = null; # see https://bugs.php.net/bug.php?id=44081
        
        if (! $state instanceof Syncroton_Model_ISyncState) {
            throw new Syncroton_Exception_NotFound('id not found');
        }
        
        $this->_convertFields($state);
        
        return $state;
    }
    
    /**
     * delete all stored synckeys for given type
     *
     * @param  Syncroton_Model_IDevice|string  $_deviceId
     * @param  Syncroton_Model_IFolder|string  $_folderId
     */
    public function resetState($_deviceId, $_folderId)
    {
        $deviceId = $_deviceId instanceof Syncroton_Model_IDevice ? $_deviceId->id : $_deviceId;
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->id : $_folderId;
         
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('type') . ' = ?',      $folderId)
        );
    
        $this->_db->delete($this->_tablePrefix . 'synckey', $where);
    }
    
    public function update(Syncroton_Model_ISyncState $_syncState)
    {
        $deviceId = $_syncState->device_id instanceof Syncroton_Model_IDevice ? $_syncState->device_id->id : $_syncState->device_id;
        
        $this->_db->update($this->_tablePrefix . 'synckey', array(
            'counter'     => $_syncState->counter,
            'lastsync'    => $_syncState->lastsync->format('Y-m-d H:i:s'),
            'pendingdata' => isset($_syncState->pendingdata) && is_array($_syncState->pendingdata) ? Zend_Json::encode($_syncState->pendingdata) : null
        ), array(
            'id = ?' => $_syncState->id
        ));
        
        return $this->get($_syncState->id);
    }
    
    /**
     * get array of ids which got send to the client for a given class
     *
     * @param  Syncroton_Model_IDevice|string  $_deviceId
     * @param  Syncroton_Model_IFolder|string  $_folderId
     * @return Syncroton_Model_SyncState
     */
    public function validate($_deviceId, $_folderId, $_syncKey)
    {
        $deviceId = $_deviceId instanceof Syncroton_Model_IDevice ? $_deviceId->id : $_deviceId;
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->id : $_folderId;
        
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'synckey')
            ->where($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId)
            ->where($this->_db->quoteIdentifier('counter')   . ' = ?', $_syncKey)
            ->where($this->_db->quoteIdentifier('type')      . ' = ?', $folderId);
        
        $stmt = $this->_db->query($select);
        $state = $stmt->fetchObject('Syncroton_Model_SyncState');
        $stmt = null; # see https://bugs.php.net/bug.php?id=44081
        
        if (! $state instanceof Syncroton_Model_ISyncState) {
            return false;
        }

        $this->_convertFields($state);
        
        // check if this was the latest syncKey
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'synckey')
            ->where($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId)
            ->where($this->_db->quoteIdentifier('counter') . ' = ?', $_syncKey + 1)
            ->where($this->_db->quoteIdentifier('type') . ' = ?', $folderId);
        
        $stmt = $this->_db->query($select);
        $moreRecentState = $stmt->fetchObject('Syncroton_Model_SyncState');
        $stmt = null; # see https://bugs.php.net/bug.php?id=44081
        
        // found more recent synckey => the last sync repsone got not received by the client
        if ($moreRecentState instanceof Syncroton_Model_ISyncState) {
            // undelete entries marked as deleted in Syncroton_content table
            $this->_db->update($this->_tablePrefix . 'content', array(
                'is_deleted'  => 0,
            ), array(
                'device_id = ?'        => $deviceId,
                'folder_id = ?'        => $folderId,
                'creation_synckey = ?' => $state->counter,
                'is_deleted = ?'       => 1
            ));
            
            // remove entries added during latest sync in Syncroton_content table
            $this->_db->delete($this->_tablePrefix . 'content', array(
                'device_id = ?'        => $deviceId,
                'folder_id = ?'        => $folderId,
                'creation_synckey > ?' => $state->counter,
            ));
            
        } else {
            // finaly delete all entries marked for removal in Syncroton_content table    
            $this->_db->delete($this->_tablePrefix . 'content', array(
                'device_id = ?'  => $deviceId,
                'folder_id = ?'  => $folderId,
                'is_deleted = ?' => 1
            ));
            
        }
        
        // remove all other synckeys
        $this->_deleteOtherStates($state);
        
        return $state;
    }
}
