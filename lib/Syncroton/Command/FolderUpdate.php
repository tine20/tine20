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
    
    protected $_classes             = array('Contacts', 'Tasks', 'Email');
    
    /**
     * synckey sent from client
     *
     * @var string
     */
    protected $_syncKey;
    protected $_parentId;
    protected $_displayName;
    protected $_serverId;
    
    /**
     * parse FolderUpdate request
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_inputDom);
        
        $this->_syncKey     = (int)$xml->SyncKey;
        $this->_parentId    = (string)$xml->ParentId;
        $this->_displayName = (string)$xml->DisplayName;
        $this->_serverId    = (string)$xml->ServerId;
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " synckey is $this->_syncKey parentId $this->_parentId name $this->_displayName");

        $this->_syncState = $this->_syncStateBackend->validate($this->_device, 'FolderSync', $this->_syncKey);
        
        try {
            $this->_folder = $this->_folderBackend->getFolder($this->_device, $this->_serverId);
            
            $dataController = Syncroton_Data_Factory::factory($this->_folder->class, $this->_device, $this->_syncTimeStamp);
            
            $dataController->deleteFolder($this->_folder);
            $this->_folderBackend->delete($this->_folder);
            
        } catch (Syncroton_Exception_NotFound $senf) {
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " " . $senf->getMessage());
        }
    }
    
    /**
     * generate FolderUpdate response
     *
     * @todo currently we support only the main folder which contains all contacts/tasks/events/notes per class
     */
    public function getResponse($_keepSession = FALSE)
    {
        $folderUpdate = $this->_outputDom->documentElement;
        
        if($this->_syncState == false) {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " INVALID synckey");
            $folderUpdate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', Syncroton_Command_FolderSync::STATUS_INVALID_SYNC_KEY));
        } else {
            $this->_syncState->counter++;
            
            // create xml output
            $folderUpdate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status',  Syncroton_Command_FolderSync::STATUS_SUCCESS));
            $folderUpdate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $this->_syncState->counter));
            
            $this->_syncStateBackend->update($this->_syncState);
        }
        
        return $this->_outputDom;
    }    
}
