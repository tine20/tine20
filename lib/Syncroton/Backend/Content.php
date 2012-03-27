<?php
/**
 * Syncroton
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
class Syncroton_Backend_Content implements Syncroton_Backend_IContent
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
     * create new content state
     *
     * @param Syncroton_Model_IContent $_state
     * @return Syncroton_Model_IContent
     */
    public function create(Syncroton_Model_IContent $_state)
    {
        $id = sha1(mt_rand(). microtime());
        
        $deviceId = $_state->device_id instanceof Syncroton_Model_IDevice ? $_state->device_id->id : $_state->device_id;
        $folderId = $_state->folder_id instanceof Syncroton_Model_IFolder ? $_state->folder_id->id : $_state->folder_id;
        
        $this->_db->insert($this->_tablePrefix . 'content', array(
            'id'               => $id, 
            'device_id'        => $deviceId,
            'folder_id'        => $folderId,
            'contentid'        => $_state->contentid,
            'creation_time'    => $_state->creation_time->format('Y-m-d H:i:s'),
            'creation_synckey' => $_state->creation_synckey,
            'is_deleted'       => isset($_state->is_deleted) ? (int)!!$_state->is_deleted : 0
        ));
        
        return $this->get($id);
    }
    
    /**
     * mark state as deleted. The state gets removed finally, 
     * when the synckey gets validated during next sync.
     * 
     * @param Syncroton_Model_IContent|string $_id
     */
    public function delete($_id)
    {
        $id = $_id instanceof Syncroton_Model_IContent ? $_id->id : $_id;
        
        $this->_db->update($this->_tablePrefix . 'content', array(
            'is_deleted' => 1
        ), array(
            'id = ?' => $id
        ));
        
    }
    
    /**
     * @param string  $_id
     * @throws Syncroton_Exception_NotFound
     * @return Syncroton_Model_IContent
     */
    public function get($_id)
    {
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'content')
            ->where('id = ?', $_id);
    
        $stmt = $this->_db->query($select);
        $state = $stmt->fetchObject('Syncroton_Model_Content');
        
        if (! $state instanceof Syncroton_Model_IContent) {
            throw new Syncroton_Exception_NotFound('id not found');
        }
        
        if (!empty($state->creation_time)) {
            $state->creation_time = new DateTime($state->creation_time, new DateTimeZone('utc'));
        }
    
        return $state;
    }
    
    /**
     * @param Syncroton_Model_IDevice|string $_deviceId
     * @param Syncroton_Model_IFolder|string $_folderId
     * @param string $_contentId
     * @return Syncroton_Model_IContent
     */
    public function getContentState($_deviceId, $_folderId, $_contentId)
    {
        $deviceId = $_deviceId instanceof Syncroton_Model_IDevice ? $_deviceId->id : $_deviceId;
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->id : $_folderId;
    
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'content')
            ->where($this->_db->quoteIdentifier('device_id')  . ' = ?', $deviceId)
            ->where($this->_db->quoteIdentifier('folder_id')  . ' = ?', $folderId)
            ->where($this->_db->quoteIdentifier('contentid')  . ' = ?', $_contentId)
            ->where($this->_db->quoteIdentifier('is_deleted') . ' = ?', 0);
    
        $stmt = $this->_db->query($select);
        $state = $stmt->fetchObject('Syncroton_Model_Content');
        
        if (! $state instanceof Syncroton_Model_IContent) {
            throw new Syncroton_Exception_NotFound('id not found');
        }
        
        if (!empty($state->creation_time)) {
            $state->creation_time = new DateTime($state->creation_time, new DateTimeZone('utc'));
        }
    
        return $state;
    }
    
    /**
     * get array of ids which got send to the client for a given class
     *
     * @param Syncroton_Model_IDevice|string $_deviceId
     * @param Syncroton_Model_IFolder|string $_folderId
     * @return array
     */
    public function getFolderState($_deviceId, $_folderId)
    {
        $deviceId = $_deviceId instanceof Syncroton_Model_IDevice ? $_deviceId->id : $_deviceId;
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->id : $_folderId;
                
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'content', 'contentid')
            ->where($this->_db->quoteIdentifier('device_id')  . ' = ?', $deviceId)
            ->where($this->_db->quoteIdentifier('folder_id')  . ' = ?', $folderId)
            ->where($this->_db->quoteIdentifier('is_deleted') . ' = ?', 0);
        
        $stmt = $this->_db->query($select);
        $result = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
    
        return $result;
    }
    
    /**
     * reset list of stored id
     *
     * @param Syncroton_Model_IDevice|string $_deviceId
     * @param Syncroton_Model_IFolder|string $_folderId
     */
    public function resetState($_deviceId, $_folderId)
    {
        $deviceId = $_deviceId instanceof Syncroton_Model_IDevice ? $_deviceId->id : $_deviceId;
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->id : $_folderId;
         
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $folderId)
        );
        
        $this->_db->delete($this->_tablePrefix . 'content', $where);
    }
}
