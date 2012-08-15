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
class Syncroton_Backend_Folder extends Syncroton_Backend_ABackend implements Syncroton_Backend_IFolder
{
    protected $_tableName = 'folder';
    
    protected $_modelClassName = 'Syncroton_Model_Folder';
    
    protected $_modelInterfaceName = 'Syncroton_Model_IFolder';
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Backend_IFolder::getFolder()
     */
    public function getFolder($deviceId, $folderId)
    {
        $deviceId = $deviceId instanceof Syncroton_Model_IDevice ? $deviceId->id : $deviceId;
        
        $select = $this->_db->select()
            ->from($this->_tablePrefix . $this->_tableName)
            ->where($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId)
            ->where($this->_db->quoteIdentifier('folderid')  . ' = ?', $folderId);
        
        $stmt = $this->_db->query($select);
        $data = $stmt->fetch();
        
        if ($data === false) {
            throw new Syncroton_Exception_NotFound('id not found');
        }
        
        return $this->_getObject($data);
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Backend_IFolder::getFolderState()
     */
    public function getFolderState($deviceId, $class)
    {
        $deviceId = $deviceId instanceof Syncroton_Model_IDevice ? $deviceId->id : $deviceId;
        
        $select = $this->_db->select()
            ->from($this->_tablePrefix . $this->_tableName)
            ->where($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId)
            ->where($this->_db->quoteIdentifier('class')     . ' = ?', $class);
        
        $result = array();
        
        $stmt = $this->_db->query($select);
        while ($data = $stmt->fetch()) {
            $result[$data['folderid']] = $this->_getObject($data); 
        }
        
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Backend_IFolder::resetState()
     */
    public function resetState($deviceId)
    {
        $deviceId = $deviceId instanceof Syncroton_Model_IDevice ? $deviceId->id : $deviceId;
         
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('device_id') . ' = ?', $deviceId)
        );
        
        $this->_db->delete($this->_tablePrefix . $this->_tableName, $where);
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Backend_ABackend::_fromCamelCase()
     */
    protected function _fromCamelCase($string)
    {
        switch ($string) {
            case 'displayName':
            case 'parentId':
                return strtolower($string);
                break;
                
            case 'serverId':
                return 'folderid';
                break;
                
            default:
                return parent::_fromCamelCase($string);
                
                break;
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Backend_ABackend::_toCamelCase()
     */
    protected function _toCamelCase($string, $ucFirst = true)
    {
        switch ($string) {
            case 'displayname':
                return 'displayName';
                break;
                
            case 'parentid':
                return 'parentId';
                break;
                
            case 'folderid':
                return 'serverId';
                break;
            
            default:
                return parent::_toCamelCase($string, $ucFirst);
                
                break;
        }
    }
}
