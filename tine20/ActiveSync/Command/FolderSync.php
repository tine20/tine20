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
    const FOLDERTYPE_INBOX          = 2;
    const FOLDERTYPE_DRAFTS         = 3;
    const FOLDERTYPE_WASTEBASKET    = 4;
    const FOLDERTYPE_SENTMAIL       = 5;
    const FOLDERTYPE_OUTBOX         = 6;
    const FOLDERTYPE_TASK           = 7;
    const FOLDERTYPE_APPOINTMENT    = 8;
    const FOLDERTYPE_CONTACT        = 9;
    const FOLDERTYPE_NOTE           = 10;
    const FOLDERTYPE_JOURNAL        = 11;
    const FOLDERTYPE_OTHER          = 12;
    
    protected $_defaultNameSpace    = 'uri:FolderHierarchy';
    protected $_documentElement     = 'FolderSync';
    
    protected $_classes             = array('Contacts'/*, 'Tasks'*/);
    
    protected $_syncKey;
    
    /**
     * parse FolderSync request
     *
     */
    public function handle()
    {
        $controller = ActiveSync_Controller::getInstance();
        
        $xml = simplexml_import_dom($this->_inputDom);
        $this->_syncKey = (int)$xml->SyncKey;
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " synckey is $this->_syncKey");        
    }
    
    /**
     * generate FolderSync response
     *
     * @todo currently we support only the main folder which contains all contacts/tasks/events/notes per class
     */
    public function getResponse()
    {
        $controller = ActiveSync_Controller::getInstance();
        
        $folderSync = $this->_outputDom->documentElement;
        
        if($this->_syncKey > '0' && $controller->validateSyncKey($this->_device, $this->_syncKey, 'FolderSync') !== true) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " INVALID synckey");
            $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', self::STATUS_INVALID_SYNC_KEY));
        } else {
            $newSyncKey = $this->_syncKey + 1;
            
            $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', self::STATUS_SUCCESS));
            $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $newSyncKey));
            
            $changes = $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Changes'));
            if($this->_syncKey == 0) {
                $changes->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Count', count($this->_classes)));
                foreach($this->_classes as $class) {
                    $dataController = ActiveSync_Controller::dataFactory($class, $this->_syncTimeStamp);
                    foreach($dataController->getFolders() as $folder) {
                        $add = $changes->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Add'));
                        $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'ServerId', $folder['folderId']));
                        $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'ParentId', $folder['parentId']));
                        $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'DisplayName', $folder['displayName']));
                        $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Type', $folder['type']));
                    }
                }
            } else {
                $changes->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Count', 0));
            }
            
            $controller->updateSyncKey($this->_device, $newSyncKey, 'FolderSync', $this->_syncTimeStamp);
        }
        
        parent::getResponse();
    }
}