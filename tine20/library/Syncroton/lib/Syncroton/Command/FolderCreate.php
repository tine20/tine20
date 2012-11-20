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
 * class to handle ActiveSync FolderSync command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_FolderCreate extends Syncroton_Command_Wbxml 
{        
    protected $_defaultNameSpace    = 'uri:FolderHierarchy';
    protected $_documentElement     = 'FolderCreate';
    
    /**
     * 
     * @var Syncroton_Model_Folder
     */
    protected $_folder;
    
    /**
     * parse FolderCreate request
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
        
        $folder   = new Syncroton_Model_Folder($xml);
        
        if ($this->_logger instanceof Zend_Log)
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " parentId: {$folder->parentId} displayName: {$folder->displayName}");
        
        switch($folder->type) {
            case Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR_USER_CREATED:
                $folder->class = Syncroton_Data_Factory::CLASS_CALENDAR;
                break;
                
            case Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED:
                $folder->class = Syncroton_Data_Factory::CLASS_CONTACTS;
                break;
                
            case Syncroton_Command_FolderSync::FOLDERTYPE_MAIL_USER_CREATED:
                $folder->class = Syncroton_Data_Factory::CLASS_EMAIL;
                break;
                
            case Syncroton_Command_FolderSync::FOLDERTYPE_TASK_USER_CREATED:
                $folder->class = Syncroton_Data_Factory::CLASS_TASKS;
                break;
                
            default:
                throw new Syncroton_Exception_UnexpectedValue('invalid type defined');
                break;
        }
        
        
        $dataController = Syncroton_Data_Factory::factory($folder->class, $this->_device, $this->_syncTimeStamp);
        
        $this->_folder = $dataController->createFolder($folder);
        $this->_folder->class        = $folder->class;
        $this->_folder->deviceId     = $this->_device;
        $this->_folder->creationTime = $this->_syncTimeStamp;
        
        $this->_folderBackend->create($this->_folder);
    }
    
    /**
     * generate FolderCreate response
     */
    public function getResponse()
    {
        $folderCreate = $this->_outputDom->documentElement;
        
        if (!$this->_syncState instanceof Syncroton_Model_SyncState) {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " invalid synckey provided. FolderSync 0 needed.");
            $folderCreate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', Syncroton_Command_FolderSync::STATUS_INVALID_SYNC_KEY));
            
        } else {
            $this->_syncState->counter++;
            $this->_syncState->lastsync = $this->_syncTimeStamp;
            
            // store folder in state backend
            $this->_syncStateBackend->update($this->_syncState);
            
            // create xml output
            $folderCreate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status',   Syncroton_Command_FolderSync::STATUS_SUCCESS));
            $folderCreate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey',  $this->_syncState->counter));
            $folderCreate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'ServerId', $this->_folder->serverId));
        }
        
        return $this->_outputDom;
    }
}
