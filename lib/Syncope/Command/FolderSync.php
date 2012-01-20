<?php
/**
 * Tine 2.0
 *
 * @package     Command
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class documentation
 *
 * @package     Command
 */
class Syncope_Command_FolderSync extends Syncope_Command_Wbxml 
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
     *
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
        Syncope_Data_Factory::CALENDAR,
        Syncope_Data_Factory::CONTACTS,
        Syncope_Data_Factory::EMAIL,
        Syncope_Data_Factory::TASKS
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
        $xml = simplexml_import_dom($this->_inputDom);
        $syncKey = (int)$xml->SyncKey;
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
        #    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " synckey is $this->_syncKey");
        
        if ($syncKey == 0) {
            $this->_syncState = new Syncope_Model_SyncState(array(
                'device_id' => $this->_device,
                'counter'   => 0,
                'type'      => 'FolderSync', // this is not the complete type the class is missing, but thats ok in this case
                'lastsync'  => $this->_syncTimeStamp
            ));
        } else {
            if (($this->_syncState = $this->_syncStateBackend->validate($this->_device, $syncKey, 'FolderSync')) instanceof Syncope_Model_SyncState) {
                $this->_syncState->lastsync = $this->_syncTimeStamp;
            } else  {
                $this->_folderStateBackend->resetState($this->_device);
                $this->_syncStateBackend->resetState($this->_device, 'FolderSync');
            }
        }
    }
    
    /**
     * generate FolderSync response
     *
     * @todo currently we support only the main folder which contains all contacts/tasks/events/notes per class
     * 
     * @param boolean $_keepSession keep session active(don't logout user) when true
     */
    public function getResponse($_keepSession = FALSE)
    {
        $folderSync = $this->_outputDom->documentElement;
        
        if($this->_syncState === false) {
            #Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " INVALID synckey provided");
            $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', self::STATUS_INVALID_SYNC_KEY));

        } else {
            $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', self::STATUS_SUCCESS));
            
            $adds = array();
            $deletes = array();
            $count = 0;
            
            foreach($this->_classes as $class) {
                $dataController = Syncope_Data_Factory::factory($class, $this->_device, $this->_syncTimeStamp);

                $folders = $dataController->getAllFolders();

                if($this->_syncState->counter == 0) {
                    foreach($folders as $folderId => $folder) {
                        $adds[$class][$folderId] = $folder;
                        $count++;
                    }
                } else {
                    $allServerEntries = array_keys($folders);
                    $allClientEntries = $this->_folderStateBackend->getClientState($this->_device, $class);
                    
                    // added entries
                    $serverDiff = array_diff($allServerEntries, $allClientEntries);
                    foreach($serverDiff as $folderId) {
                        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " add $class $folderId");
                        $adds[$class][$folderId] = $folders[$folderId];
                        $count++;
                    }
                    
                    // deleted entries
                    $serverDiff = array_diff($allClientEntries, $allServerEntries);
                    foreach($serverDiff as $folderId) {
                        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " delete $class $folderId");
                        $deletes[$class][$folderId] = $folderId;
                        $count++;
                    }
                }                
            }
            
            
            if($count > 0) {
                $this->_syncState->counter++;
            }
            
            // create xml output
            $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $this->_syncState->counter));
            
            $changes = $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Changes'));            
            $changes->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Count', $count));
            foreach($adds as $class => $folders) {
                foreach((array)$folders as $folder) {
                    $add = $changes->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Add'));
                    $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'ServerId', $folder['folderId']));
                    $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'ParentId', $folder['parentId']));
                    $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'DisplayName', $folder['displayName']));
                    $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Type', $folder['type']));
                    
                    // store state in backend
                    $this->_folderStateBackend->create(new Syncope_Model_Folder(array(
                        'device_id'         => $this->_device,
                        'class'             => $class,
                        'folderid'          => $folder['folderId'],
                        'creation_time'     => $this->_syncTimeStamp,
                        'lastfiltertype'    => null
                    )));
                    
                    #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " $class => " . $folder['folderId']);
                }
            }
            
            foreach($deletes as $class => $folders) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " $class");
                foreach((array)$folders as $folderId) {
                    $delete = $changes->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Delete'));
                    $delete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'ServerId', $folderId));
                    
                    $this->_deleteFolder($class, $folderId);
                }
            }
            
            if (empty($this->_syncState->id)) {
                $this->_syncStateBackend->create($this->_syncState);
            } else {
                $this->_syncStateBackend->update($this->_syncState);
            }
        }
        
        return $this->_outputDom;
    }
    
    /**
     * delete folderstate (aka: forget that we have sent the folder to the client)
     *
     * @param string $_class the class from the xml
     * @param string $_contentId the Tine 2.0 id of the folder
     */
    protected function __deleteFolder($_class, $_folderId)
    {
        $folderStateFilter = new Syncope_Model_FolderFilter(array(
            array(
                    'field'     => 'device_id',
                    'operator'  => 'equals',
                    'value'     => $this->_device->getId()
            ),
            array(
                    'field'     => 'class',
                    'operator'  => 'equals',
                    'value'     => $_class
            ),
            array(
                    'field'     => 'folderid',
                    'operator'  => 'equals',
                    'value'     => $_folderId
            )
        ));
        $state = $this->_folderStateBackend->search($folderStateFilter, NULL, true);
        
        if(count($state) > 0) {
            $this->_folderStateBackend->delete($state[0]);
        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " no folderstate found for " . print_r($folderStateFilter->toArray(), true));
        }
    }    
}
