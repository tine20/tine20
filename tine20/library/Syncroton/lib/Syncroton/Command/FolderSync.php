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
class Syncroton_Command_FolderSync extends Syncroton_Command_Wbxml 
{
    const STATUS_SUCCESS                = 1;
    const STATUS_FOLDER_EXISTS          = 2;
    const STATUS_IS_SPECIAL_FOLDER      = 3;
    const STATUS_FOLDER_NOT_FOUND       = 4;
    const STATUS_PARENT_FOLDER_NOT_FOUND = 5;
    const STATUS_SERVER_ERROR           = 6;
    const STATUS_ACCESS_DENIED          = 7;
    const STATUS_REQUEST_TIMED_OUT      = 8;
    const STATUS_INVALID_SYNC_KEY       = 9;
    const STATUS_MISFORMATTED           = 10;
    const STATUS_UNKNOWN_ERROR          = 11;

    /**
     * some usefull constants for working with the xml files
     */
    const FOLDERTYPE_GENERIC_USER_CREATED   = 1;
    const FOLDERTYPE_INBOX                  = 2;
    const FOLDERTYPE_DRAFTS                 = 3;
    const FOLDERTYPE_DELETEDITEMS           = 4;
    const FOLDERTYPE_SENTMAIL               = 5;
    const FOLDERTYPE_OUTBOX                 = 6;
    const FOLDERTYPE_TASK                   = 7;
    const FOLDERTYPE_CALENDAR               = 8;
    const FOLDERTYPE_CONTACT                = 9;
    const FOLDERTYPE_NOTE                   = 10;
    const FOLDERTYPE_JOURNAL                = 11;
    const FOLDERTYPE_MAIL_USER_CREATED      = 12;
    const FOLDERTYPE_CALENDAR_USER_CREATED  = 13;
    const FOLDERTYPE_CONTACT_USER_CREATED   = 14;
    const FOLDERTYPE_TASK_USER_CREATED      = 15;
    const FOLDERTYPE_JOURNAL_USER_CREATED   = 16;
    const FOLDERTYPE_NOTES_USER_CREATED     = 17;
    const FOLDERTYPE_UNKOWN                 = 18;
    
    protected $_defaultNameSpace    = 'uri:FolderHierarchy';
    protected $_documentElement     = 'FolderSync';
    
    protected $_classes             = array(
        Syncroton_Data_Factory::CLASS_CALENDAR,
        Syncroton_Data_Factory::CLASS_CONTACTS,
        Syncroton_Data_Factory::CLASS_EMAIL,
        Syncroton_Data_Factory::CLASS_TASKS
    );

    /**
     * @var string
     */
    protected $_syncKey;
    
    /**
     * parse FolderSync request
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_requestBody);
        $syncKey = (int)$xml->SyncKey;
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " synckey is $syncKey");
        
        if ($syncKey === 0) {
            $this->_syncState = new Syncroton_Model_SyncState(array(
                'device_id' => $this->_device,
                'counter'   => 0,
                'type'      => 'FolderSync',
                'lastsync'  => $this->_syncTimeStamp
            ));
            
            // reset state of foldersync
            $this->_syncStateBackend->resetState($this->_device, 'FolderSync');
            
            return;
        } 
        
        if (!($this->_syncState = $this->_syncStateBackend->validate($this->_device, 'FolderSync', $syncKey)) instanceof Syncroton_Model_SyncState) {
            $this->_syncStateBackend->resetState($this->_device, 'FolderSync');
        }
    }
    
    /**
     * generate FolderSync response
     * 
     * @todo changes are missing in response (folder got renamed for example)
     */
    public function getResponse()
    {
        $folderSync = $this->_outputDom->documentElement;
        
        // invalid synckey provided
        if (!$this->_syncState instanceof Syncroton_Model_SyncState) {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " invalid synckey provided. FolderSync 0 needed.");
            $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', self::STATUS_INVALID_SYNC_KEY));

            return $this->_outputDom;
        }

