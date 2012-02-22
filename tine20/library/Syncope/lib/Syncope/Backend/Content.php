<?php
/**
 * Tine 2.0
 *
 * @package     Syncope
 * @subpackage  Backend
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
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
class Syncope_Backend_Content implements Syncope_Backend_IContent
{
    /**
     * the database adapter
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    protected $_tablePrefix;
    
    public function __construct(Zend_Db_Adapter_Abstract $_db, $_tablePrefix = 'syncope_')
    {
        $this->_db          = $_db;
        $this->_tablePrefix = $_tablePrefix;
    }
    
    /**
     * create new content state
     *
     * @param Syncope_Model_IContent $_state
     * @return Syncope_Model_IContent
     */
    public function create(Syncope_Model_IContent $_state)
    {
        $id = sha1(mt_rand(). microtime());
        
        $deviceId = $_state->device_id instanceof Syncope_Model_IDevice ? $_state->device_id->id : $_state->device_id;
        $folderId = $_state->folder_id instanceof Syncope_Model_IFolder ? $_state->folder_id->id : $_state->folder_id;
        
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
     * @param Syncope_Model_IContent|string $_id
     */
    public function delete($_id)
    {
        $id = $_id instanceof Syncope_Model_IContent ? $_id->id : $_id;
        
        $this->_db->update($this->_tablePrefix . 'content', array(
            'is_deleted' => 1
        ), array(
            'id = ?' => $id
        ));
        
    }
    
    /**
     * @param string  $_id
     * @throws Syncope_Exception_NotFound
     * @return Syncope_Model_IContent
     */
    public function get($_id)
    {
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'content')
            ->where('id = ?', $_id);
    
        $stmt = $this->_db->query($select);
        $state = $stmt->fetchObject('Syncope_Model_Content');
        
        if (! $state instanceof Syncope_Model_IContent) {
            throw new Syncope_Exception_NotFound('id not found');
        }
        
        if (!empty($state->creation_time)) {
            $state->creation_time = new DateTime($state->creation_time, new DateTimeZone('utc'));
        }
    
        return $state;
    }
    
    /**
     * @param Syncope_Model_IDevice|string $_deviceId
     * @param Syncope_Model_IFolder|string $_folderId
     * @param string $_contentId
     * @return Syncope_Model_IContent
     */
    public function getContentState($_deviceId, $_folderId, $_contentId)
    {
        $deviceId = $_deviceId instanceof Syncope_Model_IDevice ? $_deviceId->id : $_deviceId;
        $folderId = $_folderId instanceof Syncope_Model_IFolder ? $_folderId->id : $_folderId;
    
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'content')
            ->where($this->_db->quoteIdentifier('device_id')  . ' = ?', $deviceId)
            ->where($this->_db->quoteIdentifier('folder_id')  . ' = ?', $folderId)
            ->where($this->_db->quoteIdentifier('contentid')  . ' = ?', $_contentId)
            ->where($this->_db->quoteIdentifier('is_deleted') . ' = ?', 0);
    
        $stmt = $this->_db->query($select);
        $state = $stmt->fetchObject('Syncope_Model_Content');
        
        if (! $state instanceof Syncope_Model_IContent) {
            throw new Syncope_Exception_NotFound('id not found');
        }
        
        if (!empty($state->creation_time)) {
            $state->creation_time = new DateTime($state->creation_time, new DateTimeZone('utc'));
        }
    
        return $state;
    }
    
    /**
     * get array of ids which got send to the client for a given class
     *
     * @param Syncope_Model_IDevice|string $_deviceId
     * @param Syncope_Model_IFolder|string $_folderId
     * @return array
     */
    public function getFolderState($_deviceId, $_folderId)
    {
        $deviceId = $_deviceId instanceof Syncope_Model_IDevice ? $_deviceId->id : $_deviceId;
        $folderId = $_folderId instanceof Syncope_Model_IFolder ? $_folderId->id : $_folderId;
                
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
     * @param Syncope_Model_IDevice|string $_deviceId
     * @param Syncope_Model_IFolder|string $_folderId
     */
    public function resetState($_deviceId, $_folderId)
    {
        $deviceId = $_deviceId instanceof Syncope_Model_IDevice ? $_deviceId->id : $_deviceId;
        $folderId = $_folderId instanceof Syncope_Model_IFolder ? $_folderId->id : $_folderId;
         
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $folderId)
        );
        
        $this->_db->delete($this->_tablePrefix . 'content', $where);
    }
}
