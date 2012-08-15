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
 * class to handle ActiveSync FolderDelete command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_FolderDelete extends Syncroton_Command_Wbxml 
{        
    protected $_defaultNameSpace    = 'uri:FolderHierarchy';
    protected $_documentElement     = 'FolderDelete';
    
    protected $_classes             = array(
        Syncroton_Data_Factory::CLASS_CALENDAR,
        Syncroton_Data_Factory::CLASS_CONTACTS,
        Syncroton_Data_Factory::CLASS_EMAIL,
        Syncroton_Data_Factory::CLASS_TASKS
    );
    
    protected $_serverId;
    
    /**
     * @var Syncroton_Model_ISyncState
     */
    protected $_syncState;
    
    /**
     * parse FolderDelete request
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_requestBody);
        
        $syncKey = (int)$xml->SyncKey;
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " synckey is $syncKey");
        
        if (!($this->_syncState = $this->_syncStateBackend->validate($this->_device, 'FolderSync', $syncKey)) instanceof Syncroton_Model_SyncState) {
        
            $this->_syncStateBackend->resetState($this->_device, 'FolderSync');
        
            return;
        }
        
        $serverId = (string)$xml->ServerId;
        
        try {
            $this->_folder = $this->_folderBackend->getFolder($this->_device, $serverId);
            
            $dataController = Syncroton_Data_Factory::factory($this->_folder->class, $this->_device, $this->_syncTimeStamp);
            
            $dataController->deleteFolder($this->_folder);
            $this->_folderBackend->delete($this->_folder);
            
        } catch (Syncroton_Exception_NotFound $senf) {
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " " . $senf->getMessage());
        }
    }
    
    /**
     * generate FolderDelete response
     *
     * @todo currently we support only the main folder which contains all contacts/tasks/events/notes per class
     */
    public function getResponse()
    {
        $folderDelete = $this->_outputDom->documentElement;
        
        if (!$this->_syncState instanceof Syncroton_Model_SyncState) {
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " invalid synckey provided. FolderSync 0 needed.");
            $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', Syncroton_Command_FolderSync::STATUS_INVALID_SYNC_KEY));
            
        } else {
            if ($this->_folder instanceof Syncroton_Model_IFolder) {
                $this->_syncState->counter++;
                $this->_syncState->lastsync = $this->_syncTimeStamp;
                
                // store folder in state backend
                $this->_syncStateBackend->update($this->_syncState);
                
                // create xml output
                $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', Syncroton_Command_FolderSync::STATUS_SUCCESS));
                $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $this->_syncState->counter));

            } else {
                // create xml output
                $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', Syncroton_Command_FolderSync::STATUS_FOLDER_NOT_FOUND));
                $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $this->_syncState->counter));
            }
        }
        
        return $this->_outputDom;
    }
}
