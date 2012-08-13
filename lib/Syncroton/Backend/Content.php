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
class Syncroton_Backend_Content extends Syncroton_Backend_ABackend implements Syncroton_Backend_IContent
{
    protected $_tableName = 'content';
    
    protected $_modelClassName = 'Syncroton_Model_Content';
    
    protected $_modelInterfaceName = 'Syncroton_Model_IContent';
    
    /**
     * mark state as deleted. The state gets removed finally, 
     * when the synckey gets validated during next sync.
     * 
     * @param Syncroton_Model_IContent|string $_id
     */
    public function delete($id)
    {
        $id = $id instanceof $this->_modelInterfaceName ? $id->id : $id;
        
        $this->_db->update($this->_tablePrefix . 'content', array(
            'is_deleted' => 1
        ), array(
            'id = ?' => $id
        ));
        
    }
    
    /**
     * @param Syncroton_Model_IDevice|string $deviceId
     * @param Syncroton_Model_IFolder|string $folderId
     * @param string $_contentId
     * @return Syncroton_Model_IContent
     */
    public function getContentState($deviceId, $folderId, $contentId)
    {
        $deviceId = $deviceId instanceof Syncroton_Model_IDevice ? $deviceId->id : $deviceId;
        $folderId = $folderId instanceof Syncroton_Model_IFolder ? $folderId->id : $folderId;
    
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'content')
            ->where($this->_db->quoteIdentifier('device_id')  . ' = ?', $deviceId)
            ->where($this->_db->quoteIdentifier('folder_id')  . ' = ?', $folderId)
            ->where($this->_db->quoteIdentifier('contentid')  . ' = ?', $contentId)
            ->where($this->_db->quoteIdentifier('is_deleted') . ' = ?', 0);
    
        $stmt = $this->_db->query($select);
        $data = $stmt->fetch();
        $stmt = null; # see https://bugs.php.net/bug.php?id=44081
        
        if ($data === false) {
            throw new Syncroton_Exception_NotFound('id not found');
        }
        
        return $this->_getObject($data);        
    }
    
    /**
     * get array of ids which got send to the client for a given class
     *
     * @param Syncroton_Model_IDevice|string $deviceId
     * @param Syncroton_Model_IFolder|string $folderId
     * @return array
     */
    public function getFolderState($deviceId, $folderId)
    {
        $deviceId = $deviceId instanceof Syncroton_Model_IDevice ? $deviceId->id : $deviceId;
        $folderId = $folderId instanceof Syncroton_Model_IFolder ? $folderId->id : $folderId;
                
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
     * @param Syncroton_Model_IDevice|string $deviceId
     * @param Syncroton_Model_IFolder|string $folderId
     */
    public function resetState($deviceId, $folderId)
    {
        $deviceId = $deviceId instanceof Syncroton_Model_IDevice ? $deviceId->id : $deviceId;
        $folderId = $folderId instanceof Syncroton_Model_IFolder ? $folderId->id : $folderId;
         
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $folderId)
        );
        
        $this->_db->delete($this->_tablePrefix . 'content', $where);
    }
}
