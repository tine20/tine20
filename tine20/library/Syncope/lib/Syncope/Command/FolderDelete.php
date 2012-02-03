<?php
/**
 * Syncope
 *
 * @package     Syncope
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync FolderDelete command
 *
 * @package     Syncope
 * @subpackage  Command
 */
class Syncope_Command_FolderDelete extends Syncope_Command_Wbxml 
{        
    protected $_defaultNameSpace    = 'uri:FolderHierarchy';
    protected $_documentElement     = 'FolderDelete';
    
    protected $_classes             = array(
        Syncope_Data_Factory::CLASS_CALENDAR,
        Syncope_Data_Factory::CLASS_CONTACTS,
        Syncope_Data_Factory::CLASS_EMAIL,
        Syncope_Data_Factory::CLASS_TASKS
    );
    
    protected $_serverId;
    
    /**
     * @var Syncope_Model_ISyncState
     */
    protected $_syncState;
    
    /**
     * parse FolderDelete request
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_inputDom);
        
        $syncKey  = (int)$xml->SyncKey;
        $folderId = (string)$xml->ServerId;
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " synckey is $syncKey");        
        
        $this->_syncState = $this->_syncStateBackend->validate($this->_device, 'FolderSync', $syncKey);
        
        try {
            $this->_folder = $this->_folderBackend->getFolder($this->_device, $folderId);
            
            $dataController = Syncope_Data_Factory::factory($this->_folder->class, $this->_device, $this->_syncTimeStamp);
            
            $dataController->deleteFolder($this->_folder);
            $this->_folderBackend->delete($this->_folder);
            
        } catch (Syncope_Exception_NotFound $senf) {
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
        
        if($this->_syncState == false) {
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " INVALID synckey");
            $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', Syncope_Command_FolderSync::STATUS_INVALID_SYNC_KEY));
        } else {
            if ($this->_folder instanceof Syncope_Model_IFolder) {
                $this->_syncState->counter++;
                
                // create xml output
                $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', Syncope_Command_FolderSync::STATUS_SUCCESS));
                $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $this->_syncState->counter));
                
                $this->_syncStateBackend->update($this->_syncState);
            } else {
                // create xml output
                $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', Syncope_Command_FolderSync::STATUS_FOLDER_NOT_FOUND));
                $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $this->_syncState->counter));
            }
        }
        
        return $this->_outputDom;
    }
}