        // send headers from options command also when FolderSync SyncKey is 0
        if ($this->_syncState->counter == 0) {
            $optionsCommand = new Syncroton_Command_Options();
            $this->_headers = array_merge($this->_headers, $optionsCommand->getHeaders());
        }
        
        $adds = array();
        $deletes = array();

        foreach($this->_classes as $class) {
            try {
                $dataController = Syncroton_Data_Factory::factory($class, $this->_device, $this->_syncTimeStamp);
            } catch (Exception $e) {
                // backend not defined
                if ($this->_logger instanceof Zend_Log)
                    $this->_logger->info(__METHOD__ . '::' . __LINE__ . " no data backend defined for class: " . $class);
                continue;
            }

            try {
                // retrieve all folders available in data backend
                $serverFolders = $dataController->getAllFolders();

                // retrieve all folders sent to client
                $clientFolders = $this->_folderBackend->getFolderState($this->_device, $class);
                
            } catch (Exception $e) {
                if ($this->_logger instanceof Zend_Log)
                    $this->_logger->crit(__METHOD__ . '::' . __LINE__ . " Syncing folder hierarchy failed: " . $e->getMessage());
                if ($this->_logger instanceof Zend_Log)
                    $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " Syncing folder hierarchy failed: " . $e->getTraceAsString());

                // The Status element is global for all collections. If one collection fails,
                // a failure status MUST be returned for all collections.
                if ($e instanceof Syncroton_Exception_Status) {
                    $status = $e->getCode();
                } else {
                    $status = Syncroton_Exception_Status_FolderSync::UNKNOWN_ERROR;
                }

                $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', $status));

                return $this->_outputDom;
            }

            $serverFoldersIds = array_keys($serverFolders);

            // is this the first sync?
            if ($this->_syncState->counter == 0) {
                $clientFoldersIds = array();
            } else {
                $clientFoldersIds = array_keys($clientFolders);
            }

            // calculate added entries
            $serverDiff = array_diff($serverFoldersIds, $clientFoldersIds);
            foreach ($serverDiff as $serverFolderId) {
                // have we created a folderObject in syncroton_folder before?
                if (isset($clientFolders[$serverFolderId])) {
                    $add = $clientFolders[$serverFolderId];
                } else {
                    $add = $serverFolders[$serverFolderId];
                    $add->creationTime = $this->_syncTimeStamp;
                    $add->deviceId     = $this->_device;
                    unset($add->id);
                }
                $add->class = $class;

                $adds[] = $add;
            }

            // calculate deleted entries
            $serverDiff = array_diff($clientFoldersIds, $serverFoldersIds);
            foreach ($serverDiff as $serverFolderId) {
                $deletes[] = $clientFolders[$serverFolderId];
            }
        }

        $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', self::STATUS_SUCCESS));

        $count = count($adds) + /*count($changes) + */count($deletes);
        if($count > 0) {
            $this->_syncState->counter++;
            $this->_syncState->lastsync = $this->_syncTimeStamp;
        }
        
        // create xml output
        $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $this->_syncState->counter));
        
        $changes = $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Changes'));            
        $changes->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Count', $count));
        
        foreach($adds as $folder) {
            $add = $changes->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Add'));
            
            $folder->appendXML($add, $this->_device);

            // store folder in backend
            if (empty($folder->id)) {
                $this->_folderBackend->create($folder);
            }
        }
        
        foreach($deletes as $folder) {
            $delete = $changes->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Delete'));
            $delete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'ServerId', $folder->serverId));
            
            $this->_folderBackend->delete($folder);
        }
        
        if (empty($this->_syncState->id)) {
            $this->_syncStateBackend->create($this->_syncState);
        } else {
            $this->_syncStateBackend->update($this->_syncState);
        }
        
        return $this->_outputDom;
    }    
}
