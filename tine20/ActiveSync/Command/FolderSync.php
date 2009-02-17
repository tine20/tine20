<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
class ActiveSync_Command_FolderSync extends ActiveSync_Command_Wbxml 
{
    const STATUS_SUCCESS = 1;
    const STATUS_FOLDER_EXISTS = 2;
    const STATUS_IS_SPECIAL_FOLDER = 3;
    const STATUS_FOLDER_NOT_FOUND = 4;
    const STATUS_PARENT_FOLDER_NOT_FOUND = 5;
    const STATUS_SERVER_ERROR = 6;
    const STATUS_ACCESS_DENIED = 7;
    const STATUS_REQUEST_TIMED_OUT = 8;
    const STATUS_INVALID_SYNC_KEY = 9;
    const STATUS_MISFORMATTED = 10;
    const STATUS_UNKNOWN_ERROR = 11;

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
    
    /**
     * create response to FolderSync request
     *
     * @todo get available folders from backend
     * @return resource stream containing wbxml encoded response
     */
    public function handle()
    {
        #$syncStateClass = new ActiveSync_SyncState();
        $controller = ActiveSync_Controller::getInstance();
        
        #$xml = new SimpleXMLElement($this->_inputDom->saveXML(), LIBXML_NOWARNING);
        $clientSyncKey = $this->_inputDom->getElementsByTagName('SyncKey')->item(0)->nodeValue;
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " synckey is $clientSyncKey");
        
        if($clientSyncKey == '0' || $controller->validateSyncKey($this->_device, $clientSyncKey, 'FolderSync')) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " valid synckey");
            $newSyncKey = $clientSyncKey + 1;
                        
            $folderSync = $this->_outputDom->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'FolderSync'));
            $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', self::STATUS_SUCCESS));
            #if($clientSyncKey == '0') {
                $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $newSyncKey));
                $changes = $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Changes'));
                // the count must be sent before the actual elements => very, very stupid
                // @todo remove the hardcoded result
                $changes->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Count', 2));
                $count = 0;
                foreach(array('Contacts', 'Tasks') as $class) {
                    $dataController = ActiveSync_Controller::dataFactory($class, $this->_syncTimeStamp);
                    #$folderClass = $this->_backend->factory($class);
                    foreach($dataController->getFolders() as $folder) {
                        $add = $changes->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Add'));
                        $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'ServerId', $folder['folderId']));
                        $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'ParentId', $folder['parentId']));
                        $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'DisplayName', $folder['displayName']));
                        $add->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Type', $folder['type']));
                        $count++;
                    }
                }
            #}
                        
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " new synckey is $newSyncKey");
            $controller->updateSyncKey($this->_device, $newSyncKey, 'FolderSync', $this->_syncTimeStamp);
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " INVALID synckey");
            $folderSync = $this->_outputDom->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'FolderSync'));
            $folderSync->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', self::STATUS_INVALID_SYNC_KEY));
        }
        
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . $this->_outputDom->saveXML());
        
        #$outputStream = fopen("php://temp", 'r+');
        #$encoder = new Wbxml_Encoder($outputStream, 'UTF-8', 3);
        #$encoder->encode($this->_outputDom);
        
        #return $outputStream;
    }
}