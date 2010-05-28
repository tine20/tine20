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
 * @version     $Id: FolderUpdate.php 6991 2009-02-25 12:35:19Z p.schuele@metaways.de $
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 */
class ActiveSync_Command_FolderUpdate extends ActiveSync_Command_Wbxml 
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " synckey is $this->_syncKey parentId $this->_parentId name $this->_displayName");        
    }
    
    /**
     * generate FolderUpdate response
     *
     * @todo currently we support only the main folder which contains all contacts/tasks/events/notes per class
     */
    public function getResponse()
    {
        $folderUpdate = $this->_outputDom->documentElement;
        
        if($this->_syncKey > '0' && $this->_controller->validateSyncKey($this->_device, $this->_syncKey, 'FolderSync') !== true) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " INVALID synckey");
            $folderUpdate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', ActiveSync_Command_FolderSync::STATUS_INVALID_SYNC_KEY));
        } else {
            $newSyncKey = $this->_syncKey + 1;
            
            // create xml output
            $folderUpdate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', ActiveSync_Command_FolderSync::STATUS_SUCCESS));
            $folderUpdate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $newSyncKey));

            $this->_controller->updateSyncKey($this->_device, $newSyncKey, $this->_syncTimeStamp, 'FolderSync');
        }
        
        parent::getResponse();
    }    
}