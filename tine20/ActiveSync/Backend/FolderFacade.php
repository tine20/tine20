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
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * backend device class
 * @package     ActiveSync
 */
class ActiveSync_Backend_FolderFacade implements Syncope_Backend_IFolder
{
    /**
     * @var ActiveSync_Backend_Folder
     */
    var $_backend;

    public function __construct()
    {
        $this->_backend = new ActiveSync_Backend_Folder();
    }
    
    /**
     * Create a new device
     *
     * @param  Syncope_Model_IDevice $_device
     * @return ActiveSync_Model_Device
     */
    public function create(Syncope_Model_IFolder $_folder)
    {
        return $this->_backend->create($_folder);
    }
    
    /**
     * Deletes one or more existing devices
     *
     * @param string|array $_id
     * @return void
     */
    public function delete($_id)
    {
        $this->_backend->delete($_id);
    }
    
    /**
     * Return a single folder
     *
     * @param string $_id
     * @return Syncope_Model_IDevice
     */
    public function get($_id)
    {
        try {
            $folder = $this->_backend->get($_id);
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Syncope_Exception_NotFound($tenf->getMessage());
        }
        
        return $folder;
    }
    
    public function getFolder($_deviceId, $_folderId)
    {
        $deviceId = $_deviceId instanceof Syncope_Model_IDevice ? $_deviceId->id : $_deviceId;

        $folder = $this->_backend->search(new ActiveSync_Model_FolderFilter(array(
            array(
        		'field'     => 'device_id',
        		'operator'  => 'equals',
        		'value'     => $deviceId
            ),
            array(
        		'field'     => 'folderid',
        		'operator'  => 'equals',
        		'value'     => $_folderId
            )
        )))->getFirstRecord();
        
        if (! $folder instanceof Syncope_Model_IFolder) {
            throw new Syncope_Exception_NotFound('folder not found');
        }
        
        return $folder;
        
    }
    
    public function getFolderState($_deviceId, $_class)
    {
        $deviceId = $_deviceId instanceof Syncope_Model_IDevice ? $_deviceId->id : $_deviceId;
    
        $folders = $this->_backend->search(new ActiveSync_Model_FolderFilter(array(
            array(
        		'field'     => 'device_id',
        		'operator'  => 'equals',
        		'value'     => $deviceId
            ),
            array(
        		'field'     => 'class',
        		'operator'  => 'equals',
        		'value'     => $_class
            )
        )));
        
        $result = array();
    
        foreach ($folders as $folder) {
            $result[$folder->folderid] = $folder;
        }
    
        return $result;
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
        
        $this->_backend->deleteByProperty($deviceId, 'device_id');
    }
    
    
    /**
     * Upates an existing persistent record
     *
     * @param  Syncope_Model_IDevice $_device
     * @return ActiveSync_Model_Device
     */
    public function update(Syncope_Model_IFolder $_folder)
    {
        return $this->_backend->update($_folder);
    }
}
