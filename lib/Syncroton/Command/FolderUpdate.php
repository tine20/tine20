<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync FolderUpdate command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_FolderUpdate extends Syncroton_Command_Wbxml 
{        
    protected $_defaultNameSpace    = 'uri:FolderHierarchy';
    protected $_documentElement     = 'FolderUpdate';
    
    /**
     * @var Syncroton_Model_SyncState
     */
    protected $_folder;
    
    /**
     * 
     * @var Syncroton_Model_Folder
     */
    protected $_folderUpdate;
    
    /**
     * parse FolderUpdate request
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_requestBody);
        
        $syncKey = (int)$xml->SyncKey;
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " synckey is $syncKey");

        if (!($this->_syncState = $this->_syncStateBackend->validate($this->_device, 'FolderSync', $syncKey)) instanceof Syncroton_Model_SyncState) {
            return;
        }
        
        $updatedFolder = new Syncroton_Model_Folder($xml);
        
        if ($this->_logger instanceof Zend_Log)
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " parentId: {$updatedFolder->parentId} displayName: {$updatedFolder->displayName}");
        
        try {
            $folder = $this->_folderBackend->getFolder($this->_device, $updatedFolder->serverId);
            
            $folder->displayName = $updatedFolder->displayName;
            $folder->parentId    = $updatedFolder->parentId;
            
            $dataController = Syncroton_Data_Factory::factory($folder->class, $this->_device, $this->_syncTimeStamp);
            
            // update folder in data backend
            $dataController->updateFolder($folder);
            
        } catch (Syncroton_Exception_NotFound $senf) {
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " " . $senf->getMessage());
            
            return;
        }
        
        // update folder status in Syncroton backend
        $this->_folder = $this->_folderBackend->update($folder);
    }
    
    /**
     * generate FolderUpdate response
     *
     * @todo currently we support only the main folder which contains all contacts/tasks/events/notes per class
     */
    public function getResponse($_keepSession = FALSE)
    {
        $folderUpdate = $this->_outputDom->documentElement;
        
        if (!$this->_syncState instanceof Syncroton_Model_SyncState) {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " invalid synckey provided. FolderSync 0 needed.");
            $folderUpdate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status',  Syncroton_Command_FolderSync::STATUS_INVALID_SYNC_KEY));
            
        } elseif (!$this->_folder instanceof Syncroton_Model_IFolder) {
            $folderUpdate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', Syncroton_Command_FolderSync::STATUS_FOLDER_NOT_FOUND));
            
        } else {
            $this->_syncState->counter++;
            $this->_syncState->lastsync = $this->_syncTimeStamp;
            
            // store folder in state backend
            $this->_syncStateBackend->update($this->_syncState);
                        
            // create xml output
            $folderUpdate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status',  Syncroton_Command_FolderSync::STATUS_SUCCESS));
            $folderUpdate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $this->_syncState->counter));
        }
        
        return $this->_outputDom;
    }    
}
