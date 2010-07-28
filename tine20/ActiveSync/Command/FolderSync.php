<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 */
class ActiveSync_Command_FolderSync extends ActiveSync_Command_Wbxml 
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
    
    protected $_classes             = array('Contacts', 'Tasks', 'Email', 'Calendar');
    
    protected $_syncKey;
    
    /**
     * the folderState sql backend
     *
     * @var ActiveSync_Backend_FolderState
     */
    protected $_folderStateBackend;
    
    /**
     * instance of ActiveSync_Controller
     *
     * @var ActiveSync_Controller
     */
    protected $_controller;
    
    /**
     * the constructor
     *
     * @param ActiveSync_Model_Device $_device
     */
    public function __construct(ActiveSync_Model_Device $_device)
    {
        parent::__construct($_device);
        
        $this->_folderStateBackend   = new ActiveSync_Backend_FolderState();
        $this->_controller           = ActiveSync_Controller::getInstance();

    }
    
    /**
     * parse FolderSync request
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_inputDom);
        $this->_syncKey = (int)$xml->SyncKey;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " synckey is $this->_syncKey");        
    }
    
    /**
     * generate FolderSync response
     *
     * @todo currently we support only the main folder which contains all contacts/tasks/events/notes per class
     * @param boolean $_keepSession keep session active(don't logout user) when true
     */
    public function getResponse($_keepSession = false)
    {
        $folderSync = $this->_outputDom->documentElement;
        
        if($this->_syncKey > '0' && $this->_controller->validateSyncKey($this->_device, $this->_syncKey, 'FolderSync') !== true) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " INVALID synckey provided");
            $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', self::STATUS_INVALID_SYNC_KEY));
        } else {
            $adds = array();
            $deletes = array();
            $count = 0;
            
            if($this->_syncKey == 0) {
                $this->_folderStateBackend->resetState($this->_device);
            }
            
            foreach($this->_classes as $class) {
                $dataController = ActiveSync_Controller::dataFactory($class, $this->_device, $this->_syncTimeStamp);

                try {
                    $folders = $dataController->getSupportedFolders();
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " failed to get folders for class $class. " . $e->getMessage());
                    continue;
                }

                if($this->_syncKey == 0) {
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
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " add $class $folderId");
                        $adds[$class][$folderId] = $folders[$folderId];
                        $count++;
                    }
                    
                    // deleted entries
                    $serverDiff = array_diff($allClientEntries, $allServerEntries);
                    foreach($serverDiff as $folderId) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " delete $class $folderId");
                        $deletes[$class][$folderId] = $folderId;
                        $count++;
                    }
                }                
            }
            
            
            if($count > 0) {
                $newSyncKey = $this->_syncKey + 1;
            } else {
                $newSyncKey = $this->_syncKey;
            }
            
            // create xml output
            $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', self::STATUS_SUCCESS));
            $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $newSyncKey));
            
            $changes = $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Changes'));            
            $changes->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Count', $count));
            foreach($adds as $class => $folders) {
                foreach((array)$folders as $folder) {
                    $add = $changes->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Add'));
                    $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'ServerId', $folder['folderId']));
                    $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'ParentId', $folder['parentId']));
                    $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'DisplayName', $folder['displayName']));
                    $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Type', $folder['type']));
                    $this->_addFolderState($class, $folder['folderId']);
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " $class => " . $folder['folderId']);
                }
            }
            
            foreach($deletes as $class => $folders) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " $class");
                foreach((array)$folders as $folderId) {
                    $delete = $changes->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Delete'));
                    $delete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'ServerId', $folderId));
                    
                    $this->_deleteFolderState($class, $folderId);
                }
            }
            
            $this->_controller->updateSyncKey($this->_device, $newSyncKey, $this->_syncTimeStamp, 'FolderSync');
        }
        
        parent::getResponse();
    }
    
    /**
     * save folderstate (aka: remember that we have sent the folder to the client)
     *
     * @param string $_class the class from the xml
     * @param string $_folderId the Tine 2.0 id of the folder
     */
    protected function _addFolderState($_class, $_folderId)
    {
        $folderState = new ActiveSync_Model_FolderState(array(
            'device_id'         => $this->_device->getId(),
            'class'             => $_class,
            'folderid'          => $_folderId,
            'creation_time'     => $this->_syncTimeStamp/*,
            'lastfiltertype'    => '0'*/
        ));
        
        /**
         * if the entry got added earlier, and there was an error, the entry gets added again
         * @todo it's better to wrap the whole process into a transation
         */
        try {
            $this->_folderStateBackend->create($folderState);
        } catch (Zend_Db_Statement_Exception $e) {
            $this->_deleteFolderState($_class, $_folderId);
            $this->_folderStateBackend->create($folderState);
        }
    }
    
    /**
     * delete folderstate (aka: forget that we have sent the folder to the client)
     *
     * @param string $_class the class from the xml
     * @param string $_contentId the Tine 2.0 id of the folder
     */
    protected function _deleteFolderState($_class, $_folderId)
    {
        $folderStateFilter = new ActiveSync_Model_FolderStateFilter(array(
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