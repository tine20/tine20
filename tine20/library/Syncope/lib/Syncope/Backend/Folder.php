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
class Syncope_Backend_Folder implements Syncope_Backend_IFolder
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
        $this->_db = $_db;
        
        $this->_tablePrefix = $_tablePrefix;
    }
    
    /**
     * create new folder state
     *
     * @param Syncope_Model_IFolder $_folder
     * @return Syncope_Model_IFolder
     */
    public function create(Syncope_Model_IFolder $_folder)
    {
        $id = sha1(mt_rand(). microtime());
        $deviceId = $_folder->device_id instanceof Syncope_Model_IDevice ? $_folder->device_id->id : $_folder->device_id;
    
        $this->_db->insert($this->_tablePrefix . 'folder', array(
            'id'             => $id, 
            'device_id'      => $deviceId,
            'class'          => $_folder->class,
            'folderid'       => $_folder->folderid instanceof Syncope_Model_IFolder ? $_folder->folderid->id : $_folder->folderid,
            'parentid'       => $_folder->parentid,
            'displayname'    => $_folder->displayname,
            'type'           => $_folder->type,
            'creation_time'  => $_folder->creation_time->format('Y-m-d H:i:s'),
            'lastfiltertype' => $_folder->lastfiltertype
        ));
        
        return $this->get($id);
    }
    
    public function delete($_id)
    {
        $id = $_id instanceof Syncope_Model_IFolder ? $_id->id : $_id;
    
        $result = $this->_db->delete($this->_tablePrefix . 'folder', array('id = ?' => $id));
    
        return (bool) $result;
    }
    
    /**
     * @param string  $_id
     * @throws Syncope_Exception_NotFound
     * @return Syncope_Model_IFolder
     */
    public function get($_id)
    {
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'folder')
            ->where('id = ?', $_id);
    
        $stmt = $this->_db->query($select);
        $state = $stmt->fetchObject('Syncope_Model_Folder');
        
        if (! $state instanceof Syncope_Model_IFolder) {
            throw new Syncope_Exception_NotFound('id not found');
        }
        
        if (!empty($state->creation_time)) {
            $state->creation_time = new DateTime($state->creation_time, new DateTimeZone('utc'));
        }
    
        return $state;
    }
    
    /**
     * delete all stored folderId's for given device
     *
     * @param Syncope_Model_Device|string $_deviceId
     * @param string $_class
     */
    public function resetState($_deviceId)
    {
        $deviceId = $_deviceId instanceof Syncope_Model_IDevice ? $_deviceId->id : $_deviceId;
         
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId)
        );
        
        $this->_db->delete($this->_tablePrefix . 'folder', $where);
    }
    
    public function update(Syncope_Model_IFolder $_folder)
    {
        $deviceId = $_folder->device_id instanceof Syncope_Model_IDevice ? $_folder->device_id->id : $_folder->device_id;
    
        $this->_db->update($this->_tablePrefix . 'folder', array(
            'lastfiltertype'     => $_folder->lastfiltertype
        ), array(
            'id = ?' => $_folder->id
        ));
    
        return $this->get($_folder->id);
    }
    
    /**
     * get array of ids which got send to the client for a given class
     *
     * @param Syncope_Model_Device|string $_deviceId
     * @param string $_class
     * @return array
     */
    public function getFolderState($_deviceId, $_class)
    {
        $deviceId = $_deviceId instanceof Syncope_Model_IDevice ? $_deviceId->id : $_deviceId;
        
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'folder')
            ->where($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId)
            ->where($this->_db->quoteIdentifier('class') . ' = ?', $_class);
        
        $result = array();
        
        $stmt = $this->_db->query($select);
        while ($row = $stmt->fetchObject("Syncope_Model_Folder")) {
            $result[$row->folderid] = $row; 
        }
        
        return $result;
    }
    
    /**
     * get folder indentified by $_folderId
     *
     * @param  Syncope_Model_Device|string  $_deviceId
     * @param  string                       $_folderId
     * @return Syncope_Model_IFolder
     */
    public function getFolder($_deviceId, $_folderId)
    {
        $deviceId = $_deviceId instanceof Syncope_Model_IDevice ? $_deviceId->id : $_deviceId;
        
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'folder')
            ->where($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId)
            ->where($this->_db->quoteIdentifier('folderid') . ' = ?', $_folderId);
        
        $stmt = $this->_db->query($select);
        $folder = $stmt->fetchObject('Syncope_Model_Folder');
        
        if (! $folder instanceof Syncope_Model_IFolder) {
            throw new Syncope_Exception_NotFound('folder not found');
        }
        
        if (!empty($folder->creation_time)) {
            $folder->creation_time = new DateTime($folder->creation_time, new DateTimeZone('utc'));
        }
        
        return $folder;
    }
}
